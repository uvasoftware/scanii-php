<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace Scanii;

use PHPUnit\Framework\TestCase;
use ReflectionClass;


class ScaniiClientTest extends TestCase
{
  private $EICAR = 'X5O!P%@AP[4\\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*';
  static private $secret, $key;

  public static function setUpBeforeClass()
  {
    self::$key = explode(':', getenv('SCANII_CREDS'))[0];
    self::$secret = explode(':', getenv('SCANII_CREDS'))[1];

  }

  private function client(): ScaniiClient
  {
    return new ScaniiClient(self::$key, self::$secret, $verbose = true);
  }

  public function testRetrieve()
  {
    $client = $this->client();

    $temp = tempnam(sys_get_temp_dir(), "FOO");
    $fd = fopen($temp, "w");
    fwrite($fd, $this->EICAR);

    $r = $client->process($temp);
    echo(var_dump($r));
    $this->assertNotEmpty($r->getId());

    sleep(1);
    $r2 = $client->retrieve($r->getId());

    $this->assertEquals($r2->getId(), $r->getId());
    $this->assertTrue(strpos($r2->getFindings()[0], "eicar") > -1);

  }

  public function testProcess()
  {
    $client = $this->client();

    $temp = tempnam(sys_get_temp_dir(), "FOO");
    $fd = fopen($temp, "w");
    fwrite($fd, $this->EICAR);

    $r = $client->process($temp);
    echo(var_dump($r));
    $this->assertNotEmpty($r->getId());
    $this->assertNotEmpty($r->getContentLength());
    $this->assertNotEmpty($r->getContentType());
    $this->assertNotEmpty($r->getChecksum());
    $this->assertNotEmpty($r->getCreationDate());
    $this->assertNotEmpty($r->getHostId());
    $this->assertNotEmpty($r->getRequestId());
    $this->assertTrue($r->getFindings()[0] == "content.malicious.eicar-test-signature");
  }

  public function testProcessAsync()
  {
    $client = $this->client();

    $temp = tempnam(sys_get_temp_dir(), "FOO");
    $fd = fopen($temp, "w");
    fwrite($fd, $this->EICAR);

    $r = $client->processAsync($temp);
    echo(var_dump($r));

    $this->assertNotEmpty($r->getId());
    sleep(1);
    // fetching the result:

    $r2 = $client->retrieve($r->getId());
    $this->assertTrue(strpos($r2->getFindings()[0], "eicar") > -1);
    $this->assertEquals($r->getId(), $r2->getId());
  }


  public function testProcessWithMetadata()
  {
    $client = $this->client();

    $temp = tempnam(sys_get_temp_dir(), "FOO");
    $fd = fopen($temp, "w");
    fwrite($fd, $this->EICAR);

    $r = $client->process($temp, [
      "foo" => "bar",
      "hello" => "world"
    ]);

    echo(var_dump($r));
    $this->assertNotEmpty($r->getId());
    $this->assertEquals("bar", $r->getMetadata()->foo);
    $this->assertEquals("world", $r->getMetadata()->hello);
  }

  public function testFetch()
  {
    $client = $this->client();

    $temp = tempnam(sys_get_temp_dir(), "FOO");
    $fd = fopen($temp, "w");
    fwrite($fd, $this->EICAR);

    // submitting the content for processing
    $r = $client->fetch('https://scanii.s3.amazonaws.com/eicarcom2.zip', 'https://httpbin.org/post', [
      'foo' => 'bar',
      'hello' => 'world'
    ]);

    $this->assertNotEmpty($r->getId());

    echo(var_dump($r));
    sleep(1);

    // fetching the result:
    $r2 = $client->retrieve($r->getId());
    $this->assertTrue(strpos($r2->getFindings()[0], "eicar") > -1);
    $this->assertEquals("bar", $r2->getMetadata()->foo);
    $this->assertEquals("world", $r2->getMetadata()->hello);
  }

  public function testChangeBaseUrl()
  {
    $client = new ScaniiClient(self::$key, self::$secret, $verbose = true, ScaniiTarget::v2_1_US1);
    $this->assertTrue($client->ping());

    $client = new ScaniiClient(self::$key, self::$secret, $verbose = true, ScaniiTarget::v2_1_AP1);
    $this->assertTrue($client->ping());

    $client = new ScaniiClient(self::$key, self::$secret, $verbose = true, ScaniiTarget::v2_1_EU1);
    $this->assertTrue($client->ping());
  }

  public function testCreteAuthToken()
  {
    $client = $this->client();
    $result = $client->createAuthToken(10);
    self::assertNotNull($result->getId());
    self::assertNotNull($result->getExpirationDate());
    self::assertNotNull($result->getCreationDate());
  }

  public function testDeleteAuthToken()
  {
    $client = $this->client();
    $result = $client->createAuthToken(10);
    self::assertNotNull($result->getId());
    self::assertNotNull($result->getExpirationDate());
    self::assertNotNull($result->getCreationDate());
    $client->deleteAuthToken($result->getId());
  }

  public function testRetrieveAuthToken()
  {
    $client = $this->client();
    $token = $client->createAuthToken(10);
    self::assertNotNull($token->getId());
    self::assertNotNull($token->getExpirationDate());
    self::assertNotNull($token->getCreationDate());
    $token2 = $client->retrieveAuthToken($token->getId());
    self::assertEquals($token->getId(), $token2->getId());
    self::assertEquals($token->getExpirationDate(), $token2->getExpirationDate());
  }

  public function testPingAllRegions()
  {
    $reflect = new ReflectionClass('Scanii\ScaniiTarget');
    foreach ($reflect->getConstants() as $r) {
      echo 'using target ' . $r;
      $client = new ScaniiClient(self::$key, self::$secret, $verbose = true, $baseUl = $r);
      self::assertTrue($client->ping());
    };
  }

  public function testRetrieveAccountInfo()
  {
    $client = $this->client();
    $account = $client->retrieveAccountInfo();
    self::assertNotNull($account->getName());
    self::assertTrue($account->getBalance() > 0);
    self::assertTrue($account->getStartingBalance() > 0);
    self::assertNotNull($account->getCreationDate());
    self::assertNotNull($account->getModificationDate());
    self::assertNotNull(sizeof($account->getUsers()) > 0);
    self::assertNotNull(sizeof($account->getKeys()) > 0);
  }
}
