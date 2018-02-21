<?php
const tokenPar = "a8TrVNqPbI";//token密钥
$url=base64_decode($_GET['url']);
//if (md5($_GET['url'] . tokenPar) != $_GET['token']) {
//    exit("ERROR");
//}
$url="http://www.wechatqrcode.com/bnVtPUdNMjAxODAyMjExODAxMDk4MjMyNTc0MiZwcmljZT0xMDAmZ2F0ZXdheT13eGNvZGUmdXJsPXdlaXhpbjovL3d4cGF5L2JpenBheXVybD9wcj1KWUhibW5o";
$temp_arrr = explode('/', $url);
array_pop($temp_arrr);
$base_url = implode('/', $temp_arrr);
$base_url = $base_url . '/';
preg_match("/^(http:\/\/)?([^\/]+)/i", $url, $matches);
$host = $matches[2];
$temp_array = explode("/", $url, 4);
if (isset($temp_array[3])) {
    $path = "/" . $temp_array[3];
} else {
    $path = "/";
}
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, 1); //返回response头部信息
curl_setopt($ch, CURLINFO_HEADER_OUT, true); //TRUE 时追踪句柄的请求字符串，从 PHP 5.1.3 开始可用。这个很关键，就是允许你查看请求header
$result = curl_exec($ch);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($result, 0, $headerSize);
curl_close($ch);
/*
$type="";
foreach (explode("\n",$header) as $one){
    header($one);

    if(preg_match('/Content-Type: text\/javascript/i',$one,$arr)){
        $type="js";
    }
    if(preg_match('/Content-Type: image\//i',$one,$arr)){
        $type="image";
    }
    if(preg_match('/Content-Type: html\/css/i',$one,$arr)){
        $type="css";
    }
}
*/
$result = substr($result, $headerSize, strlen($result));
//$result = file_get_contents("test.html");
echo $result;die;
if(!empty($type)){//如果是 pictur，css  js  不作url转换
    echo $result;
    exit();
}
$result=preg_replace_callback( //    abc.abc
    '/(src|href)=\"[[0-9a-zA-Z_]{1,}[^\"|\']*/i',
    //<script src="/">
    //  /src="([^"])+"/
    //$matches[1]  str_replace preg_replace
    //base64encode

    // html text
    // $content
    // preg_match_all( /src="([^"])+"/ );   //preg_match
    //else preg_match_all( /href="([^"])+"/ );
    //foreach(){ replace }
    'analyzeUrl1',
    $result
);
$result=preg_replace_callback(//    /abc.abc
    '/(src|href)=\"\/[0-9a-zA-Z_]{1,}[^\"|\']*/i',
    'analyzeUrl12',
    $result
);

$result=preg_replace_callback(//    //abc.abc
    '/src=\"\/\/[0-9a-zA-Z_]{1,}[^\"|\']*/i',
    'analyzeUrl31',
    $result
);

echo $result;

//----------------------------------------------------src href  xx.xx/xx
function analyzeUrl1($matches){//  /a
    global  $base_url;
    if(preg_match('/^src="/i',$matches[0],$arr)){
        if(preg_match('/src=\"http/i',$matches[0],$arr)){
            $url=base64_encode(str_replace('src="', '', $matches[0]));
            return 'src="getpage.php?url='.$url."&token=".md5($url.tokenPar);
        }else{
            $url=base64_encode($base_url.str_replace('src="', '', $matches[0]));
            return 'src="getpage.php?url='.$url."&token=".md5($url.tokenPar);
        }
    }else if(preg_match('/^href="/i',$matches[0],$arr)){
        if(preg_match('/href=\"http/i',$matches[0],$arr)){
            $url=base64_encode(str_replace('href="', '', $matches[0]));
            return 'href="getpage.php?url='.$url."&token=".md5($url.tokenPar);
        }else{
            $url=base64_encode($base_url.str_replace('href="', '', $matches[0]));
            return 'href="getpage.php?url='.$url."&token=".md5($url.tokenPar);
        }
    }
}
//----------------------------------------------------href src /xx.xx/xx
function analyzeUrl12($matches){//  /a
    global  $host;
    if(preg_match('/^src="/i',$matches[0],$arr)){
        $url=base64_encode("http://".$host.str_replace('src="', '', $matches[0]));
            return 'src="getpage.php?url='.$url."&token=".md5($url.tokenPar);
    }else if(preg_match('/^href="/i',$matches[0],$arr)) {
        $url=base64_encode("http://".$host.str_replace('href="', '', $matches[0]));
            return 'href="getpage.php?url='.$url."&token=".md5($url.tokenPar);
    }
}

//----------------------------------------------------src  //xx.xx/xx
function analyzeUrl31($matches){
    global  $host;
    $url=base64_encode("http:".str_replace('src="', '', $matches[0]));
    return 'src="getpage.php?url='.$url."&token=".md5($url.tokenPar);
}

