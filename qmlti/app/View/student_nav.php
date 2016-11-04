<div id="body" class="container-fluid col-md-6">
<h1>Student Portal</h1>
<form action="student_nav.php" method="POST">
  <table class="DataTable table table-sm" cellpadding="0" cellspacing="0">
    <tr class="GridHeader">
      <th>Assessment Name</th>
      <th>Launch</th>
    <?php if ($bool_coaching_report) { ?>
      <th>Coaching Report</th>
    <?php } ?>
    </tr>
    <tr class="GridRow">
      <td><?php echo $assessment->Session_Name; ?></td>
      <td><input class="btn btn-sm btn-link" type="submit" name="action" value="Launch Assessment" /></td>
    <?php if ($bool_coaching_report) { ?>
      <td><input class="btn btn-sm btn-link" type="submit" name="action" value="View Coaching Report" /></td>
    <?php } ?>
    </tr>
  </table>
</form>
<br>
</div>