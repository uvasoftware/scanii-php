### A pure PHP interface to the Scanii content processing service - https://scanii.com

### How to use this client

#### Installing using composer:

```
{
   "require": {
      "uvasoftware/scanii-php": "~$LATEST_RELEASE_VERSION"
   }
}
```

### Basic usage:

```php
 use Scanii\ScaniiClient;
 // creating the client
 $client = ScaniiClient::create($this->key, $this->secret, $verbose = true);

 // scans a file
 $temp = tempnam(sys_get_temp_dir(), "FOO");
 $fd = fopen($temp, "w");
 fwrite($fd, $this->EICAR);

 $result = $this->client->process($temp);
 echo($result->getFindings()[0]);

```

Please note that you will need a valid scanii.com account and API Credentials.

More advanced usage examples can be found [here](https://github.com/uvasoftware/scanii-php/blob/master/tests/Scanii/ScaniiClientTest.php)

More general documentation on scanii can be found [here](http://docs.scanii.com)

This library supports PHP 7.4 and above.

