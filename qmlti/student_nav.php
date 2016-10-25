<?php
/*
 *  LTI-Connector - Connect to Perception via IMS LTI
 *  Copyright (C) 2013  Questionmark
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
*/

require_once('lib.php');
require_once('LTI_Data_Connector_qmp.php');

  $db = open_db();

  session_name(SESSION_NAME);
  session_start();

// Get data from session
  $consumer_key = $_SESSION['consumer_key'];
  $resource_link_id = $_SESSION['resource_link_id'];
  $assessment_id = $_SESSION['assessment_id'];
  $username = $_SESSION['username'];
  $firstname = $_SESSION['firstname'];
  $lastname = $_SESSION['lastname'];
  $email = $_SESSION['email'];
  $return_url = $_SESSION['lti_return_url'];
  if (!$return_url) {
    $return_url = get_root_url() . 'return.php';
  }

  $coachingReport = $_SESSION['coaching_report'];
  $isStudent = $_SESSION['isStudent'];

  $notify_url = get_root_url() . 'notify.php';
  $result_id = $_SESSION['result_id'];

  // Ensure this is a student, an assessment has been defined and the LMS will accept an outcome
  if (!$isStudent) {
    $_SESSION['error'] = 'Not a student';
  } else if (!$assessment_id) {
    $_SESSION['error'] = 'No assignment selected';
  } else if (!$result_id) {
    $_SESSION['error'] = 'No grade book column';
  }

// Activate SOAP Connection.
  if (!isset($_SESSION['error'])) {
    perception_soapconnect();
  }

  // An action was previously selected
  if (isset($_POST['action'])) {
    if ($_POST['action'] == 'assessment') {
      // start assessment
      $redirect =  get_root_url() . 'student.php';
      header("Location: {$redirect}");
    } else if ($_POST['action'] == 'coachingreport') {
      // view coaching report
      $participant_name = "{$firstname} {$lastname}";
      $resultIDs = get_result_id($participant_name);
      $coachingreport = get_report_url($resultIDs->AssessmentResult[0]->Result->Result_ID);
      header("Location: {$coachingreport->URL}");
    }
  }

// // Create participant if it doesn't exist
  if (!isset($_SESSION['error'])) {
    $participant_details = get_participant_by_name($username);
    $participant_id = $participant_details->Participant_ID;
  } else if (!isset($_SESSION['error'])) {
    $participant_id = create_participant($username, $firstname, $lastname, $email);
  }

// Get assessment URL
  if (!isset($_SESSION['error'])) {
    $url = get_access_assessment_notify($assessment_id, "${firstname} {$lastname}", $consumer_key, $resource_link_id, $result_id,
       $notify_url, $return_url, $coachingReport);
  }

  // Get assessment
  $assessment = '';
  if (!isset($_SESSION['error'])) {
    $assessment = get_assessment($assessment_id);
  }

  if (isset($_SESSION['error'])) {
    $url = "error.php";
  }

  # header("Location: {$url}");
  page_header();
?>
<h1>Student Portal</h1>
<form action="student_nav.php" method="POST">
  Select one of the following options:<br><br>
  <input type="radio" name="action" value="assessment" />Start assessment<br>
  <input type="radio" name="action" value="coachingreport" />View coaching report<br><br>
  <input type="submit" id="id_action" value="Start" />
</form>
<br>