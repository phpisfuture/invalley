<?php
/**
 * Created by PhpStorm.
 * User: weilai
 * Date: 2018/9/4
 * Time: 上午11:36
 */
class MysqlPool{
    protected $pool;
    public function __construct (){
        $this->pool = new SplQueue;
    }
    public function put($mysql){
        $this->pool->push($mysql);
    }
    public function get(){
        //有空闲连接
        if (count($this->pool) > 0){
            return $this->pool->pop();
        }
        //无空闲连接，创建新连接
        $mysql = new Swoole\Coroutine\Mysql();
        $res = $mysql->connect([
            'host' => config('db.host'),
            'user' => config('db.user'),
            'password' => config('db.password'),
            'database' => config('db.database'),
            'port' => 3306,
            'charset' => 'utf8'
        ]);
        if($res == false){
            return false;
        }else{
            return $mysql;
        }
    }
}