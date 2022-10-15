<?php

namespace Scanii\Models;

class ApiKey
{
  private string $active, $creationDate, $lastSeenDate;
  private array $detectionCategoriesEnabled = array();
  private array $tags = array();

  /**
   * ApiKey constructor.
   * @param $active
   * @param $creationDate
   * @param $lastSeenDate
   * @param array $detectionCategoriesEnabled
   * @param array $tags
   */
  public function __construct($active, $creationDate, $lastSeenDate, array $detectionCategoriesEnabled, array $tags)
  {
    $this->active = $active;
    $this->creationDate = $creationDate;
    $this->lastSeenDate = $lastSeenDate;
    $this->detectionCategoriesEnabled = $detectionCategoriesEnabled;
    $this->tags = $tags;
  }

  public function getActive(): string
  {
    return $this->active;
  }

  public function getCreationDate(): string
  {
    return $this->creationDate;
  }

  public function getLastSeenDate(): string
  {
    return $this->lastSeenDate;
  }

  public function getDetectionCategoriesEnabled(): array
  {
    return $this->detectionCategoriesEnabled;
  }

  public function getTags(): array
  {
    return $this->tags;
  }
}
