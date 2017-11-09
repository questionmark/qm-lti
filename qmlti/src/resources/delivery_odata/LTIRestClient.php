<?php

/**
 * @file
 * Copyright (C) 2016 Questionmark Computing Limited.
 *
 * License GNU GPL version 2 or later (see LICENSE.TXT file)
 * There is NO WARRANTY, to the extent permitted by law.
 */

require_once(  dirname(__FILE__) . '/../php-restclient/restclient.php');

class LTIRestClient {

  private $api = NULL;

  /**
   * RestClient constructor.
   */
  public function __construct($customer_id, $url, $qmwise_username, $qmwise_password) {
    $this->api = new RestClient([
      'base_url' => $url,
      'username' => $qmwise_username,
      'password' => $qmwise_password,
      'curl_options' => [
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLINFO_HEADER_OUT => TRUE,
        CURLOPT_HEADER => TRUE,
        CURLOPT_SSLVERSION => 'CURL_SSLVERSION_SSLv4',
        CURLOPT_FOLLOWLOCATION => TRUE
      ]
    ]);
  }

  public function callApi($endpoint, $method, $params = null, $headers = null) {
    if ($method == 'GET') {
      $result = $this->api->get($endpoint, $params, $headers);
    } else if ($method == 'POST') {
      $result = $this->api->post($endpoint, $params, $headers);
    } else {
      return false;
    }

    if ($result->info->http_code == 200) {
      return $result->decode_response();
    } else {
      error_log(print_r($result, 1));
      return $result;
    }

  }

}
