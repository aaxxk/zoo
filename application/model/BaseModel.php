<?php
namespace app\model;

use Think\Exception;
use think\Model;
use think\newlog\NewLog;

class BaseModel extends Model {

    protected $autoCheckFields = true; //虚拟模型关闭自动检测

    /**
     * @param $table
     * @param $param
     * @param string $field
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function think_select_one($table, $param, $field = '*'){
        $where = array();
        $bind = array();
        foreach ($param as $k=>$v){
            $where[$k] = ':'.$k;
            $bind[':'.$k] = $v;
        }

        return $this->table($table)->field($field)->where($where)->bind($bind)->find();
    }

    /**
     * @param $table
     * @param $param
     * @param string $field
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function think_select_all($table, $param, $field = '*'){
        $where = array();
        $bind = array();
        foreach ($param as $k=>$v){
            $where[$k] = ':'.$k;
            $bind[':'.$k] = $v;
        }

        return $this->table($table)->field($field)->where($where)->bind($bind)->select();
    }

    /**
     * 原生sql数据查询
     * @param $sql sql语句
     * @param array $param 绑定参数，可不传，仅支持传入数组
     * @return mixed 返回一条数据
     */
    public function fetchRow($sql, $param = array()){
        try {
            $parseSql = $this->bindParam($sql, $param);
            $this->isSelectSql($parseSql);

            $result = $this->query($parseSql);
            return !empty($result) ? $result[0] : false;

        } catch (Exception $e) {
            echo  $e->getMessage();
        }
    }

    /**
     * 原生sql数据查询
     * @param $sql sql语句
     * @param array $param 绑定参数，可不传，仅支持传入数组
     * @return 返回多条数据
     */
    public function fetchAll($sql, $param = array()){
        try {
            $parseSql = $this->bindParam($sql, $param);
            $this->isSelectSql($parseSql);

            $result = $this->query($parseSql);
            return $result;
        } catch (Exception $e) {
            echo $e->getMessage();die();
        }
    }

    /**
     * 原生sql数据执行
     * @param $sql sql语句
     * @param $param 绑定参数
     * @return bool|int 执行sql操作，$record 为true时返回影响的行数，否则返回(bool) false或true
     */
    public function native_execute($sql, $param, $record=false){
        try {
            $parseSql = $this->bindParam($sql, $param);
            $this->isExecuteSql($parseSql);

            $result = $this->execute($parseSql);
            return $result !== false ? ($record ? $result : true) : false;
        } catch (Exception $e) {
            throw_exception($e->getMessage());
        }
    }

    /**
     * @param $sqlArr sql数组，只支持执行类sql语句
     * @param $paramArr 参数数组
     * @return bool sql事务操作，成功返回true，失败返回false
     * @throws \think\exception\PDOException
     */
    public function native_transaction($sqlArr, $paramArr){
        try {
            if(!is_array($sqlArr) || empty($sqlArr)){
                return false;
            }

            if(!empty($paramArr) && !is_array($paramArr)){
                return false;
            }

            $this->startTrans();

            foreach ($sqlArr as $sk => $sv) {
                $parseSql = $this->bindParam($sv, $paramArr[$sk]);
                $this->isExecuteSql($parseSql);
                $result = $this->execute($parseSql);
            }

            if(!$this->commit()){
                $this->rollback();
                return false;
            }else {
                return true;
            }
        } catch (Exception $e) {
            $this->rollback();
            throw_exception($e->getMessage());
        }
    }

    /**
     * @param $table
     * @param $param
     * @param $condition
     * @return bool|int|string
     */
    public function think_update($table, $param, $condition){

            $where = array();
            $bind = array();
            $update = array();

            foreach ($condition as $k=>$v){
                $where[$k] = ':w_'.$k;
                $bind['w_'.$k] = $v;
            }

            foreach ($param as $k=>$v){
                $update[$k] = ':u_'.$k;
                $bind['u_'.$k] = $v;
            }

            try{
                $result =  $this->table($table)->where($where)->bind($bind)->update($update);
            }catch (Exception $e){
                dump($e->getMessage());
                NewLog::log($e->getMessage());
                return false;
            }
            return $result;
    }

    /**
     * @param $table
     * @param $param
     * @return int|string
     */
    public function think_insert($table, $param){

            $insert = array();
            $bind = array();

            foreach ($param as $k => $v){
                $insert[$k] = ':i_'.$k;
                $bind['i_'.$k] = $v;
            }

            $result = $this->table($table)->bind($bind)->insertGetId($insert);
            return $result;
    }

    /**
     * @param $table 数据表名
     * @param $condition 删除sql的条件
     * @return boolean 成功返回true，失败返回false
     * @throws \think\exception\PDOException
     */
    public function think_delete($table, $condition){
        try {
            $this->startTrans();

            $where = array();
            $bind = array();

            foreach ($condition as $k=>$v){
                $where[$k] = ':w_'.$k;
                $bind[':w_'.$k] = $v;
            }

            $this->table($table)->where($where)->bind($bind)->delete();

            if(!$this->commit()){
                $this->rollback();
                return false;
            }else {
                return true;
            }
        } catch (Exception $e) {
            $this->rollback();
            throw_exception($e->getMessage());
        }
    }

    /**
     * 查询单字段count
     * @param $table
     * @param $param
     * @param string $field
     * @return mixed
     */
    public function think_count($table, $param, $field='*'){
        $where = array();
        $bind = array();
        foreach ($param as $k=>$v){
            $where[$k] = ':'.$k;
            $bind[$k] = $v;
        }
        try{
           $count =  $this->table($table)->where($where)->bind($bind)->count($field);
        }catch (Exception $e){
            $e->getMessage();die();
        }
        return $count;
    }

    /**
     * 获取表最大ID+1
     * @param string $table 表名
     * @return int
     */
    public function last_sequence_id($table){
        $sql = "SELECT NEXTVAL('" . $table . "') AS num";
        $result = $this->native_select_one($sql);

        if(!$result['num']) {
            throw_exception('获取主键失败：' . $table, '');
        }
        return $result['num'];
    }

    /**
     * 判断是否为SELECT语句
     */
    protected function isSelectSql($sql){
        if(0 !== strpos(strtoupper($sql), 'SELECT')){
            throw_exception('SQL查询语句不合法：' . $sql, '');
        }

        return true;
    }

    /**
     * 判断是否为执行类的sql语句
     */
    protected function isExecuteSql($sql){
        $extArr = array('UPDATE', 'DELETE', 'INSERT INTO');
        $i = 0;
        foreach ($extArr as $v){
            if(0 === strpos(strtoupper($sql), $v)){
                $i = 1;
                break;
            }
        }

        $i != 1 && throw_exception('SQL语句不合法：' . $sql, '');

        return true;
    }

    /**
     * 原生sql绑定参数
     */
    protected function bindParam($sql, $param){
        if(!empty($param) && !is_array($param)){
            return false;
        }

        $bind = $value = [];
        foreach ($param as $k => $v){
            $bind[] = ":".$k;

            if(strpos($v, "'") === 0 && substr($v, -1) == "'"){
                $value[] = $v;
            }else{
                $value[] = "'".$v."'"; //将参数值强制转为带引号的字符串
            }
        }

        return str_replace($bind, $value, $sql);
    }
}