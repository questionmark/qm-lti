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
 *    1.0.01   2-May-12  Corrected GET to POST requests
 *    1.2.00  23-Jul-12
 *    2.0.00  18-Feb-13
*/

require_once('lib.php');
require_once('LTI_Data_Connector_qmp.php');

// initialise database
  $db = open_db();

  $consumer_key = $_POST['lti_consumer_key'];
  $resource_link_id = $_POST['lti_context_id'];
  $result_id = $_POST['lti_result_id'];
  $score = $_POST['Percentage_Score'];

// Initialise tool consumer and resource link objects
  $data_connector = LTI_Data_Connector::getDataConnector(TABLE_PREFIX, $db, DATA_CONNECTOR);
  $consumer = new LTI_Tool_Consumer($consumer_key, $data_connector);
  $resource_link = new LTI_Resource_Link($consumer, $resource_link_id);

  if ($resource_link->hasOutcomesService()) {
// Save result
    $outcome = new LTI_Outcome($result_id);
    $outcome->setValue($score);
    $outcome->type = 'percentage';
    if (!$resource_link->doOutcomesService(LTI_Resource_Link::EXT_WRITE, $outcome)) {
      error_log("Error saving outcome of {$score} for {$result_id}");
    }
  }

?>