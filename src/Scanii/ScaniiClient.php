<?php

namespace Scanii;

use GuzzleHttp;
use InvalidArgumentException;
use Scanii\Models\ScaniiAccountInfo;
use Scanii\Models\ScaniiAuthToken;
use Scanii\Models\ScaniiResult;

/**
 * Class ScaniiClient - the https://scanii.com client interface
 * @package Scanii
 */
class ScaniiClient
{
  private GuzzleHttp\Client $httpClient;
  private bool $verbose;

  // version constant, updated by the build process, do not change:
  private const VERSION = '5.2.0';

  /**
   * ScaniiClient private constructor. Please use one of the helper static factory methods instead.
   * @param $key String API key
   * @param $secret String API secret
   * @param bool $verbose turn on verbose mode on the http client and lib
   * @param string $target optional target to be used @see ScaniiTarget, defaults to the nearest target
   */
  private function __construct(string $key, ?string $secret, bool $verbose = false, $target = ScaniiTarget::AUTO)
  {

    if (str_contains($key, ":")) {
      throw new InvalidArgumentException("key cannot contain ':'");
    }

    assert(!str_contains($key, ":"));

    $this->verbose = $verbose;

    // small workaround for guzzle base uri handling
    if (!str_ends_with($target, '/')) {
      $target = $target . '/';
    }

    $this->httpClient = new GuzzleHttp\Client([
      'base_uri' => $target,
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

  public static function createFromToken(ScaniiAuthToken $token, bool $verbose = false, $target = ScaniiTarget::AUTO): ScaniiClient
  {
    return new ScaniiClient($token->getResourceId(), null, $verbose, $target);
  }

  public static function create(string $key, string $secret, bool $verbose = false, $target = ScaniiTarget::AUTO)
  {
    assert(strlen($key) > 0);
    assert(strlen($secret) > 0);
    return new ScaniiClient($key, $secret, $verbose, $target);

  }

  /**
   * Fetches the results of a previously processed file @link <a href="http://docs.scanii.com/v2.1/resources.html#files">http://docs.scanii.com/v2.1/resources.html#files</a>
   * @param $id String processing file id to retrieve results for
   * @return ScaniiResult
   * @throws GuzzleHttp\Exception\GuzzleException
   */
  public function retrieve(string $id): ScaniiResult
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
  public function process(string $path, array $metadata = []): ScaniiResult
  {

    $this->log('processing ' . $path);

    $parts = [
      ['name' => 'file', 'contents' => fopen($path, 'r')]
    ];

    $parts = $this->populateMultipartMetadata($metadata, $parts);

    $this->log('post contents ' . print_r($parts, true));

    $r = $this->httpClient->request('POST', 'files', ["multipart" => $parts]);

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
  public function processAsync(string $path, array $metadata = []): ScaniiResult
  {

    $this->log('processing ' . $path);

    $parts = [
      ['name' => 'file', 'contents' => fopen($path, 'r')]
    ];

    $parts = $this->populateMultipartMetadata($metadata, $parts);

    $this->log('post contents ' . print_r($parts, true));


    $r = $this->httpClient->request('POST', 'files/async', ["multipart" => $parts]);

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
  public function fetch(string $location, string $callback, array $metadata = []): ScaniiResult
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

    $this->log('post contents ' . print_r($post_contents, true));

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
   * @return ScaniiAuthToken @see ScaniiAuthToken
   * @throws GuzzleHttp\Exception\GuzzleException
   */
  public function createAuthToken(int $timeout = 300): ScaniiAuthToken
  {
    assert($timeout > 0);

    $this->log("creating auth token with timeout $timeout");
    $post_contents = [
      'form_params' => [
        'timeout' => $timeout
      ]
    ];

    $this->log('post contents ' . print_r($post_contents, true));
    $r = $this->httpClient->request('POST', 'auth/tokens', $post_contents);

    return new ScaniiAuthToken((string)$r->getBody(), $r->getHeaders());
  }

  /**
   * Deletes a previously created authentication token
   * @param $id string id of the token to be deleted
   * @throws GuzzleHttp\Exception\GuzzleException
   */
  public function deleteAuthToken(string $id): void
  {
    assert(strlen($id) > 0);

    $this->log("deleting auth token $id");
    $this->httpClient->request('DELETE', "auth/tokens/$id");
  }


  /**
   * Retrieves a previously created auth token
   * @param $id string the id of the token to be retrieved
   * @return ScaniiAuthToken
   * @throws GuzzleHttp\Exception\GuzzleException
   */
  public function retrieveAuthToken(string $id): ScaniiAuthToken
  {
    assert(strlen($id) > 0);

    $this->log("retrieving auth token $id");
    $r = $this->httpClient->request('GET', "auth/tokens/$id");

    return new ScaniiAuthToken((string)$r->getBody(), $r->getHeaders());
  }

  /**
   * Returns the client version
   * @return string
   */
  public function getVersion(): string
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

  private function populateMultipartMetadata(array $metadata, array $parts): array
  {
    foreach ($metadata as $key => $val) {
      $parts[] = ['name' => "metadata[$key]", 'contents' => $val, 'headers' => ['Content-Type' => 'text/plain']];
    }
    return $parts;
  }
}


