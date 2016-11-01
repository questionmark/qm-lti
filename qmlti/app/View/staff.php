       
        <p>
        <a href="<?php echo $em_url; ?>" target="_blank" />Log into Enterprise Manager</a>&nbsp;&nbsp;
        <a href="staff_results.php" />View Assessment Results</a>
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
  if ((count($assessments) > 0) && !is_null($assessments[0])) {
?>
        <form action="staff.php" method="POST">
        <table class="DataTable" cellpadding="0" cellspacing="0">
        <tr class="GridHeader">
          <td>&nbsp;</td>
          <td class="AssessmentName">Assessment Name</td>
          <td class="AssessmentAuthor">Assessment Author</td>
          <td class="LastModified">Last Modified</td>
        </tr>
<?php
    $i = 0;
    foreach ($assessments as $assessment) {
      $i++;
      if ($assessment->Assessment_ID == $assessment_id) {
        $selected = ' checked="checked" onclick="doReset();"';
      } else {
        $selected = ' onclick="doChange(\'img' . $i . '\');"';
      }
?>
        <tr class="GridRow">
          <td><img src="images/exclamation.png" alt="Unsaved change" title="Unsaved change" class="hide" id="img<?php echo $i; ?>" />&nbsp;<input type="radio" name="assessment" value="<?php echo $assessment->Assessment_ID; ?>"<?php echo $selected; ?> /></td>
          <td><?php echo $assessment->Session_Name; ?></td>
          <td><?php echo $assessment->Author; ?></td>
          <td><?php echo $assessment->Modified_Date; ?></td>
        </tr>
<?php
    }
?>
        </table>
        <br><hr>
        <input type="hidden" id="id_coachingreport" name="id_coachingreport" value="0">
        <input type="checkbox" id="id_coachingreport" name="id_coachingreport" onclick="doChange('id_coachingreport');" value="1" <?php echo $coaching_check ?> >Allow participants to view coaching reports.
        <p>
        <input type="submit" id="id_save" value="Save change" disabled="disabled" />
        </p>
        </form>
<?php
  } else {
?>
        <p>No assessments available.</p>
<?php
  }
?>