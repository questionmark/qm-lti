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

  if (!$ok) {
    header('Location: error.php');
    exit;
  }

  page_header();
?>
        <p>
        <a href="<?php echo $em_url; ?>" target="_blank" />Log into Enterprise Manager</a>&nbsp;&nbsp;
        <a href="staff.php" />Back to Control Panel</a>
        </p>
        <h1>Assessment Results</h1>
<?php
  if ((count($results) > 0) && !is_null($results[0])) {
?>
        <form action="staff.php" method="POST">
        <table class="DataTable" cellpadding="0" cellspacing="0">
        <tr class="GridHeader">
          <td>Participant</td>
          <td>Score</td>
          <td>Time Taken</td>
          <td>When Finished</td>
          <td>Coaching Result</td>
        </tr>
<?php
    $i = 0;
    foreach ($results as $result) {
      $i++;
?>
        <tr class="GridRow">
          <td><?php echo $result->Result->Participant; ?></td>
          <td><?php echo "{$result->Result->Total_Score}/{$result->Result->Max_Score} ({$result->Result->Percentage_Score}%)";  ?></td>
          <td><?php echo "{$result->Result->Time_Taken}s"; ?></td>
          <td><?php echo str_replace('T', ' ', $result->Result->When_Finished); ?>
          </td>
          <td><a href="<?php echo get_report_url($result->Result->Result_ID)->URL ?>">View Now</a></td>
        </tr>
<?php
    }
?>
        </table>
        <br><br><br>
        </form>
<?php
  } else {
?>
        <p>No results available.</p>
<?php
  }

  page_footer();
?>