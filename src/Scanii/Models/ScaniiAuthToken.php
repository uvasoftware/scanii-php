<?php

namespace Scanii\Models;

class ScaniiAuthToken extends ScaniiResult
{
  private string $resourceId, $creationDate, $expirationDate;

  public function __construct($contents, $headers)
  {
    parent::__construct($contents, $headers);
    $json = $this->json;
    $this->creationDate = $json["creation_date"];
    $this->expirationDate = $json["expiration_date"];
    $this->resourceId = $json["id"];
  }

  /**
   * @return string
   */
  public function getResourceId(): string
  {
    return $this->resourceId;
  }

  /**
   * @return string
   */
  public function getCreationDate(): string
  {
    return $this->creationDate;
  }

  /**
   * @return string
   */
  public function getExpirationDate(): string
  {
    return $this->expirationDate;
  }

}
