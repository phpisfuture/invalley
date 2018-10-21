<?php
/**
 * Created by PhpStorm.
 * User: weilai
 * Date: 2018/7/24
 * Time: 下午6:22
 */
if(!function_exists('handleFatal')){
    function handleFatal()
    {
        $error = error_get_last();
        if (isset($error['type']))
        {
            switch ($error['type'])
            {
                case E_ERROR :
                case E_PARSE :
                case E_CORE_ERROR :
                case E_COMPILE_ERROR :
                    $message = $error['message'];
                    $file = $error['file'];
                    $line = $error['line'];
                    $log = "$message ($file:$line)\nStack trace:\n";
                    $trace = debug_backtrace();
                    foreach ($trace as $i => $t)
                    {
                        if (!isset($t['file']))
                        {
                            $t['file'] = 'unknown';
                        }
                        if (!isset($t['line']))
                        {
                            $t['line'] = 0;
                        }
                        if (!isset($t['function']))
                        {
                            $t['function'] = 'unknown';
                        }
                        $log .= "#$i {$t['file']}({$t['line']}): ";
                        if (isset($t['object']) and is_object($t['object']))
                        {
                            $log .= get_class($t['object']) . '->';
                        }
                        $log .= "{$t['function']}()\n";
                    }
                    if (isset($_SERVER['REQUEST_URI']))
                    {
                        $log .= '[QUERY] ' . $_SERVER['REQUEST_URI'];
                    }
                    error_log($log);
                    $responseObj = Yaf_Registry::get('SWOOLE_HTTP_RESPONSE');
                    $responseObj->end($log);
                default:
                    break;
            }
        }
    }
}
/**
 * 读取配置
 * @param string $name
 * @return mixed
 */
if(!function_exists('config')){
    function config($name=''){
        $config = Yaf_Application::app()->getConfig();
        if(empty($name)){
            return $config;
        }
        if(strpos($name,'.')===false){
            return $config[$name];
        }
        $name = explode('.',$name);
        foreach ($name as $v){
            if(isset($config[$v])){
                $config = $config[$v];
            }else{
                return $config;
            }
        }
        return $config;
    }
}
/**
 * 获取posts数据
 * @param string $name
 * @return mixed
 */
if(!function_exists('post')){
    function post($name='',$default=false){
        if(is_array($name)){
            $arr = [];
            foreach($name as $v){
                if(is_string($v)){
                    $arr[$v] = HttpServer::$post[$v];
                }elseif(is_array($v)){
                    $arr[$v[0]] = !empty(HttpServer::$post[$v[0]])?HttpServer::$post[$v[0]]:$v[1];
                }
            }
            return array_map('trim',$arr);
        }else{
            if(''===$name){
                return array_map('trim',HttpServer::$post);
            }else{
                if(strpos($name,'/')){
                    list($name,$type) = explode('/',$name);
                    $name = !empty(HttpServer::$post[$name])?HttpServer::$post[$name]:$default;
                    switch (strtolower($type)){
                        case 'd':
                            return (int)$name;
                            break;
                        case 'a':
                            return (array)$name;
                            break;
                        case 'f':
                            return (float)$name;
                            break;
                        case 'b':
                            return (boolean)$name;
                            break;
                        case 's':
                        default:
                            if(is_scalar($name)){
                                return (string)$name;
                            }else{
                                throw new ErrorException('转换类型错误');
                            }
                            break;
                    }
                }else{
                    return !empty(HttpServer::$post[$name])?HttpServer::$post[$name]:$default;
                }

            }
        }
    }
}
/**
 * 获取get数据
 * @param string $name
 * @param bool $default
 * @return mixed
 */
if(!function_exists('get')){
    function get($name='',$default=false){
        if(is_array($name)){
            $arr = [];
            foreach($name as $v){
                if(is_string($v)){
                    $arr[$v] = HttpServer::$get[$v];
                }elseif(is_array($v)){
                    $arr[$v[0]] = !empty(HttpServer::$get[$v[0]])?HttpServer::$post[$v[0]]:$v[1];
                }
            }
            return array_map('trim',$arr);
        }else {
            if ('' === $name) {
                return array_map('trim',HttpServer::$get);
            } else {
                return trim(!empty(HttpServer::$get[$name])?HttpServer::$get[$name]:$default);
            }
        }
    }
}
if(!function_exists('param')){
    function param($name='',$default=false){
        $http = new Yaf_Request_Http();
        if(is_array($name)){
            $arr = [];
            foreach($name as $v){
                if(is_string($v)){
                    $arr[$v] = $http->get($v);
                }elseif(is_array($v)){
                    $arr[$v[0]] = $http->get($v[0],$v[1]);
                }
            }
            return array_map('trim',$arr);
        }else {
            if ('' === $name) {
                return array_map('trim',$http->get());
            } else {
                return trim($http->get($name, $default));
            }
        }
    }
}
/**
 * json返回信息
 * @param string $code
 * @param string $msg
 * @param string $data
 * @return json
 */
if(!function_exists('ajaxReturn')){
    function ajaxReturn($code=true,$msg='请求成功',$data=''){
        echo json_encode(['code'=>$code,'msg'=>$msg,'data'=>$data]);
    }
}
/**
 * array
 * @param string $code
 * @param string $msg
 * @param string $data
 * @return array
 */
if(!function_exists('modelReturn')){
    function modelReturn($code=true,$msg='请求成功',$data=''){
        return ['code'=>$code,'msg'=>$msg,'data'=>$data];
    }
}
if(!function_exists('cookie')){
    function cookie($name=false,$value=null,$expire=0,$path='/',$domain='',$secure=false,$httponly=false){
        if($name===false){
            return $_COOKIE;
        }elseif(is_null($name)){
            foreach($_COOKIE as $k=>$v){
                $responseObj = Yaf_Registry::get('SWOOLE_HTTP_RESPONSE');
                $responseObj->cookie($k,null,(int)(time()-3600),$path,$domain,$secure,$httponly);
            }
            return true;
        }elseif(is_string($name)&&is_string($value)){
            $responseObj = Yaf_Registry::get('SWOOLE_HTTP_RESPONSE');
            $status = $responseObj->cookie($name,$value,(int)(time()+$expire),$path,$domain,$secure,$httponly);
            if($status===true){
                $_COOKIE[$name] = $value;
            }
            return $status;
        }elseif(is_string($name)&&is_null($value)){
            $requestObj = Yaf_Registry::get('SWOOLE_HTTP_REQUEST');
            return !empty($requestObj->cookie[$name])?$requestObj->cookie[$name]:'';
        }
    }
}
if(!function_exists('swoole_header')){
    function swoole_header($key,$value,$ucwords = true){
        if(!empty($key)&&!empty($value)){
            $responseObj = Yaf_Registry::get('SWOOLE_HTTP_RESPONSE');
            $rs = $responseObj->header($key,$value,$ucwords);
            if($rs!==false){
                return true;
            }else{
                return false;
            }
        }elseif(!empty($key)&&empty($value)){
            $requestObj = Yaf_Registry::get('SWOOLE_HTTP_REQUEST');
            return $requestObj->header[strtolower($key)];
        }else{
            return false;
        }
        
    }
}
if(!function_exists('tree')){
    function tree($data,$id='id',$p_id='p_id',$childField='child'){
        $tmp = [];
        $item = [];
        foreach($data as $v){
            $tmp[$v[$id]] = $v;
        }
        foreach($tmp as $k=>$v){
            if($v[$p_id]==0){
              $item[] = &$tmp[$k];
            }else{
                $tmp[$v[$p_id]][$childField][] = &$tmp[$k];
            }
        }
        return $item;
    }
}
if(!function_exists('dd')){
    function dd($data){
        echo "<pre>";
        var_dump($data);
        echo "</pre>";die;
    }
}
if(!function_exists('pp')){
    function pp($data){
        echo "<pre>";
        var_dump($data);
        echo "</pre>";
    }
}
/**
 * @param $curldata
 * @param $url
 * @return mixed
 */
if(!function_exists('curl_post')) {
    function curl_post($curldata, $url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curldata);
        $return_str = curl_exec($curl);
        curl_close($curl);
        return $return_str;
    }
}
if(!function_exists('curl_get')){
    function curl_get($url){
        $ua='Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.9';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $ua);
        curl_setopt($ch, CURLOPT_URL, $url);
        $response =  curl_exec($ch);
        if($error=curl_error($ch)){
            die($error);
        }
        curl_close($ch);
        return $response;
    }
}
if(!function_exists('unfile')){
    function unfile($item){
        $url = 'https://static.phpisfuture.com/unlink';
        $data['i'] = 20160123;
        $data['s'] = md5(md5('20160123').base64_encode('yaf').md5('static'));
        $data['t'] = md5(md5('static20160123')).(md5(date('YmdH')));
        $data['item'] = json_encode($item);
        return curl_post($data,$url);
    }
}
if(!function_exists('get_ip')){
    function get_ip(){
        //判断服务器是否允许$_SERVER
        if(isset($_SERVER)){
            if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
                $realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }elseif(isset($_SERVER['HTTP_CLIENT_IP'])) {
                $realip = $_SERVER['HTTP_CLIENT_IP'];
            }else{
                $realip = $_SERVER['REMOTE_ADDR'];
            }
        }else{
            //不允许就使用getenv获取
            if(getenv("HTTP_X_FORWARDED_FOR")){
                $realip = getenv( "HTTP_X_FORWARDED_FOR");
            }elseif(getenv("HTTP_CLIENT_IP")) {
                $realip = getenv("HTTP_CLIENT_IP");
            }else{
                $realip = getenv("REMOTE_ADDR");
            }
        }
        return $realip;
    }
}
if(!function_exists('get_swoole_ip')){
    function get_swoole_ip(){
        $request = Yaf_Registry::get("SWOOLE_HTTP_REQUEST");
        return $request->header['x-real-ip'];
    }
}
/**
 * 检查一个函数是否可用
 * [function_usable description]
 * @param  [type] $function_name [description]
 * @return [type]                [description]
 */
if(!function_exists('function_usable')){
    function function_usable($function_name)
    {
        static $_suhosin_func_blacklist;

        if (function_exists($function_name))
        {
            if ( !isset($_suhosin_func_blacklist))
            {
                $_suhosin_func_blacklist = extension_loaded('suhosin')
                    ? explode(',', trim(ini_get('suhosin.executor.func.blacklist')))
                    : array();
            }

            return !in_array($function_name, $_suhosin_func_blacklist, TRUE);
        }

        return FALSE;
    }
}
if (!function_exists('is_https')) {
    function is_https()
    {
        if ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        {
            return TRUE;
        }
        elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        {
            return TRUE;
        }
        elseif ( ! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off')
        {
            return TRUE;
        }

        return FALSE;
    }
}
if (!function_exists('is_php')) {
    function is_php($version)
    {
        static $_is_php;
        $version = (string) $version;

        if ( ! isset($_is_php[$version]))
        {
            $_is_php[$version] = version_compare(PHP_VERSION, $version, '>=');
        }

        return $_is_php[$version];
    }
}
if (!function_exists('is_really_writable')) {
    function is_really_writable($file)
    {
        // If we're on a Unix server with safe_mode off we call is_writable
        if (DIRECTORY_SEPARATOR === '/' && (is_php('5.4') OR ! ini_get('safe_mode')))
        {
            return is_writable($file);
        }

        /* For Windows servers and safe_mode "on" installations we'll actually
         * write a file then read it. Bah...
         */
        if (is_dir($file))
        {
            $file = rtrim($file, '/').'/'.md5(mt_rand());
            if (($fp = @fopen($file, 'ab')) === FALSE)
            {
                return FALSE;
            }

            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return TRUE;
        }
        elseif ( ! is_file($file) OR ($fp = @fopen($file, 'ab')) === FALSE)
        {
            return FALSE;
        }

        fclose($fp);
        return TRUE;
    }
}