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

    /**
     * 快捷生成sql语句的where搜索条件
     * @param array $where
     * @return Mysql
     */
    public static function myWhere($where=[]){
        foreach($where as $k=>$v){
            $obj = self::where(1);
            $value = get($k,'');
            if(!empty($value)){
                switch ($v){
                    case 'time':
                        $value = explode('|',$value);
                        $obj = $obj->where($k,'between',strtotime($value[0]).','.(strtotime($value[1])+86400));
                        break;
                    default:
                        $obj = $obj->where($k,$v,$value);
                        break;
                }
            }
        }
        return $obj;
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