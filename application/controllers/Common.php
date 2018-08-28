<?php
/**
 * Created by PhpStorm.
 * User: weilai
 * Date: 2018/7/24
 * Time: 下午5:41
 */
class CommonController extends Yaf_Controller_Abstract{
    public function init(){

    }

    /**
     * 数据验证解析
     * @param $data
     * @param $condition
     * @return bool
     */
    protected function validate($data,$condition){
        foreach($condition as $k=>$v){
            $op = explode('|',$v[0]);
            foreach($op as $kk=>$vv){
                $vv = explode(':',$vv);
                $info = $this->validateExp($data[$k],$vv[0],$v[1],isset($vv[1])?$vv[1]:'',$data);
                if($info['code']===false){
                    return $info['msg'];
                }
            }
        }
        return true;
    }

    /**
     * 数据验证
     * @param $data     验证数据
     * @param $type     验证类型
     * @param $msg     验证信息
     * @param $op       验证条件
     * @param $original 原始数据
     */
    private function validateExp($data,$type,$msg,$op,$original){
        switch ($type){
            case 'require':
                if(!isset($data)){
                    return modelReturn(false,$msg.'不能为空');
                }
                break;
            case 'length':
                $len = mb_strlen($data);
                $op = explode(',',$op);
                if($len<$op[0]||$len>$op[1]){
                    return modelReturn(false,$msg.'长度为'.$op[0].'~'.$op[1].'个字符');
                }
                break;
            case 'confirm':
                if($data!=$original[$op]){
                    return modelReturn(false,$msg.'不一致');
                }
                break;
            case 'number':
                if(!is_numeric($data)){
                    return modelReturn(false,$msg.'必须为数字');
                }
                break;
            case 'eq':
                if($data!=$op){
                    return modelReturn(false,$msg.'不一致');
                }
                break;
            case 'unique':
                $op = explode(',',$op);$count = count($op);
                if($count==1){

                }elseif ($count==2){
                    $r = CommonModel::table($op[0])->field($op[1])->where($op[1],$data)->find();
                    if(!empty($r)){
                        return modelReturn(false,$msg.'已经存在');
                    }
                }
                break;
            default :
                break;
        }
    }
    protected function token(){
        $this->getView()->assign('i',20160123);
        $this->getView()->assign('s',md5(md5('20160123').base64_encode('yaf').md5('static')));
        $this->getView()->assign('t',md5(md5('static20160123')).(md5(date('YmdH'))));
    }
}