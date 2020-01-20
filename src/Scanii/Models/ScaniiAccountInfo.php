<?php


namespace Scanii\Models;

class User
{
  private $creationDate, $lastLoginDate;

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
  public function getLastLoginDate()
  {
    return $this->lastLoginDate;
  }


}

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

class ScaniiAccountInfo extends ScaniiResult
{
  private $name, $balance, $startingBalance, $billingEmail, $subscription, $creationDate, $modificationDate;
  private $users = array();
  private $keys = array();

  /**
   * ScaniiAccountInfo constructor.
   * @param $contents
   * @param $headers
   */
  public function __construct($contents, $headers)
  {
    parent::__construct($contents, $headers);

    $json = $this->json;

    if (property_exists($json, 'name')) {
      $this->name = $json->name;
    }

    if (property_exists($json, 'balance')) {
      $this->balance = $json->balance;
    }

    if (property_exists($json, 'starting_balance')) {
      $this->startingBalance = $json->starting_balance;
    }

    if (property_exists($json, 'billing_email')) {
      $this->billingEmail = $json->billing_email;
    }

    if (property_exists($json, 'subscription')) {
      $this->subscription = $json->subscription;
    }

    if (property_exists($json, 'creation_date')) {
      $this->creationDate = $json->creation_date;
    }

    if (property_exists($json, 'modification_date')) {
      $this->modificationDate = $json->modification_date;
    }

    if (property_exists($json, 'users')) {
      foreach ($json->users as $k => $v) {
        $this->users[$k] = new User($v->creation_date, $v->last_login_date);
      }
    }

    if (property_exists($json, 'keys')) {
      foreach ($json->keys as $k => $v) {
        $this->keys[$k] = new ApiKey($v->active, $v->creation_date, $v->last_seen_date, $v->detection_categories_enabled, $v->tags);
      }
    }

  }

  /**
   * @return mixed
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   * @param mixed $name
   */
  public function setName($name): void
  {
    $this->name = $name;
  }

  /**
   * @return mixed
   */
  public function getBalance()
  {
    return $this->balance;
  }

  /**
   * @param mixed $balance
   */
  public function setBalance($balance): void
  {
    $this->balance = $balance;
  }

  /**
   * @return mixed
   */
  public function getStartingBalance()
  {
    return $this->startingBalance;
  }

  /**
   * @param mixed $startingBalance
   */
  public function setStartingBalance($startingBalance): void
  {
    $this->startingBalance = $startingBalance;
  }

  /**
   * @return mixed
   */
  public function getBillingEmail()
  {
    return $this->billingEmail;
  }

  /**
   * @param mixed $billingEmail
   */
  public function setBillingEmail($billingEmail): void
  {
    $this->billingEmail = $billingEmail;
  }

  /**
   * @return mixed
   */
  public function getSubscription()
  {
    return $this->subscription;
  }

  /**
   * @param mixed $subscription
   */
  public function setSubscription($subscription): void
  {
    $this->subscription = $subscription;
  }

  /**
   * @return mixed
   */
  public function getCreationDate()
  {
    return $this->creationDate;
  }

  /**
   * @param mixed $creationDate
   */
  public function setCreationDate($creationDate): void
  {
    $this->creationDate = $creationDate;
  }

  /**
   * @return mixed
   */
  public function getModificationDate()
  {
    return $this->modificationDate;
  }

  /**
   * @param mixed $modificationDate
   */
  public function setModificationDate($modificationDate): void
  {
    $this->modificationDate = $modificationDate;
  }

  /**
   * @return mixed
   */
  public function getUsers()
  {
    return $this->users;
  }

  /**
   * @param mixed $users
   */
  public function setUsers($users): void
  {
    $this->users = $users;
  }

  /**
   * @return mixed
   */
  public function getKeys()
  {
    return $this->keys;
  }

  /**
   * @param mixed $keys
   */
  public function setKeys($keys): void
  {
    $this->keys = $keys;
  }
}
