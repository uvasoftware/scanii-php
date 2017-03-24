### A pure PHP interface to the Scanii content processing service - https://scanii.com

### How to use this client

#### Installing using composer:

```
{
   "require": {
      "uvasoftware/scanii-php": "~2.1"
   }
}
```

### Basic usage:

```
 // creating the client
 $client = new ScaniiClient($this->key, $this->secret, $verbose = true);

 // scans a file
 $temp = tempnam(sys_get_temp_dir(), "FOO");
 $fd = fopen($temp, "w");
 fwrite($fd, $this->EICAR);

 $r = json_decode($this->client->process($temp));
```

Please note that you will need a valid scanii.com account and API Credentials.

More advanced usage examples can be found [here](https://github.com/uvasoftware/scanii-php/blob/master/tests/Scanii/ScaniiClientTest.php)

General documentation on scanii can be found [here](http://docs.scanii.com)

This library supports PHP 7 and above.

