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
 *    1.0.01   2-May-12  Corrected GET to POST requests
 *    1.2.00  23-Jul-12
 *    2.0.00  18-Feb-13
*/

require_once('../resources/lib.php');
require_once('../resources/LTI_Data_Connector_qmp.php');

  // initialise database
  $db = open_db();

  // Catch any issues with using an incompatible lti.pip
  $post_required = array('lti_participant_id');
  foreach ($post_required as $field) {
    if (empty($_POST[$field])) {
      error_log("Invalid parameter configuration, did not save result.");
      exit();
    }
  }

  $consumer_key = $_POST['lti_consumer_key'];
  $resource_link_id = $_POST['lti_context_id'];
  $result_id = $_POST['lti_result_id'];
  $report_id = $_POST['Result_ID'];
  $participant_id = $_POST['lti_participant_id'];
  $score = $_POST['Percentage_Score'];
  $participant = $_POST['Participant'];
  $schedule_id = $_POST['schedule_id'];

  $is_saved = FALSE;
  $score_decimal = $score / 100;

  // Initialise tool consumer and resource link objects
  $data_connector = LTI_Data_Connector::getDataConnector(TABLE_PREFIX, $db, DATA_CONNECTOR);
  $consumer = new LTI_Tool_Consumer($consumer_key, $data_connector);
  $resource_link = new LTI_Resource_Link($consumer, $resource_link_id);

  $multiple_results = $resource_link->getSetting(MULTIPLE_RESULTS);

  switch ($multiple_results) {
    case 'Newest':
      $is_saved = TRUE;
      break;
    case 'Best':
      $is_saved = is_best_result($db, $consumer, $resource_link, $participant_id, $score_decimal);
      break;
    case 'Worst':
      $is_saved = is_worst_result($db, $consumer, $resource_link, $participant_id, $score_decimal);
      break;
    case 'Oldest':
      $is_saved = is_oldest_result($db, $consumer, $resource_link, $participant_id);
      break;
    default:
      error_log("Failed to establish result parameter, did not save result.");
  }

  $outcome = new LTI_Outcome($result_id);
  $outcome->setValue($score);
  $outcome->setResultID($report_id);
  $outcome->type = 'percentage';

  if ($is_saved) {
    if ($resource_link->hasOutcomesService()) {
      // Save result
      if ($resource_link->doOutcomesService(LTI_Resource_Link::EXT_WRITE, $outcome)) {
        $outcome->clearAccessedResult($consumer, $resource_link, $participant_id);
        $outcome->saveToResult($consumer, $resource_link, $participant_id, 1, $result_id);
        $outcome->deleteAttempt($consumer, $resource_link, $schedule_id, $participant_id);
      } else {
        error_log("Failed to pass outcome of {$score} for {$result_id}.");
      }
    }
  } else {
    $resource_link->checkValueType($outcome);
    $outcome->saveToResult($consumer, $resource_link, $participant_id, 0, $result_id);
    $outcome->deleteAttempt($consumer, $resource_link, $participant_id, $schedule_id, $participant_id);
  }
?>
