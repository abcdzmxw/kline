<?php

namespace App\Traits;


use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Storage;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

trait FileTools
{

    /**
     * 上传单图片
     */
      public function uploadSingleImg($imageFile,$imageName = '')
      {
          if (is_string($imageFile)) return $this->uploadBase64Img($imageFile,$imageName);
          $disk = Storage::disk('qiniu');
          try{
              $re = $disk->put($imageName,$imageFile);
//              dd($re);
              return $re;
          }catch (\Exception $exception){
              throw new ApiException('上传图片失败:'.$exception->getMessage());
          }
      }

    /**
     * 上传单文件
     */
    public function uploadSingleFile($file,$fileName = '')
    {
        $disk = Storage::disk('qiniu');
        try{
            $re = $disk->put($fileName,$file,'r');
            return $re;
        }catch (\Exception $exception){
            return false;
        }
    }

    //base64图片上传
    public function uploadBase64Img($base64img,$imageName = '')
    {
        //获取图片
        $file=$base64img;
        if (strpos($base64img,'jpg') === 11){
            $type = 'jpg';
            $img = base64_decode(str_replace('data:image/jpg;base64,', '', $file));
        }elseif (strpos($base64img,'jpeg') === 11){
            $type = 'jpg';
            $img = base64_decode(str_replace('data:image/jpeg;base64,', '', $file));
        }else{
            $type = 'png';
            $img = base64_decode(str_replace('data:image/png;base64,', '', $file));
        }
        $accessKey =config('filesystems.disks.qiniu.access_key');
        $secretKey = config('filesystems.disks.qiniu.secret_key');
        $bucket = config('filesystems.disks.qiniu.bucket');
        // 构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);
        // 生成上传 Token
        $token = $auth->uploadToken($bucket);
        if (!$imageName){
            $key = time().rand(0,9999).'.'.$type;
        }else{
            $key = $imageName. '/' . time().rand(0,9999).'.'.$type;
        }
        $uploadMgr = new UploadManager();
        list($ret,$err) = $uploadMgr->put($token,$key,$img);
        if($ret){
            //这里返回的是一个bucket的域名,在前面添加http://后就可以正常看到图片
            return $key;
        }else{
            throw new ApiException('上传图片失败',40001);
        }
    }

    //除图片之外的文件上传
    public function upload($file,$fileName = '')
    {
        $accessKey =config('filesystems.disks.qiniu.access_key');
        $secretKey = config('filesystems.disks.qiniu.secret_key');
        $bucket = config('filesystems.disks.qiniu.bucket');
        // 构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);

        // 生成上传 Token
        $token = $auth->uploadToken($bucket);

        // 要上传文件的本地路径
        $filePath = $file;
        // 上传到七牛后保存的文件名
        $key = $fileName;
        // 初始化 UploadManager 对象并进行文件的上传。
        $uploadMgr = new UploadManager();
        // 调用 UploadManager 的 putFile 方法进行文件的上传。
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
        //        echo "\n====> putFile result: \n";
        if ($err !== null) {
            return false;
            //dd($err);
        } else {
            return $ret['key'];
        }
    }

    public function checkFileExists($fileName)
    {
        $disk = Storage::disk('qiniu');
        $re = $disk->exists($fileName);

        dd($re);
    }

    /**
     * 获取图片的Base64编码(不支持url)
     * @date 2017-02-20 19:41:22
     *
     * @param $img_file 传入本地图片地址
     *
     * @return string
     */
    function imgToBase64($img_file) {

        if (file_exists($img_file)) {
            $app_img_file = $img_file; // 图片路径
            $img_info = getimagesize($app_img_file); // 取得图片的大小，类型等

            //echo '<pre>' . print_r($img_info, true) . '</pre><br>';
            $fp = fopen($app_img_file, "r"); // 图片是否可读权限

            if ($fp) {
                $filesize = filesize($app_img_file);
                $content = fread($fp, $filesize);
                $file_content = chunk_split(base64_encode($content)); // base64编码
                switch ($img_info[2]) {           //判读图片类型
                    case 1: $img_type = "gif";
                        break;
                    case 2: $img_type = "jpg";
                        break;
                    case 3: $img_type = "png";
                        break;
                }

                $img_base64 = 'data:image/' . $img_type . ';base64,' . $file_content;//合成图片的base64编码

            }
            fclose($fp);
        }

        return $img_base64; //返回图片的base64
    }


    public function outBase64($base64_string)
    {
        $base64_string= explode(',', $base64_string); //截取data:image/png;base64, 这个逗号后的字符
        $data= base64_decode($base64_string[1]);//对截取后的字符使用base64_decode进行解码
        //file_put_contents($url, $data); //写入文件并保存
        return $data;
    }


}
