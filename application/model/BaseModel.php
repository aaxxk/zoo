<?php
namespace app\model;

use Think\Exception;
use think\Model;
use think\newlog\NewLog;

class BaseModel extends Model {

    protected $autoCheckFields = true; //Virtual model turns off automatic detection

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
     * Native SQL data query
     * @param $sql sql statement
     * @param array $param Binding parameters, you don’t need to pass it, only the array can be passed in
     * @return mixed Return a piece of data
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
     * Native SQL data query
     * @param $sql sql statement
     * @param array $param Binding parameters, you don’t need to pass it, only the array can be passed in
     * @return Return multiple pieces of data
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
     * Native SQL data execution
     * @param $sql sql statement
     * @param $param Binding parameters
     * @return bool|int Perform sql operation, return the number of affected rows when $record is true, otherwise return (bool) false or true
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
     * @param $sqlArr sql array, only supports execution of SQL statements
     * @param $paramArr Parameter array
     * @return bool sql transaction operation, return true on success, false on failure
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
     * @param $table Data table name
     * @param $condition Conditions for deleting sql
     * @return boolean Return true on success, false on failure
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
     * Query single field count
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
     * Get the maximum ID of the table+1
     * @param string $table Table Name
     * @return int
     */
    public function last_sequence_id($table){
        $sql = "SELECT NEXTVAL('" . $table . "') AS num";
        $result = $this->native_select_one($sql);

        if(!$result['num']) {
            throw_exception('Failed to get the primary key:' . $table, '');
        }
        return $result['num'];
    }

    /**
     * Determine whether it is a SELECT statement
     */
    protected function isSelectSql($sql){
        if(0 !== strpos(strtoupper($sql), 'SELECT')){
            throw_exception('The SQL query statement is invalid:' . $sql, '');
        }

        return true;
    }

    /**
     * Determine whether it is an execution type sql statement
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

        $i != 1 && throw_exception('The SQL statement is invalid:' . $sql, '');

        return true;
    }

    /**
     * Native SQL binding parameters
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
                $value[] = "'".$v."'"; //Coerce the parameter value to a quoted string
            }
        }

        return str_replace($bind, $value, $sql);
    }
}