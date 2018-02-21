<body class="bg-pattern" style="background-color: rgb(250, 250, 250);">
  <div id="Wrapper">
    <div id="MainContentWrapper" class="col-md-10 col-md-offset-1" style="padding-top: 5em">
      <div id="ContentWrapper">
        <div id="HeaderWrapper" class="header-top padding-top-md">
          <a href="https://www.questionmark.com/"><img id="logoImage" src="/web/images/logo.png" alt="Questionmark" class="center-block" /></a>
        </div>
        <hr class="qm-divider" />
        <div id="PageContent" class="block-color">
        <div id="body" class="container-fluid">
        <p>
        <a class="btn btn-default btn-grey" href="<?php echo $return_url; ?>">Back to Course</button></a>
        <a class="btn btn-default btn-grey" href="<?php echo $em_url; ?>" target="_blank" />Log into Questionmark Portal</a>
        <a class="btn btn-default btn-grey" href="staff.php" />Back to Assessment Configuration Page</a>
        </p>
        <h1>Assessment Results</h1>
        <hr class="qm-divider-sm"><br>
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
          <td><a href="<?php echo $result->Result->URL; ?>" target="_blank">View Now</a></td>
        </tr>
<?php
    }
?>
        </tbody>
        </table>
        <br><br><br>
        </form>
        </div>
        </div>
      </div>
    </div>
    <div class="col-md-10 col-md-offset-1">
      <p class="footer"><span id="Copyright"> © 2018 Questionmark Computing Ltd.</span></p>
  </div>
</body>
<?php
  } else {
?>
        <p>No results available.</p>
        </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-10 col-md-offset-1">
      <p class="footer"><span id="Copyright"> © 2018 Questionmark Computing Ltd.</span></p>
  </div>
</body>
<?php
  }
?>
