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
 *  ID of schedule currently selected by resource link.
 */
  protected $schedule_id = NULL;
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
 *  Additional parameters passed by the tool consumer.
 */
  protected $additional_params = NULL;
/**
 *  Group that participant belongs to.
 */
  protected $group = NULL;
/**
 *  Group ID for the participant.
 */
  protected $group_id = 0;

/**
 * Class constructor
 *
 * @param mixed $session Session data for this instance.
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
    $this->additional_params = $session['additional_params'];
  }

/**
 * Checks if user is a student.
 *
 * @return NULL or ERROR if user is not a student
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
 * @param String $action
 *
 * @return URL redirect to either launch an assessment or view coaching report
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
 *
 * @return Student object holding student participant details
 */
  function createParticipant() {
    if (!isset($_SESSION['error']) && (($participant_details = get_participant_by_name($this->username)) !== FALSE)) {
      $this->participant_id = $participant_details->Participant_ID;
    } else if (!isset($_SESSION['error'])) {
      $this->participant_id = create_participant($this->username, $this->firstname, $this->lastname, $this->email);
    } else {
    	$this->participant_id = FALSE;
    }
    return $this;
  }

/**
 * Adds a participant to a group. If group is not available, the group is created and the participant is added.
 *
 * @return Student object with group information
 */
  function joinGroup() {
    if (!isset($_SESSION['error']) && (($group = get_group_by_name($this->context_label)) !== FALSE)) {
      $this->group = $group->Group;
    } else if (!isset($_SESSION['error'])) {
      $this->group = create_group($this->context_label, $this->context_title, 0);
    } else {
      error_log("Group not instantiated for participant " . $this->participant_id);
    }
    if ($this->group != FALSE) {
      $this->group_list = get_participant_group_list($this->participant_id);
      $found = FALSE;
      if (is_array($this->group_list) && is_array($this->group_list->GroupList) && is_array($this->group_list->GroupList->Group) && ((count( (array)$this->group_list->GroupList->Group) ) != 0)) {
        foreach ($this->group_list->GroupList->Group as $group_item ) {
          if ($group_item->Group_ID == $this->group->Group_ID) {
            $found = TRUE;
          }
        }
      } else {
        if (!stdclass_empty($this->group_list) && !stdclass_empty($this->group_list->GroupList)) {
          if (is_object($this->group_list->GroupList->Group) && !stdclass_empty($this->group_list->GroupList->Group)) {
            if (is_object($this->group) && !stdclass_empty($this->group)) {
              if ($this->group_list->GroupList->Group->Group_ID == $this->group->Group_ID) {
                $found = TRUE;
              }
            }
          }
        }
      }
      if (!$found) {
        add_group_participant_list($this->group->Group_ID, $this->participant_id);
      }
    }
    $this->group_id = $this->group->Group_ID;
    return $this;
  }

/**
 * Gets the assessment attempt.
 *
 * @return NULL
 */
  function getLatestAttempt() {
    if (!isset($_SESSION['error'])) {
      $this->schedule_id = get_latest_attempt($this->db, $this->consumer_key, $this->resource_link_id, $this->assessment_id, $this->username);
    }
    return $this;
  }

/**
 * Saves the assessment attempt until deleted later.
 *
 * @return NULL
 */
  function setLatestAttempt() {
    if (!isset($_SESSION['error'])) {
      return set_latest_attempt($this->db, $this->consumer_key, $this->resource_link_id, $this->assessment_id, $this->username, $this->schedule_id);
    }
    return false;
  }

/**
 * Checks if student has schedule id
 *
 * @return Boolean TRUE if schedule ID is not false
 */
  function hasScheduleID() {
    return (($this->schedule_id != null) && ($this->schedule_id != false));
  }

/**
 * Checks if student has schedule id
 *
 * @return Boolean TRUE if schedule ID is not false
 */
  function getScheduleID() {
    return $this->schedule_id;
  }

/**
 * Checks database to identify if coaching report is available for participant and assessment.
 * Includes a sanitary check to identify if a previous assessment was taken.
 *
 * @return Boolean TRUE if available.
 */
  function isCoachingReportAvailable() {
    if ($this->hasAttemptInProgress()) {
      $this->past_attempts--;
    }
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
      if ($this->hasAttemptInProgress()) { # Already has an attempt setup
        $this->past_attempts++;
      }
    }
    return $this->past_attempts;
  }

/**
 * Gets past attempts list
 *
 * @return Student
 */
  function getPastAttempts() {
    if (!isset($_SESSION['error'])) {
      $this->past_attempts = get_past_attempts($this->db, $this->resource_link_id, $this->assessment_id, $this->username);
    }
    return $this;
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
 * Checks if user has attempt in progress.
 *
 * @return Boolean
 */
  function hasAttemptInProgress() {
    return (get_latest_attempt($this->db, $this->consumer_key, $this->resource_link_id, $this->assessment_id, $this->username) != false);
  }

/**
 * Checks whether or not launch is disabled due to maximum attempts taken.
 *
 * @return String UI disable string.
 */
  function checkLaunchDisabled() {
    if ($this->number_attempts != 'none') {
      if (($this->past_attempts >= $this->number_attempts) && (!$this->hasAttemptInProgress())) {
        return '';
      } else {
        return '<input class="btn btn-sm" type="submit" name="action" value="Launch Assessment"/>';
      }
    } else {
      $this->parsed_attempts = 'No limit';
      return '<input class="btn btn-sm" type="submit" name="action" value="Launch Assessment"/>';
    }
  }

/**
 * Provides a user-readable format for getting attempts in progress
 *
 * @return String text for attempt in progress details
 */
  function getAttemptProgress() {
    if ($this->hasAttemptInProgress()) {
      return 'Yes';
    } else {
      return 'No';
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

  function createScheduleParticipant() {
    if (!isset($_SESSION['error'])) {
      $this->past_attempts = get_past_attempts($this->db, $this->resource_link_id, $this->assessment_id, $this->username);
      $schedule_name = 'Assessment ' . $this->assessment_id . ' for user ' . $this->username . ' attempt ' . $this->past_attempts++;
      # Make the start time and end time difference about 30 seconds
      $schedule_starts = new DateTime('NOW');
      $schedule_stops = new DateTime('NOW');
      $schedule_stops->modify('+1 day');
      $schedule_starts = $schedule_starts->format('Y-m-d\TH:i:s');
      $schedule_stops = $schedule_stops->format('Y-m-d\TH:i:s');
      $this->schedule_id = create_schedule_participant(0, $schedule_name, $this->assessment_id, $this->participant_id, 0, $schedule_starts, $schedule_stops, $this->group_id, $this->group_id, 1, 0, 1, 1, 0, 0, 0, 0, 0, 0);
    }
    return $this;
  }

/**
 * Returns the URL for the assessment given participant details.
 *
 * @return String Assessment URL.
 */
  function getAccessScheduleNotify() {
  	$url = '';
  	if (!isset($_SESSION['error'])) {
	    $url = get_access_schedule_notify($this->schedule_id, $this->username, $this->consumer_key, $this->resource_link_id, $this->result_id, $this->notify_url, $this->return_url, $this->username, $this->additional_params);
	  }
	  return $url;
  }

}

?>
