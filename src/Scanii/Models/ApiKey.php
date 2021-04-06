<?php

namespace Scanii\Models;

class ApiKey
{
  private $active, $creationDate, $lastSeenDate;
  private $detectionCategoriesEnabled = array();
  private $tags = array();

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

  /**
   * @return mixed
   */
  public function getActive()
  {
    return $this->active;
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
  public function getLastSeenDate()
  {
    return $this->lastSeenDate;
  }

  /**
   * @return array
   */
  public function getDetectionCategoriesEnabled(): array
  {
    return $this->detectionCategoriesEnabled;
  }

  /**
   * @return array
   */
  public function getTags(): array
  {
    return $this->tags;
  }
}
