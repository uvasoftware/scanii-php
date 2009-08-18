<?
/*
 * Sample driver for the ScaniiClient class
 */

require_once('scanii_class.php');

# using getopts for ops
if ( count($argv) < 2  ) 
{
	echo <<<EOF
usage: php scanii.php [options] -f path

valid options:
-u URL          uses a custom api url instead of scanii's default	
-c KEY:SECRET   the API key and secret to be used in the KEY:SECRET format
-f filename     the file to be scanned
-v              runs in verbose mode

EOF;
	exit(1);

}

$options = getopt('vf:u:c:');

if ($options['c'] == "") {
	echo "please supply credentials with -c\n";
	exit(2);
}


$creds = split(':',$options['c']);
$file = $options['f'];


if ( isset($options['v'])  ) {
	echo("running in debug mode\n");
}
echo "using key: $creds[0] and secret: $creds[1]\n";
echo "scanning file: $file\n";

if ( isset($options['v']) ) {
	$client = new ScaniiClient($creds[0],$creds[1], $verbose=1);
} else {
	$client = new ScaniiClient($creds[0],$creds[1]);
}
$resp = $client->scan($file);

if ( isset($options['v']) ) {
	echo("raw response[$resp]\n");
}

$json = json_decode($resp, $assoc=True);

# due to kirkness with th json_decode lib in php I'm not using it here
if ( $json['status'] == 'clean' ) {
	echo "file $file is clean\n";
	exit(0);

} else if ($json['status'] == 'infected' ) {
	echo "file $file is infected, virus: ";
	echo implode($json["virus"]);
	echo "\n";
	exit(0);
}

echo "parsing error, raw response $resp\n";


?>
