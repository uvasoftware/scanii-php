<?php

namespace Scanii\Models;

use Scanii\Internal\HttpHeaders;

class ScaniiResult
{
  private $rawResponse, $resourceId, $contentType, $contentLength, $resourceLocation, $requestId, $hostId, $checksum;
  private $message, $expirationDate, $creationDate, $id;
  private $metadata;
  private $findings = array();
  protected $json;

  /**
   * ScaniiResult constructor.
   * @param $contents
   * @param $headers
   */
  public function __construct($contents, $headers)
  {
    $json = json_decode($contents);

    $this->rawResponse = $contents;

    if (property_exists($json, 'checksum')) {
      $this->checksum = $json->checksum;
    }

    if (property_exists($json, 'content_length')) {
      $this->contentLength = $json->content_length;
    }

    if (property_exists($json, 'content_type')) {
      $this->contentType = $json->content_type;
    }

    if (property_exists($json, 'creation_date')) {
      $this->creationDate = $json->creation_date;
    }

    if (property_exists($json, 'findings')) {
      $this->findings = $json->findings;
    }

    if (property_exists($json, 'metadata')) {
      $this->metadata = $json->metadata;
    }

    if (property_exists($json, 'expiration_date')) {
      $this->expirationDate = $json->expiration_date;
    }

    if (property_exists($json, 'message')) {
      $this->message = $json->message;
    }

    if (property_exists($json, 'id')) {
      $this->id = $json->id;
    }

    if (array_key_exists(HttpHeaders::LOCATION, $headers)) {
      $this->resourceLocation = $headers[HttpHeaders::LOCATION][0];
    }

    if (array_key_exists(HttpHeaders::X_HOST_HEADER, $headers)) {
      $this->hostId = $headers[HttpHeaders::X_HOST_HEADER][0];
    }

    if (array_key_exists(HttpHeaders::X_REQUEST_HEADER, $headers)) {
      $this->requestId = $headers[HttpHeaders::X_REQUEST_HEADER][0];
    }
    $this->json = $json;
  }


  /**
   * @return mixed
   */
  public function getRawResponse()
  {
    return $this->rawResponse;
  }

  /**
   * @return mixed
   */
  public function getResourceId()
  {
    return $this->resourceId;
  }


  /**
   * @return mixed
   */
  public function getContentType()
  {
    return $this->contentType;
  }


  /**
   * @return mixed
   */
  public function getContentLength()
  {
    return $this->contentLength;
  }


  /**
   * @return mixed
   */
  public function getResourceLocation()
  {
    return $this->resourceLocation;
  }


  /**
   * @return mixed
   */
  public function getRequestId()
  {
    return $this->requestId;
  }


  /**
   * @return mixed
   */
  public function getHostId()
  {
    return $this->hostId;
  }

  /**
   * @return mixed
   */
  public function getChecksum()
  {
    return $this->checksum;
  }

  /**
   * @return mixed
   */
  public function getMessage()
  {
    return $this->message;
  }

  /**
   * @return mixed
   */
  public function getExpirationDate()
  {
    return $this->expirationDate;
  }

  /**
   * @return mixed
   */
  public function getCreationDate()
  {
    return $this->creationDate;
  }

  /**
   * @return mixed
   */
  public function getMetadata()
  {
    return $this->metadata;
  }

  /**
   * @return mixed
   */
  public function getFindings()
  {
    return $this->findings;
  }


  /**
   * @return mixed
   */
  public function getId()
  {
    return $this->id;
  }
}
