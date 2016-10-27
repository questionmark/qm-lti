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
 *    2.0.00  18-Feb-13  Renamed from index.php
*/

require_once('lib.php');
require_once('LTI_Data_Connector_qmp.php');

  session_name(SESSION_NAME);
  session_start();

// initialise database
  $db = open_db();
  if ($db === FALSE) {
    header('Location: error.php');
    exit;
  }

// process launch request
  $data_connector = LTI_Data_Connector::getDataConnector(TABLE_PREFIX, $db, DATA_CONNECTOR);
  $tool = new LTI_Tool_Provider('doLaunch', $data_connector);
  $tool->execute();

  exit;

// process validated connection
  function doLaunch($tool_provider) {

    global $db;

    if (defined('QMWISE_URL')) {
      $prefix = QM_USERNAME_PREFIX;
    } else {
      $prefix = $tool_provider->consumer->custom['username_prefix'];
    }

    $consumer_key = $tool_provider->consumer->getKey();
    $resource_link_id = $tool_provider->resource_link->getId();
    $supportsOutcomes = $tool_provider->resource_link->hasOutcomesService();
    $username = $prefix . $tool_provider->user->getId();
// remove invalid characters in username
    $username = strtr($username, INVALID_USERNAME_CHARS, str_repeat('-', strlen(INVALID_USERNAME_CHARS)));
    $username = substr($username, 0, MAX_NAME_LENGTH);
    $firstname = substr($tool_provider->user->firstname, 0, MAX_NAME_LENGTH);
    $lastname = substr($tool_provider->user->lastname, 0, MAX_NAME_LENGTH);
    $email = substr($tool_provider->user->email, 0, MAX_EMAIL_LENGTH);
    $isStudent = $tool_provider->user->isLearner();
    $result_id = $tool_provider->user->lti_result_sourcedid;

    $assessment_id = $tool_provider->resource_link->getSetting(ASSESSMENT_SETTING);
    $coaching_report = $tool_provider->resource_link->getSetting(COACHING_REPORT);

    $ok = ($resource_link_id && $username && ($tool_provider->user->isLearner() || $tool_provider->user->isStaff()));

    if ($ok) {
// initialise session
      session_unset();
      $_SESSION['username'] = $username;
      $_SESSION['firstname'] = $firstname;
      $_SESSION['lastname'] = $lastname;
      $_SESSION['email'] = $email;
      $_SESSION['isStudent'] = $isStudent;
      $_SESSION['consumer_key'] = $consumer_key;
      $_SESSION['resource_link_id'] = $resource_link_id;
      $_SESSION['assessment_id'] = $assessment_id;
      $_SESSION['lti_return_url'] = $tool_provider->return_url;
      $_SESSION['result_id'] = $result_id;
      $_SESSION['allow_outcome'] = $supportsOutcomes;
      $_SESSION['coaching_report'] = $coaching_report;
      if (defined('QMWISE_URL')) {
        $_SESSION['qmwise_url'] = QMWISE_URL;
        $_SESSION['qmwise_client_id'] = SECURITY_CLIENT_ID;
        $_SESSION['qmwise_checksum'] = SECURITY_CHECKSUM;
      } else {
        $customer = loadCustomer($db, $tool_provider->consumer->custom['customer_id']);
        $_SESSION['qmwise_url'] = getQMWISeUrl($customer['customer_id']);
        $_SESSION['qmwise_client_id'] = $customer['qmwise_client_id'];
        $_SESSION['qmwise_checksum'] = $customer['qmwise_checksum'];
      }

      // set redirect URL
      if ($isStudent) {
        $page = 'student_nav';
      } else {
        $page = 'staff';
      }
      $ok = get_root_url() . "{$page}.php";
    } else {
      $tool_provider->reason = 'Missing data';
    }

    return $ok;

  }

?>
