<?php

namespace Scanii;

use GuzzleHttp;
use Scanii\Models\ScaniiAccountInfo;
use Scanii\Models\ScaniiResult;

/**
 * Class ScaniiClient - the https://scanii.com client interface
 * @package Scanii
 */
class ScaniiClient
{
  private $verbose, $httpClient;

  // version constant - always update when changes are made:
  const VERSION = '3.1.0';

  /**
   * ScaniiClient constructor.
   * @param $key String API key
   * @param $secret String API secret
   * @param bool $verbose turn on verbose mode on the http client and lib
   * @param string $baseUrl optional base url to be used @see ScaniiTarget
   */
  function __construct($key, $secret, $verbose = false, $baseUrl = ScaniiTarget::v2_1)
  {

    assert(strlen($key) > 0);
    assert(strlen($secret) > 0);

    $this->verbose = $verbose;

    // small workaround for guzzle base uri handling
    if (substr($baseUrl, -1) != '/') {
      $baseUrl = $baseUrl . '/';
    }

    $this->httpClient = new GuzzleHttp\Client([
      'base_uri' => $baseUrl,
      'connect_timeout' => 30,
      'read_timeout' => 30,
      'debug' => $verbose,
      'auth' => [$key, $secret],
      'headers' => [
        'User-Agent' => 'scanii-php/v' . self::VERSION
      ]
    ]);
    return $this;
  }


  /**
   * Fetches the results of a previously processed file @link <a href="http://docs.scanii.com/v2.1/resources.html#files">http://docs.scanii.com/v2.1/resources.html#files</a>
   * @param $id String processing file id to retrieve results for
   * @return ScaniiResult
   * @throws GuzzleHttp\Exception\GuzzleException
   */
  public function retrieve($id): ScaniiResult
  {
    $this->log('loading result ' . $id);

    $res = $this->httpClient->request('GET', 'files/' . $id);

    $this->log('content ' . $res->getBody());
    $this->log('status code ' . $res->getStatusCode());

    return new ScaniiResult((string)$res->getBody(), $res->getHeaders());
  }

  /**
   * Submits a file to be processed @link <a href="http://docs.scanii.com/v2.1/resources.html#files">http://docs.scanii.com/v2.1/resources.html#files</a>
   * @param $path String file path to the file to submit for processing
   * @param array $metadata associative array of custom metadata
   * @return ScaniiResult
   * @throws GuzzleHttp\Exception\GuzzleException
   */
  public function process($path, $metadata = []): ScaniiResult
  {

    $this->log('processing ' . $path);

    $post_contents = [
      'multipart' => [
        [
          'name' => 'file',
          'contents' => fopen($path, 'r')
        ],
      ]
    ];

    foreach ($metadata as $key => $val) {
      array_push($post_contents['multipart'], [
        'name' => "metadata[$key]",
        'contents' => $val
      ]);
    }

    $this->log('post contents ' . var_export($post_contents));

    $r = $this->httpClient->request('POST', 'files', $post_contents);

    $this->log("result message " . $r->getBody());
    return new ScaniiResult((string)$r->getBody(), $r->getHeaders());
  }

  /**
   * Submits a file to be processed @link <a href="http://docs.scanii.com/v2.1/resources.html#files">http://docs.scanii.com/v2.1/resources.html#files</a>
   * @param $path String file path to the file to submit for processing
   * @param array $metadata associative array of custom metadata
   * @return ScaniiResult
   * @throws GuzzleHttp\Exception\GuzzleException
   */
  public function processAsync($path, $metadata = []): ScaniiResult
  {

    $this->log('processing ' . $path);

    $post_contents = [
      'multipart' => [
        [
          'name' => 'file',
          'contents' => fopen($path, 'r')
        ],
      ]
    ];

    foreach ($metadata as $key => $val) {
      array_push($post_contents['multipart'], [
        'name' => "metadata[$key]",
        'contents' => $val
      ]);
    }

    $this->log('post contents ' . var_export($post_contents));

    $r = $this->httpClient->request('POST', 'files/async', $post_contents);

    $this->log("result message " . $r->getBody());
    return new ScaniiResult((string)$r->getBody(), $r->getHeaders());

  }


  /**
   * Makes a fetch call to scanii @link <a href="http://docs.scanii.com/v2.1/resources.html#files">http://docs.scanii.com/v2.1/resources.html#files</a>
   * @param $location String the url of the content to be fetched and processed
   * @param $callback String the callback url to submit the processing result to
   * @param array $metadata associative array of custom metadata
   * @return ScaniiResult
   * @throws GuzzleHttp\Exception\GuzzleException
   */
  public function fetch($location, $callback, $metadata = []): ScaniiResult
  {
    $this->log("fetching $location with callback: $callback");

    $post_contents = [
      'form_params' => [
        'location' => $location
      ]
    ];

    if (!empty($callback)) {
      $post_contents['form_params']['callback'] = $callback;
    }

    foreach ($metadata as $key => $val) {
      $post_contents['form_params']["metadata[$key]"] = $val;
    }

    $this->log('post contents ' . var_export($post_contents));

    $r = $this->httpClient->request('POST', 'files/fetch', $post_contents);

    return new ScaniiResult((string)$r->getBody(), $r->getHeaders());

  }

  /**
   * Pings the scanii service using the credentials provided @link <a href="http://docs.scanii.com/v2.1/resources.html#ping">http://docs.scanii.com/v2.1/resources.html#ping</a>
   * @return bool
   * @throws GuzzleHttp\Exception\GuzzleException
   */
  public function ping(): bool
  {
    $r = $this->httpClient->request('GET', 'ping');
    return $r->getStatusCode() == 200;
  }

  private function log($message)
  {
    if ($this->verbose) {
      echo("[" . date("d-m-Y H:i:s") . "] scanii-> " . $message . "\n");
    }
  }

  /**
   * Creates a new temporary authentication token @link <a href="http://docs.scanii.com/v2.1/resources.html#auth-tokens">http://docs.scanii.com/v2.1/resources.html#auth-tokens</a>
   * @param int $timeout how long the token should be valid for
   * @return ScaniiResult @see ScaniiResult
   * @throws GuzzleHttp\Exception\GuzzleException
   */
  public function createAuthToken($timeout = 300): ScaniiResult
  {
    assert($timeout > 0);

    $this->log("creating auth token with timeout $timeout");
    $post_contents = [
      'form_params' => [
        'timeout' => $timeout
      ]
    ];

    $this->log('post contents ' . var_export($post_contents));
    $r = $this->httpClient->request('POST', 'auth/tokens', $post_contents);

    return new ScaniiResult((string)$r->getBody(), $r->getHeaders());
  }

  /**
   * Deletes a previously created authentication token
   * @param $id string id of the token to be deleted
   * @throws GuzzleHttp\Exception\GuzzleException
   */
  public function deleteAuthToken($id): void
  {
    assert(strlen($id) > 0);

    $this->log("deleting auth token $id");
    $this->httpClient->request('DELETE', "auth/tokens/$id");
  }


  /**
   * Retrieves a previously created auth token
   * @param $id string the id of the token to be retrieved
   * @return ScaniiResult
   * @throws GuzzleHttp\Exception\GuzzleException
   */
  public function retrieveAuthToken($id): ScaniiResult
  {
    assert(strlen($id) > 0);

    $this->log("retrieving auth token $id");
    $r = $this->httpClient->request('GET', "auth/tokens/$id");

    return new ScaniiResult((string)$r->getBody(), $r->getHeaders());
  }

  /**
   * Returns the client version
   * @return string
   */
  public function getVersion(): String
  {
    return self::VERSION;
  }

  /**
   * Returns whether this client is in debug mode or not
   * @return bool
   */
  public function isVerbose(): bool
  {
    return $this->verbose;
  }

  /**
   * Retrieves account information.
   * @return ScaniiAccountInfo
   * @throws GuzzleHttp\Exception\GuzzleException
   */
  public function retrieveAccountInfo(): ScaniiAccountInfo
  {
    $r = $this->httpClient->request('GET', 'account.json');
    return new ScaniiAccountInfo((string)$r->getBody(), $r->getHeaders());
  }
}


