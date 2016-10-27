<?php

	require_once('config.php');
	require_once('lti/LTI_Tool_Provider.php');
	require_once('lib.php');

  class Student {

    public $consumer_key = NULL;
    public $resource_link_id = NULL;
    public $assessment_id = NULL;
    public $username = NULL;
    public $firstname = NULL;
    public $lastname = NULL;
    public $participant_name = NULL;
    public $email = NULL;
    public $return_url = NULL;
    public $isStudent = NULL;
    public $notify_url = NULL;
    public $result_id = NULL;
    public $participant_id = NULL;

    function __construct() {
      $this->consumer_key = $_SESSION['consumer_key'];
      $this->resource_link_id = $_SESSION['resource_link_id'];
      $this->assessment_id = $_SESSION['assessment_id'];
      $this->username = $_SESSION['username'];
      $this->firstname = $_SESSION['firstname'];
      $this->lastname = $_SESSION['lastname'];
      $this->participant_name = "{$this->firstname} {$this->lastname}";
      $this->email = $_SESSION['email'];
      $this->return_url = $_SESSION['lti_return_url'];
      if (!$this->return_url) {
        $this->return_url = get_root_url() . 'return.php';
      }
      $this->isStudent = $_SESSION['isStudent'];
      $this->notify_url = get_root_url() . 'notify.php';
      $this->result_id = $_SESSION['result_id'];
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
          $resultIDs = get_result_id($this->participant_name);
          if (is_array($resultIDs)) {
            $coachingreport = get_report_url($resultIDs[0]->Result->Result_ID);
          } else {
            $coachingreport = get_report_url($resultIDs->Result->Result_ID);
          }
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

  class Staff_Main {



  }

  class Staff_Results {




  }






?>