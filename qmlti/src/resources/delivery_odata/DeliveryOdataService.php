<?php
/**
 * Copyright (C) 2016 Questionmark Computing Limited.
 * License GNU GPL version 2 or later (see LICENSE.TXT file)
 * There is NO WARRANTY, to the extent permitted by law.
 */

require_once "LTIRestClient.php";

/**
 * Class DeliveryOdataService
 *
 * Provides a valid Delivery oData endpoint for interfacing between the LTI and the endpoint. The documentation for supporting this
 * interface can be found at https://questionmark.github.io/qm-oap-docs/deliveryodata.html.
 */
class DeliveryOdataService  {

  private $ServiceEndpoint = '';
  private $ServiceName = 'Delivery Odata Service';
  private $RestClient = NULL;

  /**
   * Constructor
   *
   * @param String $customer_id The ID of the customer to be be used in generating the URL
   * @param String $url The URL of the DeliveryOData endpoint to access
   * @param String $qmwise_username The username to be used for security purposes
   * @param String $qmwise_password The password to be used for security purposes
   */
  public function __construct($customer_id, $url, $qmwise_username, $qmwise_password) {
    $this->RestClient = new LTIRestClient($customer_id, $url, $qmwise_username, $qmwise_password);
    $this->ServiceEndpoint = $url;
  }

  /**
   * Gets the assessment details from the assessment ID, or gets a list of assessments. Uses assessments feed.
   *
   * @param Integer $id The ID of the assessment to be used.
   *
   * @return Array of assessments with matching IDs
   */
  function GetAssessment($id = null) {
    $endpoint = 'Assessments';
    if (isset($id)) {
      $endpoint .= "?\$filter=" . urlencode("ID eq " . $id . "L");
    }
    $method = "GET";
    return $this->RestClient->callApi($endpoint, $method);
  }

  /**
   * Gets external attempt using attempts feed. External attempt ID is used to create an URL to access the assessment.
   *
   * @param Integer $externalAttemptID
   * @param Integer $assessmentID
   * @param Integer $participantID
   *
   * @return Array of attempts with matching IDs
   */
  function GetAttemptID($externalAttemptID, $assessmentID, $participantID) {
    $endpoint = 'Attempts?\$filter=' . urlencode("ExternalAttemptID eq " . $externalAttemptID . "L&AssessmentID eq " . $assessmentID . "L&ParticipantID eq " . $participantID . "L");
    $method = "GET";
    return $this->RestClient->callApi($endpoint, $method);
  }

  /**
   * Gets a singular attempt given an attempt id.
   *
   * @param Integer $attemptID
   *
   * @return Attempt object
   */
  function GetAttempt($attemptID) {
    $endpoint = 'Attempts(' . $attemptID . ')';
    $method = "GET";
    return $this->RestClient->callApi($endpoint, $method);
  }

  /**
   * Sets a singular attempt.
   *
   * @param Integer $externalAttemptID
   * @param Integer $assessmentID
   * @param Integer $participantID
   *
   * @return Attempt object
   */
  function SetAttempt($externalAttemptID, $assessmentID, $participantID) {
    $endpoint = "Attempts";
    $params = (object) array(
      "ExternalAttemptID" => $externalAttemptID,
      "AssessmentID" => $assessmentID,
      "ParticipantID" => $participantID,
      "LockStatus" => false,
      "LockRequired" => false
    );
    $params = json_encode($params);
    $method = "POST";
    $headers = array(
      "Content-Type" => "application/json"
    );
    return $this->RestClient->callApi($endpoint, $method, $params, $headers);
  }

}

