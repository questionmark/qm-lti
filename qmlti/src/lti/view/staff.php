
        <div id="body" class="container-fluid">
        <p>
        <a class="btn btn-default" href="<?php echo $em_url; ?>" target="_blank" />Log into Questionmark Portal</a>&nbsp;&nbsp;&nbsp;
        <a class="btn btn-default" href="staff_results.php" />View Assessment Results</a>
        </p>
<?php
  if (!$_SESSION['allow_outcome']) {
?>
        <p><strong>No score will be saved by this connection.</strong></p>
<?php
  }
?>
        <h1>Assessments</h1>
<?php
  if ((count($assessments) !== 0) && !is_null($assessments[0])) {
?>
        <form action="staff.php" method="POST">
        <table class="DataTable-staff table table-sm" cellpadding="0" cellspacing="0">
          <thead>
            <tr class="GridHeader">
              <td>&nbsp;</td>
              <td class="AssessmentName">Assessment Name</td>
              <td class="AssessmentAuthor">Assessment Author</td>
              <td class="LastModified">Last Modified</td>
            </tr>
          </thead>
          <tbody>
<?php
    $i = 0;
    foreach ($assessments as $assessment) {
      $i++;
      if ($assessment->Assessment_ID == $assessment_id) {
        $selected = ' checked="checked" onclick="doReset();"';
      } else {
        $selected = ' onclick="doChange(\'\');"';
      }
?>
            <tr class="GridRow">
              <td>
                <input type="radio" name="assessment" value="<?php echo $assessment->Assessment_ID; ?>" <?php echo $selected; ?> />
              </td>
              <td><?php echo $assessment->Session_Name; ?></td>
              <td><?php echo $assessment->Author; ?></td>
              <td><?php echo $assessment->Modified_Date; ?></td>
            </tr>
<?php
    }
?>
          </tbody>
        </table>
        <br><br>
        <p>
        <input type="hidden" id="id_coachingreport" name="id_coachingreport" value="0">
        <div class="row">
          <div class="col1">
          Allow participants to view coaching reports
          </div>
          <div class="col2">
          <input type="checkbox" id="id_coachingreport" name="id_coachingreport" onclick="doChange('');" value="1" <?php echo $coaching_check ?> >
          </div>
        </div>
        <br>
        <div class="row">
          <div class="col1">
          Select result to display:
          </div>
          <div class="col2">
          <select id="id_multipleresult" class="form-control dropdown-select" name="id_multipleresult" onchange="doChange('');">

<?php
      foreach ($arr_results as $results) {
        if ($results == $multiple_results) {
          $selectresults = 'selected';
        } else {
          $selectresults = '';
        }
?>
          <option value="<?php echo $results; ?>" <?php echo $selectresults; ?>><?php echo $results; ?></option>
<?php
      }
?>
        </select>
        </div>
        </div>
        <br>
        <div class="row">
          <div class="col1">
          Number of Attempts
          </div>
          <div class="col2">

          <select id="id_numberattempts" class="form-control dropdown-select" name="id_numberattempts" onchange="doChange('');">
            <option value="none" <?php echo $no_attempts; ?>>No limit</option>
            <?php
              for ($i = 1; $i <= 10; $i++) {
                if ($i == $number_attempts) {
                  $selected_attempts = 'selected';
                } else {
                  $selected_attempts = '';
                }
            ?>
              <option value="<?php echo $i; ?>" <?php echo $selected_attempts; ?>><?php echo $i; ?></option>
            <?php
              }
            ?>
          </select>
          </div>
        </div>
        <br><br><br>
        <input class="button btn" type="submit" id="id_save" value="Save change" disabled="disabled" />
        </p>
        <br><br><br>
        <br><br><br>
        </form>
        </div>
<?php
  } else {
?>
        <p>No assessments available.</p>
        </div>
<?php
  }
?>
