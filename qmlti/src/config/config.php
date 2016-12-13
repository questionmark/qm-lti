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
 *    2.0.00  18-Feb-13  Updated to support multiple LMS configuration
*/

// General settings
error_reporting(E_ALL);
ini_set('display_errors', 1);
define('DEBUG_MODE', TRUE);
define('SECURE_COOKIE_ONLY', FALSE);
define('ADMINISTRATOR_ROLE', 'LTI_INSTRUCTOR');
define('WEB_PATH', '');  // enter the path starting with a "/" but without a trailing "/"; only required if the automated version does not work
define('TABLE_PREFIX', '');  // optional prefix added to standard LTI database table names (allowing multiple installations to share the same database schema)

// Uncomment and complete this section when using this connector with a single LMS
// (this data is retrieved from the database for installations supporting multiple LMSs)

// define('CONSUMER_KEY', 'KEY');        // consumer key as used when defining links to this connector in LMS
// define('CONSUMER_SECRET', 'SECRET');     // shared secret as used when defining links to this connector in LMS
// define('QMWISE_URL', 'https://gateway2.teamlab.questionmark.local/qmwise/20035/qmwise.asmx');  // e.g. https://ondemand.questionmark.com/qmwise/123456/qmwise.asmx
// define('SECURITY_CLIENT_ID', '20035');
// define('SECURITY_CHECKSUM', 'Passw0rd');
// define('QM_USERNAME_PREFIX', '');  // prefix to add to user IDs supplied by the LMS (allowing these users to be easily distinguished from direct login accounts

// Uncomment and complete this section if the database settings are not specified via environment variables (of the same names)
define('DB_SERVER', '');           // leave empty except for SQL Server
define('DB_NAME', 'sqlite:' .  $_SERVER['DOCUMENT_ROOT'] . ' /src/qmp-lti.sqlitedb');
// e.g. 'sqlite:qmp-lti.sqlitedb' or 'mysql:dbname=qmp-lti;host=localhost'
define('DB_USERNAME', '');        // name of account used to access database
define('DB_PASSWORD', '');         // password for database account


?>