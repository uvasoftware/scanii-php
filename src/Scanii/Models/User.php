<?php

namespace Scanii\Models;

class User
{
  private string $creationDate, $lastLoginDate;

  /**
   * User constructor.
   * @param $creationDate
   * @param $lastLoginDate
   */
  public function __construct($creationDate, $lastLoginDate)
  {
    $this->creationDate = $creationDate;
    $this->lastLoginDate = $lastLoginDate;
  }

  public function getCreationDate(): string
  {
    return $this->creationDate;
  }

  public function getLastLoginDate(): string
  {
    return $this->lastLoginDate;
  }
}
