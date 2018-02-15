<?php

// This one deals with all the really heavy tasks. Looks for links, image
// tags etc in html pages and replaces them with v3 referenced ones.
// Still gotta keep the code clean and simple to save processing time, but
// this one doesn't handle quite as much data as the getfile script.
//
// All code lisencable under the GPL.
// Code version 0.0.2
// check for cached version of url
const  SERVERPATH="https://nanxiao.cba123.cn/onlineget";
const SERVERCACHE="/var/www/cache/";
const SERVERLOGS="/var/www/logs/pages.log";
const iscache=true;
$url = $_GET['url'];
$directory = substr($url, 0, 18);
if (file_exists(SERVERCACHE."$directory/" . $url)) {
    // if we have a local cache of the url, send that to them
    $cache = fopen(SERVERCACHE."$directory/" . $url, "rb");
    while (!feof($cache)) {
        // read the cached file 128 bytes at a time and return
        $buffer = fread($cache, 128);
        print $buffer;

    }
    exit(0);
}
$dec_url = base64_decode($url);
$log = fopen(SERVERLOGS, "a");
fputs($log, $dec_url . "\n");
fclose($log);
$temp_arrr = explode('/', $dec_url);
array_pop($temp_arrr);
$base_url = implode('/', $temp_arrr);
$base_url = $base_url . '/';
preg_match("/^(http:\/\/)?([^\/]+)/i", $dec_url, $matches);
$host = $matches[2];
$temp_array = explode("/", $dec_url, 4);
//print_r($temp_array);die;
#echo $temp_array[3];
if(isset($temp_array[3])){
    $path = "/" . $temp_array[3];
}else{
    $path = "/" ;
}
// open socket to host
$fp = fsockopen($host, 80, $errno, $errstr, 30);
// die if there was an error connecting
if (!$fp) {
    echo "$errstr ($errno)<br>\n";
} else {
    // send http 1.0 request
    fputs($fp, "GET " . $path . " HTTP/1.0\r\nHost: " . $host . "\r\n\r\n");
    $finished_headers = 0;
    while (!feof($fp)) {
        // if the headers have finished coming, do the funky shit.
        if ($finished_headers == 1) {
            // If we got an html, do the parsing
            if ($type == 'text/html' || $type == 'text/css') {
                while (!feof($fp)) {
                    $line = fgets($fp, 1024);
                    $hacked_line = $line;
                    if (preg_match('/src/i', $line)) {
                        // split by <
                        $element_array = array();
                        $element_array = explode('<', $line);
                        // for each element run a loop
                        for ($a = 0; $a < count($element_array); $a++) {
                            // if the current tag is an image tag
                            if (preg_match('/img/i', $element_array[$a])) {
                                $tarr = array();
                                $tarr = explode('>', $element_array[$a], 2);
                                $tag_arr = array();
                                $tag_arr = explode(' ', $tarr[0]);
                                for ($b = 0; $b < count($tag_arr); $b++) {
                                    if(preg_match('/src="data:/i', $tag_arr[$b])){

                                    }else if (preg_match('/src/i', $tag_arr[$b])) {
                                        $old_el = $tag_arr[$b];
                                        $tag_arr[$b] = str_replace("\"", "", $old_el);
                                        $old_el = $tag_arr[$b];
                                        $tag_arr[$b] = str_replace("src=", "", $old_el);
                                        $old_el = $tag_arr[$b];
                                        $tag_arr[$b] = str_replace("SRC=", "", $old_el);
                                        $old_el = $tag_arr[$b];
                                        $tag_arr[$b] ='src="'.SERVERPATH.'/getfile.php?url=' . analyzeUrl($old_el,$host,$base_url) . '"';
                                    }
                                }
                                $tarr[0] = implode(' ', $tag_arr);
                                $element_array[$a] = implode('>', $tarr);
                            }
                            if (preg_match('/script/i', $element_array[$a])) {
                                $tarr = array();
                                $tarr = explode('>', $element_array[$a], 2);
                                $tag_arr = array();
                                $tag_arr = explode(' ', $tarr[0]);
                                for ($b = 0; $b < count($tag_arr) ; $b++) {
                                    if (preg_match('/src/i', $tag_arr[$b])) {
                                        $old_el = $tag_arr[$b];
                                        $tag_arr[$b] = str_replace("\"", "", $old_el);
                                        $old_el = $tag_arr[$b];
                                        $tag_arr[$b] = str_replace("src=", "", $old_el);
                                        $old_el = $tag_arr[$b];
                                        $tag_arr[$b] = str_replace("SRC=", "", $old_el);
                                        $old_el = $tag_arr[$b];
                                        $tag_arr[$b] ='src="'.SERVERPATH.'/getfile.php?url=' . analyzeUrl($old_el,$host,$base_url) . '"';
                                    }
                                }
                                $tarr[0] = implode(' ', $tag_arr);
                                $element_array[$a] = implode('>', $tarr);
                            }

                        }
                        $hacked_line = implode('<', $element_array);
                    }
                    if (preg_match('/href/i', $hacked_line)) {
                        //echo $hacked_line;
                        $el_array = explode('"', $hacked_line);//print_r($el_array);die;
                        for ($a = 0; $a < count($el_array) - 1; $a++) {
                            if (isset($el_array[$a - 1]) && preg_match('/href/i', $el_array[$a - 1])) {
                                // current element is an href tag
                                $src_old_url = $el_array[$a];
                                $el_array[$a]=SERVERPATH.'/getpage.php?url=' . analyzeUrl($src_old_url,$host,$base_url);
                            }
                        }
                        $hacked_line = implode('"', $el_array);
                    }
                    print $hacked_line;
                }
            }else{
                if (file_exists(SERVERCACHE."$directory")) {
                    $cache = fopen(SERVERCACHE."$directory/" . $url, "wb");
                } else {
                    mkdir(SERVERCACHE."$directory", 0777);
                    $cache = fopen(SERVERCACHE."$directory/" . $url, "wb");
                }
                while (!feof($fp)) {
                    $buffer = fread($fp, 128);
                    fwrite($cache, $buffer);
                    print $buffer;
                }
                fclose($cache);
            }
        }
        $line = fgets($fp, 128);
        if ($finished_headers == 0) {
            if (preg_match("/Location/i", $line)) {
                list($cheader, $location) = explode(" ", $line);
                $location = trim($location);
                $enc_url = base64_encode($location);
                $prox_location = SERVERPATH.'/getpage.php?url=' . $enc_url;
                header("Location: " . $prox_location);
                exit(0);
            }
            if (preg_match("/Content-Type/i", $line)) {
                list($ctype, $type) = explode(" ", $line);
                $type = trim($type);
            }
            if (strlen($line) < 3) {
                $finished_headers = 1;
                header("Content-Type: " . $type);
            }
        }
    }
    // close the socket and exit.
    fclose($fp);
}

function analyzeUrl($old_el,$host,$base_url){
    if (preg_match('/http/i', $old_el)) {
        // url is direct
        $enc_url = base64_encode($old_el);

    } else if (preg_match('/^\/\//i', $old_el)){  //   //
        $temp_url="http:".$old_el;
        $enc_url = base64_encode($temp_url);
    }else if(preg_match('/^\/[0-9a-zA-Z_]{1,}/i', $old_el)){   //   /a
        $temp_url="http://".$host.$old_el;
        $enc_url = base64_encode($temp_url);
    }else{
        // url is not direct
        $temp_url = "$base_url" . $old_el;
        $enc_url = base64_encode($temp_url);
    }
    return $enc_url;
}


?>