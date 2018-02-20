<?php
const  SERVERPATH = ".";//在线代理部署地址
const tokenPar = "a8TrVNqPbI";//token密钥
const SERVERCACHE = "./";//缓存地址
const SERVERLOGS = "./pages.log";//log
const iscache = false;//是否缓存
$url = $_GET['url'];
if (md5($url . tokenPar) != $_GET['token']) {
    exit("ERROR");
}
$dec_url = base64_decode($url);
writeLog(SERVERLOGS, $dec_url);
$temp_arrr = explode('/', $dec_url);
array_pop($temp_arrr);
$base_url = implode('/', $temp_arrr);
$base_url = $base_url . '/';
preg_match("/^(http:\/\/)?([^\/]+)/i", $dec_url, $matches);
$host = $matches[2];
$temp_array = explode("/", $dec_url, 4);
if (isset($temp_array[3])) {
    $path = "/" . $temp_array[3];
} else {
    $path = "/";
}
if (!isset($_GET['file'])) {
    $directory = substr($url, 0, 18);
    if (iscache && file_exists(SERVERCACHE . "$directory/" . $url)) {
        openByCache(SERVERCACHE . "$directory/" . $url);
        exit(0);
    }
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
                if (strpos($type, 'text/html') === 0) {
                    while (!feof($fp)) {
                        $line = fgets($fp, 1024);
                        $hacked_line = $line;
                        if (preg_match('/src/i', $line)) {
                            $hacked_line = switchSrc($line, $base_url, $host);
                        }
                        if (preg_match('/href/i', $hacked_line)) {
                            $hacked_line = switchHref($hacked_line, $base_url, $host);
                        }
                        print $hacked_line;
                    }
                } else {
                    if (iscache) {
                        if (file_exists(SERVERCACHE . "$directory")) {
                            $cache = fopen(SERVERCACHE . "$directory/" . $url, "wb");
                        } else {
                            mkdir(SERVERCACHE . "$directory", 0777);
                            $cache = fopen(SERVERCACHE . "$directory/" . $url, "wb");
                        }
                    }
                    while (!feof($fp)) {
                        $buffer = fread($fp, 128);
                        iscache && fwrite($cache, $buffer);
                        print $buffer;
                    }
                    iscache && fclose($cache);
                }
            }
            $line = fgets($fp, 128);
            if ($finished_headers == 0) {
                if (preg_match("/Location/i", $line)) {
                    list($cheader, $location) = explode(" ", $line);
                    $location = trim($location);
                    $enc_url = base64_encode($location);
                    $prox_location = SERVERPATH . '/getpage.php?url=' . $enc_url;
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
} else {
    $directory = substr($url, 0, 18);
    if (iscache) {
        if (file_exists(SERVERCACHE . "$directory/" . $url)) {
            openByCache(SERVERCACHE . "$directory/" . $url);
            exit(0);
        } else if (file_exists(SERVERCACHE . "$directory")) {
            $cache = fopen(SERVERCACHE . "$directory/" . $url, "wb");
        } else {
            mkdir(SERVERCACHE . "$directory", 0777);
            $cache = fopen(SERVERCACHE . "$directory/" . $url, "wb");
        }
    }
    // open socket to host
    $fp = fsockopen($host, 80, $errno, $errstr, 30);
    // die if there was an error connecting
    if (!$fp) {
        echo "$errstr ($errno)<br>\n";
    } else {
        fputs($fp, "GET " . $path . " HTTP/1.0\r\nHost: " . $host . "\r\n\r\n");
        $finished_headers = 0;
        while (!feof($fp)) {
            if ($finished_headers == 1) {
                while (!feof($fp)) {
                    $buffer = fread($fp, 128);
                    isset($cache) && fwrite($cache, $buffer);
                    print $buffer;
                }
            }
            $line = fgets($fp, 128);
            if ($finished_headers == 0) {
                if (preg_match("/Location/i", $line)) {
                    list($cheader, $location) = explode(" ", $line);
                    $location = trim($location);
                    $enc_url = base64_encode($location);
                    $prox_location = SERVERPATH . '/getpage.php?url=' . $enc_url;
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
        fclose($fp);
        isset($cache) && fclose($cache);
    }
}
function analyzeUrl($old_el, $host, $base_url)//url替换解析
{
    if (preg_match('/http/i', $old_el)) {
        // url is direct
        $urlbase64 = base64_encode($old_el);
        $enc_url = $urlbase64 . "&token=" . md5($urlbase64 . tokenPar);

    } else if (preg_match('/^\/\//i', $old_el)) {  //   //
        $temp_url = "http:" . $old_el;
        $urlbase64 = base64_encode($temp_url);
        $enc_url = $urlbase64 . "&token=" . md5($urlbase64 . tokenPar);
    } else if (preg_match('/^\/[0-9a-zA-Z_]{1,}/i', $old_el)) {   //   /a
        $temp_url = "http://" . $host . $old_el;
        $urlbase64 = base64_encode($temp_url);
        $enc_url = $urlbase64 . "&token=" . md5($urlbase64 . tokenPar);
    } else {
        // url is not direct
        $temp_url = "$base_url" . $old_el;
        $urlbase64 = base64_encode($temp_url);
        $enc_url = $urlbase64 . "&token=" . md5($urlbase64 . tokenPar);
    }
    return $enc_url;
}

function switchSrc($line, $base_url, $host)
{//处理src资源
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
                if (preg_match('/src="data:/i', $tag_arr[$b])) {

                } else if (preg_match('/src/i', $tag_arr[$b])) {
                    $old_el = $tag_arr[$b];
                    $tag_arr[$b] = str_replace("\"", "", $old_el);
                    $old_el = $tag_arr[$b];
                    $tag_arr[$b] = str_replace("src=", "", $old_el);
                    $old_el = $tag_arr[$b];
                    $tag_arr[$b] = str_replace("SRC=", "", $old_el);
                    $old_el = $tag_arr[$b];
                    $tag_arr[$b] = 'src="' . SERVERPATH . '/getpage.php?file=1&url=' . analyzeUrl($old_el, $host, $base_url) . '"';
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
            for ($b = 0; $b < count($tag_arr); $b++) {
                if (preg_match('/src/i', $tag_arr[$b])) {
                    $old_el = $tag_arr[$b];
                    $tag_arr[$b] = str_replace("\"", "", $old_el);
                    $old_el = $tag_arr[$b];
                    $tag_arr[$b] = str_replace("src=", "", $old_el);
                    $old_el = $tag_arr[$b];
                    $tag_arr[$b] = str_replace("SRC=", "", $old_el);
                    $old_el = $tag_arr[$b];
                    $tag_arr[$b] = 'src="' . SERVERPATH . '/getpage.php?file=1&url=' . analyzeUrl($old_el, $host, $base_url) . '"';
                }
            }
            $tarr[0] = implode(' ', $tag_arr);
            $element_array[$a] = implode('>', $tarr);
        }
    }
    $hacked_line = implode('<', $element_array);
    return $hacked_line;
}

function switchHref($hacked_line, $base_url, $host)
{//处理href资源
    //echo $hacked_line;
    $el_array = explode('"', $hacked_line);//print_r($el_array);die;
    for ($a = 0; $a < count($el_array) - 1; $a++) {
        if (isset($el_array[$a - 1]) && preg_match('/href/i', $el_array[$a - 1])) {
            // current element is an href tag
            $src_old_url = $el_array[$a];
            $el_array[$a] = SERVERPATH . '/getpage.php?url=' . analyzeUrl($src_old_url, $host, $base_url);
        }
    }
    $hacked_line = implode('"', $el_array);
    return $hacked_line;
}

function openByCache($src)
{//从缓存读取
    $cache = fopen($src, "rb");
    while (!feof($cache)) {
        // read the cached file 128 bytes at a time and return
        $buffer = fread($cache, 128);
        print $buffer;
    }
    fclose($cache);
}

function writeLog($SERVERLOGS, $dec_url)
{
    $log = fopen($SERVERLOGS, "a");
    fputs($log, $dec_url . "\n");
    fclose($log);
}

?>