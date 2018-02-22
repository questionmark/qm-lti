<body class="bg-pattern" style="background-color: rgb(250, 250, 250);">
  <div id="Wrapper">
    <div id="MainContentWrapper" class="col-md-6 col-md-offset-3" style="padding-top: 5em">
      <div id="ContentWrapper">
        <div id="HeaderWrapper" class="header-top padding-top-md">
          <a href="https://www.questionmark.com/"><img id="logoImage" src="/web/images/logo.png" alt="Questionmark" class="center-block" /></a>
        </div>
        <hr class="qm-divider" />
        <div id="PageContent" class="block-color">
          <div class="container-fluid">
            <p>
              <button type="button" class="btn btn-default btn-grey no-margin" onclick="location.href='<?php echo $return_url; ?>'">Back to Course</button>
            </p>
          </div>
          <div id="body" class="container-fluid" style="padding: 0px">
            <form action="student_nav.php" method="POST">
              <div class="col-md-12">
                <h2 style="font-weight: normal; color: #999;">Assessment</h2>
                <hr class="qm-divider-sm">
                <h1 id="AssessmentName" style="margin: 0px; color: #444"><?php echo $assessment->Session_Name; ?></h1>
              </div>
              <br><br>
              <div class="col-md-12" style="padding: 35px 0px 0px 0px;">
                <div class="mb-5 col-md-6">
                  <p>You have attempted <span id="PastAttempts"><?php echo $past_attempts; ?></span> out of <span id="ParsedAttempts"><?php echo $parsed_attempts; ?></span> attempts.
                  <br><br>
                </div>
                <div class="col-md-6">
                  <div class="top-block"></div>
                  <div class="button-input">
                    <?php if ($launch) { ?>
                       <input class="btn btn-wide btn-success btn-green" type="submit" name="action" value="Start Test"/>
                    <?php } ?>
                    <br><br>
                    <?php if ($bool_coaching_report) { ?>
                      <input class="btn btn-wide btn-info btn-blue" type="submit" name="action" value="View Coaching Report" formtarget="_blank"/>
                    <?php } ?>
                  </div>
                </div>
              </div>
            </form>
            <br>
          </div>
          <div id="spacer-lg"></div>
        </div>
      </div>
    </div>
    <div class="col-md-12">
      <p class="footer"><span id="Copyright"> © 2018 Questionmark Computing Ltd.</span></p>
    </div>
  </div>
</body>
