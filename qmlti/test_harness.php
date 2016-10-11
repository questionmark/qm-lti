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

  session_name(SESSION_NAME . '-TEST');
  session_start();

// Initialise database
  $db = open_db();
  if ($db === FALSE) {
    header('Location: error.php');
    exit;
  }
  init_db($db);

  if ((strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') && !isset($_GET['lti_consumer_key'])) {
    set_session('url');
    set_session('key');
    set_session('secret');
    set_session('cid');
    set_session('rid');
    set_session('uid');
    set_session('name');
    set_session('fname');
    set_session('lname');
    set_session('email');
    set_session('result');
    set_session('roles');
    set_session('outcome');
    set_session('outcomes');
    set_session('debug');
  } else {
    init_data();
  }

  $script = <<< EOD
<script type="text/javascript">
<!--
var save;
var launch;
window.onload = onLoad;

function onLoad() {
  save = document.getElementById('id_save');
  launch = document.getElementById('id_launch');
  save.disabled = true;
  launch.disabled = ((document.getElementById('id_url').value.length <= 0) ||
                     (document.getElementById('id_key').value.length <= 0));
}

function onChange() {
  save.disabled = false;
  launch.disabled = true;
}

function doLaunch() {
  location.href = 'test_launch.php';
}

function doReset() {
  if (confirm('Reset.  Are you sure?')) {
    location.href = 'test_reset.php';
  }
}
// -->
</script>

EOD;

  page_header($script);

?>
<?php
  if (isset($_GET['lti_msg'])) {
    echo '<p style="font-weight: bold;">' . htmlentities($_GET['lti_msg']) . "</p>\n";
  }
  if (isset($_GET['lti_errormsg'])) {
    echo '<p style="font-weight: bold; color: #f00;">' . htmlentities($_GET['lti_errormsg']) . "</p>\n";
  }

?>
        <h1>LTI Connector Test Harness</h1>

<?php
  $sql = 'SELECT result_sourcedid, score, created ' .
         'FROM ' . TABLE_PREFIX . 'lti_outcome ' .
         'ORDER BY created DESC';
  $query = $db->prepare($sql);
  $query->execute();

  $row = $query->fetch();

  $ok = ($row !== FALSE);

  if ($ok) {
?>
        <div class="grades_box">
          <h2>Grades</h2>

          <table class="DataTable" cellpadding="0" cellspacing="0">
          <tr class="GridHeader">
            <td class="AssessmentAuthor">Result SourcedId</td>
            <td class="AssessmentAuthor">Score</td>
            <td class="Created">Created</td>
          </tr>
<?php
    do {
?>
          <tr border="1" class="GridRow">
            <td>&nbsp;<?php echo $row['result_sourcedid']; ?></td>
            <td>&nbsp;<?php echo $row['score']; ?></td>
            <td>&nbsp;<?php echo $row['created']; ?></td>
          </tr>
<?php
      $row = $query->fetch();
      $ok = ($row !== FALSE);
    } while ($ok);
?>
          </table>
        </div>
<?php
  }
?>
        <form action="test_harness.php" method="POST">

        <h2>Tool Provider Details</h2>

        <div class="row">
          <div class="col1">
            Launch URL *
          </div>
          <div class="col2">
            <input type="text" id="id_url" name="url" value="<?php echo htmlentities($_SESSION['url']); ?>" size="75" onchange="onChange();" />
          </div>
        </div>
        <div class="row">
          <div class="col1">
            Consumer key *
          </div>
          <div class="col2">
            <input type="text" id="id_key" name="key" value="<?php echo htmlentities($_SESSION['key']); ?>" size="60" maxlength="255" onchange="onChange();" />
          </div>
        </div>
        <div class="row">
          <div class="col1">
            Shared secret
          </div>
          <div class="col2">
            <input type="text" name="secret" value="<?php echo htmlentities($_SESSION['secret']); ?>" size="60" maxlength="255" onchange="onChange();" />
          </div>
        </div>

        <h2>Context and Resource Link Details</h2>

        <div class="row">
          <div class="col1">
            Context ID
          </div>
          <div class="col2">
            <input type="text" name="cid" value="<?php echo htmlentities($_SESSION['cid']); ?>" size="10" maxlength="255" onchange="onChange();" />
          </div>
        </div>
        <div class="row">
          <div class="col1">
            Resource Link ID
          </div>
          <div class="col2">
            <input type="text" name="rid" value="<?php echo htmlentities($_SESSION['rid']); ?>" size="10" maxlength="255" onchange="onChange();" />
          </div>
        </div>

        <h2>User Details</h2>

        <div class="row">
          <div class="col1">
            ID
          </div>
          <div class="col2">
            <input type="text" name="uid" value="<?php echo htmlentities($_SESSION['uid']); ?>" size="10" maxlength="255" onchange="onChange();" />
          </div>
        </div>
        <div class="row">
          <div class="col1">
            Full name
          </div>
          <div class="col2">
            <input type="text" name="name" value="<?php echo htmlentities($_SESSION['name']); ?>" size="50" maxlength="255" onchange="onChange();" />
          </div>
        </div>
        <div class="row">
          <div class="col1">
            First name
          </div>
          <div class="col2">
            <input type="text" name="fname" value="<?php echo htmlentities($_SESSION['fname']); ?>" size="30" maxlength="100" onchange="onChange();" />
          </div>
        </div>
        <div class="row">
          <div class="col1">
            Last name
          </div>
          <div class="col2">
            <input type="text" name="lname" value="<?php echo htmlentities($_SESSION['lname']); ?>" size="30" maxlength="100" onchange="onChange();" />
          </div>
        </div>
        <div class="row">
          <div class="col1">
            Email address
          </div>
          <div class="col2">
            <input type="text" name="email" value="<?php echo htmlentities($_SESSION['email']); ?>" size="50" maxlength="255" onchange="onChange();" />
          </div>
        </div>
        <div class="row">
          <div class="col1">
            Results sourcedId
          </div>
          <div class="col2">
            <input type="text" name="result" value="<?php echo htmlentities($_SESSION['result']); ?>" size="40" maxlength="255" onchange="onChange();" />
          </div>
        </div>

        <h2>Role Details</h2>

        <div class="row">
          <div class="col1">
            Role(s)
          </div>
          <div class="col2">
            <select name="roles[]" size="6" multiple="multiple" onchange="onChange();">
<?php
  foreach ($LTI_ROLES as $role => $name) {
    $selected = '';
    if (is_array($_SESSION['roles']) && in_array($role, $_SESSION['roles'])) {
      $selected = ' selected="selected"';
    }
    echo '              <option value="' . $role . '"' . $selected . '>' . $name . '</option>' . "\n";
  }
?>
            </select>
          </div>
        </div>

        <h2>Tool Consumer Service Details</h2>

<?php
  $checked = '';
  if (!empty($_SESSION['outcome'])) {
    $checked = ' checked="checked"';
  }
?>
        <div class="row">
          <div class="col1">
            LTI 1.1 Outcome service URL
          </div>
          <div class="col2">
            <input type="checkbox" name="outcome" value="1"<?php echo $checked; ?> onchange="onChange();" />
          </div>
        </div>
<?php
  $checked = '';
  if (!empty($_SESSION['outcomes'])) {
    $checked = ' checked="checked"';
  }
?>
        <div class="row">
          <div class="col1">
            LTI 1.0 Outcomes extension service URL
          </div>
          <div class="col2">
            <input type="checkbox" name="outcomes" value="1"<?php echo $checked; ?> onchange="onChange();" />
          </div>
        </div>
<?php
  $checked = '';
  if (!empty($_SESSION['debug'])) {
    $checked = ' checked="checked"';
  }
?>
        <div class="row">
          <div class="col1">
            Launch in debug mode
          </div>
          <div class="col2">
            <input type="checkbox" name="debug" value="1"<?php echo $checked; ?> onchange="onChange();" />
          </div>
        </div>

        <p>
          <input id="id_save" type="submit" value="Save data" />
          <input type="button" value="Reset data" onclick="doReset(); return false;" />&nbsp;&nbsp;&nbsp;
          <input id="id_launch" type="button" value="Launch" onclick="doLaunch(); return false;" />
        </p>

        </form>
<?php

  page_footer();

?>