<?php

require_once('../config/config.php');
require_once('../resources/LTI_Tool_Provider.php');
require_once('../resources/lib.php');

class Student {

/**
 *  Database object.
 */
  protected $db = NULL;
/**
 *  Consumer key object.
 */
  protected $consumer_key = NULL;
/**
 *  ID for resource link, called by database.
 */
  protected $resource_link_id = NULL;
/**
 *  Data connector object.
 */
  protected $data_connector = NULL;
/**
 *  Consumer object.
 */
  protected $consumer = NULL;
/**
 *  Resource link object.
 */
  protected $resource_link = NULL;
/**
 *  ID of participant.
 */
  protected $participant_id = NULL;
/**
 *  User details.
 */
  protected $username = NULL;
  protected $firstname = NULL;
  protected $lastname = NULL;
  protected $email = NULL;
/**
 *  Full name of participant, made with first name and last name
 */
  protected $participant_name = NULL;
/**
 *  Boolean identifying student or staff - should be TRUE.
 */
  protected $is_student = NULL;
/**
 *  Course Title, to be used in generating group description on tenant.
 */
  protected $context_title = NULL;
/**
 *  Course ID, to be used in generating group id on tenant.
 */
  protected $context_label = NULL;
/**
 *  ID of assessment currently selected by resource link.
 */
  protected $assessment_id = NULL;
/**
 *  URL of LTI page to complete return process.
 */
  protected $return_url = NULL;
/**
 *  URL of LTI page to complete outcome process.
 */
  protected $notify_url = NULL;
/**
 *  ID of assessment outcome.
 */
  protected $result_id = NULL;
/**
 *  Number of attempts setting for the resource link.
 */
  protected $number_attempts = NULL;
/**
 *  UI response for number of attempts setting.
 */
  protected $parsed_attempts = NULL;
/**
 *  Number of attempts previously taken.
 */
  protected $past_attempts = NULL;

/**
 * Class constructor
 *
 * @param mixed   $session  Session data for this instance.
 */
  function __construct($session) {
    $this->db = open_db();
    $this->consumer_key = $session['consumer_key'];
    $this->resource_link_id = $session['resource_link_id'];
    $this->data_connector = LTI_Data_Connector::getDataConnector(TABLE_PREFIX, $this->db, DATA_CONNECTOR);
    $this->consumer = new LTI_Tool_Consumer($this->consumer_key, $this->data_connector);
    $this->resource_link = new LTI_Resource_Link($this->consumer, $this->resource_link_id);
    $this->username = $session['username'];
    $this->firstname = $session['firstname'];
    $this->lastname = $session['lastname'];
    $this->participant_name = "{$this->firstname} {$this->lastname}";
    $this->email = $session['email'];
    $this->is_student = $session['is_student'];
    $this->context_title = $session['context_title'];
    $this->context_label = $session['context_label'];
    $this->assessment_id = $session['assessment_id'];
    $this->return_url = $session['lti_return_url'];
    if (!$this->return_url) {
      $this->return_url = get_root_url() . '../lti/return.php';
    }
    $this->notify_url = get_root_url() . '../lti/notify.php';
    $this->result_id = $session['result_id'];
    $this->number_attempts = $session['number_attempts'];
    $this->parsed_attempts = $this->number_attempts;
    $this->past_attempts = 0;
  }

/**
 * Checks if user is a student.
 */
  function checkValid() {
    if (!$this->is_student) {
      $_SESSION['error'] = 'Not a student';
    } else if (!$this->assessment_id) {
      $_SESSION['error'] = 'No assignment selected';
    } else if (!$this->result_id) {
      $_SESSION['error'] = 'No grade book column';
    }
  }

/**
 * If action was submitted, page is redirected to appropriate area.
 * 
 * @param String action
 */
  function identifyAction($action) {
    // An action was previously selected
    if (isset($action)) {
      if ($action == 'Launch Assessment') {
        // start assessment
        $redirect =  get_root_url() . '../lti/student.php';
        header("Location: {$redirect}");
      } else if ($action == 'View Coaching Report') {
        // view coaching report
        $resultID = get_accessed_result($this->db, $this->consumer, $this->resource_link, $this->username);
        $coachingreport = get_report_url($resultID);
        header("Location: {$coachingreport->URL}");
      }
    }
  }

/**
 * Creates a participant in Perception.
 */
  function createParticipant() {
    if (!isset($_SESSION['error']) && (($participant_details = get_participant_by_name($this->username)) !== FALSE)) {
      $this->participant_id = $participant_details->Participant_ID;
    } else if (!isset($_SESSION['error'])) {
      $this->participant_id = create_participant($this->username, $this->firstname, $this->lastname, $this->email);
    } else {
    	$this->participant_id = FALSE;
    }
  }

/**
 * Adds a participant to a group. If group is not available, the group is created and the participant is added.
 */
  function joinGroup() {
    if (!isset($_SESSION['error']) && (($group = get_group_by_name($this->context_label)) !== FALSE)) {
      $this->group = $group->Group;
    } else if (!isset($_SESSION['error'])) {
      $this->group = create_group($this->context_label, $this->context_title, 0);
    } else {
      $this->group = FALSE;
    }
    if ($this->group != FALSE) {
      $this->group_list = get_participant_group_list($this->participant_id);
      $found = FALSE;
      if (((count( (array)$this->group_list->GroupList) ) != 0) && is_array($this->group_list->GroupList)) {
        foreach ($this->group_list->GroupList as $group_item ) {
          if ($group_item->Group_ID == $this->group->Group_ID) {
            $found = TRUE;
          }
        }
      } else {
        if ((!stdclass_empty($this->group_list)) && (!stdclass_empty($this->group_list->GroupList)) && ($this->group_list->GroupList->Group->Group_ID == $this->group->Group_ID)) {
          $found = TRUE;
        }
      }
      if (!$found) {
        add_group_participant_list($this->group->Group_ID, $this->participant_id);
      } 
    }
  }

/**
 * Checks database to identify if coaching report is available for participant and assessment. 
 * Includes a sanitary check to identify if a previous assessment was taken.
 * 
 * @return Boolean TRUE if available.
 */
  function isCoachingReportAvailable() {
    return (($this->past_attempts > 0) && (is_coaching_report_available($this->db, $this->consumer_key, $this->resource_link_id, $this->assessment_id, $this->participant_name)));
  }

/**
 * Gets assessment given the assessment ID.
 * 
 * @return Object Assessment.
 */
  function getAssessment() {
    $assessment = '';
    if (!isset($_SESSION['error'])) {
      $assessment = get_assessment($this->assessment_id);
    }
    return $assessment;
  }

/**
 * Gets number of attempts previously taken.
 * 
 * @return Integer Past attempts.
 */
  function getAttemptDetails() {
    if (!isset($_SESSION['error'])) {
      $this->past_attempts = get_past_attempts($this->db, $this->resource_link_id, $this->assessment_id, $this->username);
    }
    return $this->past_attempts;
  }

/**
 * Gets number of attempts available for assessment.
 * 
 * @return Integer Number of attempts.
 */
  function getNumberAttempts() {
    return $this->number_attempts;
  }

/**
 * Checks whether or not launch is disabled due to maximum attempts taken.
 * 
 * @return String UI disable string.
 */
  function checkLaunchDisabled() {
    if ($this->number_attempts != 'none') {
      if ($this->past_attempts >= $this->number_attempts) {
        return 'disabled';
      } else {
        return '';
      }
    } else {
      $this->parsed_attempts = 'No limit';
      return '';
    }
  }

/**
 * Returns the number of attempts available in UI-interpretable form.
 * 
 * @return String Parsed attempts value.
 */
  function getParsedAttempts() {
    return $this->parsed_attempts;
  }

/**
 * Returns the URL for the assessment given participant details.
 * 
 * @return String Assessment URL.
 */
  function getAccessAssessmentNotify() {
  	$url = '';
  	if (!isset($_SESSION['error'])) {
	    $url = get_access_assessment_notify($this->assessment_id, "{$this->firstname} {$this->lastname}", $this->consumer_key, $this->resource_link_id, $this->result_id, $this->notify_url, $this->return_url, $this->username);
	  }
	  return $url;
  }

}

?>