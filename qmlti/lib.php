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
 *    2.0.00  18-Feb-13  Updated to support multiple LMS configuration
*/

require_once('config.php');
require_once('lti/LTI_Tool_Provider.php');

// Ensure timezone is set (default to UTC)
  $cfg_timezone = date_default_timezone_get();
  date_default_timezone_set($cfg_timezone);

// Set secure cookie mode
  $secure = NULL;
  if (SECURE_COOKIE_ONLY) {
    $secure = TRUE;
  }
  session_set_cookie_params(0, NULL, NULL, $secure, TRUE);

  define('SESSION_NAME', 'QMP-LTI');  // name of session cookie
  define('INVALID_USERNAME_CHARS', '\'"&\\/£,:><');  // characters not allowed in QM usernames
  define('MAX_NAME_LENGTH', 50);  // maximum length of a username in QM
  define('MAX_EMAIL_LENGTH', 255);  // maximum length of a email address in QM
  define('ASSESSMENT_SETTING', 'qmp_assessment_id');
  define('COACHING_REPORT', 'qmp_coaching_reports');
// LTI roles supported
  $LTI_ROLES = array('a' => 'Administrator',
                     'd' => 'ContentDeveloper',
                     'i' => 'Instructor',
                     't' => 'TeachingAssistant',
                     'l' => 'Learner',
                     'm' => 'Mentor');
  define('DATA_CONNECTOR', 'QMP');  // suffix for LTI_Tool_Provider data connector
  define('PIP_FILE', 'lti.pip');  // name of PIP file on QM server used to handle LTI connections and grade return
  define('MIN_EU_CUSTOMER_ID', 600000);  // minimum value for customer IDs associated with the EU-based QM OnDemand server


/*
 * Open the database
 *
 *   returns a PDO instance for a database connection or FALSE if an error occurred
 */
  function open_db() {

    $db = FALSE;

    if (defined('DB_SERVER')) {
      $db_server = DB_SERVER;
    } else {
      $db_server = getenv('DB_SERVER');
    }
    if (defined('DB_NAME')) {
      $db_name = DB_NAME;
    } else {
      $db_name = getenv('DB_NAME');
    }
    if (defined('DB_USERNAME')) {
      $db_username = DB_USERNAME;
    } else {
      $db_username = getenv('DB_USERNAME');
    }
    if (defined('DB_PASSWORD')) {
      $db_password = DB_PASSWORD;
    } else {
      $db_password = getenv('DB_PASSWORD');
    }

    if (!empty($db_server)) {
      $db_name = "sqlsrv:server={$db_server};Database={$db_name}";
    }

    try {

      $db = new PDO($db_name, $db_username, $db_password, array(PDO::MYSQL_ATTR_FOUND_ROWS => true));

    } catch(PDOException $e) {
      log_error($e);
      $_SESSION['error'] = 'Unable to connect to database';
      $db = FALSE;
    }

    return $db;

  }


/*
 * Check if a named tables exists in the database
 *
 *   returns TRUE if the table exists, FALSE otherwise
 */
  function sqlsrv_table_exists($db, $name) {

    $sql = <<< EOD
SELECT COUNT(*)
FROM sys.objects
WHERE object_id = OBJECT_ID(N'[dbo].[{$name}]') AND type in (N'U')
EOD;

    $n = $db->exec($sql);

    return $n != 0;

  }


// Create each of the required database tables if they do not already exist

  function init_db($db) {

    $ok = TRUE;

    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) == 'sqlsrv') {
      $customer_table_name = TABLE_PREFIX . 'lti_customer';
      if (!defined('CONSUMER_KEY') && !sqlsrv_table_exists($db, $customer_table_name)) {

        $sql = <<< EOD
CREATE TABLE [dbo].[{$customer_table_name}] (
  [customer_id] VARCHAR(25) NOT NULL,
  [qmwise_client_id] VARCHAR(20) NOT NULL,
  [qmwise_checksum] CHAR(32) NOT NULL,
 CONSTRAINT [PK_{$customer_table_name}] PRIMARY KEY CLUSTERED ([customer_id] ASC)
)
EOD;

// TODO: sqlsrv_table_exists is not reliable so ignore result
//        $ok = $db->exec($sql) !== FALSE;
        $db->exec($sql);

      }

      $consumer_table_name = TABLE_PREFIX . LTI_Data_Connector::CONSUMER_TABLE_NAME;
      if ($ok && !defined('CONSUMER_KEY') && !sqlsrv_table_exists($db, $consumer_table_name)) {

        $sql = <<< EOD
CREATE TABLE [dbo].[{$consumer_table_name}] (
  [consumer_key] VARCHAR(50) NOT NULL,
  [secret] VARCHAR(50) NOT NULL,
  [consumer_name] VARCHAR(20) NOT NULL,
  [customer_id] VARCHAR(25) NOT NULL,
  [username_prefix] VARCHAR(10) NULL,
  [last_access] DATE NULL,
  [created] DATETIME NOT NULL,
  [updated] DATETIME NOT NULL,
 CONSTRAINT [PK_{$consumer_table_name}] PRIMARY KEY CLUSTERED ([consumer_key] ASC)
)
EOD;

//        $ok = $db->exec($sql) !== FALSE;
        $db->exec($sql);

      }

      $resource_link_table_name = TABLE_PREFIX . LTI_Data_Connector::RESOURCE_LINK_TABLE_NAME;
      if ($ok && !sqlsrv_table_exists($db, $resource_link_table_name)) {

        $sql = <<< EOD
CREATE TABLE [dbo].[{$resource_link_table_name}] (
  [consumer_key] VARCHAR(50) NOT NULL DEFAULT '',
  [context_id] VARCHAR(100) NOT NULL,
  [settings] TEXT,
  [created] DATETIME,
  [updated] DATETIME,
 CONSTRAINT [PK_{$resource_link_table_name}] PRIMARY KEY CLUSTERED ([consumer_key] ASC, [context_id] ASC)
)
EOD;

//        $ok = $db->exec($sql) !== FALSE;
        $db->exec($sql);

      }

      $nonce_table_name = TABLE_PREFIX . LTI_Data_Connector::NONCE_TABLE_NAME;
      if ($ok && !sqlsrv_table_exists($db, $nonce_table_name)) {

        $sql = <<< EOD
CREATE TABLE [dbo].[{$nonce_table_name}] (
  [consumer_key] VARCHAR(50) NOT NULL DEFAULT '',
  [value] VARCHAR(32) NOT NULL,
  [expires] DATETIME NOT NULL,
 CONSTRAINT [PK_{$nonce_table_name}] PRIMARY KEY CLUSTERED ([consumer_key] ASC, [value] ASC)
)
EOD;

//        $ok = $db->exec($sql) !== FALSE;
        $db->exec($sql);

      }

      $outcome_table_name = TABLE_PREFIX . 'lti_outcome';
      if ($ok && !sqlsrv_table_exists($db, $outcome_table_name)) {

        $sql = <<< EOD
CREATE TABLE [dbo].[{$outcome_table_name}] (
  [result_sourcedid] VARCHAR(255),
  [score] VARCHAR(255),
  [created] DATETIME,
  [report_url] VARCHAR(255),
 CONSTRAINT [PK_{$outcome_table_name}] PRIMARY KEY CLUSTERED ([result_sourcedid] ASC, [score] ASC, [created] ASC)
)
EOD;

//        $ok = $db->exec($sql) !== FALSE;
        $db->exec($sql);

      }

      $reports_table_name = TABLE_PREFIX . 'lti_coachingreports';
      if ($ok && !sqlsrv_table_exists($db, $reports_table_name)) {

        $sql = <<< EOD
CREATE TABLE [dbo].[{$reports_table_name}] (
  [context_id] VARCHAR(255),
  [assessment_id] VARCHAR(255),
  [is_accessible] BIT,
 CONSTRAINT [PK_{$reports_table_name}] PRIMARY KEY CLUSTERED ([context_id] ASC, [assessment_id] ASC, [is_accessible] ASC)
)
EOD;

//        $ok = $db->exec($sql) !== FALSE;
        $db->exec($sql);

      }

    } else if (($db->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql') || ($db->getAttribute(PDO::ATTR_DRIVER_NAME) == 'sqlite')) {

      if (!defined('CONSUMER_KEY')) {
        $sql = 'CREATE TABLE IF NOT EXISTS ' . TABLE_PREFIX . 'lti_customer ' .
               '(customer_id VARCHAR(100),' .
               ' qmwise_client_id VARCHAR(32),' .
               ' qmwise_checksum CHAR(32), ' .
               'PRIMARY KEY (customer_id))';
        $ok = $db->exec($sql) !== FALSE;
      }

      if ($ok && !defined('CONSUMER_KEY')) {
        $sql = 'CREATE TABLE IF NOT EXISTS ' . TABLE_PREFIX . LTI_Data_Connector::CONSUMER_TABLE_NAME . ' ' .
               '(consumer_key VARCHAR(50) NOT NULL,' .
               ' secret VARCHAR(50) NOT NULL,' .
               ' consumer_name VARCHAR(20) NOT NULL,' .
               ' customer_id VARCHAR(25) NOT NULL,' .
               ' username_prefix VARCHAR(10) NULL,' .
               ' last_access DATETIME NULL,' .
               ' created DATETIME NOT NULL,' .
               ' updated DATETIME NOT NULL,' .
               'PRIMARY KEY (consumer_key))';
        $ok = $db->exec($sql) !== FALSE;
      }

      if ($ok) {
        $sql = 'CREATE TABLE IF NOT EXISTS ' . TABLE_PREFIX . LTI_Data_Connector::RESOURCE_LINK_TABLE_NAME . ' ' .
               '(consumer_key VARCHAR(50) NOT NULL DEFAULT \'\',' .
               ' context_id VARCHAR(100),' .
               ' settings TEXT,' .
               ' created DATETIME,' .
               ' updated DATETIME, ' .
               'PRIMARY KEY (consumer_key, context_id))';
        $ok = $db->exec($sql) !== FALSE;
      }

      if ($ok) {
        $sql = 'CREATE TABLE IF NOT EXISTS ' . TABLE_PREFIX . LTI_Data_Connector::NONCE_TABLE_NAME . ' ' .
               '(consumer_key VARCHAR(50) NOT NULL DEFAULT \'\',' .
               ' value VARCHAR(32) NOT NULL,' .
               ' expires DATETIME NOT NULL, ' .
               'PRIMARY KEY (consumer_key, value))';
        $ok = $db->exec($sql) !== FALSE;
      }

      if ($ok) {
        $sql = 'CREATE TABLE IF NOT EXISTS ' . TABLE_PREFIX . 'lti_outcome ' .
               '(result_sourcedid VARCHAR(255),' .
               ' score VARCHAR(255),' .
               ' report_url VARCHAR(255),' .
               ' created DATETIME)';
        $ok = $db->exec($sql) !== FALSE;
      }

      if ($ok) {
        $sql = 'CREATE TABLE IF NOT EXISTS ' . TABLE_PREFIX . 'lti_coachingreports ' .
               '(context_id VARCHAR(255),' .
               ' assessment_id VARCHAR(255),' .
               ' is_accessible TINYINT)';
        $ok = $db->exec($sql) !== FALSE;
      }

    } else {

      $ok = FALSE;

    }

    return $ok;

  }


/*
 * For reference - remove LTI tables from database
 *
  function reset_db() {

    $db = new PDO(DB_NAME, DB_USERNAME, DB_PASSWORD);

    $res = $db->exec('DROP TABLE ' . TABLE_PREFIX . 'lti_outcome');
    $res = $db->exec('DROP TABLE ' . TABLE_PREFIX . LTI_Data_Connector::NONCE_TABLE_NAME);
    $res = $db->exec('DROP TABLE ' . TABLE_PREFIX . LTI_Data_Connector::RESOURCE_LINK_TABLE_NAME);
    if (!defined('CONSUMER_KEY')) {
      $res = $db->exec('DROP TABLE ' . TABLE_PREFIX . LTI_Data_Connector::CONSUMER_TABLE_NAME);
      $res = $db->exec('DROP TABLE ' . TABLE_PREFIX . 'lti_customer');
    }

  }
*/


/*
 * Get ID of SOAP connection
 */
 function perception_soapconnect_id() {

   return $_SESSION['qmwise_url'] . $_SESSION['qmwise_client_id'] . $_SESSION['qmwise_checksum'] . DEBUG_MODE;

 }


/*
 * Connect to the Perception server
 */
  function perception_soapconnect() {

    require_once 'PerceptionSoap.php';

    $ok = TRUE;

    $soap_connection_id = perception_soapconnect_id();
    if (!isset($GLOBALS['perceptionsoap']) ||
        !isset($GLOBALS['perceptionsoap'][$soap_connection_id])) {
      try {
        $GLOBALS['perceptionsoap'][$soap_connection_id] = new PerceptionSoap($_SESSION['qmwise_url'], array(
          'security_client_id' => $_SESSION['qmwise_client_id'],
          'security_checksum'  => $_SESSION['qmwise_checksum'],
          'debug'              => DEBUG_MODE
        ));
      } catch(Exception $e) {
        log_error($e);
        $ok = FALSE;
      }
    }

    return $ok;

  }


/*
 * SOAP call to get details for an administrator account
 *
 *   returns the details object or FALSE
 */
  function get_administrator_by_name($username) {

    $admin_details = FALSE;
    try {
      $soap_connection_id = perception_soapconnect_id();
      $admin_details = $GLOBALS['perceptionsoap'][$soap_connection_id]->get_administrator_by_name($username);
    } catch (Exception $e) {
    }

    return $admin_details;

  }


/*
 * SOAP call to get details for an administrator account
 *
 *   returns the user ID or FALSE
 */
  function create_administrator_with_password($username, $firstname, $lastname, $email, $profile) {

    $admin_id = FALSE;
    try {
      $soap_connection_id = perception_soapconnect_id();
      $admin_details = $GLOBALS['perceptionsoap'][$soap_connection_id]->create_administrator_with_password($username, $firstname, $lastname, $email, $profile);
      $admin_id = $admin_details->Administrator_ID;
    } catch (Exception $e) {
      log_error($e);
    }

    return $admin_id;

  }


/*
 * SOAP call to get a direct login URL for an administrator account
 *
 *   returns the URL or FALSE
 */
  function get_access_administrator($username) {

    $url = FALSE;
    try {
      $soap_connection_id = perception_soapconnect_id();
      $access = $GLOBALS['perceptionsoap'][$soap_connection_id]->get_access_administrator($username);
      $url = $access->URL;
    } catch (Exception $e) {
      log_error($e);
    }

    return $url;

  }


/*
 * SOAP call to get a list of assessments availalble to an administrator account
 *
 *   returns the array of assessment objects or FALSE
 */
  function get_assessment_list_by_administrator($id) {

    try {
      $soap_connection_id = perception_soapconnect_id();
      $assessments = $GLOBALS['perceptionsoap'][$soap_connection_id]->get_assessment_list_by_administrator($id, 0, 1);
    } catch (Exception $e) {
      log_error($e);
      $assessments = FALSE;
    }

    return $assessments;

  }

/*
 * External call to grab coaching report url if allowed
 *
 * returns the coaching report url
 */
 function get_coaching_report($db, $lti_outcome, $resource_link_id, $assessment_id) {
   $data_connector = LTI_Data_Connector::getDataConnector(TABLE_PREFIX, $db, DATA_CONNECTOR);
   if ($data_connector->ReportConfig_loadAccessible($resource_link_id, $assessment_id) == 1) {
      return get_report_url($lti_outcome->getResultID());
   } else {
      return FALSE;
   }
 }


/*
 * SOAP call to get coaching report URL given a result ID
 *
 *   returns the coaching report url or FALSE
 */
  function get_report_url($report_id) {

    try {
      $soap_connection_id = perception_soapconnect_id();
      $report_url = $GLOBALS['perceptionsoap'][$soap_connection_id]->get_report_url($report_id);
    } catch (Exception $e) {
      log_error($e);
      $report_url = FALSE;
    }

    return $report_url;

  }

/*
 * SOAP call to get all result IDs from an assessment for all participants
 * 
 *   returns an array of resultIDs
 */
  function get_assessment_result_list_by_assessment($assessment_id) {
    try {
      $soap_connection_id = perception_soapconnect_id();
      $assessment_results = $GLOBALS['perceptionsoap'][$soap_connection_id]->get_assessment_result_list_by_assessment($assessment_id);
    } catch (Exception $e) {
      log_error($e);
      $assessment_results = FALSE;
    }

    return $assessment_results;
  }


/*
 * SOAP call to get a direct URL to an assessment for a participant which includes the notify option
 *
 *   returns the URL or FALSE
 */
  function get_access_assessment_notify($assessment_id, $participant_name, $consumer_key, $resource_link_id, $result_id, $notify_url, $home_url, $coaching_report) {

    try {
      $soap_connection_id = perception_soapconnect_id();
      $access = $GLOBALS['perceptionsoap'][$soap_connection_id]->get_access_assessment_notify($assessment_id, $participant_name, $consumer_key, $resource_link_id, $result_id, $notify_url, $home_url, $coaching_report);
      $url = $access->URL;
    } catch (Exception $e) {
      log_error($e);
      $url = FALSE;
    }

    error_log( print_r( $url, true));
    return $url;

  }


/*
 * SOAP call to get details for a participant account
 *
 *   returns the details object or FALSE
 */
  function get_participant_by_name($username) {

    $participant_details = FALSE;
    try {
      $soap_connection_id = perception_soapconnect_id();
      $participant_details = $GLOBALS['perceptionsoap'][$soap_connection_id]->get_participant_by_name($username);
    } catch (Exception $e) {
      log_error($e);
    }

    return $participant_details;

  }


/*
 * SOAP call to create a participant account
 *
 *   returns the participant IS or FALSE
 */
  function create_participant($username, $firstname, $lastname, $email) {

    $participant_id = FALSE;
    try {
      $soap_connection_id = perception_soapconnect_id();
      $participant_details = $GLOBALS['perceptionsoap'][$soap_connection_id]->create_participant($username, $firstname, $lastname, $email);
      $participant_id = $participant_details->Participant_ID;
    } catch (Exception $e) {
      log_error($e);
    }

    return $participant_id;

  }


/*
 * Record details of an error to the default log file with a copy added to the user session
 */
  function log_error($e) {

    $error = "Error {$e->getCode()}: {$e->getMessage()}";
    error_log($error);
    $_SESSION['error'] = $error;

  }


/*
 * Ouput the page header with an optional Javascript section; the logo is omitted if the output is to a frame and a link
 * to return to the LMS is included if a return URL is available and the output is not to a frame
 */
  function page_header($script='', $isFrame=FALSE) {

    header('Cache-control: no-cache');
    header('Pragma: no-cache');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');

    $html = <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta charset="utf-8" />
<title>QMP: LTI</title>
<link href="css/qmp-lti.css" type="text/css" rel="stylesheet" />
{$script}
</head>
<body>
<div id="Wrapper">

EOD;

    if (!$isFrame) {
      $html .= <<<EOD
  <div id="HeaderWrapper">
    <img id="logoImage" src="images/logo.gif" alt="Questionmark" style="width: 175px; height: 32px; margin-left: 10px" />
  </div>

EOD;

    }

    $html .= <<<EOD
  <div id="MainContentWrapper">
    <div id="ContentWrapper">
      <div id="PageContent">
EOD;

    if (!$isFrame && isset($_SESSION['lti_return_url']) && (strlen($_SESSION['lti_return_url']) > 0)) {
      $html .= '        <p><button type="button" onclick="location.href=\'' . $_SESSION['lti_return_url'] . '\';">Return to course environment</button></p>' . "\n";
    }

    echo $html;

  }


/*
 * Ouput the page footer
 */
  function page_footer($isFrame=FALSE) {

    $html = <<<EOD
      </div>
    </div>
  </div>

EOD;

    if (!$isFrame) {
      $html .= <<<EOD
  <div id="FooterWrapper">
    <span id="Copyright">
      <a id="lnkCopyright" href="http://www.questionmark.com" target="_blank">Copyright &copy;2016 Questionmark Computing Ltd.</a>
    </span>
  </div>
</div>
</body>
</html>
EOD;
    }

    echo $html;

  }


/*
 * Get the URL (protocol, domain and path) to the root of the connector
 *
 *   returns the URL
 */
  function get_root_url() {
    if (!defined('WEB_PATH') || (strlen(WEB_PATH) <= 0)) {
      $root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
      $file = str_replace('\\', '/', dirname(__FILE__));
      $path = str_replace($root, '', $file);
    } else {
      $path = WEB_PATH;
    }
    $scheme = (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on")
              ? 'http'
              : 'https';
    $url = $scheme . '://' . $_SERVER['HTTP_HOST'] . $path . '/';

    return $url;

  }


/*
 * Set a named value from post data in the user session; using a default value if the parameter does not exist
 */
  function set_session($name, $value = '') {

    if (isset($_POST[$name])) {
      $value = $_POST[$name];
    }

    $_SESSION[$name] = $value;

  }


/*
 * Initialise a named value in the user session if it does not already exist
 */
  function init_session($name, $value) {

    if (!isset($_SESSION[$name])) {
      $_SESSION[$name] = $value;
    }

  }


/*
 * Initialise dummy data in the user session
 */
  function init_data() {

    init_session('url', get_root_url() . 'launch.php');
    if (defined('CONSUMER_KEY')) {
      init_session('key', CONSUMER_KEY);
    } else {
      init_session('key', '');
    }
    if (defined('CONSUMER_SECRET')) {
      init_session('secret', CONSUMER_SECRET);
    } else {
      init_session('secret', '');
    }
    init_session('cid', '');
    init_session('rid', 'linkABC');
    init_session('uid', 'jt001');
    init_session('name', 'Jane Teacher');
    init_session('fname', 'Jane');
    init_session('lname', 'Teacher');
    init_session('email', 'jt@inst.edu');
    init_session('result', 'WLdfkdkjl213ljsOOS');
    init_session('roles', array('i'));
    init_session('outcome', '1');
    init_session('outcomes', '1');

  }


/*
 * Add an OAuth signature to the parameters being passed
 *
 *   returns the updated array of parameters
 */
  function signRequest($url, $params) {

// Check for query parameters which need to be included in the signature
    $query_params = array();
    $query_string = parse_url($url, PHP_URL_QUERY);
    if (!is_null($query_string)) {
      $query_items = explode('&', $query_string);
      foreach ($query_items as $item) {
        if (strpos($item, '=') !== FALSE) {
          list($name, $value) = explode('=', $item);
          $query_params[$name] = $value;
        } else {
          $query_params[$name] = '';
        }
      }
    }
    $params = $params + $query_params;
    $params['oauth_callback'] = 'about:blank';
    $params['oauth_consumer_key'] = $_SESSION['key'];
// Add OAuth signature
    $hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
    $consumer = new OAuthConsumer($_SESSION['key'], $_SESSION['secret'], NULL);
    $req = OAuthRequest::from_consumer_and_token($consumer, NULL, 'POST', $url, $params);
    $req->sign_request($hmac_method, $consumer, NULL);
    $params = $req->get_parameters();
// Remove parameters being passed on the query string
    foreach (array_keys($query_params) as $name) {
      unset($params[$name]);
    }

    return $params;

  }


/**
 * Generate a random string; the generated string will only comprise letters (upper- and lower-case) and digits
 *
 *   returns the generated string
 */
  function getRandomString($length = 8) {

    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

    $value = '';
    $charsLength = strlen($chars) - 1;

    for ($i = 1 ; $i <= $length; $i++) {
      $value .= $chars[rand(0, $charsLength)];
    }

    return $value;

  }


/**
 * Generate a GUID
 *
 *   returns the generated GUID
 */
  function create_guid() {

    if (function_exists('com_create_guid')) {
      return com_create_guid();
    } else {
      $md5 = strtoupper(md5(uniqid(rand(), true)));
      $guid = '{' . substr($md5, 0, 8) . '-' . substr($md5, 8, 4) . '-' . substr($md5, 12, 4) . '-' . substr($md5, 16, 4) . '-' . substr($md5, 20, 12) . '}';
      return $guid;
    }

  }


/**
 * Extract the customer ID from a QMWISe URL or return the value unchanged if it is not a URL
 *
 *   returns the customer ID or an empty string
 */
  function getCustomerId($value) {

    if ((substr($value, 0, 7) == 'http://') || (substr($value, 0, 8) == 'https://')) {
      if (substr($value, - 12) == '/qmwise.asmx') {
        $value = substr($value, 0, - 12);
        $pos = strrpos($value, '/');
        $value = substr($value, $pos + 1);
      } else {
        $value = '';
      }
    }

    return $value;

  }


/**
 * Get the QMWISe URL for a customer ID
 *
 *   returns the URL
 */
  function getQMWISeUrl($customer_id) {

    $url = "https://ondemand.questionmark.com/qmwise/{$customer_id}/qmwise.asmx";
// Check for EU customer IDs
    $id_string = $customer_id;
    while ((strlen($id_string) > 0) && (substr($id_string, 0, 1) == '0')) {  // remove any leading zeroes
      $id_string = substr($id_string, 1);
    }
    $id = intval($id_string);
    if ($id != 0) {
      if ($id >= MIN_EU_CUSTOMER_ID) {
        $url = "https://ondemand.questionmark.eu/qmwise/{$customer_id}/qmwise.asmx";
      }
    }

    return $url;

  }


/**
 * Check that the details for a customer are valid
 *
 *   returns TRUE if the details are valid, otherwise FALSE
 */
  function checkCustomer($customer) {

    require_once 'PerceptionSoap.php';
    $ok = FALSE;
    $customer_id = $customer['customer_id'];
    if (preg_match('/^[a-z0-9]+$/', $customer_id) === 1) {
      $url = getQMWISeUrl($customer_id);
      try {
        $soap = new PerceptionSoap($url, array(
          'security_client_id' => $customer['qmwise_client_id'],
          'security_checksum'  => $customer['qmwise_checksum'],
          'debug'              => FALSE
        ));
        $ok = $soap->get_about();
      } catch(Exception $e) {
      }
    }

    return $ok;

  }


/**
 * Load a customer record from the database
 *
 *   returns the customer record or an empty array if the record does not exist
 */
  function loadCustomer($db, $customer_id) {

    $table_name = TABLE_PREFIX . 'lti_customer';
    $sql = <<< EOD
SELECT customer_id, qmwise_client_id, qmwise_checksum
FROM {$table_name}
WHERE customer_id = :customer_id
EOD;

    $query = $db->prepare($sql);
    $query->bindValue('customer_id', $customer_id, PDO::PARAM_STR);
    $query->execute();

    $row = $query->fetch(PDO::FETCH_ASSOC);
    if ($row === FALSE) {
      $row = array();
      $row['customer_id'] = '';
      $row['qmwise_client_id'] = '';
      $row['qmwise_checksum'] = '';
    }

    return $row;

  }


/**
 * Save a customer record to the database (updating any existing record)
 *
 *   returns TRUE if the record is saved, otherwise FALSE
 */
  function saveCustomer($db, $customer) {


    $table_name = TABLE_PREFIX . 'lti_customer';
    $sql = <<< EOD
UPDATE {$table_name}
SET qmwise_client_id = :qmwise_client_id, qmwise_checksum = :qmwise_checksum
WHERE customer_id = :customer_id
EOD;

    $updateQuery = $db->prepare($sql);
    $updateQuery->bindValue('qmwise_client_id', $customer['qmwise_client_id'], PDO::PARAM_STR);
    $updateQuery->bindValue('qmwise_checksum', $customer['qmwise_checksum'], PDO::PARAM_STR);
    $updateQuery->bindValue('customer_id', $customer['customer_id'], PDO::PARAM_STR);
    $ok = $updateQuery->execute();
    $ok = $ok && ($updateQuery->rowCount() > 0);

    if (!$ok) {
      $sql = <<< EOD
INSERT INTO {$table_name} (customer_id, qmwise_client_id, qmwise_checksum)
VALUES (:customer_id, :qmwise_client_id, :qmwise_checksum)
EOD;
      $insertQuery = $db->prepare($sql);
      $insertQuery->bindValue('customer_id', $customer['customer_id'], PDO::PARAM_STR);
      $insertQuery->bindValue('qmwise_client_id', $customer['qmwise_client_id'], PDO::PARAM_STR);
      $insertQuery->bindValue('qmwise_checksum', $customer['qmwise_checksum'], PDO::PARAM_STR);
      $ok = $insertQuery->execute();
    }

    return $ok;

  }


/**
 * Delete a customer record from the database
 *
 *   returns TRUE if the record is deleted, otherwise FALSE
 */
  function deleteCustomer($db, $customer) {

// Delete all consumers for this customer
    $consumers = loadConsumers($db, $customer['customer_id']);
    foreach ($consumers as $key => $consumer) {
      $consumer->delete();
    }

// Delete the customer
    $table_name = TABLE_PREFIX . 'lti_customer';
    $sql = <<< EOD
DELETE {$table_name}
WHERE customer_id = :customer_id
EOD;

    $deleteQuery = $db->prepare($sql);
    $deleteQuery->bindValue('customer_id', $customer['customer_id'], PDO::PARAM_STR);
    $ok = $deleteQuery->execute();
    $ok = $ok && ($deleteQuery->rowCount() > 0);

    return $ok;

  }


/**
 * Get an array of tool consumer records indexed by the consumer key
 *
 *   returns associative array of consumers
 */
  function loadConsumers($db, $customer_id) {

    $consumers = array();

    $data_connector = LTI_Data_Connector::getDataConnector(TABLE_PREFIX, $db, DATA_CONNECTOR);
    $tool = new LTI_Tool_Provider(NULL, $data_connector);
    $all_consumers = $tool->getConsumers();
    foreach ($all_consumers as $consumer) {
      if ($consumer->custom['customer_id'] == $customer_id) {
        $consumers[$consumer->getKey()] = $consumer;
      }
    }

    return $consumers;

  }


/**
 * Get a tool consumer record, set custom fields for the customer ID and username prefix settings
 *
 *   returns consumer object
 */
  function loadConsumer($db, $customer_id, $consumer_key) {

    $data_connector = LTI_Data_Connector::getDataConnector(TABLE_PREFIX, $db, DATA_CONNECTOR);
    $consumer = new LTI_Tool_Consumer($consumer_key, $data_connector);
    if (!isset($consumer->custom['customer_id'])) {
      $consumer->custom['customer_id'] = $customer_id;
      $consumer->custom['username_prefix'] = '';
    } else if ($consumer->custom['customer_id'] != $customer_id) {
      $consumer->initialise();
    }

    return $consumer;

  }

?>