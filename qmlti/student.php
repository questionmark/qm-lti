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

  session_name();
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
  # $notify_url = get_root_url() . 'notify.php';
  # error_log($notify_url);

  #$notify_url = "http://pipnotifyreflector.azurewebsites.net/home/index/anson-li-notify-999" . rand(0, 100000);
  $notify_url = 'http://localhost/LTI/notify.php';
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

// Create participant
  if (!isset($_SESSION['error']) && (($participant_details = get_participant_by_name($username)) !== FALSE)) {
    $participant_id = $participant_details->Participant_ID;
  } else if (!isset($_SESSION['error'])) {
    $participant_id = create_participant($username, $firstname, $lastname, $email);
  }

// Get assessment URL
  if (!isset($_SESSION['error'])) {
    $url = get_access_assessment_notify($assessment_id, "${firstname} {$lastname}", $consumer_key, $resource_link_id, $result_id,
       $notify_url, $return_url, $coachingReport);
  }

  if (isset($_SESSION['error'])) {
    $url = "error.php";
    error_log( print_r( $_SESSION['error'], true ) );
  }

  error_log( print_r( $url, true));
  header("Location: {$url}");

?>