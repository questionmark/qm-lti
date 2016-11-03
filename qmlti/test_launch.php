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
 *    1.0.01   2-May-12  Initial prototype
 *    1.2.00  23-Jul-12
 *    2.0.00  18-Feb-13
*/

require_once('lib.php');
require_once('lti/OAuth.php');

  session_name(SESSION_NAME . '-TEST');
  session_start();

  $url = $_SESSION['url'];
  $params = array();
  $params['lti_message_type'] = 'basic-lti-launch-request';
  $params['lti_version'] = 'LTI-1p0';
  if (!empty($_SESSION['outcome'])) {
    $params['lis_outcome_service_url'] = get_root_url() . 'test_outcome.php';
  }
  if (!empty($_SESSION['outcomes'])) {
    $params['ext_ims_lis_basic_outcome_url'] = get_root_url() . 'test_outcome.php';
  }

  $params['launch_presentation_return_url'] = get_root_url() . 'test_harness.php';
  $params['resource_link_id'] = $_SESSION['rid'];
  $params['context_id'] = $_SESSION['cid'];
  $params['user_id'] = $_SESSION['uid'];
  $params['roles'] = '';
  if (is_array($_SESSION['roles'])) {
    foreach ($_SESSION['roles'] as $role) {
      $params['roles'] .= ',' . $LTI_ROLES[$role];
    }
    $params['roles'] = substr($params['roles'], 1);
  }
  $params['lis_person_name_full'] = $_SESSION['name'];
  $params['lis_person_name_given'] = $_SESSION['fname'];
  $params['lis_person_name_family'] = $_SESSION['lname'];
  $params['lis_person_contact_email_primary'] = $_SESSION['email'];
  $params['lis_result_sourcedid'] = $_SESSION['result'];

  if (!empty($_SESSION['debug'])) {
    $params['custom_debug'] = 'true';
  }

  $params = signRequest($url, $params);

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>QMP: LTI</title>
<script language="javascript" type="text/javascript">
function doOnLoad() {
  document.forms[0].submit();
}
window.onload=doOnLoad;
</script>
</head>
<body>
<div class="col-md-12">
<p>Redirecting, please wait...</p>
<?php
  echo "<form name=\"frmConnect\" action=\"{$url}\" method=\"post\">\n";
  foreach ($params as $name => $value) {
    echo "  <input type=\"hidden\" name=\"{$name}\" value=\"{$value}\">\n";
  }
?>
</div>
</form>
</body>
</html>
