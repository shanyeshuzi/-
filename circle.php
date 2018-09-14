<?php
   function radius_img($imgpath = './t.png', $radius = 15) {
        $ext     = pathinfo($imgpath);
        $src_img = null;
        switch ($ext['extension']) {
            case 'jpg':
                $src_img = @imagecreatefromjpeg($imgpath);
                break;
            case 'png':
                $src_img = @imagecreatefrompng($imgpath);
                break;
        }
        if(!$src_img)
            return false;
        $wh = getimagesize($imgpath);
        $w  = $wh[0];
        $h  = $wh[1];
         $radius = $radius == 0 ? (min($w, $h) / 2) : $radius;
        $img = imagecreatetruecolor($w, $h);
        //这一句一定要有
        imagesavealpha($img, true);
        //拾取一个完全透明的颜色,最后一个参数127为全透明
        $bg = imagecolorallocatealpha($img, 255, 255, 255, 127);
        imagefill($img, 0, 0, $bg);
        $r = $radius; //圆 角半径
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $rgbColor = imagecolorat($src_img, $x, $y);
                if (($x >= $radius && $x <= ($w - $radius)) || ($y >= $radius && $y <= ($h - $radius))) {
                    //不在四角的范围内,直接画
                    imagesetpixel($img, $x, $y, $rgbColor);
                } else {
                    //在四角的范围内选择画
                    //上左
                    $y_x = $r; //圆心X坐标
                    $y_y = $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    //上右
                    $y_x = $w - $r; //圆心X坐标
                    $y_y = $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    //下左
                    $y_x = $r; //圆心X坐标
                    $y_y = $h - $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                    //下右
                    $y_x = $w - $r; //圆心X坐标
                    $y_y = $h - $r; //圆心Y坐标
                    if (((($x - $y_x) * ($x - $y_x) + ($y - $y_y) * ($y - $y_y)) <= ($r * $r))) {
                        imagesetpixel($img, $x, $y, $rgbColor);
                    }
                }
            }
        }
        return $img;
    }
    public function changeimg($oldpath){
        $image =$oldpath; // 原图
        $imgstream = file_get_contents($image);
        $im = imagecreatefromstring($imgstream);
        $x = imagesx($im);//获取图片的宽
        $y = imagesy($im);//获取图片的高

        if(function_exists("imagecreatetruecolor")) {
            $dim = imagecreatetruecolor($x, $y); // 创建目标图gd2
        } else {
            $dim = imagecreate($x, $y); // 创建目标图gd1
        }
        imageCopyreSampled ($dim,$im,0,0,0,0,$x,$y,$x,$y-30);

        imagepng($dim,$oldpath);
    }
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	    $i=1746;
        while($i<1990){
            $this->info($i);
            $i++;
            file_put_contents("finishnum",$i);
            $lastMongoVideo  =  mongoVideo::orderBy('id','DESC')->skip($i)->first();
            //$this->info($lastMongoVideo->data);
            $data=json_decode($lastMongoVideo->data,true);
            if(empty($data))
                continue;
            foreach ($data as $one){

                $this->info($one['userID']);
                if(!file_exists ("img/".$one['userID']."/userinfo")){
                    mkdir ("img/".$one['userID'],0777,true);
                    file_put_contents("img/".$one['userID']."/userinfo",json_encode($one));
                    $headPhotoUrl=!empty($one['headPhotoUrl'])?$one['headPhotoUrl']:$one['headPhotoUrl1'];
                    if(empty($headPhotoUrl))
                        continue;
                    $tempheadarray=explode("/",$headPhotoUrl);
                    $headstyle=explode(".",$tempheadarray[count($tempheadarray)-1]);
                    if(strtolower($headstyle[count($headstyle)-1]) !="jpg"){
                        $this->info("header img error");
                        continue;
                    }


                    file_put_contents("img/".$one['userID']."/header.jpg",file_get_contents($headPhotoUrl));
                    $imgg = $this->radius_img("img/".$one['userID']."/header.jpg", 0);
                    if(!$imgg)
                        continue;
                    imagepng($imgg,"img/".$one['userID']."/header.png");
                    imagedestroy($imgg);
                    if(empty($one['photoList']))
                        continue;
                    foreach ($one['photoList'] as $key=> $oneone){
                        if(strpos($oneone,"290_290")){
                            $temponeone = str_replace("290_290","640_480",$oneone);
                            @$result=file_get_contents($temponeone);
                            if(!empty($result)){
                                file_put_contents("img/".$one['userID']."/img$key.png",$result);
                                $this->changeimg("img/".$one['userID']."/img$key.png");
                            }else{
                                file_put_contents("img/".$one['userID']."/img$key.png",file_get_contents($oneone));
                                $this->changeimg("img/".$one['userID']."/img$key.png");
                            }
                        }else{
                            file_put_contents("img/".$one['userID']."/img$key.png",file_get_contents($oneone));
                            $this->changeimg("img/".$one['userID']."/img$key.png");
                        }

                    }
                    $user=json_decode(file_get_contents("baiheuser"),true);
                    $user[]=$one['userID'];
                    file_put_contents("baiheuser",json_encode($user));
                }

            }


        }

        die;