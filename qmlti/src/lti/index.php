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
 *    2.0.00  18-Feb-13  Added to release (old index.php renamed to launch.php with backwards compatible support)
*/

// Check if this is a launch request for backwards compatibility
if (isset($_POST['oauth_consumer_key'])) {
  require_once('launch.php');
  exit;
}

require_once('../resources/lib.php');

  session_name(SESSION_NAME);
  session_start();

// initialise database
  $db = open_db();
  $ok = ($db !== FALSE);
  if (!$ok) {
    $_SESSION['error'] = 'Unable to open database.';
  } else {
    $ok = init_db($db);
    if (!$ok) {
      $_SESSION['error'] = 'Unable to initialize database.';
    }
  }
  if ($ok && defined('CONSUMER_KEY')) {
    $_SESSION['error'] = 'Your configuration settings have been pre-defined.';
    $ok = FALSE;
  }
  if (!$ok) {
    $_SESSION['frame'] = TRUE;
    header('Location: error.php');
    exit;
  }

  $customer = array();

  if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
    $action = $_POST['action'];
    if (isset($_POST['debug'])) {
      $customer['customer_id'] = $_POST['customer_id'];
    } else {
      $customer['customer_id'] = getCustomerId($_POST['customer_id']);
    }
    $customer['qmwise_client_id'] = $_POST['qmwise_client_id'];
    $customer['qmwise_checksum'] = $_POST['qmwise_checksum'];
    $ok = checkCustomer($customer);
    if (!$ok) {
      $message = 'Unable to confirm QMWISe credentials, please check data and try again.';
      $alert = '<p class="alert alert-danger col-md-6">';
    } else if ($action == 'Delete') {
      if (deleteCustomer($db, $customer)) {
        $message = 'SUCCESS: Access to the LTI Connector App has been removed.';
        $alert = '<p class="alert alert-success col-md-6">';
        $customer = array();
        $customer['customer_id'] = '';
        $customer['qmwise_client_id'] = '';
        $customer['qmwise_checksum'] = '';
      } else {
        $message = 'Unable to remove customer, please check data and try again.';
        $alert = '<p class="alert alert-danger col-md-6">';
      }
    } else if ($action != 'Configure') {
      $message = 'Request not recognised.';
      $alert = '<p class="alert alert-danger col-md-6">';
    } else {
      $ok = saveCustomer($db, $customer);
      if (!$ok) {
        $message = 'Unable to save details, please check data and try again.';
        $alert = '<p class="alert alert-danger col-md-6">';
      } else {
        if (!isset($_SESSION['customer_id']) || ($_SESSION['customer_id'] != $customer['customer_id'])) {
          $_SESSION['customer_id'] = $customer['customer_id'];
          unset($_SESSION['lti_consumer_key']);
        }
        header('Location: consumer.php');
        exit;
      }
    }
  } else if (isset($_GET['customer_id'])) {
    $customer['customer_id'] = $_GET['customer_id'];
    unset($_SESSION['customer_id']);
    unset($_SESSION['lti_consumer_key']);
  } else if (isset($_SESSION['customer_id'])) {
    $customer = loadCustomer($db, $_SESSION['customer_id']);
  } else {
    $customer['customer_id'] = '';
    $customer['qmwise_client_id'] = '';
    $customer['qmwise_checksum'] = '';
  }

  $script = <<< EOD
<script type="text/javascript">
<!--
if (!String.prototype.trim) {
 String.prototype.trim = function() {
  return this.replace(/^\s+|\s+$/g,'');
 }
}

function toggleShow(el) {
  var el2 = document.getElementById(el.id.substring(0, el.id.length - 5));
  try {
    if (el2.type.toLowerCase() == 'password') {
      el2.type = 'text';
    } else {
      el2.type = 'password';
    }
  } catch (err) {
    var show_text = el2.getAttribute('type') == 'password';
    var new_input = document.createElement('input');
    with (new_input) {
      id        = el2.id;
      name      = el2.name;
      value     = el2.value;
      size      = el2.size;
      className = el2.className;
      type      = show_text ? 'text' : 'password';
    }
    el2.parentNode.replaceChild(new_input, el2);
  }
}

function confirmDelete() {
  return confirm('Are you sure you want to delete the LTI connector app for this repository?\\n\\nAll existing assessment links from Learning Management Systems configured with this connector will be removed.');
}

function checkForm() {
  var el = document.getElementById('id_customer_id');
  el.value = el.value.trim();
  var ok = el.value.length > 0;
  if (!ok) {
    alert('Please enter a Customer ID or URL');
    el.focus();
  } else {
    el = document.getElementById('id_qmwise_client_id');
    el.value = el.value.trim();
    var ok = el.value.length > 0;
    if (!ok) {
      alert('Please enter a QMWISe User Name');
      el.focus();
    }
  }
  if (ok) {
    el = document.getElementById('id_qmwise_checksum');
    el.value = el.value.trim();
    var ok = el.value.length > 0;
    if (!ok) {
      alert('Please enter a QMWISe Password');
      el.focus();
    } 
  }
  return ok;
}
// -->
</script>
EOD;

  page_header($script, TRUE);

?>  <div class="col-md-12">
      <div class="container-fluid">
        <br><br>
        <img src="../../web/images/exchange.gif" style="float: left; width: 50px; height: 50px; margin-right: 10px;" />
        <h1>LTI Connector App Settings</h1>

<?php
  if (isset($message)) {
    echo "        <div class=\"spacer-sm\"></div>{$alert}{$message}</p><div class=\"spacer-sm\"></div><div class=\"spacer-sm\"></div>\n";
  } else {
    echo "        <p style=\"clear: left;\">&nbsp;</p>\n";
  }
?>
        <form action="index.php" method="POST" onsubmit="return checkForm()">
        <div class="row">
          <div class="col1">
            Questionmark Customer ID (or URL): *
          </div>
          <div class="col2">
            <input type="text" class="form-control" id="id_customer_id" name="customer_id" value="<?php echo htmlentities($customer['customer_id']); ?>" size="75" />
          </div>
        </div>
        <div class="row">
          <div class="col1">
            QMWISe User Name: *
          </div>
          <div class="col2">
            <input type="text" class="form-control" id="id_qmwise_client_id" name="qmwise_client_id" value="<?php echo htmlentities($customer['qmwise_client_id']); ?>" size="20" maxlength="20" />
          </div>
        </div>
        <div class="row">
          <div class="col1">
            QMWISe Password: *
          </div>
          <div class="col2">
           <input type="password" class="form-control" id="id_qmwise_checksum" name="qmwise_checksum" value="<?php echo htmlentities($customer['qmwise_checksum']); ?>" size="50" maxlength="32" />
          </div>
        </div>
        <div class="row">
          <div class="col1">&nbsp;</div>
          <div class="col2">
            <input type="checkbox" id="id_qmwise_checksum_show" onclick="toggleShow(this);" /> Show checksum
          </div>
        </div>
        <div class="row">
          <div class="col1">
            Launch in debug mode
          </div>
          <div class="col2">
            <input type="checkbox" id="debug" name="debug" value="1" />
          </div>
        </div>
        <br><br>
        <p>
          <input id="id_delete" type="submit" class="btn btn-default" name="action" value="Delete" onclick="return confirmDelete();" />
          <input id="id_configure" type="submit" class="btn btn-default" name="action" value="Configure" />
        </p>

        </form>
      </div>
    </div>
<?php

  page_footer(TRUE);

?>