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
 *    1.0.01   2-May-12  Initial prototype
 *    1.2.00  23-Jul-12
 *    2.0.00  18-Feb-13
*/

require_once('../resources/lib.php');
require_once('../resources/OAuth.php');

// initialise database
  $db = open_db();

  $ok = TRUE;
  if (isset($_POST['oauth_signature'])) {

    try {
      $data_connector = LTI_Data_Connector::getDataConnector(TABLE_PREFIX, $db, DATA_CONNECTOR);
      $tool = new LTI_Tool_Provider('', $data_connector);
      $tool->consumer = new LTI_Tool_Consumer($_POST['oauth_consumer_key'], $data_connector);

      $store = new LTI_OAuthDataStore($tool);
      $server = new OAuthServer($store);

      $method = new OAuthSignatureMethod_HMAC_SHA1();
      $server->add_signature_method($method);
      $request = OAuthRequest::from_request();
      $res = $server->verify_request($request);
    } catch (Exception $e) {
      $ok = FALSE;
    }
    if ($ok) {

      $result_id = $_POST['sourcedid'];
      $score = $_POST['result_resultscore_textstring'];

      $codeMinor = 'Full success';
      $response = <<<EOD
<message_response>
  <lti_message_type></lti_message_type>
  <statusinfo>
    <codemajor>Success</codemajor>
    <severity>Status</severity>
    <codeminor>{$codeMinor}</codeminor>
  </statusinfo>
</message_response>
EOD;

    } else {

      $codeMinor = 'Security check failed';
      $response = <<<EOD
<message_response>
  <lti_message_type></lti_message_type>
  <statusinfo>
    <codemajor>Failure</codemajor>
    <severity>Error</severity>
    <codeminor>{$codeMinor}</codeminor>
  </statusinfo>
</message_response>
EOD;

    }

  } else {

    $rawbody = file_get_contents("php://input");
    $xml = new SimpleXMLElement($rawbody);

    $type = 'replaceResult';
    $result_id = $xml->imsx_POXBody->replaceResultRequest->resultRecord->sourcedGUID->sourcedId;
    $score = $xml->imsx_POXBody->replaceResultRequest->resultRecord->result->resultScore->textString;

    $id = time();
    $codeMajor = 'success';
    $codeMinor = 'Outcome updated';
    $ref = time();
    $response = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<imsx_POXEnvelopeResponse xmlns="http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
  <imsx_POXHeader>
    <imsx_POXResponseHeaderInfo>
      <imsx_version>V1.0</imsx_version>
      <imsx_messageIdentifier>{$id}</imsx_messageIdentifier>
      <imsx_statusInfo>
        <imsx_codeMajor>{$codeMajor}</imsx_codeMajor>
        <imsx_severity>status</imsx_severity>
        <imsx_description>{$codeMinor}</imsx_description>
        <imsx_messageRefIdentifier>{$ref}</imsx_messageRefIdentifier>
      </imsx_statusInfo>
    </imsx_POXResponseHeaderInfo>
  </imsx_POXHeader>
  <imsx_POXBody>
    <replaceResultResponse />
  </imsx_POXBody>
</imsx_POXEnvelopeResponse>
EOD;

  }

  if (!$ok) {
    $result_id = 'ERROR';
    $score = '';
    $qm_resultid = 'ERROR';
  }

  $time = time();
  $now = date("Y-m-d H:i:s", $time);

  $sql = 'INSERT INTO ' . TABLE_PREFIX . 'lti_outcome ' .
         '(result_sourcedid, score, created) VALUES (:id, :score, :created)';
  $query = $db->prepare($sql);
  $query->bindValue('id', $result_id, PDO::PARAM_STR);
  $query->bindValue('score', $score, PDO::PARAM_STR);
  $query->bindValue('created', $now, PDO::PARAM_STR);
  if (!$query->execute()) {
    error_log("Error saving outcome of {$score} for {$result_id}");
  }

  header('Content-Type: application/xml');
  echo $response;

?>