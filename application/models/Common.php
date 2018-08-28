<?php
/**
 * Created by PhpStorm.
 * User: weilai
 * Date: 2018/7/24
 * Time: 下午5:51
 */
class CommonModel implements \JsonSerializable, \ArrayAccess{
    use Attribute;
    public function __call($method, $arguments)
    {
        $query = Mysql::getInstance()->initConfig(get_class($this));
        return call_user_func_array([$query,$method],$arguments);
    }
    static public function __callStatic($method, $arguments){
        $query = Mysql::getInstance()->initConfig(static::class);
        return call_user_func_array([$query,$method],$arguments);
    }
    public function offsetSet($name, $value)
    {

    }

    public function offsetExists($name)
    {

    }

    public function offsetUnset($name)
    {

    }

    public function offsetGet($name)
    {

    }
    public function jsonSerialize()
    {

    }
}