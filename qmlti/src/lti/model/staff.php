<?php

require_once('../resources/lib.php');
require_once('../resources/LTI_Data_Connector_qmp.php');

class Staff {

  protected $db = NULL;
  protected $consumer_key = NULL;
  protected $resource_link_id = NULL;
  protected $data_connector = NULL;
  protected $consumer = NULL;
  protected $resource_link = NULL;
  protected $ok = TRUE;

  protected $username = NULL;
  protected $firstname = NULL;
  protected $lastname = NULL;
  protected $email = NULL;
  protected $is_student = NULL;
  protected $group_id = NULL;

  protected $context_label = NULL;
  protected $context_title = NULL;
  protected $assessment_id = NULL;

  protected $coaching_report = NULL;
  protected $int_coaching = NULL;
  protected $multiple_results = NULL;
  protected $number_attempts = NULL;
  protected $arr_results = NULL;

  protected $coaching_check = NULL;
  protected $no_attempts = NULL;
  protected $admin_id = NULL;
  protected $results = NULL;

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
    $this->is_student = $session['isStudent'];

    $this->context_label = $session['context_label'];
    $this->context_title = $session['context_title'];
    $this->assessment_id = $session['assessment_id'];

    $this->multiple_results = $session['multiple_results'];
    $this->number_attempts = $session['number_attempts'];
    $this->arr_results = [ "Best", "Worst", "Newest", "Oldest" ];

    $this->coaching_report = $session['coaching_report'];
    $this->coaching_check = '';

  }

  function getMultipleResults() {
    return $this->multiple_results;
  }

  function getArrResults() {
    return $this->arr_results;
  }

  function isOK() {
    return $this->ok;
  }

  function getNoAttempts() {
    return $this->no_attempts;
  }

  function getNumberAttempts() {
    return $this->number_attempts;
  }

  function getAssessmentID() {
    return $this->assessment_id;
  }

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

  function enableCoachingReports() {
    $this->coaching_check = 'checked';
    $this->coaching_report = TRUE;
    $this->int_coaching = 1;
  }

  function disableCoachingReports() {
    $this->coaching_check = '';
    $this->coaching_report = FALSE;
    $this->int_coaching = 0;
  }

  function getCoachingCheck() {
    return $this->coaching_check;
  }

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

  function checkValid() {
    $this->ok = !$this->is_student;
    if (!$this->ok) {
      $_SESSION['error'] = 'Invalid role.';
    }
  }

  function setupAdministratorCredentials() {
    if ($this->ok && (($admin_details = get_administrator_by_name($this->username)) !== FALSE)) {
      $this->admin_id = $admin_details->Administrator_ID;
    } else if ($this->ok && ($this->admin_id = create_administrator_with_password($this->username, $this->firstname, $this->lastname, $this->email, ADMINISTRATOR_ROLE) === FALSE)) {
      $this->ok = FALSE;
    }
  }

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

  function connectToPerception() {
    if ($this->ok) {
      $this->ok = perception_soapconnect();
    }
  }

  function setupAdministrator() {
    $this->checkValid();
    $this->connectToPerception();
    $this->setupAdministratorCredentials();
    $this->setupGroupConnections();
  }

  function getLoginURL() {
    $em_url = FALSE;
    if ($this->ok) {
      $em_url = get_access_administrator($this->username);
      $this->ok = !empty($em_url);
    }
    return $em_url;
  }

  function getAssessments() {
    $assessments = array();
    if ($this->ok && (($assessments = get_assessment_list()) === FALSE)) {
      $assessments = array();
    }
    return $assessments;
  }

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