<?php

	require_once('config.php');
	require_once('lti/LTI_Tool_Provider.php');
	require_once('lib.php');

  class Student {

    protected $consumer_key = NULL;
    protected $resource_link_id = NULL;
    protected $assessment_id = NULL;
    protected $username = NULL;
    protected $firstname = NULL;
    protected $lastname = NULL;
    protected $participant_name = NULL;
    protected $email = NULL;
    protected $return_url = NULL;
    protected $isStudent = NULL;
    protected $notify_url = NULL;
    protected $result_id = NULL;
    protected $participant_id = NULL;

    protected $db = NULL;
    protected $data_connector = NULL;
    protected $consumer = NULL;
    protected $resource_link = NULL;

    function __construct() {
      $this->db = open_db();
      $this->consumer_key = $_SESSION['consumer_key'];
      $this->resource_link_id = $_SESSION['resource_link_id'];
      $this->assessment_id = $_SESSION['assessment_id'];
      $this->username = $_SESSION['username'];
      $this->firstname = $_SESSION['firstname'];
      $this->lastname = $_SESSION['lastname'];
      $this->participant_name = "{$this->firstname} {$this->lastname}";
      $this->email = $_SESSION['email'];
      $this->return_url = $_SESSION['lti_return_url'];
      $this->context_title = $_SESSION['context_title'];
      $this->context_label = $_SESSION['context_label'];
      if (!$this->return_url) {
        $this->return_url = get_root_url() . 'return.php';
      }
      $this->isStudent = $_SESSION['isStudent'];
      $this->notify_url = get_root_url() . 'notify.php';
      $this->result_id = $_SESSION['result_id'];

      $this->data_connector = LTI_Data_Connector::getDataConnector(TABLE_PREFIX, $this->db, DATA_CONNECTOR);
      $this->consumer = new LTI_Tool_Consumer($this->consumer_key, $this->data_connector);
      $this->resource_link = new LTI_Resource_Link($this->consumer, $this->resource_link_id);
    }

    function checkValid() {
      if (!$this->isStudent) {
        $_SESSION['error'] = 'Not a student';
      } else if (!$this->assessment_id) {
        $_SESSION['error'] = 'No assignment selected';
      } else if (!$this->result_id) {
        $_SESSION['error'] = 'No grade book column';
      }
    }

    function identifyAction($action) {
      // An action was previously selected
      if (isset($action)) {
        if ($action == 'Launch Assessment') {
          // start assessment
          $redirect =  get_root_url() . 'student.php';
          header("Location: {$redirect}");
        } else if ($action == 'View Coaching Report') {
          // view coaching report
          $multiple_results = $this->resource_link->getSetting(MULTIPLE_RESULTS);
          $resultID = get_accessed_result($this->db, $this->consumer, $this->resource_link, $this->participant_name);
          $coachingreport = get_report_url($resultID);
          header("Location: {$coachingreport->URL}");
        }
      }
    }


    function createParticipant() {
      if (!isset($_SESSION['error']) && (($participant_details = get_participant_by_name($this->username)) !== FALSE)) {
        $this->participant_id = $participant_details->Participant_ID;
      } else if (!isset($_SESSION['error'])) {
        $this->participant_id = create_participant($this->username, $this->firstname, $this->lastname, $this->email);
      } else {
      	$this->participant_id = FALSE;
      }
    }

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
          if (((count( (array)$this->group_list->GroupList) ) != 0) && ($this->group_list->GroupList->Group->Group_ID == $this->group->Group_ID)) {
            $found = TRUE;
          }
        }
        if (!$found) {
          add_group_participant_list($this->group->Group_ID, $this->participant_id);
        } 
      }
    }

    function isCoachingReportAvailable($db) {
      return is_coaching_report_available($db, $this->consumer_key, $this->resource_link_id, $this->assessment_id, $this->participant_name);
    }

    function getAssessment() {
      $assessment = '';
      if (!isset($_SESSION['error'])) {
        $assessment = get_assessment($this->assessment_id);
      }
      return $assessment;
    }

    function getAccessAssessmentNotify() {
    	$url = '';
    	if (!isset($_SESSION['error'])) {
		    $url = get_access_assessment_notify($this->assessment_id, "{$this->firstname} {$this->lastname}", $this->consumer_key, $this->resource_link_id, $this->result_id,
		       $this->notify_url, $this->return_url);
		  }
		  return $url;
    }

  }

?>