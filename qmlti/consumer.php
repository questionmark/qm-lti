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
 *    2.0.00  18-Feb-13  Added to release
*/

require_once('lib.php');

  session_name(SESSION_NAME);
  session_start();

// initialise database
  $db = open_db();
  if ($db === FALSE) {
    $_SESSION['frame'] = TRUE;
    header('Location: error.php');
    exit;
  } else if (!isset($_SESSION['customer_id'])) {
//    $_SESSION['frame'] = TRUE;
    $_SESSION['error'] = 'Your session has expired.';
    header('Location: error.php');
    exit;
  }

  if (isset($_GET['consumer_key'])) {
    $_SESSION['consumer_key'] = $_GET['consumer_key'];
  }
  if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
    $action = $_POST['action'];
    $consumer = loadConsumer($db, $_SESSION['customer_id'], $_SESSION['consumer_key']);
    $consumer->secret = $_POST['secret'];
    $consumer->consumer_name = $_POST['consumer_name'];
    $consumer->custom['username_prefix'] = $_POST['username_prefix'];
    if ($action == 'Cancel') {
      header('Location: index.php');
      exit;
    } else if ($action == 'Delete Profile') {
      if ($consumer->delete()) {
        $message = '*** SUCCESS *** Consumer profile has been deleted.';
        unset($_SESSION['consumer_key']);
        $consumer->initialise();
      } else {
        $message = '*** ERROR *** Unable to delete Consumer profile, please try again.';
      }
    } else if ($action == 'Apply') {
      $ok = $consumer->save();
      if (!$ok) {
        $message = '*** ERROR *** Unable to save details, please check data and try again.';
      }
    } else {
      $message = '*** ERROR *** Request not recognised.';
    }
  } else if (isset($_SESSION['consumer_key'])) {
    $consumer = loadConsumer($db, $_SESSION['customer_id'], $_SESSION['consumer_key']);
  } else {
    $consumer = loadConsumer($db, $_SESSION['customer_id'], NULL);
  }
  $consumers = loadConsumers($db, $_SESSION['customer_id']);
  if (isset($_SESSION['consumer_key']) && !isset($consumers[$_SESSION['consumer_key']])) {
    unset($_SESSION['consumer_key']);
  }
  if (!isset($_SESSION['consumer_key'])) {
    $_SESSION['consumer_key'] = create_guid();
    $_SESSION['secret'] = getRandomString(32);
  }
  if (!isset($consumer->secret)) {
    $consumer->secret = $_SESSION['secret'];
  }

  $script = <<< EOD
<script type="text/javascript">
<!--
var unsaved_changes = false;
var is_cancelling = false;

function doChange(el) {
  var el2 = document.getElementById(el.id + '_img');
  if (el2) {
    el2.className = 'show';
  }
  unsaved_changes = true;
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

function doLoadConsumer(el) {
  location.href = encodeURI('consumer.php?consumer_key=' + el.options[el.selectedIndex].value);
}

function confirmDelete() {
  return confirm('Are you sure you want to delete this profile?');
}

function confirmCancel() {
  var ok = true;
  if (unsaved_changes) {
    ok = confirm('Your unsaved changes will be lost - are you sure?');
  }
  is_cancelling = ok;
  return ok;
}

function checkForm() {
  var ok = true;
  if (!is_cancelling) {
    var el = document.getElementById('id_consumer_name');
    el.value = el.value.trim();
    var ok = el.value.length > 0;
    if (!ok) {
      alert('Please enter a name for the profile');
      el.focus();
    } else {
      el = document.getElementById('id_secret');
      el.value = el.value.trim();
      var ok = el.value.length > 0;
      if (!ok) {
        alert('Please enter a secret');
        el.focus();
      }
    }
  }
  return ok;
}
// -->
</script>
EOD;

  page_header($script, TRUE);

?>
        <img src="images/exchange.gif" style="float: left; width: 50px; height: 50px; margin-right: 10px" />
        <h1>LTI Connector App Settings</h1>

<?php
  if (isset($message)) {
    echo "        <p style=\"font-weight: bold; color: #f00; clear: left;\">\n{$message}\n</p>\n";
  } else {
    echo "        <p style=\"clear: left;\">&nbsp;</p>\n";
  }
?>
        <form action="consumer.php" method="POST" onsubmit="return checkForm();">

        <p>
          Configure the connection to your Learning Management System here:
        </p>
        <div class="row">
          <div class="col1">
            Select a profile:
          </div>
          <div class="col2">
            <select id="id_consumer" name="consumer" onchange="doLoadConsumer(this);">
<?php
  $hasSelected = FALSE;
  foreach ($consumers as $key => $aConsumer) {
    if ($key == $_SESSION['consumer_key']) {
      $selected = ' selected="selected"';
      $hasSelected = TRUE;
    } else {
      $selected = '';
    }
?>
              <option value="<?php echo htmlentities($aConsumer->getKey()); ?>"<?php echo $selected; ?>><?php echo htmlentities($aConsumer->consumer_name); ?></option>

<?php
  }
  if (!$hasSelected) {
    $selected = ' selected="selected"';
  } else {
    $selected = '';
  }
?>
              <option value=""<?php echo $selected; ?>>New Profile...</option>
            </select>
<?php
  if ($hasSelected) {
?>
            <input id="id_delete" type="submit" name="action" value="Delete Profile" onclick="return confirmDelete();" />
<?php
  }
?>
          </div>
        </div>
        <hr style="text-align: center; width: 80%;" />
        <div class="row">
          <div class="col1">
            <img src="images/exclamation.png" alt="Unsaved change" title="Unsaved change" class="hide" id="id_consumer_name_img" />&nbsp;
            Profile Name: *
          </div>
          <div>
            <img src="images/spacer.png" alt="" /><input type="text" id="id_consumer_name" name="consumer_name" value="<?php echo htmlentities($consumer->consumer_name); ?>" size="25" maxlength="20" onchange="doChange(this);" />
          </div>
        </div>
        <p class="col">
          &nbsp; The LTI App's launch URL is: <?php echo get_root_url() . 'launch.php'; ?>
        </p>
        <p class="col">
          &nbsp; The LTI consumer key for this LMS is: <?php echo $_SESSION['consumer_key']; ?>
        </p>
        <div class="row">
          <div class="col1">
            <img src="images/exclamation.png" alt="Unsaved change" title="Unsaved change" class="hide" id="id_secret_img" />&nbsp;
            LTI Consumer Secret: *
          </div>
          <div>
            <img src="images/spacer.png" alt="" /><input type="password" id="id_secret" name="secret" value="<?php echo htmlentities($consumer->secret); ?>" size="60" maxlength="50" onchange="doChange(this);" />&nbsp;
            <input type="checkbox" id="id_secret_show" onclick="toggleShow(this);" />&nbsp;Show&nbsp;secret
          </div>
        </div>
        <hr style="text-align: center; width: 80%;" />
        <p>
          Other options:
        </p>
        <div class="row">
          <div class="col1">
            <img src="images/exclamation.png" alt="Unsaved change" title="Unsaved change" class="hide" id="id_username_prefix_img" />&nbsp;
            Username Prefix:
          </div>
          <div>
            <img src="images/spacer.png" alt="" /><input type="text" id="id_username_prefix" name="username_prefix" value="<?php echo htmlentities($consumer->custom['username_prefix']); ?>" size="10" maxlength="10" onchange="doChange(this);" />
          </div>
        </div>
        <p class="col">
          &nbsp; Download the <a href="<?php echo get_root_url() . 'pip.php'; ?>">LTI PIP file</a> for your Perception server.
        </p>

        <p class="row">
          <input id="id_configure" type="submit" name="action" value="Cancel" onclick="return confirmCancel();" />
          <input id="id_configure" type="submit" name="action" value="Apply" />
        </p>

        </form>
<?php

  page_footer(TRUE);

?>