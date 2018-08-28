<?php
/**
 * Created by PhpStorm.
 * User: weilai
 * Date: 2018/8/6
 * Time: 上午9:59
 */
class File extends SplFileObject{
    private $filename = '';
    private $info = '';
    public function __construct ($file_name){
        parent::__construct($file_name);
        $this->filename = $file_name;
    }
    public function setFileInfo($info){
        $this->info = $info;
        return $this;
    }
    public function validate($config){
        foreach($config as $k=>$v){
            $info = $this->check($k,$v);
            if($info['code']===false){
                throw new ErrorException($info['msg']);
            }
        }
        return $this;
    }
    private function check($type,$value){
        $type = strtolower($type);
        switch ($type){
            case 'size':
                if($this->getSize()>$value){
                    return modelReturn(false,'文件大小超出限制');
                }
                break;
            case 'ext':
                if(is_string($value)){
                    if(function_exists('exif_imagetype')){
                        $type = $this->intToImgType(exif_imagetype($this->filename));
                        if(!in_array($type,$value)){
                            throw new ErrorException($type.'文件格式错误');
                        }
                    }
                    $fileinfo = pathinfo($this->info['name']);
                    if(strtolower($value)!=$fileinfo['extension']){
                        throw new ErrorException($fileinfo['extension'].'文件格式错误');
                    }
                }elseif(is_array($value)){
                    if(function_exists('exif_imagetype')){
                        $type = $this->intToImgType(exif_imagetype($this->filename));
                        if(!in_array($type,$value)){
                            throw new ErrorException($type.'文件格式错误');
                        }
                    }
                    $fileinfo = pathinfo($this->info['name']);
                    if(!in_array($fileinfo['extension'],array_map('strtolower',$value))){
                        throw new ErrorException($fileinfo['extension'].'文件格式错误');
                    }
                }else{
                    throw new ErrorException('ext参数格式错误');
                }
                break;
            case 'type':
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $info = finfo_file($finfo, $this->filename);
                if($info!=$value){
                    throw new ErrorException($info.'文件格式错误');
                }
                break;
            default :
                break;
        }
    }
    public function move($path){
        $savename = date('Ymd') . DIRECTORY_SEPARATOR . md5(microtime(true));
        $filepath = $path.$savename;
        $path = dirname($filepath);
        if (!is_dir($path)) {
            if(!mkdir($path, 0777, true)){
                return '创建文件夹失败';
            }
        }
        $filename = $filepath.'.'.pathinfo($this->info['name'])['extension'];
        $result = move_uploaded_file($this->filename,$filename);
        if($result===false){
            return '上传文件失败';
        }else{
            return $filename;
        }
    }

    /**
     * exif_imagetype int to imgtype
     * @param $number
     * @return string
     */
    private function intToImgType($number){
        switch ($number){
            case 1:
                return 'gif';
                break;
            case 2:
                return 'jpeg';
                break;
            case 3:
                return 'png';
                break;
            case 4:
                return 'swf';
                break;
            case 5:
                return 'psd';
                break;
            case 6:
                return 'bmp';
                break;
            case 7:
                return 'tiff_ii';
                break;
            case 8:
                return 'tiff_mm';
                break;
            case 9:
                return 'jpc';
                break;
            case 10:
                return 'jp2';
                break;
            case 11:
                return 'jpx';
                break;
            case 12:
                return 'jb2';
                break;
            case 13:
                return 'swc';
                break;
            case 14:
                return 'iff';
                break;
            case 15:
                return 'wbmp';
                break;
            case 16:
                return 'xbm';
                break;
        }
    }
}