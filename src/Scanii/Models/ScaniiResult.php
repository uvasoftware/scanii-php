<?php

namespace Scanii\Models;

use Scanii\Internal\HttpHeaders;

class ScaniiResult
{
  private string $rawResponse, $contentType, $contentLength, $resourceLocation, $requestId, $hostId, $checksum;
  private string $message, $expirationDate, $creationDate, $id;
  private array $metadata;
  private array $findings;
  protected array $json;

  /**
   * ScaniiResult constructor.
   * @param $contents
   * @param $headers
   */
  public function __construct($contents, $headers)
  {
    $json = json_decode($contents, true);

    $this->rawResponse = $contents;
    $this->checksum = $json["checksum"] ?? "";
    $this->contentLength = $json["content_length"] ?? "";
    $this->contentType = $json["content_type"] ?? "";
    $this->creationDate = $json["creation_date"] ?? "";
    $this->findings = $json["findings"] ?? [];
    $this->metadata = $json["metadata"] ?? [];
    $this->expirationDate = $json["expiration_date"] ?? "";
    $this->message = $json["message"] ?? "";
    $this->id = $json["id"] ?? "";

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


  public function getRawResponse(): string
  {
    return $this->rawResponse;
  }

  public function getContentType(): string
  {
    return $this->contentType;
  }

  public function getContentLength(): string
  {
    return $this->contentLength;
  }

  public function getResourceLocation()
  {
    return $this->resourceLocation;
  }

  public function getRequestId(): string
  {
    return $this->requestId;
  }

  public function getHostId()
  {
    return $this->hostId;
  }

  public function getChecksum(): string
  {
    return $this->checksum;
  }

  public function getMessage(): string
  {
    return $this->message;
  }

  public function getExpirationDate(): string
  {
    return $this->expirationDate;
  }

  public function getCreationDate(): string
  {
    return $this->creationDate;
  }

  public function getMetadata(): array
  {
    return $this->metadata;
  }

  public function getFindings(): array
  {
    return $this->findings;
  }

  public function getId(): string
  {
    return $this->id;
  }
}
