<?php
$pool = new RedisPool();
$server = new Swoole\Http\Server('127.0.0.1', 9501);

$server->on('Request', function($req, $resp) use ($pool) {
    //从连接池中获取一个Redis协程客户端
    $redis = $pool->get();
    //连接失败
    if ($redis === false)
    {
        $resp->end("ERROR");
        return;
    }
    $result = $redis->hgetall('key');
    $resp->end(var_export($result, true));
    //释放客户端，其他协程可复用此对象
    $pool->put($redis);
});

$server->start();

class RedisPool
{
    protected $pool;

    function __construct()
    {
        $this->pool = new SplQueue;
    }

    function put($redis)
    {
        $this->pool->push($redis);
    }

    function get()
    {
        //有空闲连接
        if (count($this->pool) > 0)
        {
            return $this->pool->pop();
        }

        //无空闲连接，创建新连接
        $redis = new Swoole\Coroutine\Redis();
        $res = $redis->connect('127.0.0.1', 6379);
        if ($res == false)
        {
            return false;
        }
        else
        {
            return $redis;
        }
    }
}
