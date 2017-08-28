<?php
/*
 *  LTI-Connector - Connect to Perception via IMS LTI
 *  Copyright (C) 2017  Questionmark
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
 *    1.0.00   1-May-12  Initial prototype
 *    1.1.00   3-May-12  Added test harness
 *    1.2.00  23-Jul-12
 *    2.0.00  18-Feb-13
*/

require_once('../resources/lib.php');
require_once('model/student.php');
require_once('../resources/LTI_Data_Connector_qmp.php');

  session_name(SESSION_NAME);
  session_start();

  $student = new Student($_SESSION);
  $student->checkValid();

  // Activate SOAP Connection.
  if (!isset($_SESSION['error'])) {
    perception_soapconnect();
  }

  if (isset($_POST['action'])) {
    $student->identifyAction($_POST['action']);
  }
  $student->createParticipant();
  $student->joinGroup();

  $assessment = $student->getAssessment();
  $past_attempts = $student->getAttemptDetails();
  $bool_coaching_report = $student->isCoachingReportAvailable();
  $number_attempts = $student->getNumberAttempts();
  $launch_disabled = $student->checkLaunchDisabled();
  $parsed_attempts = $student->getParsedAttempts();
  
  if (isset($_SESSION['error'])) {
   header("Location: error.php");
  }

  page_header();
  include_once("view/student_nav.php");
?>
