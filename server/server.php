<?php

class HttpServer
{
    public static $instance;
    public $http;
    public static $get;
    public static $post;
    public static $header;
    public static $server;
    private $application;

    private function __construct() {
        $http = new swoole_http_server("127.0.0.1", 9501);

        $http->set([
            'worker_num' => 16,
            'daemonize' => true,
            'max_request' => 10000
        ]);

        $http->on('WorkerStart' ,[$this , 'onWorkerStart']);

        $http->on('request',[$this,'onRequest']);

        $http->start();
    }

    public function onWorkerStart() {
        define("APP_PATH",  realpath(dirname(__FILE__) . '/../')); /* 指向public的上一级 */
        $this->application = new Yaf_Application(APP_PATH . "/conf/application.ini");
    }

    public function onRequest($request, $response) {
        if( isset($request->server) ) {
            HttpServer::$server = $request->server;
            foreach($request->server as $k=>$v){
                $_SERVER[$k] = $v;
            }
        }else{
            HttpServer::$server = [];
        }
        if( isset($request->header) ) {
            HttpServer::$header = $request->header;
            foreach($request->header as $k=>$v){
                $_SERVER['HTTP_'.strtoupper($k)] = $v;
            }
        }else{
            HttpServer::$header = [];
        }
        if( isset($request->get) ) {
            HttpServer::$get = $request->get;
            foreach($request->get as $k=>$v){
                $_GET[$k] = $v;
            }
        }else{
            HttpServer::$get = [];
        }
        if( isset($request->post) ) {
            HttpServer::$post = $request->post;
            foreach($request->post as $k=>$v){
                $_POST[$k] = $v;
            }
        }else{
            HttpServer::$post = [];
        }
        $arr = ['SWOOLE_HTTP_REQUEST','SWOOLE_HTTP_RESPONSE'];
        foreach($arr as $v){
            if(Yaf_Registry::has($v)){
                Yaf_Registry::del($v);
            }
        }
        // TODO handle img
        Yaf_Registry::set('SWOOLE_HTTP_REQUEST', $request);
        Yaf_Registry::set('SWOOLE_HTTP_RESPONSE', $response);
        ob_start();
        try {
            $yaf_request = new Yaf_Request_Http(HttpServer::$server['request_uri']);
            $this->application->bootstrap()->getDispatcher()->dispatch($yaf_request);

        } catch ( Yaf_Exception $e ) {
            var_dump( $e );
        }
        $result = ob_get_contents();

        ob_end_clean();
        $response->end($result);
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new HttpServer;
        }
        return self::$instance;
    }
}

HttpServer::getInstance();