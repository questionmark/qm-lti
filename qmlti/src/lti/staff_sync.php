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
 *    1.0.00   1-May-12  Initial prototype
 *    1.2.00  23-Jul-12
 *    2.0.00  18-Feb-13  Moved script into page header
*/

require_once('../resources/lib.php');
require_once('../resources/LTI_Data_Connector_qmp.php');
require_once('model/staff.php');

  session_name(SESSION_NAME);
  session_start();

  $staff = new Staff($_SESSION);
  $staff->setupAdministrator();
  $em_url = $staff->getLoginURL();

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['btn_sync'])) {
      if ($resource_link->hasMembershipsService()) {
        $gen_users = $resource_link->doMembershipsService();
        foreach ($gen_users as $user) {
          $user->setContext($context_id);
          save_user($db, $user);
        }
      } else {
        error_log("Manual sync failed due to lack of membership service.");
      }
    }
  }

  // Get list of students logged in LTI
  $students_list = get_participants_by_context_id($db, $consumer_key, $context_id);

  if (!$ok) {
    header('Location: error.php');
    exit;
  }

  page_header();
  include_once("view/staff_sync.php");
  page_footer();
?>