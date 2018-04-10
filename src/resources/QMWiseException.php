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
 *    2.0.00  18-Feb-13
*/

/**
 * QMWiseException
 * @author Bart Nagel
 * Turn SoapFault objects into QMWiseException objects for easier access to the
 * QMWise error code and message
 */

class QMWiseException extends Exception {
  /**
   * constructor
   * Takes a SoapFault as an argument, takes useful details from it
   */
  public function __construct($e) {
    if(isset($e->detail) && !empty($e->detail)) {
      parent::__construct($e->detail->message, intval($e->detail->error));
    } else {
      parent::__construct($e->getMessage(), $e->getCode());
    }
  }

  /**
   * errorType
   * Return a string for the type of error
   * From QMWise API Guide - Error Codes
   *
   *
   */
  public function errorType() {
    if($this->getCode() <= 0) return "unknown";
    else if($this->getCode() < 100) return "security";
    else if($this->getCode() < 1000) return "parameter";
    else if($this->getCode() < 2000) return "operation";
    else if($this->getCode() < 3000) return "configuration";
    else if($this->getCode() < 4000) return "database";
    else if($this->getCode() < 5000) return "internal";
    else return "unknown";
  }

}

?>