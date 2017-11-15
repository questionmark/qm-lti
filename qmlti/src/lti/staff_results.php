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
  $results = $staff->getResults();

  if (!$staff->isOk()) {
    header('Location: error.php');
    exit;
  }

  $script = <<< EOD
<script src="../../../web/js/staff.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="../../../web/datatables/datatables.min.css"/>
<script type="text/javascript" src="../../../web/datatables/datatables.min.js"></script>
<script type="text/javascript" src="../../../web/js/datatables.js"></script>
EOD;

  page_header($script);
  include_once("view/staff_results.php");
  page_footer();
?>
