        <div id="body" class="container-fluid">
        <p>
        <a class="btn btn-default" href="<?php echo $em_url; ?>" target="_blank" />Log into Questionmark Portal</a>&nbsp;&nbsp;
        <a class="btn btn-default" href="staff.php" />Back to Assessment Configuration Page</a>
        </p>
        <h1>Assessment Results</h1>
<?php
  if (($results != NULL) && (count($results) > 0)) {
?>
        <form action="staff.php" method="POST">
        <table class="DataTable table table-sm table-bordered" cellpadding="0" cellspacing="0">
        <thead>
          <tr class="GridHeader">
            <th>Participant</th>
            <th>Score</th>
            <th>Time Taken</th>
            <th>When Finished</th>
            <th>Coaching Result</th>
          </tr>
        </thead>
        <tbody>
<?php
    $i = 0;
    foreach ($results as $result) {
      $i++;
?>
        <tr class="GridRow">
          <td><?php echo $result->Result->Participant; ?></td>
          <td><?php echo "{$result->Result->Total_Score}/{$result->Result->Max_Score} ({$result->Result->Percentage_Score}%)";  ?></td>
          <td><?php echo "{$result->Result->Time_Taken}s"; ?></td>
          <td><?php echo str_replace('T', ' ', $result->Result->When_Finished); ?>
          </td>
          <td><a href="<?php echo $result->Result->URL; ?>">View Now</a></td>
        </tr>
<?php
    }
?>
        </tbody>
        </table>
        <br><br><br>
        </form>
        </div>
<?php
  } else {
?>
        <p>No results available.</p>
        </div>
<?php
  }
?>