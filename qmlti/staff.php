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
  $multipleResults = $_SESSION['multiple_results'];
  $arr_results = [ "Best", "Worst", "Newest", "Oldest" ];

  $coaching_check = '';
  $data_connector = LTI_Data_Connector::getDataConnector(TABLE_PREFIX, $db, DATA_CONNECTOR);
  $consumer = new LTI_Tool_Consumer($consumer_key, $data_connector);
  $resource_link = new LTI_Resource_Link($consumer, $resource_link_id);

  // checks if a coaching report setting is already set
  if (isset($coachingReport)) {
    if ($coachingReport) {
      $coaching_check = 'checked';
      $coachingReport = True;
      $intCoaching = 1;
    } else {
      $coaching_check = '';
      $coachingReport = False;
      $intCoaching = 0;
    }
  }

  // checks if the coaching report option was changed
  if (isset($_POST['id_coachingreport'])) {
    if ($_POST['id_coachingreport'] == '1') {
      $coaching_check = 'checked';
      $coachingReport = True;
      $intCoaching = 1;
    } else {
      $coaching_check = '';
      $coachingReport = False;
      $intCoaching = 0;
    }
  } 

  if (isset($_POST['assessment'])) {

    $_SESSION['assessment_id'] = htmlentities($_POST['assessment']);
    $resource_link->setSetting(ASSESSMENT_SETTING, $_SESSION['assessment_id']);
    $assessment_id = $_SESSION['assessment_id'];

    if (isset($_POST['id_multipleresult'])) {
      if ($multipleResults != $_POST['id_multipleresult']) {
        update_result_accessed($db, $consumer, $resource_link, $assessment_id, $_POST['id_multipleresult'] );
        $multipleResults = $_POST['id_multipleresult'];
      }
    }

    $resource_link->setSetting(COACHING_REPORT, $coachingReport);
    $resource_link->setSetting(MULTIPLE_RESULTS, $multipleResults);
    $resource_link->save();

    // Insert / Update Coaching Reports index
    if ($data_connector->ReportConfig_loadAccessible($consumer_key, $resource_link_id, $assessment_id) != NULL) {
      $save = $data_connector->ReportConfig_update($consumer_key, $resource_link_id, $assessment_id, $intCoaching);
    } else {
      $save = $data_connector->ReportConfig_insert($consumer_key, $resource_link_id, $assessment_id, $intCoaching);
    }
  }

  if (isset($_POST['id_multipleresult'])) {
    if ($multipleResults != $_POST['id_multipleresult']) {
      update_result_accessed($db, $consumer, $resource_link, $assessment_id, $_POST['id_multipleresult'] );
      $multipleResults = $_POST['id_multipleresult'];
    }
  }


  $ok = !$isStudent;
  if (!$ok) {
    $_SESSION['error'] = 'Invalid role';
  }

// Activate SOAP Connection.
  if ($ok) {
    $ok = perception_soapconnect();
  }

// Create administrator
  if ($ok && (($admin_details = get_administrator_by_name($username)) !== FALSE)) {
    $admin_id = $admin_details->Administrator_ID;
  } else if ($ok && (($admin_id = create_administrator_with_password($username, $firstname, $lastname, $email, ADMINISTRATOR_ROLE)) === FALSE)) {
    $ok = FALSE;
  }

// Get login URL
  if ($ok) {
    $em_url = get_access_administrator($username);
    $ok = !empty($em_url);
  }

  // Get assessments
  // if ($ok && (($assessments = get_assessment_list_by_administrator($admin_id)) === FALSE)) {
  if ($ok && (($assessments = get_assessment_list()) === FALSE)) {
    $assessments = array();
  }

  if (!$ok) {
    header('Location: error.php');
    exit;
  }

  $script = <<< EOD
<script src="js/staff.js" type="text/javascript"></script>

EOD;
  page_header($script);
  include_once("app/View/staff.php");
  page_footer();
?>