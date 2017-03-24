<?php

namespace Scanii;

use PHPUnit\Framework\TestCase;

class ScaniiClientTest extends TestCase
{
  private $EICAR = 'X5O!P%@AP[4\\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*';
  private $secret, $key, $client;

  public function __construct()
  {
    $this->key = explode(':', getenv('SCANII_CREDS'))[0];
    $this->secret = explode(':', getenv('SCANII_CREDS'))[1];
    echo("using key:" . $this->key);
    $this->client = new ScaniiClient($this->key, $this->secret, $verbose = true);

  }

  public function test_retrieve()
  {

    $temp = tempnam(sys_get_temp_dir(), "FOO");
    $fd = fopen($temp, "w");
    fwrite($fd, $this->EICAR);

    $r = json_decode($this->client->process($temp));
    echo(var_dump($r));
    $this->assertNotEmpty($r->id);

    $r2 = json_decode($this->client->retrieve($r->id));

    $this->assertEquals($r2->id, $r->id);
    $this->assertTrue($r2->findings[0] == "content.malicious.eicar-test-signature");

  }

  public function test_process()
  {

    $temp = tempnam(sys_get_temp_dir(), "FOO");
    $fd = fopen($temp, "w");
    fwrite($fd, $this->EICAR);

    $r = json_decode($this->client->process($temp));
    echo(var_dump($r));
    $this->assertNotEmpty($r->id);
    $this->assertTrue($r->findings[0] == "content.malicious.eicar-test-signature");
  }

  public function test_process_async()
  {

    $temp = tempnam(sys_get_temp_dir(), "FOO");
    $fd = fopen($temp, "w");
    fwrite($fd, $this->EICAR);

    $r = json_decode($this->client->process_async($temp));
    echo(var_dump($r));

    $this->assertNotEmpty($r->id);

    // fetching the result:

    $r2 = json_decode($this->client->retrieve($r->id));
    $this->assertTrue($r2->findings[0] == "content.malicious.eicar-test-signature");
    $this->assertEquals($r->id, $r2->id);
  }


  public function test_process_with_metadata()
  {

    $temp = tempnam(sys_get_temp_dir(), "FOO");
    $fd = fopen($temp, "w");
    fwrite($fd, $this->EICAR);

    $r = json_decode($this->client->process($temp, [
      "foo" => "bar",
      "hello" => "world"
    ]));

    echo(var_dump($r));
    $this->assertNotEmpty($r->id);
    $this->assertEquals("bar", $r->metadata->foo);
    $this->assertEquals("world", $r->metadata->hello);
  }

  public function test_fetch()
  {

    $temp = tempnam(sys_get_temp_dir(), "FOO");
    $fd = fopen($temp, "w");
    fwrite($fd, $this->EICAR);

    // submitting the content for processing
    $r = json_decode($this->client->fetch('https://scanii.s3.amazonaws.com/eicarcom2.zip', 'https://httpbin.org/post', [
      'foo' => 'bar',
      'hello' => 'world'
    ]));

    $this->assertNotEmpty($r->id);

    echo(var_dump($r));
    // fetching the result:

    $r2 = json_decode($this->client->retrieve($r->id));
    $this->assertTrue($r2->findings[0] == "content.malicious.eicar-test-signature");
    $this->assertEquals("bar", $r2->metadata->foo);
    $this->assertEquals("world", $r2->metadata->hello);
  }

  public function test_change_base_url()
  {
    $client = new ScaniiClient($this->key, $this->secret, $verbose = true, ScaniiTarget::v2_1_US1);
    $this->assertTrue($client->ping());

    $client = new ScaniiClient($this->key, $this->secret, $verbose = true, ScaniiTarget::v2_1_AP1);
    $this->assertTrue($client->ping());

    $client = new ScaniiClient($this->key, $this->secret, $verbose = true, ScaniiTarget::v2_1_EU1);
    $this->assertTrue($client->ping());
  }

}
