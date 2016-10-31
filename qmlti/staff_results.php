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
 *    1.2.00  23-Jul-12
 *    2.0.00  18-Feb-13  Moved script into page header
*/

require_once('lib.php');
require_once('LTI_Data_Connector_qmp.php');

// Initialise database
  $db = open_db();

  session_name(SESSION_NAME);
  session_start();

  $consumer_key = $_SESSION['consumer_key'];
  $resource_link_id = $_SESSION['resource_link_id'];
  $username = $_SESSION['username'];
  $firstname = $_SESSION['firstname'];
  $lastname = $_SESSION['lastname'];
  $email = $_SESSION['email'];
  $isStudent = $_SESSION['isStudent'];
  $coachingReport = $_SESSION['coaching_report'];
  $assessment_id = $_SESSION['assessment_id'];

  $ok = !$isStudent;
  if (!$ok) {
    $_SESSION['error'] = 'Invalid role';
  }

  // Activate SOAP Connection.
  if ($ok) {
    $ok = perception_soapconnect();
  }

  // Get login URL
  if ($ok) {
    $em_url = get_access_administrator($username);
    $ok = !empty($em_url);
  }

  if ($ok && (($admin_details = get_administrator_by_name($username)) !== FALSE)) {
    $admin_id = $admin_details->Administrator_ID;
  } else if ($ok && (($admin_id = create_administrator_with_password($username, $firstname, $lastname, $email, ADMINISTRATOR_ROLE)) === FALSE)) {
    $ok = FALSE;
  }

  $results = get_assessment_result_list_by_assessment($assessment_id)->AssessmentResult;
  foreach ($results as $result) {
    $result->Result->URL = get_report_url($result->Result->Result_ID)->URL;
  }

  if (!$ok) {
    header('Location: error.php');
    exit;
  }


  page_header();
  include_once("app/View/staff_results.php");
  page_footer();
?>