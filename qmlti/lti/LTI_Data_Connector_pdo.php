<?php
/*
 *  LTI-Connector - Connect to Perception via IMS LTI
 *  Copyright (C) 2013  Questionmark
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 *  Contact: info@questionmark.com
 *
 *  Version history:
 *    1.0.00   1-May-12  Initial prototype
 *    1.2.00  23-Jul-12
 *    2.0.00  18-Feb-13
*/

###
###  Class to represent a QMP LTI Data Connector
###

class LTI_Data_Connector_QMP extends LTI_Data_Connector {

  private $dbTableNamePrefix = '';
  private $db = NULL;

###
#    Class constructor
###
  function __construct($db, $dbTableNamePrefix = '') {

    $this->db = $db;
    $this->dbTableNamePrefix = $dbTableNamePrefix;

  }


###
###  LTI_Tool_Consumer methods
###

###
#    Load the tool consumer from the database
###
  public function Tool_Consumer_load($consumer) {

    $ok = TRUE;
    if (defined('CONSUMER_KEY')) {
      $consumer->secret = CONSUMER_SECRET;
      $consumer->enabled = TRUE;
      $now = time();
      $consumer->created = $now;
      $consumer->updated = $now;
    } else {
      $sql = 'SELECT secret, consumer_name, customer_id, username_prefix, last_access, created, updated ' .
             'FROM ' .$this->dbTableNamePrefix . LTI_Data_Connector::CONSUMER_TABLE_NAME . ' ' .
             'WHERE consumer_key = :key';
      $query = $this->db->prepare($sql);
      $key = $consumer->getKey();
      $query->bindValue('key', $key, PDO::PARAM_STR);
      $ok = $query->execute();

      if ($ok) {
        $row = $query->fetch();
        $ok = ($row !== FALSE);
      }

      if ($ok) {
        $consumer->secret = $row['secret'];
        $consumer->enabled = TRUE;
        $consumer->consumer_name = $row['consumer_name'];
        $consumer->custom['customer_id'] = $row['customer_id'];
        $consumer->custom['username_prefix'] = $row['username_prefix'];
        $consumer->last_access = NULL;
        if (!is_null($row['last_access'])) {
          $consumer->last_access = strtotime($row['last_access']);
        }
        $consumer->created = strtotime($row['created']);
        $consumer->updated = strtotime($row['updated']);
      }
    }

    return $ok;

  }

###
#    Save the tool consumer to the database
###
  public function Tool_Consumer_save($consumer) {

    $ok = TRUE;
    if (defined('CONSUMER_KEY')) {
      $consumer->updated = time();
    } else {
      $time = time();
      $now = date('Y-m-d H:i:s', $time);
      $last = NULL;
      if (!is_null($consumer->last_access)) {
        $last = date('Y-m-d', $consumer->last_access);
      }
      $key = $consumer->getKey();
      if (is_null($consumer->created)) {
        $sql = 'INSERT INTO ' . $this->dbTableNamePrefix . LTI_Data_Connector::CONSUMER_TABLE_NAME . ' ' .
               '(consumer_key, secret, consumer_name, customer_id, username_prefix, last_access, created, updated) ' .
               'VALUES (:key, :secret, :consumer_name, :customer_id, :username_prefix, ' .
               ':last_access, :created, :updated)';
        $query = $this->db->prepare($sql);
        $query->bindValue('key', $key, PDO::PARAM_STR);
        $query->bindValue('secret', $consumer->secret, PDO::PARAM_STR);
        $query->bindValue('consumer_name', $consumer->consumer_name, PDO::PARAM_STR);
        $query->bindValue('customer_id', $consumer->custom['customer_id'], PDO::PARAM_STR);
        $query->bindValue('username_prefix', $consumer->custom['username_prefix'], PDO::PARAM_STR);
        $query->bindValue('last_access', $last, PDO::PARAM_STR);
        $query->bindValue('created', $now, PDO::PARAM_STR);
        $query->bindValue('updated', $now, PDO::PARAM_STR);
      } else {
        $sql = 'UPDATE ' . $this->dbTableNamePrefix . LTI_Data_Connector::CONSUMER_TABLE_NAME . ' ' .
               'SET secret = :secret, ' .
               'consumer_name = :consumer_name, customer_id = :customer_id, username_prefix = :username_prefix, ' .
               'last_access = :last_access, updated = :updated ' .
               'WHERE consumer_key = :key';
        $query = $this->db->prepare($sql);
        $query->bindValue('key', $key, PDO::PARAM_STR);
        $query->bindValue('secret', $consumer->secret, PDO::PARAM_STR);
        $query->bindValue('consumer_name', $consumer->consumer_name, PDO::PARAM_STR);
        $query->bindValue('customer_id', $consumer->custom['customer_id'], PDO::PARAM_STR);
        $query->bindValue('username_prefix', $consumer->custom['username_prefix'], PDO::PARAM_STR);
        $query->bindValue('last_access', $last, PDO::PARAM_STR);
        $query->bindValue('updated', $now, PDO::PARAM_STR);
      }
      $ok = $query->execute();
      if ($ok) {
        if (is_null($consumer->created)) {
          $consumer->created = $time;
        }
        $consumer->updated = $time;
      }
    }

    return $ok;

  }

###
#    Delete the tool consumer from the database
###
  public function Tool_Consumer_delete($consumer) {

    $ok = TRUE;

    if (defined('CONSUMER_KEY')) {

// Delete any nonce values
      $sql = 'DELETE FROM ' . $this->dbTableNamePrefix . LTI_Data_Connector::NONCE_TABLE_NAME;
      $query = $this->db->prepare($sql);
      $query->execute();

    } else {

      $key = $consumer->getKey();
// Delete any nonce values for this consumer
      $sql = 'DELETE FROM ' . $this->dbTableNamePrefix . LTI_Data_Connector::NONCE_TABLE_NAME . ' WHERE consumer_key = :key';
      $query = $this->db->prepare($sql);
      $query->bindValue('key', $key, PDO::PARAM_STR);
      $query->execute();

// Delete any resource links for this consumer
      $sql = 'DELETE FROM ' . $this->dbTableNamePrefix . LTI_Data_Connector::RESOURCE_LINK_TABLE_NAME . ' WHERE consumer_key = :key';
      $query = $this->db->prepare($sql);
      $query->bindValue('key', $key, PDO::PARAM_STR);
      $query->execute();

// Delete consumer
      $sql = 'DELETE FROM ' . $this->dbTableNamePrefix . LTI_Data_Connector::CONSUMER_TABLE_NAME . ' WHERE consumer_key = :key';
      $query = $this->db->prepare($sql);
      $query->bindValue('key', $key, PDO::PARAM_STR);
      $ok = $query->execute();
    }

    if ($ok) {
      $consumer->initialise();
    }

    return $ok;

  }

###
#    Load all tool consumers from the database
###
  public function Tool_Consumer_list() {

    $consumers = array();

    if (!defined('CONSUMER_KEY')) {

      $sql = 'SELECT consumer_key, secret, consumer_name, customer_id, username_prefix, last_access, created, updated ' .
             'FROM ' .$this->dbTableNamePrefix . LTI_Data_Connector::CONSUMER_TABLE_NAME;
      $query = $this->db->prepare($sql);
      $ok = ($query !== FALSE);

      if ($ok) {
        $ok = $query->execute();
      }
      if ($ok) {
        while ($row = $query->fetch()) {
          $consumer = new LTI_Tool_Consumer($row['consumer_key'], $this);
          $consumer->secret = $row['secret'];
          $consumer->consumer_name = $row['consumer_name'];
          $consumer->custom['customer_id'] = $row['customer_id'];
          $consumer->custom['username_prefix'] = $row['username_prefix'];
          $consumer->last_access = NULL;
          if (!is_null($row['last_access'])) {
            $consumer->last_access = strtotime($row['last_access']);
          }
          $consumer->created = strtotime($row['created']);
          $consumer->updated = strtotime($row['updated']);
          $consumers[] = $consumer;
        }
      }
    }

    return $consumers;

  }

###
###  LTI_Resource_Link methods
###

###
#    Load the resource link from the database
###
  public function Resource_Link_load($resource_link) {

    if (defined('CONSUMER_KEY')) {
      $id = $resource_link->getId();
      $sql = 'SELECT lti_context_id, settings, created, updated ' .
             'FROM ' .$this->dbTableNamePrefix . LTI_Data_Connector::RESOURCE_LINK_TABLE_NAME . ' ' .
             'WHERE lti_context_id = :id';
      $query = $this->db->prepare($sql);
      $query->bindValue('id', $id, PDO::PARAM_STR);
    } else {
      $key = $resource_link->getKey();
      $id = $resource_link->getId();
      $sql = 'SELECT consumer_key, lti_context_id, settings, created, updated ' .
             'FROM ' .$this->dbTableNamePrefix . LTI_Data_Connector::RESOURCE_LINK_TABLE_NAME . ' ' .
             'WHERE (consumer_key = :key) AND (lti_context_id = :id)';
      $query = $this->db->prepare($sql);
      $query->bindValue('key', $key, PDO::PARAM_STR);
      $query->bindValue('id', $id, PDO::PARAM_STR);
    }
    $query->execute();

    $row = $query->fetch();

    $ok = ($row !== FALSE);

    if ($ok) {
      $settingsValue = $row['settings'];
      if (!empty($settingsValue)) {
        $resource_link->settings = unserialize($settingsValue);
        if (!is_array($resource_link->settings)) {
          $resource_link->settings = array();
        }
      } else {
        $resource_link->settings = array();
      }
      $resource_link->created = strtotime($row['created']);
      $resource_link->updated = strtotime($row['updated']);
    }

    return $ok;

  }

###
#    Save the resource link to the database
###
  public function Resource_Link_save($resource_link) {

    $time = time();
    $now = date("Y-m-d H:i:s", $time);
    $settingsValue = serialize($resource_link->settings);
    $id = $resource_link->getId();

    if (defined('CONSUMER_KEY')) {
      if (is_null($resource_link->created)) {
        $sql = 'INSERT INTO ' . $this->dbTableNamePrefix . LTI_Data_Connector::RESOURCE_LINK_TABLE_NAME . ' ' .
               '(lti_context_id, settings, created, updated) VALUES (:id, :settings, :created, :updated)';
        $query = $this->db->prepare($sql);
        $query->bindValue('id', $id, PDO::PARAM_STR);
        $query->bindValue('settings', $settingsValue, PDO::PARAM_INT);
        $query->bindValue('created', $now, PDO::PARAM_STR);
        $query->bindValue('updated', $now, PDO::PARAM_STR);
      } else {
        $sql = 'UPDATE ' . $this->dbTableNamePrefix . LTI_Data_Connector::RESOURCE_LINK_TABLE_NAME . ' ' .
               'SET settings = :settings, updated = :updated ' .
               'WHERE lti_context_id = :id';
        $query = $this->db->prepare($sql);
        $query->bindValue('id', $id, PDO::PARAM_STR);
        $query->bindValue('settings', $settingsValue, PDO::PARAM_STR);
        $query->bindValue('updated', $now, PDO::PARAM_STR);
      }
    } else {
      $key = $resource_link->getKey();
      if (is_null($resource_link->created)) {
        $sql = 'INSERT INTO ' . $this->dbTableNamePrefix . LTI_Data_Connector::RESOURCE_LINK_TABLE_NAME . ' ' .
               '(consumer_key, lti_context_id, settings, created, updated) VALUES (:key, :id, :settings, :created, :updated)';
        $query = $this->db->prepare($sql);
        $query->bindValue('key', $key, PDO::PARAM_STR);
        $query->bindValue('id', $id, PDO::PARAM_STR);
        $query->bindValue('settings', $settingsValue, PDO::PARAM_INT);
        $query->bindValue('created', $now, PDO::PARAM_STR);
        $query->bindValue('updated', $now, PDO::PARAM_STR);
      } else {
        $sql = 'UPDATE ' . $this->dbTableNamePrefix . LTI_Data_Connector::RESOURCE_LINK_TABLE_NAME . ' ' .
               'SET settings = :settings, updated = :updated ' .
               'WHERE (consumer_key = :key) AND (lti_context_id = :id)';
        $query = $this->db->prepare($sql);
        $query->bindValue('key', $key, PDO::PARAM_STR);
        $query->bindValue('id', $id, PDO::PARAM_STR);
        $query->bindValue('settings', $settingsValue, PDO::PARAM_STR);
        $query->bindValue('updated', $now, PDO::PARAM_STR);
      }
    }

    $ok = $query->execute();

    return $ok;

  }

###
#    Delete the resource link from the database
###
  public function Resource_Link_delete($resource_link) {

    $id = $resource_link->getId();
// Delete resource link
    if (defined('CONSUMER_KEY')) {
      $sql = 'DELETE FROM ' . $this->dbTableNamePrefix . LTI_Data_Connector::RESOURCE_LINK_TABLE_NAME . ' ' .
             'WHERE lti_context_id = :id';
      $query = $this->db->prepare($sql);
      $query->bindValue('id', $id, PDO::PARAM_STR);
    } else {
      $key = $resource_link->getKey();
      $sql = 'DELETE FROM ' . $this->dbTableNamePrefix . LTI_Data_Connector::RESOURCE_LINK_TABLE_NAME . ' ' .
             'WHERE (consumer_key = :key) AND (lti_context_id = :id)';
      $query = $this->db->prepare($sql);
      $query->bindValue('key', $key, PDO::PARAM_STR);
      $query->bindValue('id', $id, PDO::PARAM_STR);
    }

    $ok = $query->execute();

    if ($ok) {
      $resource_link->initialise();
    }

    return $ok;

  }

###
#    Obtain an array of LTI_User objects for users with a result sourcedId.  The array may include users from other
#    resource links which are sharing this resource link.  It may also be optionally indexed by the user ID of a specified scope.
###
  public function Resource_Link_getUserResultSourcedIDs($resource_link, $resource_link_only, $id_scope) {

    $users = array();

    return $users;

  }

###
#    Get an array of LTI_Resource_Link_Share objects for each resource link which is sharing this context
###
  public function Resource_Link_getShares($resource_link) {

    $shares = array();

    return $shares;

  }


###
###  LTI_Consumer_Nonce methods
###

###
#    Load the consumer nonce from the database
###
  public function Consumer_Nonce_load($nonce) {

#
### Delete any expired nonce values
#
    $now = date('Y-m-d H:i:s', time());
    $sql = 'DELETE FROM ' . $this->dbTableNamePrefix . LTI_Data_Connector::NONCE_TABLE_NAME . ' WHERE expires <= :now';
    $query = $this->db->prepare($sql);
    $query->bindValue('now', $now, PDO::PARAM_STR);
    $query->execute();

#
### load the nonce
#
    $value = $nonce->getValue();
    if (defined('CONSUMER_KEY')) {
      $sql = 'SELECT value AS T FROM ' . $this->dbTableNamePrefix . LTI_Data_Connector::NONCE_TABLE_NAME . ' WHERE value = :value';
      $query = $this->db->prepare($sql);
      $query->bindValue('value', $value, PDO::PARAM_STR);
    } else {
      $key = $nonce->getKey();
      $sql = 'SELECT value AS T FROM ' . $this->dbTableNamePrefix . LTI_Data_Connector::NONCE_TABLE_NAME . ' WHERE consumer_key = :key AND value = :value';
      $query = $this->db->prepare($sql);
      $query->bindValue('key', $key, PDO::PARAM_STR);
      $query->bindValue('value', $value, PDO::PARAM_STR);
    }

    $ok = $query->execute();

    if ($ok) {
      $row = $query->fetch();
      if ($row === FALSE) {
        $ok = FALSE;
      }
    }

    return $ok;

  }

###
#    Save the consumer nonce in the database
###
  public function Consumer_Nonce_save($nonce) {

    $value = $nonce->getValue();
    $expires = date('Y-m-d H:i:s', $nonce->expires);

    if (defined('CONSUMER_KEY')) {
      $sql = 'INSERT INTO ' . $this->dbTableNamePrefix . LTI_Data_Connector::NONCE_TABLE_NAME . ' (value, expires) VALUES (:value, :expires)';
      $query = $this->db->prepare($sql);
      $query->bindValue('value', $value, PDO::PARAM_STR);
      $query->bindValue('expires', $expires, PDO::PARAM_STR);
    } else {
      $key = $nonce->getKey();
      $sql = 'INSERT INTO ' . $this->dbTableNamePrefix . LTI_Data_Connector::NONCE_TABLE_NAME . ' (consumer_key, value, expires) VALUES (:key, :value, :expires)';
      $query = $this->db->prepare($sql);
      $query->bindValue('key', $key, PDO::PARAM_STR);
      $query->bindValue('value', $value, PDO::PARAM_STR);
      $query->bindValue('expires', $expires, PDO::PARAM_STR);
    }

    $ok = $query->execute();

    return $ok;

  }

###
###  LTI_Resource_Link_Share_Key methods
###

###
#    Load the resource link share key from the database
###
  public function Resource_Link_Share_Key_load($share_key) {

    return FALSE;

  }

###
#    Save the resource link share key to the database
###
  public function Resource_Link_Share_Key_save($share_key) {

    return TRUE;

  }

###
#    Delete the resource link share key from the database
###
  public function Resource_Link_Share_Key_delete($share_key) {

    return TRUE;

  }

###
###  Result methods
###

###
#    Saves the current result into the Results table.
###
  public function Results_save($outcome, $consumer, $resource_link, $participant) {

    $time = time();
    $now = date('Y-m-d H:i:s', $time);
    $id = $resource_link->getId();

    $sql = 'INSERT INTO ' . $this->dbTableNamePrefix . LTI_Data_Connector::RESULTS_TABLE_NAME . ' (consumer_key, context_id, ' .
             'assessment_id, customer_id, created, score, result_id, is_accessed) ' .
             'VALUES (:consumer, :context, :assessment, :customer, :created, :score, :result, :accessed)';
    $query = $this->db->prepare($sql);
    $query->bindValue('consumer', $consumer->getKey(), PDO::PARAM_STR);
    $query->bindValue('context', $id, PDO::PARAM_STR);
    $query->bindValue('assessment', $resource_link->getSetting('qmp_assessment_id'), PDO::PARAM_STR);
    $query->bindValue('customer', $participant, PDO::PARAM_STR);
    $query->bindValue('created', $now, PDO::PARAM_STR);
    $query->bindValue('score', $outcome->getValue(), PDO::PARAM_STR);
    $query->bindValue('result', $outcome->getResultID(), PDO::PARAM_INT);
    $query->bindValue('accessed', 1, PDO::PARAM_INT);
    $ok = $query->execute();

    return $ok;

  }

###
#    Gets latest result for participant given assessment id
###
  public function Results_getLatestResult($consumer, $resource_link, $participant) {
      
    $id = $resource_link->getId();
    $sql = 'SELECT result_id ' .
           'FROM ' . $this->dbTableNamePrefix . LTI_Data_Connector::RESULTS_TABLE_NAME . ' ' .
           'WHERE (assessment_id = :assessment) AND (customer_id = :customer) AND (consumer_key = :consumer) ' .
           'AND (context_id = :context)';
    $query = $this->db->prepare($sql);
    $query->bindValue('assessment', $resource_link->getSetting('qmp_assessment_id'), PDO::PARAM_STR);
    $query->bindValue('customer', $participant, PDO::PARAM_STR);
    $query->bindValue('consumer', $consumer->getKey(), PDO::PARAM_STR);
    $query->bindValue('context', $id, PDO::PARAM_STR);
    $ok = $query->execute();
    if ($ok) {
      $row = $query->fetch();
      $result_id = $row['result_id'];
    }
    if (!$ok) {
      return FALSE;
    }
    return $result_id;
  }

###
###  Coaching Config methods
###

###
#    Checks to see if report config is already loaded for specific build
###
  public function ReportConfig_loadAccessible($consumer_key, $resource_link_id, $assessment_id) {

    $sql = 'SELECT is_accessible ' .
           'FROM ' . $this->dbTableNamePrefix . LTI_Data_Connector::REPORTS_TABLE_NAME . ' ' .
           'WHERE (consumer_key = :consumer) AND (context_id = :context) AND (assessment_id = :assessment)';
    $query = $this->db->prepare($sql);
    $query->bindValue('consumer', $consumer_key, PDO::PARAM_STR);
    $query->bindValue('context', $resource_link_id, PDO::PARAM_STR);
    $query->bindValue('assessment', $assessment_id, PDO::PARAM_STR);
    $ok = $query->execute();
    if ($ok) {
      $row = $query->fetch();
      $is_accessible = $row['is_accessible'];
    }
    if (!$ok) {
      return NULL;
    }
    return $is_accessible;

  }

###
#    Inserts the report configuration to the database
###
  public function ReportConfig_insert($consumer_key, $resource_link_id, $assessment_id, $is_accessible) {

    $sql = 'INSERT INTO ' . $this->dbTableNamePrefix . LTI_Data_Connector::REPORTS_TABLE_NAME . ' (consumer_key, context_id, ' .
             'assessment_id, is_accessible) ' .
             'VALUES (:consumer, :context, :assessment, :accessible)';
    $query = $this->db->prepare($sql);
    $query->bindValue('consumer', $consumer_key, PDO::PARAM_STR);
    $query->bindValue('context', $resource_link_id, PDO::PARAM_STR);
    $query->bindValue('assessment', $assessment_id, PDO::PARAM_STR);
    $query->bindValue('accessible', $is_accessible, PDO::PARAM_INT);
    $ok = $query->execute();

    return $ok;

  }

###
#    Updates the report configuration to the database
###
  public function ReportConfig_update($consumer_key, $resource_link_id, $assessment_id, $is_accessible) {
    
    $sql = 'UPDATE ' . $this->dbTableNamePrefix . LTI_Data_Connector::REPORTS_TABLE_NAME . ' ' .
             'SET is_accessible = :accessible ' .
             'WHERE (consumer_key = :consumer) AND (context_id = :context) AND (assessment_id = :assessment)';
    $query = $this->db->prepare($sql);
    $query->bindValue('consumer', $consumer_key, PDO::PARAM_STR);
    $query->bindValue('context', $resource_link_id, PDO::PARAM_STR);
    $query->bindValue('assessment', $assessment_id, PDO::PARAM_STR);
    $query->bindValue('accessible', $is_accessible, PDO::PARAM_INT);
    $ok = $query->execute();

    return $ok;

  }


###
###  LTI_User methods
###


###
#    Load the user from the database
###
  public function User_load($user) {

    return TRUE;

  }

###
#    Save the user to the database
###
  public function User_save($user) {

    return TRUE;

  }

###
#    Delete the user from the database
###
  public function User_delete($user) {

    return TRUE;

  }

}

?>
