        <p>
        <a href="<?php echo $em_url; ?>" target="_blank" />Log into Enterprise Manager</a>&nbsp;&nbsp;
        <a href="staff.php" />Back to Control Panel</a>
        </p>
        <h1>Assessment Results</h1>
<?php
  if ((count($results) > 0) && !is_null($results[0])) {
?>
        <form action="staff.php" method="POST">
        <table class="DataTable" cellpadding="0" cellspacing="0">
        <tr class="GridHeader">
          <td>Participant</td>
          <td>Score</td>
          <td>Time Taken</td>
          <td>When Finished</td>
          <td>Coaching Result</td>
        </tr>
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
        </table>
        <br><br><br>
        </form>
<?php
  } else {
?>
        <p>No results available.</p>
<?php
  }
?>