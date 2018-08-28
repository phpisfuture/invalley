<?php
/**
 * Created by PhpStorm.
 * User: weilai
 * Date: 2018/7/25
 * Time: 下午1:13
 */
trait Attribute{
    protected $pk = 'id';
    protected $table = '';
    public function getPkName(){
        return $this->pk;
    }
    public function getTableName(){
        return config('db.prefix').$this->table;
    }
    public function __get ($name)
    {
        return $this->$name;
    }
}