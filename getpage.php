<?php
class Request{
    protected $url;
    protected $base_url;
    protected $host;
    protected $path;
    protected $sMethod;
    protected $requestHeader;
    protected $responseHeader;
    protected $responseData;
    protected $postData;
    protected $type;
    protected $http_code;
    protected $tokenPar;
    public function  recevieFromClient(){//获得客户端的请求
        $this->tokenPar = "a8TrVNqPbI";//token密钥
        $url=base64_decode($_GET['url']);
        if (md5($_GET['url'] . $this->tokenPar) != $_GET['token']) {
            exit("ERROR");
        }
        $this->url=$url;
        $temp_arrr = explode('/', $url);
        array_pop($temp_arrr);
        $base_url = implode('/', $temp_arrr);
        $this->base_url = $base_url . '/';
        preg_match("/^(http:\/\/)?([^\/]+)/i", $url, $matches);
        $this->host = $matches[2];
        $temp_array = explode("/", $url, 4);
        if (isset($temp_array[3])) {
            $this->path = "/" . $temp_array[3];
        } else {
            $this->path = "/";
        }
        if(!empty($_POST)){
            $this->sMethod="POST";
            $this->postData=$_POST;
        }else{
            $this->sMethod="GET";
        }
        $this->requestHeader=$this->getAllHeaders();
    }
    public function  beforeSend(){//发送前准备工作
        $tempHeader=[];
        foreach ($this->requestHeader as $key=>$one){
            if (in_array($key, ['User-Agent'])) {
                $tempHeader[] = $key . ": " . $one;
            }
        }
        $this->requestHeader=$tempHeader;
    }
    public function  sendToRemote(){//发送给远端服务器
       if($this->sMethod == "GET"){
            $this->get($this->url);
        } else{
            $this->post($this->url,$this->postData);
        }
    }
    public function process(){//文档处理
        foreach ($this->responseHeader as $one){
            if(preg_match('/Content-Length/i',$one,$arr)){//remove the size of content
                continue;
            }
            if(preg_match('/location/i',strtolower($one),$arr)){ //replace url in location
                $tempheaderline=explode("location:",strtolower($one));
                if(count($tempheaderline) > 1){
                    $redirect_url = trim($tempheaderline[1]);
                    header("Location: ".Request::replaceUrl($redirect_url,$this->host,$this->base_url,$this->tokenPar));
                    exit();
                }
            }
            if(preg_match('/Content-Type:/i',$one,$arr)){ //get type from Content-Type
                $contentType = trim(str_replace("Content-Type:","",$one));
                if(preg_match('/image/i',$contentType,$arr)){
                    $this->type="image";
                }else if(preg_match('/css/i',$contentType,$arr)){
                    $this->type="css";
                }else if(preg_match('/javascript/i',$contentType,$arr)){
                    $this->type="js";
                }
            }
            header($one);

        }

        if(!in_array($this->type,['js','image','css'])){  //replace all url of xml
            $parms['host']=$this->host;
            $parms['base_url']=$this->base_url;
            $parms['tokenPar']=$this->tokenPar;
            $this->responseData=preg_replace_callback(
                '/((action|src|href)([\s]*)=([\s]*)[\'|"])([\s]*)([^\s]+)([\'|"])/i',
                function($match) use ($parms){
                    $start=$match[1];
                    $url=$match[6];
                    $end=$match[7];
                    return $start.Request::replaceUrl($url,$parms['host'],$parms['base_url'],$parms['tokenPar']).$end;
                },
                $this->responseData
            );
        }
    }
    public function returnToClient(){//返回给客户端
        print $this->responseData;
    }
    public function getAllHeaders()
    {
        foreach ($_SERVER as $name => $value)
        {
            if (substr($name, 0, 5) == 'HTTP_')
            {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
    public function post($url,$postdata){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 1); //返回response头部信息
        curl_setopt($ch, CURLINFO_HEADER_OUT, true); //TRUE 时追踪句柄的请求字符串，从 PHP 5.1.3 开始可用。这个很关键，就是允许你查看请求header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->requestHeader);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        $result = curl_exec($ch);
        $curlinfo=curl_getinfo($ch);
        $this->http_code=$curlinfo['http_code'];
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($result, 0, $headerSize);
        $this->responseHeader=explode("\n",$header);
        $this->responseData= substr($result, $headerSize, strlen($result));
        curl_close($ch);
    }
    public function get($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 1); //返回response头部信息
        curl_setopt($ch, CURLINFO_HEADER_OUT, true); //TRUE 时追踪句柄的请求字符串，从 PHP 5.1.3 开始可用。这个很关键，就是允许你查看请求header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->requestHeader);
        $result = curl_exec($ch);
        $curlinfo=curl_getinfo($ch);
        $this->http_code=$curlinfo['http_code'];
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($result, 0, $headerSize);
        $this->responseHeader=explode("\n",$header);
        $this->responseData= substr($result, $headerSize, strlen($result));
        curl_close($ch);
    }
    public static function replaceUrl($url,$host,$base_url,$tokenPar){
        //////////////////////////////////////////////
        ///  相对于跟目录的相对路径          /XXX      /
        ///  相对于执行脚本的相对路径        XXX     .     ..
        ///  绝对路径                        //XXX   http://    https://
        /// //////////////////////////////////////////
        if(preg_match("/^(javascript:|data:)/",trim($url))){//        javascript:  data:  不替换
            return $url;
        }else if(preg_match("/^(http|https):\/\//",trim($url))){//    http://    https://
            $baseUrl=base64_encode(trim($url));
            return "getpage.php?token=".md5($baseUrl.$tokenPar)."&url=".$baseUrl;
        }else if(preg_match("/^\/\//",trim($url))){//                //XXX
            $baseUrl=base64_encode("http:".trim($url));
            return "getpage.php?token=".md5($baseUrl.$tokenPar)."&url=".$baseUrl;
        }else if(preg_match("/^\//",trim($url))){//                  /
            $baseUrl=base64_encode("http://$host".trim($url));
            return "getpage.php?token=".md5($baseUrl.$tokenPar)."&url=".$baseUrl;
        }else  if(preg_match("/^[\w|\.][^\s]+/",trim($url))) {//     XXX
            $baseUrl=base64_encode($base_url.'/'.trim($url));
            return "getpage.php?token=".md5($baseUrl.$tokenPar)."&url=".$baseUrl;
        }else{
            return $url;
        }
    }
}


$request=new Request();
$request->recevieFromClient();
$request->beforeSend();
$request->sendToRemote();
$request->process();
$request->returnToClient();