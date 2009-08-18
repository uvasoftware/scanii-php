<?php
/*
 * Copyright 2009 Uva Software, LLC. For licensing see LICENSE
*/
class ScaniiClient 
{
	function __construct($key, $secret, $verbose=0) {
		$this->key = $key;
		$this->secret = $secret;

		# you can always change this later
		$this->url = "http://scanii.com/a/s/1/";

		$this->verbose = $verbose;
	}
	public function apiCall($data)
	{
		$ch = curl_init($this->url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_USERAGENT, 'scanii-php');
		curl_setopt($ch, CURLOPT_USERPWD, "$this->key:$this->secret");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		if ($this->verbose == 1 ) {
			curl_setopt($ch, CURLOPT_VERBOSE, 1); 
		}else {
			curl_setopt($ch, CURLOPT_MUTE, 1); 
		}
		
		$resp = curl_exec($ch);
		#echo "RESPONSE:$resp\n";
		curl_close($ch);
		return($resp);
		
		
	}
		
	public function scan($filename) 
	{
		$data = file_get_contents($filename);
		return($this->apiCall($data));
	}
}

?>
