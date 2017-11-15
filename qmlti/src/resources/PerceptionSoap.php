<?php
/*
 *  LTI-Connector - Connect to Perception via IMS LTI
 *  Copyright (C) 2017  Questionmark
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License along
 *  with this program; if not, write to the Free Software Foundation, Inc.,
 *  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 *  Contact: info@questionmark.com
 *
 *  Version history:
 *    1.0.00   1-May-12  Initial prototype
 *    1.1.00   3-May-12  Added test harness
 *    1.2.00  23-Jul-12
 *    2.0.00  18-Feb-13
 *    2.1.00  15-Nov-17  Added schedules and attempts
*/

/**
 * PerceptionSoap
 * @author Bart Nagel
 * Accesses the various QMWise methods which will be useful for the LTI
 * Perception connector project
 * Requires the QMWiseException class
 */

require_once "QMWiseException.php";

class PerceptionSoap {

  private $debug;
  private $soap;

  /**
   * PerceptionSoap constructor
   *
   * Get the WSDL file from the Perception server and set up the Soap client
   * with the available methods
   * Throws whatever exception the Soap constructor might throw, for instance
   * if it can't get the WSDL file
   * Parameters are the Perception server's domain and an array of options
   * (purposes of which are obvious from the source code below)
   *
   * @param String URL to connect to qmwise
   * @param Array options
   *
   * @return NULL
   */
  public function __construct($perception_qmwise, $options = array()) {
    $security_client_id = isset($options["security_client_id"]) ? $options["security_client_id"] : null;
    $security_checksum = isset($options["security_checksum"]) ? $options["security_checksum"] : null;
    $this->debug = isset($options["debug"]) ? $options["debug"] : false;
    try {
      $context = stream_context_create([
        'ssl' => [
          // set some SSL/TLS specific options
          'verify_peer' => true,
          'verify_peer_name' => true,
          'allow_self_signed' => true
        ]
      ]);
      $this->soap = new SoapClient("{$perception_qmwise}?wsdl", array(
        "user_agent"  =>  "LTI Perception connector",
        "trace"     =>  $this->debug,
        'stream_context' => $context
      ));
      if(!is_null($security_client_id) || !is_null($security_checksum)) {
        if(is_null($security_client_id)) {
          trigger_error("Expected perception security clientID along with checksum -- cancelling security", E_USER_WARNING);
        } else if(is_null($security_checksum)) {
          trigger_error("Expected perception security checksum along with clientID -- cancelling security", E_USER_WARNING);
        } else {
          $this->soap->__setSoapHeaders(array(new SoapHeader("http://questionmark.com/QMWISe/", "Security", array(
            "ClientID"  =>  $security_client_id,
            "Checksum"  =>  $security_checksum
          ))));
        }
      }
    } catch(Exception $e) {
      throw $e;
    }
  }

  /**
   * Debugging function -- interfaces to Soap debugging functions
   *
   * Only available if debug var is set to true and so trace is active in the
   * Soap client
   *
   * @return String Last request or NULL
   */
  public function __getLastRequest() {
    if(!$this->debug) {
      trigger_error("debugging functions not available unless debug is set to true", E_USER_WARNING);
      return null;
    }
    return $this->soap->__getLastRequest();
  }

  /**
   * Debugging function -- interfaces to Soap debugging functions
   *
   * Only available if debug var is set to true and so trace is active in the
   * Soap client
   *
   * @return String Last request headers or NULL
   */
  public function __getLastRequestHeaders() {
    if(!$this->debug) {
      trigger_error("debugging functions not available unless debug is set to true", E_USER_WARNING);
      return null;
    }
    return $this->soap->__getLastRequestHeaders();
  }

  /**
   * Debugging function -- interfaces to Soap debugging functions
   *
   * Only available if debug var is set to true and so trace is active in the
   * Soap client
   *
   * @return String last response or NULL
   */
  public function __getLastResponse() {
    if(!$this->debug) {
      trigger_error("debugging functions not available unless debug is set to true", E_USER_WARNING);
      return null;
    }
    return $this->soap->__getLastResponse();
  }

  /**
   * Debugging function -- interfaces to Soap debugging functions
   *
   * Only available if debug var is set to true and so trace is active in the
   * Soap client
   *
   * @return String last response headers or NULL
   */
  public function __getLastResponseHeaders() {
    if(!$this->debug) {
      trigger_error("debugging functions not available unless debug is set to true", E_USER_WARNING);
      return null;
    }
    return $this->soap->__getLastResponseHeaders();
  }

  /**
   * Used to check credentials provided by user
   *
   * @return TRUE or NULL on error
   */
  public function get_about() {
    try {
      $about = $this->soap->GetAbout();
    } catch(SoapFault $e) {
      throw new QMWiseException($e);
    }
    return TRUE;
  }

  /**
   * Get an administrator's details from perception, we are especially interested in administrator id
   *
   * @param String username
   *
   * @return Administrator object
   */
  public function get_administrator_by_name($username) {
    try {
      $administrator = $this->soap->GetAdministratorByName(array(
        'Administrator_Name' => $username
      ));
    } catch(SoapFault $e) {
      throw new QMWiseException($e);
    }
    return $administrator->Administrator;
  }

  /**
   * Create an administrator in perception, we are especially interested in administrator id
   *
   * @param String username
   * @param String first name
   * @param String last name
   * @param String email
   * @param String Profile name
   *
   * @return Administrator object
   */
  public function create_administrator_with_password($username, $firstname, $lastname, $email, $profile) {
    $password = getRandomString(20);
    $admin2 = new SoapVar(array(
         'Administrator_ID' => 0,
         'Administrator_Name' => $username,
         'Password' => $password,
         'Profile_Name' => $profile,
         'AuthenticateExt' => 0,
         'Email' => $email,
         'First_Name' => $firstname,
         'Last_Name' => $lastname
      ), SOAP_ENC_OBJECT, 'Administrator2', 'http://questionmark.com/QMWISe/', NULL, 'http://questionmark.com/QMWISe/');
    $admin = new stdClass();
    $admin->Administrator = new SoapVar($admin2, SOAP_ENC_OBJECT, NULL, NULL, 'Administrator', 'http://questionmark.com/QMWISe/');
    $params = new SoapVar($admin, SOAP_ENC_OBJECT, NULL, 'http://questionmark.com/QMWISe/');
    try {
      $administrator = $this->soap->CreateAdministratorWithPassword($params);
    } catch(SoapFault $e) {
      throw new QMWiseException($e);
    }
    return $administrator;
  }

  /**
   * Gets an assessment object based on assessment id.
   *
   * @param Integer assessment id
   *
   * @return Assessment object
   */
  public function get_assessment($assessment_id) {
    try {
      $response = $this->soap->GetAssessment(array(
        "Assessment_ID" => $assessment_id
      ));
    } catch(SoapFault $e) {
      throw new QMWiseException($e);
    }
    return $response->Assessment;
  }

  /**
   * Gets an assessment list object based on parent id.
   *
   * @param Integer parent id
   *
   * @return Array of Assessment objects
   */
  public function get_assessment_list($parent_id) {
    try {
      $list = $this->soap->GetAssessmentList(array(
        "Parent_ID" => $parent_id
      ));
    } catch(SoapFault $e) {
      throw new QMWiseException($e);
    }
    if (!isset($list->AssessmentList->Assessment)) {
      return array();
    } else if (!is_array($list->AssessmentList->Assessment)) {
      return array($list->AssessmentList->Assessment);
    } else {
      return $list->AssessmentList->Assessment;
    }
  }

  /**
   * Gets an assessment list based on the administrator id
   *
   * @param Integer administrator id
   * @param Integer parent id
   * @param Boolean run from integration
   *
   * @return Array of  Assessment objects
   */
  public function get_assessment_list_by_administrator($admin_id, $parent_id, $only_run_from_integration) {
    try {
      $list = $this->soap->GetAssessmentListByAdministrator(array(
        "Administrator_ID" => $admin_id,
        "Parent_ID" => $parent_id,
        "OnlyRunFromIntegration" => $only_run_from_integration
      ));
    } catch(SoapFault $e) {
      throw new QMWiseException($e);
    }
    if (!isset($list->AssessmentList->Assessment)) {
      return array();
    } else if (!is_array($list->AssessmentList->Assessment)) {
      return array($list->AssessmentList->Assessment);
    } else {
      return $list->AssessmentList->Assessment;
    }
  }

  /**
   * Gets an assessment results based on id.
   *
   * @param Integer assessment id
   *
   * @return Array of Assessment objects
   */
  public function get_assessment_result_list_by_assessment($assessment_id) {
    try {
      $response = $this->soap->GetAssessmentResultListByAssessment(array(
        "Assessment_ID" => $assessment_id
      ));
    } catch(SoapFault $e) {
      throw new QMWiseException($e);
    }
    return $response->AssessmentResultList;
  }

  /**
   * Gets a participant list by group id
   *
   * @param Integer group id
   *
   * @return Array of Participant objects
   */
  public function get_participant_list_by_group($group_id) {
    try {
      $response = $this->soap->GetParticipantListByGroup(array(
        "Group_ID" => $group_id
      ));
    } catch(SoapFault $e) {
      throw new QMWiseException($e);
    }
    return $response;
  }

  /**
   * Gets an assessment result list based on the participant
   *
   * @param String participant name
   *
   * @return Array of assessment results
   */
  public function get_assessment_result_list_by_participant($participant_name) {
    try {
      $response = $this->soap->GetAssessmentResultListByParticipant(array(
        "Participant_Name" => $participant_name
      ));
    } catch (SoapFault $e) {
      throw new QMWiseException($e);
    }
    return $response->AssessmentResultList;
  }

  /**
   * Gets the access URL for the administrator to access the Portal.
   *
   * @param String username of administrator
   *
   * @return String url
   */
  public function get_access_administrator($username) {
    try {
      $url = $this->soap->GetAccessAdministrator(array(
        "Administrator_Name" => $username
      ));
    } catch(SoapFault $e) {
      throw new QMWiseException($e);
    }
    return $url;
  }

  /**
   * Get an access URL for a particular assessment, participant name, user ID, activity ID and course ID
   *
   * If Pip is active and Content-Type is set correctly in it a script
   * ($notify_url) is notified with these details as POST vars when the test
   * is completed. Note that a query string on the end of $notify_url
   * sometimes works -- in some versions of Perception the ampersands get lost
   * and so only one GET var can be used without unexpected results.
   * If Pip is active and the USEHOME setting is switch on, the HOME button at
   * the end of the test is set to $home_url with the details as GET vars.
   * Note that no query string is allowed at the end of $home_url since
   * Perception doesn't check for one and just adds its own questionmark and
   * query string at the end.
   *
   * @param Integer assessment id
   * @param String participant name
   * @param String consumer key
   * @param String The ID of the resource link
   * @param Integer result id
   * @param String the URL to allow the LTI to begin adjusting grades
   * @param String the URL to return to after the assessment is completed
   * @param String the participant ID
   * @param Array optional parameters passed from the LMS
   *
   * @return String URL to access the assessment
   */
  public function get_access_assessment_notify($assessment_id, $participant_name, $consumer_key, $resource_link_id, $result_id, $notify_url, $home_url, $participant_id, $additional_params = array()) {
    try {
      $access_parameters = array(
        "PIP" => PIP_FILE,
        "Assessment_ID" => $assessment_id,
        "Participant_Name" => $participant_name,
        "Notify" => $notify_url,
        "ParameterList" => array(
          "Parameter" => array(
            array("Name" => "HOME", "Value" => $home_url),
            array("Name" => "lti_consumer_key", "Value" => $consumer_key),
            array("Name" => "lti_context_id", "Value" => $resource_link_id),
            array("Name" => "lti_result_id", "Value" => $result_id),
            array("Name" => "lti_participant_id", "Value" => $participant_id),
            array("Name" => "CALLBACK", "Value" => 1)
          )
        )
      );

      foreach ($additional_params as $key => $value) {
        $access_parameters['ParameterList']['Parameter'][] = array(
          "Name" => $key,
          "Value" => $value
        );
      }

      $access_assessment = $this->soap->GetAccessAssessmentNotify($access_parameters);
    } catch(SoapFault $e) {
      throw new QMWiseException($e);
    }
    return $access_assessment;
  }

 /**
  * Maps the variables to SOAP call createScheduleParticipantv42, gets back schedule_id with parameters set.
  *
  * All parameters are required to be passed to the function.
  *
  * @param Integer old schedule id, will be overwritten after but is required for call
  * @param String schedule name
  * @param Integer assessment id
  * @param Integer participant id
  * @param Boolean set whether or nnot the schedule is time restricted.
  * @param String ISO 8601 standard for starting schedule time
  * @param String ISO 8601 standard for ending schedule time
  * @param Integer group id
  * @param Integer group tree id, often defaulted to the group id
  * @param Boolean identify whether or not this schedule is to be used for web delievery
  * @param Boolean identify if the attempts are limited
  * @param Integer max attempt count
  * @param Boolean identify if system is monitored
  * @param Integer test center id
  * @param Integer minimum days between attempts taken
  * @param Boolean identify if there is a time limit to be attached to the attempt
  * @param Integer time limit
  * @param Boolean identify if assessment should be used for offline delivery.
  *
  * @return Integer schedule id
  */
  public function create_schedule_participant($schedule_id, $schedule_name, $assessment_id, $participant_id, $restrict_times = TRUE, $schedule_starts, $schedule_stops, $group_id, $group_tree_id, $web_delivery, $restrict_attempts, $max_attempts, $monitored, $test_center_id, $min_days_between_attempts, $time_limit_override, $time_limit, $offline_delivery) {
    try {
      $access_parameters = array(
        "schedule" => array(
          "Schedule_ID" => $schedule_id,
          "Schedule_Name" => $schedule_name,
          "Assessment_ID" => $assessment_id,
          "Participant_ID" => $participant_id,
          "Restrict_Times" => $restrict_times,
          "Schedule_Starts" => $schedule_starts,
          "Schedule_Stops" => $schedule_stops,
          "Group_ID" => $group_id,
          "Group_Tree_ID" => $group_tree_id,
          "Web_Delivery" => $web_delivery,
          "Restrict_Attempts" => $restrict_attempts,
          "Max_Attempts" => $max_attempts,
          "Monitored" => $monitored,
          "Test_Center_ID" => $test_center_id,
          "Min_Days_Between_Attempts" => $min_days_between_attempts,
          "Time_Limit_Override" => $time_limit_override,
          "Time_Limit" => $time_limit,
          "Offline_Delivery" => $offline_delivery
        )
      );
      $schedule_id = $this->soap->CreateScheduleParticipantV42($access_parameters);
    } catch(SoapFault $e) {
      throw new QMWiseException($e);
    }
    return $schedule_id;
  }

  /**
   * Calls QM process to return URL to access assessment attempt
   *
   * @param Integer schedule id
   * @param String participant name
   * @param String consumer key
   * @param String The ID of the resource link
   * @param Integer result id
   * @param String the URL to allow the LTI to begin adjusting grades
   * @param String the URL to return to after the assessment is completed
   * @param String the participant ID
   * @param Array optional parameters passed from the LMS
   *
   * @return String URL to access the schedule
   */
  public function get_access_schedule_notify($schedule_id, $participant_name, $consumer_key, $resource_link_id, $result_id, $notify_url, $home_url, $participant_id, $additional_params = array()) {
    try {
      $access_parameters = array(
        "PIP" => PIP_FILE,
        "Schedule_ID" => $schedule_id,
        "Participant_Name" => $participant_name,
        "Notify" => $notify_url,
        "ParameterList" => array(
          "Parameter" => array(
            array("Name" => "HOME", "Value" => $home_url),
            array("Name" => "lti_consumer_key", "Value" => $consumer_key),
            array("Name" => "lti_context_id", "Value" => $resource_link_id),
            array("Name" => "lti_result_id", "Value" => $result_id),
            array("Name" => "lti_participant_id", "Value" => $participant_id),
            array("Name" => "schedule_id", "Value" => $schedule_id),
            array("Name" => "CALLBACK", "Value" => 1)
          )
        )
      );

      foreach ($additional_params as $key => $value) {
        $access_parameters['ParameterList']['Parameter'][] = array(
          "Name" => $key,
          "Value" => $value
        );
      }
      $access_assessment = $this->soap->GetAccessScheduleNotify($access_parameters);
    } catch(SoapFault $e) {
      throw new QMWiseException($e);
    }
    return $access_assessment;
  }


  /**
   * Return the URL of a report for a given result ID
   *
   * @param String result id
   *
   * @return String URL for coaching report
   */
  public function get_report_url($result_id) {
    try {
      $access_report = $this->soap->GetAccessReport(array(
        'Result_ID' => $result_id
      ));
    } catch (SoapFault $e) {
      throw new QMWiseException($e);
    }
    return $access_report;
  }

  /**
   * Gets a group from perception
   *
   * @param String name of the group
   *
   * @return SOAPResponse response from QMWISe
   */
  public function get_group_by_name($groupname) {
    try {
      $response = $this->soap->GetGroupByName(array(
        'Group_Name' => $groupname
      ));
    } catch (SoapFault $e) {
      throw new QMWiseException($e);
    }
    return $response;
  }

  /**
   * Creates a group with a specified name, description and parent group id
   *
   * @param String name of group
   * @param String description
   * @param Integer parent group id
   *
   * @return SOAPResponse response from QMWISe
   */
  public function create_group($groupname, $description, $parentid) {
    try {
      $response = $this->soap->CreateGroup(array(
        'Group' => array(
          'Parent_ID' => $parentid,
          'Group_Name' => $groupname,
          'Description' => $description,
          // Required attributes from QMWise
          'Account_Status' => '',
          'Max_Participants' => '',
          'Max_Sessions_Attempt' => '',
          'Session_Taken' => '',
          'Account_Type' => '',
          'Use_Emailing' => ''
        )
      ));
    } catch (SoapFault $e) {
      throw new QMWiseException($e);
    }
    return $response;
  }

  /**
   * Adds the participant to the group
   *
   * @param Integer group id
   * @param Integer participant id
   *
   * @return SOAPResponse response from QMWISe
   */
  public function add_group_participant_list($groupid, $participantid) {
    try {
      $response = $this->soap->AddGroupParticipantList(array(
        'Group_ID' => $groupid,
        'ParticipantIDList' => array(
          'Participant_ID' => $participantid
        )
      ));
    } catch (SoapFault $e) {
      throw new QMWiseException($e);
    }
    return $response;
  }

  /**
   * Adds an administrator to the group
   *
   * @param Integer group id
   * @param Integer administrator id
   *
   * @return SOAPResponse response from QMWISe
   */
  public function add_group_administrator_list($groupid, $administratorid) {
    try {
      $response = $this->soap->AddGroupAdministratorList(array(
        'Group_ID' => $groupid,
        'AdministratorIDList' => array(
          'Administrator_ID' => $administratorid
        )
      ));
    } catch (SoapFault $e) {
      throw new QMWiseException($e);
    }
    return $response;
  }

  /**
   * Gets group list attached to participant
   *
   * @param Integer participant id
   *
   * @return SOAPResponse response from QMWISe
   */
  public function get_participant_group_list($participantid) {
    try {
      $response = $this->soap->GetParticipantGroupList(array(
        'Participant_ID' => $participantid
      ));
    } catch (SoapFault $e) {
      throw new QMWiseException($e);
    }
    return $response;
  }

  /**
   * Gets group list attached to administrator
   *
   * @param Integer administrator id
   *
   * @return SOAPResponse response from QMWISe
   */
  public function get_administrator_group_list($administratorid) {
    try {
      $response = $this->soap->GetAdministratorGroupList(array(
        'Administrator_ID' => $administratorid
      ));
    } catch (SoapFault $e) {
      throw new QMWiseException($e);
    }
    return $response;
  }

  /**
   * Get an participant's details from perception
   *
   * @param String username
   *
   * @return Participant object
   */
  public function get_participant_by_name($username) {
    try {
      $response = $this->soap->GetParticipantByName(array(
        'Participant_Name' => $username
      ));
    } catch(SoapFault $e) {
      throw new QMWiseException($e);
    }
    return $response->Participant;
  }

  /**
   * Create a participant in perception
   *
   * @param String username
   * @param String first name
   * @param String last name
   * @param String email
   *
   * @return Participant object
   */
  public function create_participant($username, $firstname, $lastname, $email) {
    $password = getRandomString(20);
    $participant2 = new SoapVar(array(
         'Participant_Name' => $username,
         'Password' => $password,
         'AuthenticateExt' => 0,
         'Primary_Email' => $email,
         'First_Name' => $firstname,
         'Last_Name' => $lastname,
         'Use_Correspondence' => 0,
         'Authenticate_Ext' => 0
      ), SOAP_ENC_OBJECT, 'Participant', 'http://questionmark.com/QMWISe/', NULL, 'http://questionmark.com/QMWISe/');
    $participant = new stdClass();
    $participant->Participant = new SoapVar($participant2, SOAP_ENC_OBJECT, NULL, NULL, 'Participant', 'http://questionmark.com/QMWISe/');
    $params = new SoapVar($participant, SOAP_ENC_OBJECT, NULL, 'http://questionmark.com/QMWISe/');
    try {
      $participant = $this->soap->CreateParticipant($params);
    } catch(SoapFault $e) {
      throw new QMWiseException($e);
    }
    return $participant;
  }
}

?>
