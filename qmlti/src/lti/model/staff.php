<?php

require_once('../resources/lib.php');
require_once('../resources/LTI_Data_Connector_qmp.php');

class Staff {

/**
 *  Database object.
 */
  protected $db = NULL;
/**
 *  Consumer key object.
 */
  protected $consumer_key = NULL;
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
 *  ID for resource link, called by database.
 */
  protected $resource_link_id = NULL;
/**
 *  Debug variable.
 */
  protected $ok = TRUE;
/**
 *  User details.
 */
  protected $username = NULL;
  protected $firstname = NULL;
  protected $lastname = NULL;
  protected $email = NULL;
/**
 *  Boolean identifying student or staff - should be FALSE.
 */
  protected $is_student = NULL;
/**
 *  Group id from tenant, used to find participant's scores.
 */
  protected $group_id = NULL;
/**
 *  Course ID, to be used in generating group id on tenant.
 */
  protected $context_label = NULL;
/**
 *  Course Title, to be used in generating group description on tenant.
 */
  protected $context_title = NULL;
/**
 *  ID of assessment currently selected by resource link.
 */
  protected $assessment_id = NULL;
/**
 *  Boolean describing if coaching report is available at resource link.
 */
  protected $coaching_report = NULL;
/**
 *  Integer representative for coaching report UI.
 */
  protected $int_coaching = NULL;
/**
 *  Value of type of result returned available at resource link.
 */
  protected $multiple_results = NULL;
/**
 *  Value of number of attempts available at resource link.
 */
  protected $number_attempts = NULL;
/**
 *  Array set containing all permutations of result type returned.
 */
  protected $arr_results = NULL;
/**
 *  UI representative for coaching report UI.
 */
  protected $coaching_check = NULL;
/**
 *  UI representation for number of attempts, if set at 'unlimited'.
 */
  protected $no_attempts = NULL;
/**
 *  ID of administrator.
 */
  protected $admin_id = NULL;
/**
 *  Array of participant results available at resource link.
 */
  protected $results = NULL;

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
    $this->email = $session['email'];
    $this->is_student = $session['is_student'];
    $this->context_label = $session['context_label'];
    $this->context_title = $session['context_title'];
    $this->assessment_id = $session['assessment_id'];
    $this->multiple_results = $session['multiple_results'];
    $this->number_attempts = $session['number_attempts'];
    $this->arr_results = [ "Best", "Worst", "Newest", "Oldest" ];
    $this->coaching_report = $session['coaching_report'];
    $this->coaching_check = '';
  }

/**
 * Get multiple results value.
 * 
 * @return Integer multiple_results value
 */
  function getMultipleResults() {
    return $this->multiple_results;
  }

/**
 * Gets array of results.
 * 
 * @return Array Array object describing multiple result options.
 */
  function getArrResults() {
    return $this->arr_results;
  }

/**
 * Gets whether or not the object has encountered an error.
 * 
 * @return Boolean OK value.
 */
  function isOK() {
    return $this->ok;
  }

/**
 * Gets UI descriptor for no_attempts
 * 
 * @return String no_attempts value
 */
  function getNoAttempts() {
    return $this->no_attempts;
  }

/**
 * Gets number of attempts available for assessment
 * 
 * @return mixed number_attempts value
 */
  function getNumberAttempts() {
    return $this->number_attempts;
  }

/**
 * Gets assessment ID for assessment currently on resource link.
 * 
 * @return Integer assessment ID.
 */
  function getAssessmentID() {
    return $this->assessment_id;
  }

/**
 * Sets UI based on number_attempts value.
 * 
 * @param String number_attempts from POST call.
 */
  function checkNumAttempts($request) {
    if (isset($request)) {
      $this->number_attempts = $request;
    }
    if ($this->number_attempts == 'none') {
      $this->no_attempts = 'selected';
    } else {
      $this->no_attempts = '';
    }
  }

/**
 * Enables coaching reports in the UI
 */
  function enableCoachingReports() {
    $this->coaching_check = 'checked';
    $this->coaching_report = TRUE;
    $this->int_coaching = 1;
  }

/**
 * Disables coaching reports in the UI
 */
  function disableCoachingReports() {
    $this->coaching_check = '';
    $this->coaching_report = FALSE;
    $this->int_coaching = 0;
  }

/**
 * Gets coaching_check value.
 * 
 * @return mixed coaching_check value.
 */
  function getCoachingCheck() {
    return $this->coaching_check;
  }

/**
 * Checks coaching report setting, then changes UI depending on result.
 * 
 * @param String POST request for new coaching report
 */
  function checkCoachingReportSettings($request) {
    if (isset($this->coaching_report)) {
      if ($this->coaching_report) {
        $this->enableCoachingReports();
      } else {
        $this->disableCoachingReports();
      }
    } 

    if (isset($request)) {
      if ($request == '1') {
        $this->enableCoachingReports();
      } else {
        $this->enableCoachingReports();
      }
    }
  }

/**
 * Saves all options as settings in resource link.
 * 
 * @param String POST request for number of assessments
 * @param String POST request for multiple result option
 */
  function saveConfigurations($assessment_request, $multipleresult_request) {
    if (isset($assessment_request)) {
      $this->assessment_id = htmlentities($assessment_request);
      $this->resource_link->setSetting(ASSESSMENT_SETTING, $this->assessment_id);

      if (isset($multipleresult_request)) {
        if ($this->multiple_results != $multipleresult_request) {
          update_result_accessed($this->db, $this->consumer, $this->resource_link, $this->assessment_id, $multipleresult_request);
          $this->multiple_results = $multipleresult_request;
        }
      }

      $this->resource_link->setSetting(COACHING_REPORT, $this->coaching_report);
      $this->resource_link->setSetting(MULTIPLE_RESULTS, $this->multiple_results);
      $this->resource_link->setSetting(NUMBER_ATTEMPTS, $this->number_attempts);
      $this->resource_link->save();

      if ($this->data_connector->ReportConfig_loadAccessible($this->consumer_key, $this->resource_link_id, $this->assessment_id) != NULL) {
        $this->data_connector->ReportConfig_update($this->consumer_key, $this->resource_link_id, $this->assessment_id, $this->int_coaching);
      } else {
        $this->data_connector->ReportConfig_insert($this->consumer_key, $this->resource_link_id, $this->assessment_id, $this->int_coaching);
      }

    }
  }

/**
 * Checks if user is not a student.
 */
  function checkValid() {
    $this->ok = !$this->is_student;
    if (!$this->ok) {
      $_SESSION['error'] = 'Invalid role.';
    }
  }

/**
 * Creates administrator in Perception if not available.
 */
  function setupAdministratorCredentials() {
    if ($this->ok && (($admin_details = get_administrator_by_name($this->username)) !== FALSE)) {
      $this->admin_id = $admin_details->Administrator_ID;
    } else if ($this->ok && ($this->admin_id = create_administrator_with_password($this->username, $this->firstname, $this->lastname, $this->email, ADMINISTRATOR_ROLE) === FALSE)) {
      $this->ok = FALSE;
    }
  }

/**
 * Adds instructor to group. If group is not available, the group will be created and the instructor will be added.
 */
  function setupGroupConnections() {
    if ($this->ok && (($group_response = get_group_by_name($this->context_label)) !== FALSE)) {
      $group = $group_response->Group;
    } else if ($this->ok) {
      $group = create_group($this->context_label, $this->context_title, 0);
    } else {
      $group = FALSE;
      $this->ok = FALSE;
    }

    if ($group != FALSE) {
      $this->group_id = $group->Group_ID;
      $group_list = get_administrator_group_list($this->admin_id);
      $found = FALSE;
      if ($group_list != FALSE) {
        if (((count( (array)$group_list->GroupList )) != 0) && (is_array($group_list->GroupList->Group))) {
          foreach ($group_list->GroupList->Group as $group_item) {
            if ($group_item->Group_ID == $group->Group_ID) {
              $found = TRUE;
            }
          }
        } else {
          if (((count ( (array) $group_list->GroupList)) != 0) && ($group_list->GroupList->Group->Group_ID == $group->Group_ID)) {
            $found = TRUE;
          }
        }
      }   
      if ($this->ok && !$found) {
        add_group_administrator_list($group->Group_ID, $this->admin_id);
      }
    }
  }

/**
 * Connects the class to Perception.
 */
  function connectToPerception() {
    if ($this->ok) {
      $this->ok = perception_soapconnect();
    }
  }


/**
 * Setup administrator in Perception.
 */
  function setupAdministrator() {
    $this->checkValid();
    $this->connectToPerception();
    $this->setupAdministratorCredentials();
    $this->setupGroupConnections();
  }

/**
 * Gets URL to login to Questionmark.
 * 
 * @return String URL
 */
  function getLoginURL() {
    $em_url = FALSE;
    if ($this->ok) {
      $em_url = get_access_administrator($this->username);
      $this->ok = !empty($em_url);
    }
    return $em_url;
  }

/**
 * Gets assessment list from Perception.
 *
 * @return Array assessment list
 */
  function getAssessments() {
    $assessments = array();
    if ($this->ok && (($assessments = get_assessment_list()) === FALSE)) {
      $assessments = array();
    }
    return $assessments;
  }

/**
 * Gets result list for participants of group.
 * 
 * @return Array result list
 */
  function getResults() {
    $result_list = get_assessment_result_list_by_assessment($this->assessment_id);
    $participant_list = get_participant_list_by_group($this->group_id);
    if (($result_list != FALSE) && (!stdclass_empty($result_list)) && (!stdclass_empty($participant_list))) {
      foreach ($result_list->AssessmentResult as $assessment_key => $assessment) {
        $found = FALSE;
        foreach ($participant_list as $participant) {
          if ($assessment->Result->Participant == "{$participant->First_Name} {$participant->Last_Name}") {
            $found = TRUE;
          }
        }
        if (!$found) {
          unset($result_list->AssessmentResult[$assessment_key]);
        }
      }
      $this->results = array_values($result_list->AssessmentResult);
      foreach ($this->results as $result) {
        $result->Result->URL = get_report_url($result->Result->Result_ID)->URL;
      } 
    } 
    return $this->results;
  }
}


?>