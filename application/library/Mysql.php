<?php
final class Mysql{
    use Attribute;
    static private $instance = null;//mysql单例
    private $options = null;        //sql语句
    private $connection = null;     //Mysql链接对象
    private $unset = true;          //是否删除option

    /**
     * 禁止外部实例化
     * Mysql constructor.
     */
    private function __construct(){
        $host = $this->decode(config('db.host'),3);
        $database = $this->decode(config('db.database'),2);
        $user = $this->decode(config('db.user'),0);
        $password = $this->decode(config('db.password'),1);
        $this->connection = new PDO("mysql:host={$host};dbname={$database}",$user,$password);
    }

    /**
     * 禁止外部克隆
     */
    private function __clone (){

    }

    /**
     * 获得mysql单例
     * @return Mysql|null
     */
    static public function getInstance(){
        if(self::$instance instanceof self){
            return self::$instance;
        }else{
            return self::$instance = new Mysql();
        }
    }

    /**
     * 初始化配置
     * @param $model
     * @return $this
     * @throws ReflectionException
     */
    public function initConfig($model){
        $modelObj = new $model;
        $this->options['table'] = config('db.prefix').strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', substr($model,0,strlen($model)-5)));
        $ref = new ReflectionClass('Attribute');
        $properties = $ref->getProperties();
        foreach ($properties as $v){
            $name = $v->name;
            $method = 'get'.ucfirst($name).'Name';
            $value = $modelObj->$method();
            if(!empty($value)&&$value!='wl_'){
                $this->options[$name] = $value;
            }
        }
        return $this;
    }

    /**
     * 设置排序
     * @param $field
     * @param null $order
     * @return $this
     * @throws ErrorException
     */
    public function order($field,$order=null){
        if(is_string($field)&&is_null($order)){
            $this->orderExp($field);
        }elseif(is_string($field)&&is_string($order)){
            $this->orderExp($field.' '.$order);
        }elseif(is_array($field)&&is_null($order)){
            ['admin_id'=>'desc','id'=>'asc'];
            foreach($field as $k=>$v){
                $this->orderExp($k.' '.$v);
            }
        }else{
            throw new ErrorException('order参数格式错误');
        }
        return $this;
    }

    /**
     * 生成order表达式
     * @param $exp
     */
    private function orderExp($exp){
        if(isset($this->options['order'])){
            $this->options['order'] .= ','.$exp;
        }else{
            $this->options['order'] = ' ORDER BY '.$exp;
        }
    }

    /**
     * 删除数据
     * @param null $data
     * @return bool|int|string
     * @throws ErrorException
     */
    public function delete($data=null){
        if(!is_null($data)){
            $pk = $this->options['pk'];
            if(is_string($data)){
                $data = trim($data,',');
                if(strpos($data,',')){
                    $this->whereExp($pk,'IN',$data);
                }else{
                    $this->whereExp($pk,'=',$data);
                }
            }elseif(is_numeric($data)){
                $this->whereExp($pk,'=',$data);
            }elseif (is_array($data)){
                if(count($data)==1){
                    $this->whereExp($pk,'=',$data[0]);
                }else{
                    $str = implode(',',$data);
                    $this->whereExp($pk,'IN',$str);
                }
            }else{
                throw new ErrorException('delete参数格式错误');
            }
        }
        if(isset($this->options['table'])){
            $table = $this->options['table'];
        }else{
            throw new ErrorException('table数据表不存在');
        }
        $where = isset($this->options['where'])?$this->options['where']:1;
        $sql = 'DELETE FROM '.$table.' WHERE '.$where;
        if(isset($this->options['build_sql'])&&$this->options['build_sql']===true){
            $this->unsetOption();
            return $sql;
        }
        $this->unsetOption();
        try{
            $count = $this->connection->exec($sql);
            if($this->connection->errorCode()!='00000'){
                throw new ErrorException('MYSQL["'.$this->connection->errorCode().'"] '.$this->connection->errorInfo()[2]);
            }
            return $count==0?false:$count;
        }catch (Exception $e){
            throw new ErrorException($e->getMessage());
        }
    }

    /**
     * 新增数据
     * @param  array $data
     * @return bool|string
     * @throws ErrorException
     */
    public function insert($data){
        if(!is_array($data)){
            throw new ErrorException('insert数据格式不正确');
        }
        if(isset($this->options['table'])){
            $table = $this->options['table'];
        }else{
            throw new ErrorException('table数据表不存在');
        }
        $field = '';$content = '';
        foreach($data as $k=>$v){
            $field .= ',`'.$k.'`';
            $content .= ",'".$v."'";
        }
        $sql = 'INSERT INTO '.$table.' ('.trim($field,',').') VALUES ('.trim($content,',').')';
        if(isset($this->options['build_sql'])&&$this->options['build_sql']===true){
            $this->unsetOption();
            return $sql;
        }
        $this->unsetOption();
        try{
            $count = $this->connection->exec($sql);
            if($this->connection->errorCode()!='00000'){
                throw new ErrorException('MYSQL["'.$this->connection->errorCode().'"] '.$this->connection->errorInfo()[2]);
            }
            return ($count==0||$count==false)?false:$this->connection->lastInsertId();
        }catch (Exception $e){
            throw new ErrorException($e->getMessage());
        }
    }

    /**
     * 批量添加数据
     * @param $data
     * @return bool|string
     * @throws ErrorException
     */
    public function insertAll($data){
        if(!is_array($data)){
            throw new ErrorException();
        }
        if(isset($this->options['table'])){
            $table = $this->options['table'];
        }else{
            throw new ErrorException('table数据表不存在');
        }
        $content = '';
        $field_arr = array_keys($data[0]);
        $field_str = implode(',',$field_arr);
        foreach($data as $k=>$v){
            $tmp = '';
            foreach($field_arr as $vv){
                $tmp .= "'".$v[$vv]."',";
            }
            $content .= ',('.trim($tmp,',').')';
        }
        $sql = 'INSERT INTO '.$table.' ('.$field_str.') VALUES '.trim($content,',');
        if(isset($this->options['build_sql'])&&$this->options['build_sql']===true){
            $this->unsetOption();
            return $sql;
        }
        $this->unsetOption();
        return $this->execSql($sql);
    }

    /**
     * 更新操作
     * @param null $data 更新的数据[识别主键]
     * @return bool|int|string
     * @throws ErrorException
     */
    public function update($data=null){
        if(!is_array($data)&&!is_null($data)){
            throw new ErrorException('update更新数据错误');
        }
        if(isset($this->options['table'])){
            $table = $this->options['table'];
        }else{
            throw new ErrorException('table数据表不存在');
        }
        if(!is_null($data)){
            foreach($data as $k=>$v){
                if($k==$this->options['pk']){
                    $this->whereExp($k,'=',$v);
                }else{
                    $this->setExp($k,$v);
                }
            }
        }
        if(!isset($this->options['set'])){
            throw new ErrorException('update更新数据错误');
        }
        $set = $this->options['set'];
        $where = isset($this->options['where'])?$this->options['where']:1;
        $sql = 'UPDATE '.$table.' SET '.$set.' WHERE '.$where;
        if(isset($this->options['build_sql'])&&$this->options['build_sql']===true){
            $this->unsetOption();
            return $sql;
        }
        $this->unsetOption();
        return $this->execSql($sql);
    }

    /**
     * 执行sql
     * [execSql description]
     * @param  [type] $sql [description]
     * @return [type]      [description]
     */
    public function execSql($sql){
        try{
            $count = $this->connection->exec($sql);
            if($this->connection->errorCode()!='00000'){
                throw new ErrorException('MYSQL["'.$this->connection->errorCode().'"] '.$this->connection->errorInfo()[2]);
            }
            return $count==0?false:$count;
        }catch (Exception $e){
            throw new ErrorException($e->getMessage());
        }
    }

    /**
     * 启动事务
     */
    public function start(){
        $this->connection->beginTransaction();
    }

    /**
     * 提交事务
     */
    public function commit(){
        $this->connection->commit();
    }

    /**
     * 回滚事务
     */
    public function rollback(){
        $this->connection->rollBack();
    }

    /**
     * 设置自增字段
     * @param $filed    字段
     * @param int $step 步长
     * @return $this
     */
    public function inc($filed,$step=1){
        $this->setExp($filed,$step,'+');
        return $this;
    }

    /**
     * 设置自减字段
     * @param $filed    字段
     * @param int $step 步长
     * @return $this
     */
    public function dec($filed,$step=1){
        $this->setExp($filed,$step,'-');
        return $this;
    }
    /**
     * 设置字段等于
     * @param $filed    字段
     * @param $value    设置的值
     * @return $this
     */
    public function exp($filed,$value){
        $this->setExp($filed,$value,'=');
        return $this;
    }

    /**
     * 更新字段自增
     * @param $filed    字段
     * @param int $step 步长
     * @return bool|int|string
     * @throws ErrorException
     */
    public function setInc($filed,$step=1){
        $this->setExp($filed,$step,'+');
        return $this->update();
    }

    /**
     * 更新字段自减
     * @param $filed    字段
     * @param int $step 步长
     * @return bool|int|string
     * @throws ErrorException
     */
    public function setDec($filed,$step=1){
        $this->setExp($filed,$step,'-');
        return $this->update();
    }

    /**
     * 更新设置字段
     * @param $filed    字段
     * @param $value    设置的值
     * @return bool|int|string
     * @throws ErrorException
     */
    public function setField($filed,$value){
        $this->setExp($filed,$value,'=');
        return $this->update();
    }
    /**
     * 设置更新表达式
     * @param string $field    字段
     * @param string $value    值
     * @param string $type     类型
     */
    private function setExp($field,$value,$type='='){
        $value = is_string($value)?"'".$value."'":$value;
        switch ($type){
            case '=':
                $this->setRaw('`'.$field."` = ".$value);
                break;
            case '+':
            case '-':
                $this->setRaw('`'.$field.'` = `'.$field.'` '.$type.' '.$value);
                break;
            default:
                break;
        }
    }

    /**
     * 拼接更新表达式
     * @param $exp  表达式
     */
    private function setRaw($exp){
        if(isset($this->options['set'])){
            $this->options['set'] .= ','.$exp;
        }else{
            $this->options['set'] = $exp;
        }
    }

    /**
     * 查询批量select
     * @param null $data    传递为主键数据
     * @return array
     * @throws ErrorException
     */
    public function select($data=null){
        if(!is_null($data)){
            $this->getPkWhere($data);
        }
        $sql = $this->buildSelectSql();
        if(isset($this->options['build_sql'])&&$this->options['build_sql']===true){
            $this->unsetOption();
            $this->options['build_sql'] = true;
            return $sql;
        }
        return $this->querySql($sql);
    }

    /**
     * 执行查询sql
     * @param $sql      sql语句
     * @return array
     * @throws ErrorException
     */
    public function querySql($sql){
        try{
            $sth = $this->connection->prepare($sql);
            $sth->execute();
            if($sth->errorCode()!='00000'){
                throw new ErrorException('MYSQL["'.$sth->errorCode().'"] '.$sth->errorInfo()[2]);
            }
            $this->unsetOption();
            return $sth->fetchAll(PDO::FETCH_ASSOC);die;
        }catch(Exception $e) {
            throw new ErrorException($e->getMessage());die;
        }
    }

    /**
     * 查询单条select
     * @param null $data    传递则为主键数据
     * @return mixed
     * @throws ErrorException
     */
    public function find($data=null){
        $this->options['limit'] = ' limit 1';
        $info = $this->select($data);
        if(!empty($info)){
            if(!empty($this->options['build_sql'])){
                return $info;
            }else{
                return $info[0];
            }
        }else{
            return null;
        }
    }

    /**
     * 构建sql语句
     * @return $this
     */
    public function buildSql(){
        $this->options['build_sql'] = true;
        return $this;
    }

    /**
     * 拼接主键where
     * @param $where  接收查询主键数据
     * @throws ErrorException
     */
    private function getPkWhere ($where){
        $pk = $this->options['pk'];
        if(is_array($where)){
            $where = implode(',',$where);
            $this->whereExp($pk,'IN',$where);
        }else{
            $this->whereExp($pk,'=',$where);
        }
    }

    /**
     * 表名
     * @param string $name  表名
     * @return $this
     */
    public function table($name=''){
        if(''!==$name){
            $this->options['table'] = config('db.prefix').strtolower(trim($name));
        }
        return $this;
    }

    /**
     * 主键
     * @param string $name  主键名字
     */
    public function pk($name=''){
        if(''!==$name){
            $this->options['pk'] = strtolower(trim($name));
        }
        return $this;
    }

    /**
     * 设置表别名
     * @param string $name 表名字
     * @return $this
     */
    public function alias($name=''){
        if(''!==$name){
            $this->options['table'] .= ' AS '.$name;
        }
        return $this;
    }

    /**
     * 设置limit
     * @param $offset       起始位置
     * @param null $length  显示条数
     * @return $this
     */
    public function limit($offset,$length=null){
        if(is_null($length)&&strpos($offset,',')){
            list($offset,$length) = explode(',',$offset);
        }
        if(!is_null($length)){
            $this->options['limit'] = ' LIMIT '.intval($offset).','.intval($length);
        }else{
            $this->limitExp($offset);
        }
        return $this;
    }

    /**
     * limit表达式
     * @param $exp
     */
    private function limitExp($exp){
        if(isset($this->options['limit'])){
            $this->options['limit'] .= ','.$exp;
        }else{
            $this->options['limit'] = ' LIMIT '.$exp;
        }
    }

    /**
     * 设置查询分页数据
     * @param $page           显示第几页
     * @param null $listRows  每页显示数量
     * @return $this
     */
    public function page($page, $listRows = null){
        if (is_null($listRows) && strpos($page, ',')) {
            list($page, $listRows) = explode(',', $page);
            $this->options['limit'] = " LIMIT ".$listRows*($page-1).','.$listRows;
        }elseif(is_null($listRows)&&!strpos($page,',')){
            $this->limitExp($page-1);
        }else{
            $this->options['limit'] = " LIMIT ".$listRows*($page-1).','.$listRows;
        }
        return $this;
    }

    /**
     * 分页数据
     * @param $listRow
     * @param bool $flag
     * @param array $query
     * @param array $config
     * @return mixed
     * @throws ErrorException
     */
    public function paginate($listRow,$flag=false,$query=[],$config=[]){
        $page = param('page',1);
        $this->unset = false;
        $data['data'] = $this->page($page,$listRow)->select();
        unset($this->options['field'],$this->options['limit']);
        $data['total'] = $this->count();
        $data['per_page'] = $listRow;
        $data['current_page'] = $page;
        $data['last_page'] = ceil($data['total']/$listRow);
        $this->unset = true;
        $this->unsetOption();
        if($flag===true){
            $str = '';
            if($data['last_page']>1) {
                $default = [
                    'ul'        => 'pagination',
                    'active'    => 'active',
                    'disabled'  => 'disabled',
                    'li'        => ''
                ];
                if(isset($query['page'])){
                    unset($query['page']);
                }
                foreach($query as $k=>$v){
                    if(empty($v)){
                        unset($query[$k]);
                    }
                }
                $config = array_merge($default,$config);
                $window = 6;
                $str .= '<ul class="'.$config['ul'].'">';
                $str .= $this->buildPageHtml('prev', '', '', $data['current_page'],$config,$query);
                if ($data['last_page'] < $window + 6) {
                    $str .= $this->buildPageHtml('loop', 1, $data['last_page'], $data['current_page'],$config,$query);
                } elseif ($data['current_page'] <= $window) {
                    $str .= $this->buildPageHtml('loop', 1, $window + 3, $data['current_page'],$config,$query);
                    $str .= $this->buildPageHtml('behind', '', $data['last_page'], '',$config,$query);
                } elseif ($data['current_page'] > ($data['last_page'] - $window)) {
                    $str .= $this->buildPageHtml('front', '', '', '',$config,$query);
                    $str .= $this->buildPageHtml('loop', $data['last_page'] - $window - 2, $data['last_page'] + 1, $data['current_page'],$config,$query);
                } else {
                    $str .= $this->buildPageHtml('front', '', '', '',$config,$query);
                    $str .= $this->buildPageHtml('loop', $data['current_page'] - $window / 2, $data['current_page'] + $window / 2, $data['current_page'],$config,$query);
                    $str .= $this->buildPageHtml('behind', '', $data['last_page'], '',$config,$query);
                }
                $str .= $this->buildPageHtml('next', '', $data['last_page'], $data['current_page'],$config,$query);
                $str .= "</ul>";
            }
            $data['html'] = $str;
        }
        return $data;
    }

    /**
     * 构建html
     * @param $type     类型
     * @param $start    开始
     * @param $end      结束
     * @param $current  当前页
     * @param $config   class样式配置
     * @param $query    携带的get参数
     * @return string
     */
    private function buildPageHtml($type,$start,$end,$current,$config,$query){
        if (!empty($query)){
            $url_args = '&'.http_build_query($query);
        }else{
            $url_args = '';
        }
        $str = '';
        switch ($type){
            case 'prev':
                if($current==1){
                    $str .= '<li class="'.$config['disabled'].'"><span>&laquo;</span></li>';
                }else {
                    $str .= '<li class="'.$config['li'].'"><a href="?page='.($current - 1).$url_args.'">&laquo;</a></li>';
                }
                break;
            case 'front':
                $str .= '<li class="'.$config['li'].'"><a href="?page=1'.$url_args.'">1</a></li>';
                $str .= '<li class="'.$config['li'].'"><a href="?page=2'.$url_args.'">2</a></li>';
                $str .= '<li class="'.$config['disabled'].'"><span>...</span>';
                break;
            case 'loop':
                for($i=$start;$i<=$end;++$i){
                    if($i==$current){
                        $str .= '<li class="'.$config['active'].'"><span>'.$i.'</span></li>';
                    }else{
                        $str .= '<li class="'.$config['li'].'"><a href="?page='.$i.$url_args.'">'.$i.'</a></li>';
                    }
                }
                break;
            case 'behind':
                $str .= '<li class="'.$config['disabled'].'"><span>...</span>';
                $str .= '<li class="'.$config['li'].'"><a href="?page='.($end-1).$url_args.'">'.($end-1).'</a></li>';
                $str .= '<li class="'.$config['li'].'"><a href="?page='.$end.$url_args.'">'.$end.'</a></li>';
                break;
            case 'next':
                if($current==$end){
                    $str .= '<li class="'.$config['disabled'].'"><span>&raquo;</span></li>';
                }else{
                    $str .= '<li class="'.$config['li'].'"><a href="?page='.($current+1).$url_args.'">&raquo;</a></li>';
                }
                break;
            default :
                break;
        }
        return $str;
    }

    /**
     * 执行聚合函数
     * @param $type     聚合类型
     * @param $filed    查询字段
     * @return int      记录值
     * @throws ErrorException
     */
    private function aggregate($type,$filed){
        $this->field($type.'('.$filed.') AS '.$type);
        $sql = $this->buildSelectSql();
        if($this->options['build_sql']===true){
            return $sql;
        }
        return intval($this->querySql($sql)[0][$type]);
    }

    /**
     * 查询记录数量
     * @param string $filed   统计字段
     * @return int            返回数量
     * @throws ErrorException
     */
    public function count($filed='*'){
        return $this->aggregate('count',$filed);
    }

    /**
     * 查询字段最大值
     * @param $filed    字段
     * @return int      最大值
     * @throws ErrorException
     */
    public function max($filed){
        return $this->aggregate('max',$filed);
    }

    /**
     * 查询字段最小值
     * @param $filed    字段
     * @return int      最小值
     * @throws ErrorException
     */
    public function min($filed){
        return $this->aggregate('min',$filed);
    }

    /**
     * 查询记录总和
     * @param $filed    字段
     * @return int      总和
     * @throws ErrorException
     */
    public function sum($filed){
        return $this->aggregate('sum',$filed);
    }

    /**
     * 查询记录平均值
     * @param $filed    字段
     * @return int      平均值
     * @throws ErrorException
     */
    public function avg($filed){
        return $this->aggregate('avg',$filed);
    }

    /**
     * 构建查询sql语句
     * @return string       sql语句
     * @throws ErrorException
     */
    private function buildSelectSql(){
        $field = isset($this->options['field'])?$this->options['field']:'*';
        if(isset($this->options['table'])){
            $table = $this->options['table'];
        }else{
            throw new ErrorException('table数据表不存在');
        }
        $join = isset($this->options['join'])?$this->options['join']:'';
        $where = isset($this->options['where'])?$this->options['where']:1;
        $order = isset($this->options['order'])?$this->options['order']:'';
        $limit = isset($this->options['limit'])?$this->options['limit']:'';
        return 'SELECT '.$field.' FROM '.$table.$join.' WHERE '.$where.$order.$limit;
    }

    /**
     * 联表查询
     * @param $table
     * @param null $condition
     * @param string $type
     * @return $this
     */
    public function join($table,$condition=null,$type='INNER'){
        if(is_string($table)&&is_null($condition)){
            $this->joinRaw($table);
        }elseif(is_string($table)&&is_string($condition)){
            $this->joinExp($table,$condition,$type);
        }elseif(is_array($table)&&is_string($condition)){
            if(count($table)==1){
                foreach($table as $k=>$v){
                    $this->joinExp($k.' AS '.$v,$condition,$type);
                }
            }elseif(count($table)==2){
                $this->joinExp($table[0].' AS '.$table[1],$condition,$type);
            }else{
                throw new ErrorException('联表表名参数格式错误');
            }
        }else{
            throw new ErrorException('联表参数格式错误');
        }
        return $this;
    }

    /**
     * 构造联表表达式
     * @param $table
     * @param $condition
     * @param $type
     * @throws ErrorException
     */
    private function joinExp($table,$condition,$type){
        $table = config('db.prefix').strtolower($table);
        $type = strtoupper($type);
        switch ($type){
            case 'INNER':
            case 'LEFT':
            case 'RIGHT':
            case 'FULL':
                $this->joinRaw(' '.$type.' JOIN '.$table.' ON '.$condition);
                break;
            default:
                throw new ErrorException('联表类型错误');
                break;
        }
    }

    /**
     * 拼接联表表达式
     * @param $exp
     */
    private function joinRaw($exp){
        if(isset($this->options['join'])){
            $this->options['join'] .= ' '.$exp;
        }else{
            $this->options['join'] = ' '.$exp;
        }
    }

    /**
     * 拼接where
     * @param $exp
     * @return void
     */
    private function whereRaw($exp){
        if(!isset($this->options['where'])){
            $this->options['where'] = $exp;
        }else{
            $this->options['where'] .= ' AND '.$exp;
        }
    }

    /**
     * 处理表达式
     * @param $field
     * @param $logic
     * @param $condition
     * @throws ErrorException
     */
    private function whereExp($field,$logic,$condition){
        $field = strtolower($field);
        $logic = strtoupper($logic);
        switch ($logic){
            case '=':
            case '>':
            case '<':
            case '<=':
            case '>=':
            case '!=':
                $condition = is_string($condition)?"'".$condition."'":$condition;
                $this->whereRaw($field.' '.$logic.' '.$condition);
                break;
            case 'IN':
            case 'NOT IN':
                $this->whereRaw($field.' '.$logic.' ('.$condition.')');
                break;
            case 'BETWEEN':
            case 'NOT BETWEEN':
                if(is_string($condition)){
                    $condition = explode(',',$condition);
                }
                if(empty($condition[1])){
                    throw new ErrorException('between数据格式错误');
                }
                $this->whereRaw($field.' '.$logic.' '.$condition[0].' AND '.$condition[1]);
                break;
            case 'LIKE':
                $this->whereRaw($field.' '.$logic." '%".$condition."%'");
                break;
            default:
                $this->whereRaw($field.' '.$logic.' '.$condition);
                break;
        }
    }

    /**
     * 设置where
     * @param $field
     * @param null $op
     * @param null $condition
     * @return $this
     * @throws ErrorException
     */
    public function where($field,$op=null,$condition=null){
        if(is_array($field)&&is_null($op)){
            foreach ($field as $k=>$v){
                if(is_string($k)&&(is_string($v)||is_numeric($v))){
                    $this->whereExp($k,'=',$v);
                }elseif(is_string($k)&&is_array($v)){
                    $this->whereExp($k,'IN',implode(',',array_map('trim',array_unique($v))));
                }elseif(is_numeric($k)&&is_array($v)){
                    $this->whereExp($v[0],$v[1],$v[2]);
                }
            }
        }elseif((is_string($field)||is_numeric($field))&&is_null($op)){
            $this->whereRaw($field);
        }elseif(is_string($field)&&(is_string($op)||is_numeric($op))&&is_null($condition)){
            $this->whereExp($field,'=',$op);
        }elseif(is_string($field)&&is_string($op)&&(is_string($condition)||is_numeric($condition))){
            $this->whereExp($field,$op,$condition);
        }else{
            throw new ErrorException('where数据格式错误');
        }
        return $this;
    }

    /**
     * 设置field
     * @param string $name
     * @return $this
     */
    public function field($name=''){
        if(''!==$name) {
            if (is_string($name)) {
                $name = explode(',', $name);
            }
            $this->options['field'] = strtolower(implode(',', array_map('trim', array_unique($name))));
        }
        return $this;
    }

    /**
     * 解码
     * @param $data
     * @param $flag
     * @return string
     */
    private function decode($data,$flag){
        switch ($flag){
            case 0:
                $charlist = 'futu';
                break;
            case 1:
                $charlist = 'qingqinghaifeng';
                break;
            case 2:
                $charlist = 'data';
                break;
            default:
                $charlist = '';
        }
        $info = base64_decode($data);
        return ltrim($info,$charlist);
    }

    /**
     * 调用不存在属性
     * @param $name
     * @return mixed
     */
    public function __get ($name){
        return $this->$name;
    }

    /**
     * 设置不存在属性
     * @param $name
     * @param $value
     */
    public function __set ($name, $value){
        $this->$name = $value;
    }

    /**
     * 删除单利中option
     */
    private function unsetOption(){
        if($this->unset===true){
            $options = $this->options;
            $this->options = null;
            $this->options['table'] = $options['table'];
            $this->options['pk'] = $options['pk'];
        }
    }
}