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

  session_name(SESSION_NAME);
  session_start();

  $script = <<< EOD
<script type="text/javascript">
<!--
function doOnLoad() {
  if (top.location != location) {
    var el = document.getElementById('close');
    el.style.display = 'block';
  }
}
window.onload=doOnLoad;
// -->
</script>

EOD;
  page_header($script);
?>
        <p>Your assessment has been completed.</p>
        <p><input id="close" type="button" value="Close window" onclick="window.close();" style="display: none;" /></p>
<?php
  page_footer();
?>