<?php
/**
 * Created by PhpStorm.
 * User: weilai
 * Date: 2018/8/24
 * Time: 上午10:25
 */
final class Cache{
    private static $instance;//mysql单例
    private $redis = null;
    private function __construct (){
        $this->redis = new \Redis();
        $this->redis->connect('127.0.0.1',6379);
        $this->redis->auth('weilai666cool');
    }

    /**
     * @param $key
     * @param $value
     * @param int $timeout
     * @return bool
     */
    public function set($key,$value,$timeout=0){
        if($timeout===0){
            return $this->redis->set($key,$value);
        }else{
            return $this->redis->set($key,$value,$timeout);
        }
    }

    /**
     * @param $key
     * @return bool|string
     */
    public function get($key){
        return $this->redis->get($key);
    }

    /**
     * 禁止克隆
     */
    private function __clone (){

    }

    /**
     * 获取单例
     * @return Cache
     */
    static public function getInstance(){
        if(self::$instance instanceof self){
            return self::$instance;
        }else{
            return self::$instance = new Cache();
        }
    }
}