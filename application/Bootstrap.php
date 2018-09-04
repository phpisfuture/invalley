<?php
/**
 * Created by PhpStorm.
 * User: weilai
 * Date: 2018/7/23
 * Time: 上午10:59
 */
class Bootstrap extends Yaf_Bootstrap_Abstract{
    public function _initConfig() {
        $config = Yaf_Application::app()->getConfig();
        Yaf_Registry::set("config", $config);
    }
    public function _initCommonFunction(){
        //导入自定义函数
        Yaf_Loader::import(APP_PATH . "/application/functions.php");
        //注册一个会在php中止时执行的函数(exit,throw时触发)
        register_shutdown_function('handleFatal');
    }
    public function _initRoute(Yaf_Dispatcher $dispatcher){
        $router = Yaf_Dispatcher::getInstance()->getRouter();
        $route = new Yaf_Route_Rewrite(
            'index',
            array('controller' => 'index','action' => 'index'));
        $router->addRoute('index', $route);
    }
    public function _initView(Yaf_Dispatcher $dispatcher){
        //在这里注册自己的view控制器，例如smarty,firekylin
        Yaf_Dispatcher::getInstance()->disableView(); //因为要用smarty引擎作为渲染，所以关闭yaf自身的自动渲染功能
    }
    public function _initSmarty(Yaf_Dispatcher $dispatcher) {
        $smarty = new Smarty_Adapter(null, Yaf_Application::app()->getConfig()->smarty);
        Yaf_Dispatcher::getInstance()->setView($smarty);
    }
}