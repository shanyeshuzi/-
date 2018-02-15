<?php

// This is the script that gets called lots of times and handles
// the largest ammount of data. It deals with images and any other 
// kind of file that doesn't need to be parsed. 
//
// All code lisencable under the GPL.
// Code version 0.0.2
const  SERVERPATH="https://nanxiao.cba123.cn/onlineget";
const SERVERCACHE="/var/www/cache/";
// see if we have a cache of this file
$url = $_GET['url'];
$directory = substr($url, 0, 18);
if (file_exists(SERVERCACHE."$directory/".$url)) {
	// if we have a local cache of the url, send that to them
	$cache = fopen(SERVERCACHE."$directory/".$url, "rb");
	while (!feof($cache)) {
		// read the cached file 128 bytes at a time and return
                $buffer = fread($cache, 128);
                print $buffer;

	}
	exit(0);
}

// if there was no cache file, create one and open it for writing
if (file_exists(SERVERCACHE."$directory")) {
	$cache = fopen(SERVERCACHE."$directory/".$url, "wb");
} else {
	mkdir(SERVERCACHE."$directory", 0777);
	$cache = fopen(SERVERCACHE."$directory/".$url, "wb");
}
// decode the base64 encoded url
$dec_url = base64_decode($url);



// get host name from URL
preg_match("/^(http:\/\/)?([^\/]+)/i", $dec_url, $matches);
$host = $matches[2];

// get path from url
$temp_array = explode("/", $dec_url, 4);
#echo $temp_array[3];
$path = "/".$temp_array[3];

// open socket to host
$fp = fsockopen ($host, 80, $errno, $errstr, 30);

// die if there was an error connecting
if (!$fp) {
    echo "$errstr ($errno)<br>\n";

} else {

// send http 1.0 request
    fputs ($fp, "GET ".$path." HTTP/1.0\r\nHost: ".$host."\r\n\r\n");
    $finished_headers = 0;
    while (!feof($fp)) {

// if the headers have finished coming, dump binary data directly
// into the browser 128 bytes at a time. Buffer size kept at 128 bytes
// to save memory and keep this program fast.
	if ($finished_headers == 1) {
		while (!feof($fp)) {
			$buffer = fread($fp, 128);
			fwrite($cache, $buffer);
			print $buffer;
		}
	}

// if the headers are still coming, look for the content type header
// and save that in a variable
        $line = fgets ($fp, 128);

	if ($finished_headers == 0) {
		
                if (preg_match("/Location/i", $line)) {
                        list($cheader,$location) = explode(" ",$line);
                        $location = trim($location);
                        $enc_url = base64_encode($location);
                        $prox_location = SERVERPATH.'/getpage.php?url='.$enc_url;
                        header("Location: ".$prox_location);
                        exit(0);
                }

		if (preg_match("/Content-Type/i", $line)) {
			list($ctype,$type) = explode(" ",$line);
			$type = trim($type);
		}
	
		if(strlen($line)<3) {
			$finished_headers=1;
                	header("Content-Type: ".$type);

		}
	}
    }

// close the socket and exit.
    fclose ($fp);
    fclose ($cache);
}

?>