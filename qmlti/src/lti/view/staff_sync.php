        <div id="body" class="container-fluid">
        <p>
        <a class="btn btn-default" href="<?php echo $em_url; ?>" target="_blank" />Log into Questionmark Portal</a>&nbsp;&nbsp;
        <a class="btn btn-default" href="staff_results.php" />View Assessment Results</a>&nbsp;&nbsp;
        <a class="btn btn-default" href="staff.php" />Back to Control Panel</a>
        </p>
        <h1>User List</h1>
<?php
  if (($students_list != NULL) && (count($students_list) > 0) && !is_null($students_list[0])) {
?>
        <form action="staff_sync.php" method="POST">
        <table class="DataTable table table-sm" cellpadding="0" cellspacing="0">
        <tr class="GridHeader">
          <td>User ID</td>
          <td>First Name</td>
          <td>Last Name</td>
          <td>Full Name</td>
          <td>Email</td>
          <td>Roles</td>
          <td>Created</td>
          <td>Updated</td>
        </tr>
<?php
    $i = 0;
    foreach ($students_list as $student) {
      $i++;
?>
        <tr class="GridRow">
          <td><?php echo $student['user_id']; ?></td>
          <td><?php echo $student['firstname']; ?></td>
          <td><?php echo $student['lastname']; ?></td>
          <td><?php echo $student['fullname']; ?></td>
          <td><?php echo $student['email']; ?></td>
          <td><?php echo $student['roles']; ?></td>
          <td><?php echo $student['created']; ?></td>
          <td><?php echo $student['updated']; ?></td>
        </tr>
<?php
    }
?>
        </table>
        <br>
<?php
  } else {
?>      
        <form action="staff_sync.php" method="POST">
        <p>No results available.</p>
        <br>
<?php
  }
?>
        <br>
        <input type="submit" name="btn_sync" value="Start Manual Sync" class="btn btn-default" />
        </form>
        </div>