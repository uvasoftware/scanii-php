<?php

namespace Scanii;

use GuzzleHttp;

/**
 * Class ScaniiClient - the https://scanii.com client interface
 * @package Scanii
 */
class ScaniiClient
{
  private $version, $verbose;

  /**
   * ScaniiClient constructor.
   * @param $key String API key
   * @param $secret String API secret
   * @param bool $verbose turn on verbose mode on the http client and lib
   * @param string $base_url optional base url to be used @see ScaniiTarget
   */
  function __construct($key, $secret, $verbose = false, $base_url = ScaniiTarget::v2_1)
  {

    assert(strlen($key) > 0);
    assert(strlen($secret) > 0);

    $this->verbose = $verbose;
    $this->version = 'scanii-php/v2.1';

    // small workaround for guzzle base uri handling
    if (substr($base_url, -1) != '/') {
      $base_url = $base_url . '/';
    }

    $this->http_client = new GuzzleHttp\Client([
      'base_uri' => $base_url,
      'connect_timeout' => 30,
      'read_timeout' => 30,
      'debug' => $verbose,
      'auth' => [$key, $secret],
      'headers' => [
        'User-Agent' => $this->version
      ]
    ]);
  }


  /**
   * Fetches the results of a previously processed file @see <a href="http://docs.scanii.com/v2.1/resources.html#files">http://docs.scanii.com/v2.1/resources.html#files</a>
   * @param $id String processing file id to retrieve results for
   * @return \Psr\Http\Message\StreamInterface
   */
  public function retrieve($id)
  {
    $this->log('loading result ' . $id);

    $res = $this->http_client->request('GET', 'files/' . $id);

    $this->log('content ' . $res->getBody());
    $this->log('status code ' . $res->getStatusCode());

    return $res->getBody();
  }

  /**
   * Submits a file to be processed @see <a href="http://docs.scanii.com/v2.1/resources.html#files">http://docs.scanii.com/v2.1/resources.html#files</a>
   * @param $path String file path to the file to submit for processing
   * @param array $metadata associative array of custom metadata
   * @return \Psr\Http\Message\StreamInterface
   */
  public function process($path, $metadata = [])
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

    $this->log('post contents ' . var_dump($post_contents));

    $r = $this->http_client->request('POST', 'files', $post_contents);

    $this->log("result message " . $r->getBody());
    return $r->getBody();
  }

  /**
   * Submits a file to be processed @see <a href="http://docs.scanii.com/v2.1/resources.html#files">http://docs.scanii.com/v2.1/resources.html#files</a>
   * @param $path String file path to the file to submit for processing
   * @param array $metadata associative array of custom metadata
   * @return \Psr\Http\Message\StreamInterface
   */
  public function process_async($path, $metadata = [])
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

    $this->log('post contents ' . var_dump($post_contents));

    $r = $this->http_client->request('POST', 'files/async', $post_contents);

    $this->log("result message " . $r->getBody());
    return $r->getBody();
  }



  /**
   * Makes a fetch call to scanii @see <a href="http://docs.scanii.com/v2.1/resources.html#files">http://docs.scanii.com/v2.1/resources.html#files</a>
   * @param $location String the url of the content to be fetched and processed
   * @param $callback String the callback url to submit the processing result to
   * @param array $metadata associative array of custom metadata
   * @return \Psr\Http\Message\StreamInterface
   */
  public function fetch($location, $callback, $metadata = [])
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

    $this->log('post contents ' . var_dump($post_contents));

    $r = $this->http_client->request('POST', 'files/fetch', $post_contents);

    return $r->getBody();

  }

  /**
   * Pings the scanii service using the credentials provided @see <a href="http://docs.scanii.com/v2.1/resources.html#ping">http://docs.scanii.com/v2.1/resources.html#ping</a>
   * @return bool
   */
  public function ping(): bool
  {
    $r = $this->http_client->request('GET', 'ping');
    return $r->getStatusCode() == 200;
  }

  private function log($message)
  {
    if ($this->verbose) {
      echo("[" . date("d-m-Y H:i:s") . "] scanii-> " . $message . "\n");
    }
  }

  /**
   * Returns the client version
   * @return string
   */
  public function getVersion(): String
  {
    return $this->version;
  }

  /**
   * Returns whether this client is in debug mode or not
   * @return bool
   */
  public function isVerbose(): bool
  {
    return $this->verbose;
  }


}


