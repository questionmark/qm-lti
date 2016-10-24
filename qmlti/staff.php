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

  $coaching_check = '';
  
  if (isset($coachingReport)) {
    if ($coachingReport) {
      $coaching_check = 'checked';
    } else {
      $coaching_check = '';
    }
  }

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
    $data_connector = LTI_Data_Connector::getDataConnector(TABLE_PREFIX, $db, DATA_CONNECTOR);
    $consumer = new LTI_Tool_Consumer($consumer_key, $data_connector);
    $resource_link = new LTI_Resource_Link($consumer, $resource_link_id);
    $resource_link->setSetting(ASSESSMENT_SETTING, $_SESSION['assessment_id']);
    $resource_link->setSetting(COACHING_REPORT, $coachingReport);
    $resource_link->save();

    // Insert / Update Coaching Reports index
    if ($data_connector->ReportConfig_load($resource_link_id, $assessment_id)) {
      error_log("Updating coaching report configuration with {$resource_link_id}, {$assessment_id}, {$intCoaching}.");
      $save = $data_connector->ReportConfig_update($resource_link_id, $assessment_id, $intCoaching);
    } else {
      error_log("Inserting coaching report configuration with {$resource_link_id}, {$assessment_id}, {$intCoaching}.");
      $save = $data_connector->ReportConfig_insert($resource_link_id, $assessment_id, $intCoaching);
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
  if ($ok && (($assessments = get_assessment_list_by_administrator($admin_id)) === FALSE)) {
    $assessments = array();
  }

  if (!$ok) {
    header('Location: error.php');
    exit;
  }

  $script = <<< EOD
<script type="text/javascript">
<!--
function doChange(id) {
  doReset();
  var el = document.getElementById(id);
  if (el) {
    el.className = 'show';
  }
  el = document.getElementById('id_save');
  el.disabled = false;
}

function doReset() {
  var el = document.getElementById('id_save');
  el.disabled = true;
  for (var i=1; i<=document.forms[0].assessment.length; i++) {
    el = document.getElementById('img' + i);
    if (el) {
      el.className = 'hide';
    }

  }
}
// -->
</script>

EOD;
  page_header($script);
?>
        <p><a href="<?php echo $em_url; ?>" target="_blank" />Log into Enterprise Manager</a></p>
<?php
  if (!$_SESSION['allow_outcome']) {
?>
        <p><strong>No score will be saved by this connection.</strong></p>
<?php
  }
?>
        <h1>Assessments</h1>
<?php
  if ((count($assessments) > 0) && !is_null($assessments[0])) {
?>
        <form action="staff.php" method="POST">
        <table class="DataTable" cellpadding="0" cellspacing="0">
        <tr class="GridHeader">
          <td>&nbsp;</td>
          <td class="AssessmentName">Assessment Name</td>
          <td class="AssessmentAuthor">Assessment Author</td>
          <td class="LastModified">Last Modified</td>
        </tr>
<?php
    $i = 0;
    foreach ($assessments as $assessment) {
      $i++;
      if ($assessment->Assessment_ID == $assessment_id) {
        $selected = ' checked="checked" onclick="doReset();"';
      } else {
        $selected = ' onclick="doChange(\'img' . $i . '\');"';
      }
?>
        <tr class="GridRow">
          <td><img src="images/exclamation.png" alt="Unsaved change" title="Unsaved change" class="hide" id="img<?php echo $i; ?>" />&nbsp;<input type="radio" name="assessment" value="<?php echo $assessment->Assessment_ID; ?>"<?php echo $selected; ?> /></td>
          <td><?php echo $assessment->Session_Name; ?></td>
          <td><?php echo $assessment->Author; ?></td>
          <td><?php echo $assessment->Modified_Date; ?></td>
        </tr>
<?php
    }
?>
        </table>
        <br><hr>
        <input type="hidden" id="id_coachingreport" name="id_coachingreport" value="0">
        <input type="checkbox" id="id_coachingreport" name="id_coachingreport" onclick="doChange('id_coachingreport');" value="1" <?php echo $coaching_check ?> >Allow participants to view coaching reports.
        <p>
        <input type="submit" id="id_save" value="Save change" disabled="disabled" />
        </p>
        </form>
<?php
  } else {
?>
        <p>No assessments available.</p>
<?php
  }

  page_footer();
?>