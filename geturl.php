<?php
$url=$_POST['url'];
$newurl = base64_encode($url);
header("Location: http://www.shendeng.cn/phproxy/getpage.php?url=".$newurl);
?>

