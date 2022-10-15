<?php


namespace Scanii\Models;

class ScaniiAccountInfo extends ScaniiResult
{
  private string $name, $billingEmail, $subscription, $creationDate, $modificationDate;
  private array $users = array();
  private array $keys = array();
  private int $balance, $startingBalance;

  /**
   * ScaniiAccountInfo constructor.
   * @param $contents
   * @param $headers
   */
  public function __construct($contents, $headers)
  {
    parent::__construct($contents, $headers);

    $json = $this->json;
    $this->name = $json["name"];
    $this->balance = $json["balance"];
    $this->startingBalance = $json["starting_balance"];
    $this->billingEmail = $json["billing_email"];
    $this->subscription = $json["subscription"] ?? "";
    $this->creationDate = $json["creation_date"];
    $this->modificationDate = $json["modification_date"] ?? "";

    if (array_key_exists('users', $json)) {
      foreach ($json['users'] as $k => $v) {
        $this->users[$k] = new User($v['creation_date'], $v['last_login_date']);
      }
    }

    if (array_key_exists('keys', $json)) {
      foreach ($json['keys'] as $k => $v) {
        $this->keys[$k] = new ApiKey($v['active'], $v['creation_date'], $v['last_seen_date'] ?? "", $v['detection_categories_enabled'], $v['tags']);
      }
    }

  }

  public function getName(): string
  {
    return $this->name;
  }

  public function getBillingEmail(): string
  {
    return $this->billingEmail;
  }

  public function getSubscription(): string
  {
    return $this->subscription;
  }

  public function getCreationDate(): string
  {
    return $this->creationDate;
  }

  public function getModificationDate(): string
  {
    return $this->modificationDate;
  }

  public function getUsers(): array
  {
    return $this->users;
  }

  public function getKeys(): array
  {
    return $this->keys;
  }

  public function getBalance(): int
  {
    return $this->balance;
  }

  public function getStartingBalance(): int
  {
    return $this->startingBalance;
  }
}
