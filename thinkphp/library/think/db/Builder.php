<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think\db;

use PDO;
use think\Exception;

abstract class Builder
{
    // connection object instance
    protected $connection;
    // Query object instance
    protected $query;

    // Database expression
    protected $exp = ['eq' => '=', 'neq' => '<>', 'gt' => '>', 'egt' => '>=', 'lt' => '<', 'elt' => '<=', 'notlike' => 'NOT LIKE', 'not like' => 'NOT LIKE', 'like' => 'LIKE', 'in' => 'IN', 'exp' => 'EXP', 'notin' => 'NOT IN', 'not in' => 'NOT IN', 'between' => 'BETWEEN', 'not between' => 'NOT BETWEEN', 'notbetween' => 'NOT BETWEEN', 'exists' => 'EXISTS', 'notexists' => 'NOT EXISTS', 'not exists' => 'NOT EXISTS', 'null' => 'NULL', 'notnull' => 'NOT NULL', 'not null' => 'NOT NULL', '> time' => '> TIME', '< time' => '< TIME', '>= time' => '>= TIME', '<= time' => '<= TIME', 'between time' => 'BETWEEN TIME', 'not between time' => 'NOT BETWEEN TIME', 'notbetween time' => 'NOT BETWEEN TIME'];

    // SQL expression
    protected $selectSql    = 'SELECT%DISTINCT% %FIELD% FROM %TABLE%%FORCE%%JOIN%%WHERE%%GROUP%%HAVING%%UNION%%ORDER%%LIMIT%%LOCK%%COMMENT%';
    protected $insertSql    = '%INSERT% INTO %TABLE% (%FIELD%) VALUES (%DATA%) %COMMENT%';
    protected $insertAllSql = '%INSERT% INTO %TABLE% (%FIELD%) %DATA% %COMMENT%';
    protected $updateSql    = 'UPDATE %TABLE% SET %SET% %JOIN% %WHERE% %ORDER%%LIMIT% %LOCK%%COMMENT%';
    protected $deleteSql    = 'DELETE FROM %TABLE% %USING% %JOIN% %WHERE% %ORDER%%LIMIT% %LOCK%%COMMENT%';

    /**
     * Constructor
     * @access public
     * @param Connection    $connection Database connection object instance
     * @param Query         $query      Database query object instance
     */
    public function __construct(Connection $connection, Query $query)
    {
        $this->connection = $connection;
        $this->query      = $query;
    }

    /**
     * Get the current connection object instance
     * @access public
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get the current Query object instance
     * @access public
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Replace the __TABLE_NAME__ string in the SQL statement with the prefixed table name (lowercase)
     * @access protected
     * @param string $sql sql statement
     * @return string
     */
    protected function parseSqlTable($sql)
    {
        return $this->query->parseSqlTable($sql);
    }

    /**
     * data analysis
     * @access protected
     * @param array     $data data
     * @param array     $options Query parameter
     * @return array
     * @throws Exception
     */
    protected function parseData($data, $options)
    {
        if (empty($data)) {
            return [];
        }

        // Get binding information
        $bind = $this->query->getFieldsBind($options['table']);
        if ('*' == $options['field']) {
            $fields = array_keys($bind);
        } else {
            $fields = $options['field'];
        }

        $result = [];
        foreach ($data as $key => $val) {
            $item = $this->parseKey($key, $options, true);
            if ($val instanceof Expression) {
                $result[$item] = $val->getValue();
                continue;
            } elseif (is_object($val) && method_exists($val, '__toString')) {
                // Object data write
                $val = $val->__toString();
            }
            if (false === strpos($key, '.') && !in_array($key, $fields, true)) {
                if ($options['strict']) {
                    throw new Exception('fields not exists:[' . $key . ']');
                }
            } elseif (is_null($val)) {
                $result[$item] = 'NULL';
            } elseif (is_array($val) && !empty($val)) {
                switch (strtolower($val[0])) {
                    case 'inc':
                        $result[$item] = $item . '+' . floatval($val[1]);
                        break;
                    case 'dec':
                        $result[$item] = $item . '-' . floatval($val[1]);
                        break;
                    case 'exp':
                        throw new Exception('not support data:[' . $val[0] . ']');
                }
            } elseif (is_scalar($val)) {
                // Filter non-scalar data
                if (0 === strpos($val, ':') && $this->query->isBind(substr($val, 1))) {
                    $result[$item] = $val;
                } else {
                    $key = str_replace('.', '_', $key);
                    $this->query->bind('data__' . $key, $val, isset($bind[$key]) ? $bind[$key] : PDO::PARAM_STR);
                    $result[$item] = ':data__' . $key;
                }
            }
        }
        return $result;
    }

    /**
     * Field name analysis
     * @access protected
     * @param string $key
     * @param array  $options
     * @return string
     */
    protected function parseKey($key, $options = [], $strict = false)
    {
        return $key;
    }

    /**
     * value analysis
     * @access protected
     * @param mixed     $value
     * @param string    $field
     * @return string|array
     */
    protected function parseValue($value, $field = '')
    {
        if (is_string($value)) {
            $value = strpos($value, ':') === 0 && $this->query->isBind(substr($value, 1)) ? $value : $this->connection->quote($value);
        } elseif (is_array($value)) {
            $value = array_map([$this, 'parseValue'], $value);
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif (is_null($value)) {
            $value = 'null';
        }
        return $value;
    }

    /**
     * field analysis
     * @access protected
     * @param mixed     $fields
     * @param array     $options
     * @return string
     */
    protected function parseField($fields, $options = [])
    {
        if ('*' == $fields || empty($fields)) {
            $fieldsStr = '*';
        } elseif (is_array($fields)) {
            $array = [];
            foreach ($fields as $key => $field) {
                if ($field instanceof Expression) {
                    $array[] = $field->getValue();
                } elseif (!is_numeric($key)) {
                    $array[] = $this->parseKey($key, $options) . ' AS ' . $this->parseKey($field, $options, true);
                } else {
                    $array[] = $this->parseKey($field, $options);
                }
            }
            $fieldsStr = implode(',', $array);
        }
        return $fieldsStr;
    }

    /**
     * table analysis
     * @access protected
     * @param mixed $tables
     * @param array $options
     * @return string
     */
    protected function parseTable($tables, $options = [])
    {
        $item = [];
        foreach ((array) $tables as $key => $table) {
            if (!is_numeric($key)) {
                $key    = $this->parseSqlTable($key);
                $item[] = $this->parseKey($key) . ' ' . (isset($options['alias'][$table]) ? $this->parseKey($options['alias'][$table]) : $this->parseKey($table));
            } else {
                $table = $this->parseSqlTable($table);
                if (isset($options['alias'][$table])) {
                    $item[] = $this->parseKey($table) . ' ' . $this->parseKey($options['alias'][$table]);
                } else {
                    $item[] = $this->parseKey($table);
                }
            }
        }
        return implode(',', $item);
    }

    /**
     * where analysis
     * @access protected
     * @param mixed $where   Query conditions
     * @param array $options Query parameter
     * @return string
     */
    protected function parseWhere($where, $options)
    {
        $whereStr = $this->buildWhere($where, $options);
        if (!empty($options['soft_delete'])) {
            // Additional soft delete conditions
            list($field, $condition) = $options['soft_delete'];

            $binds    = $this->query->getFieldsBind($options['table']);
            $whereStr = $whereStr ? '( ' . $whereStr . ' ) AND ' : '';
            $whereStr = $whereStr . $this->parseWhereItem($field, $condition, '', $options, $binds);
        }
        return empty($whereStr) ? '' : ' WHERE ' . $whereStr;
    }

    /**
     * Generate query condition SQL
     * @access public
     * @param mixed     $where
     * @param array     $options
     * @return string
     */
    public function buildWhere($where, $options)
    {
        if (empty($where)) {
            $where = [];
        }

        if ($where instanceof Query) {
            return $this->buildWhere($where->getOptions('where'), $options);
        }

        $whereStr = '';
        $binds    = $this->query->getFieldsBind($options['table']);
        foreach ($where as $key => $val) {
            $str = [];
            foreach ($val as $field => $value) {
                if ($value instanceof Expression) {
                    $str[] = ' ' . $key . ' ( ' . $field . ' ' . $value->getValue() . ' )';
                } elseif ($value instanceof \Closure) {
                    // Use closure query
                    $query = new Query($this->connection);
                    call_user_func_array($value, [ & $query]);
                    $whereClause = $this->buildWhere($query->getOptions('where'), $options);
                    if (!empty($whereClause)) {
                        $str[] = ' ' . $key . ' ( ' . $whereClause . ' )';
                    }
                } elseif (strpos($field, '|')) {
                    // Use the same query condition for different fields (OR)
                    $array = explode('|', $field);
                    $item  = [];
                    foreach ($array as $k) {
                        $item[] = $this->parseWhereItem($k, $value, '', $options, $binds);
                    }
                    $str[] = ' ' . $key . ' ( ' . implode(' OR ', $item) . ' )';
                } elseif (strpos($field, '&')) {
                    // Use the same query condition for different fields (AND)
                    $array = explode('&', $field);
                    $item  = [];
                    foreach ($array as $k) {
                        $item[] = $this->parseWhereItem($k, $value, '', $options, $binds);
                    }
                    $str[] = ' ' . $key . ' ( ' . implode(' AND ', $item) . ' )';
                } else {
                    // Use expression queries on fields
                    $field = is_string($field) ? $field : '';
                    $str[] = ' ' . $key . ' ' . $this->parseWhereItem($field, $value, $key, $options, $binds);
                }
            }

            $whereStr .= empty($whereStr) ? substr(implode(' ', $str), strlen($key) + 1) : implode(' ', $str);
        }

        return $whereStr;
    }

    // where subunit analysis
    protected function parseWhereItem($field, $val, $rule = '', $options = [], $binds = [], $bindName = null)
    {
        // Field analysis
        $key = $field ? $this->parseKey($field, $options, true) : '';

        // Query rules and conditions
        if (!is_array($val)) {
            $val = is_null($val) ? ['null', ''] : ['=', $val];
        }
        list($exp, $value) = $val;

        // Use multiple query conditions for a field
        if (is_array($exp)) {
            $item = array_pop($val);
            // Pass in or or and
            if (is_string($item) && in_array($item, ['AND', 'and', 'OR', 'or'])) {
                $rule = $item;
            } else {
                array_push($val, $item);
            }
            foreach ($val as $k => $item) {
                $bindName = 'where_' . str_replace('.', '_', $field) . '_' . $k;
                $str[]    = $this->parseWhereItem($field, $item, $rule, $options, $binds, $bindName);
            }
            return '( ' . implode(' ' . $rule . ' ', $str) . ' )';
        }

        // Detection operator
        if (!in_array($exp, $this->exp)) {
            $exp = strtolower($exp);
            if (isset($this->exp[$exp])) {
                $exp = $this->exp[$exp];
            } else {
                throw new Exception('where express error:' . $exp);
            }
        }
        $bindName = $bindName ?: 'where_' . $rule . '_' . str_replace(['.', '-'], '_', $field);
        if (preg_match('/\W/', $bindName)) {
            // Handling field names with non-word characters
            $bindName = md5($bindName);
        }

        if ($value instanceof Expression) {

        } elseif (is_object($value) && method_exists($value, '__toString')) {
            // Object data write
            $value = $value->__toString();
        }

        $bindType = isset($binds[$field]) ? $binds[$field] : PDO::PARAM_STR;
        if (is_scalar($value) && array_key_exists($field, $binds) && !in_array($exp, ['EXP', 'NOT NULL', 'NULL', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN']) && strpos($exp, 'TIME') === false) {
            if (strpos($value, ':') !== 0 || !$this->query->isBind(substr($value, 1))) {
                if ($this->query->isBind($bindName)) {
                    $bindName .= '_' . str_replace('.', '_', uniqid('', true));
                }
                $this->query->bind($bindName, $value, $bindType);
                $value = ':' . $bindName;
            }
        }

        $whereStr = '';
        if (in_array($exp, ['=', '<>', '>', '>=', '<', '<='])) {
            // Comparison operation
            if ($value instanceof \Closure) {
                $whereStr .= $key . ' ' . $exp . ' ' . $this->parseClosure($value);
            } else {
                $whereStr .= $key . ' ' . $exp . ' ' . $this->parseValue($value, $field);
            }
        } elseif ('LIKE' == $exp || 'NOT LIKE' == $exp) {
            // Fuzzy matching
            if (is_array($value)) {
                foreach ($value as $item) {
                    $array[] = $key . ' ' . $exp . ' ' . $this->parseValue($item, $field);
                }
                $logic = isset($val[2]) ? $val[2] : 'AND';
                $whereStr .= '(' . implode($array, ' ' . strtoupper($logic) . ' ') . ')';
            } else {
                $whereStr .= $key . ' ' . $exp . ' ' . $this->parseValue($value, $field);
            }
        } elseif ('EXP' == $exp) {
            // Expression query
            if ($value instanceof Expression) {
                $whereStr .= '( ' . $key . ' ' . $value->getValue() . ' )';
            } else {
                throw new Exception('where express error:' . $exp);
            }
        } elseif (in_array($exp, ['NOT NULL', 'NULL'])) {
            // NULL Inquire
            $whereStr .= $key . ' IS ' . $exp;
        } elseif (in_array($exp, ['NOT IN', 'IN'])) {
            // IN Inquire
            if ($value instanceof \Closure) {
                $whereStr .= $key . ' ' . $exp . ' ' . $this->parseClosure($value);
            } else {
                $value = array_unique(is_array($value) ? $value : explode(',', $value));
                if (array_key_exists($field, $binds)) {
                    $bind  = [];
                    $array = [];
                    $i     = 0;
                    foreach ($value as $v) {
                        $i++;
                        if ($this->query->isBind($bindName . '_in_' . $i)) {
                            $bindKey = $bindName . '_in_' . uniqid() . '_' . $i;
                        } else {
                            $bindKey = $bindName . '_in_' . $i;
                        }
                        $bind[$bindKey] = [$v, $bindType];
                        $array[]        = ':' . $bindKey;
                    }
                    $this->query->bind($bind);
                    $zone = implode(',', $array);
                } else {
                    $zone = implode(',', $this->parseValue($value, $field));
                }
                $whereStr .= $key . ' ' . $exp . ' (' . (empty($zone) ? "''" : $zone) . ')';
            }
        } elseif (in_array($exp, ['NOT BETWEEN', 'BETWEEN'])) {
            // BETWEEN Inquire
            $data = is_array($value) ? $value : explode(',', $value);
            if (array_key_exists($field, $binds)) {
                if ($this->query->isBind($bindName . '_between_1')) {
                    $bindKey1 = $bindName . '_between_1' . uniqid();
                    $bindKey2 = $bindName . '_between_2' . uniqid();
                } else {
                    $bindKey1 = $bindName . '_between_1';
                    $bindKey2 = $bindName . '_between_2';
                }
                $bind = [
                    $bindKey1 => [$data[0], $bindType],
                    $bindKey2 => [$data[1], $bindType],
                ];
                $this->query->bind($bind);
                $between = ':' . $bindKey1 . ' AND :' . $bindKey2;
            } else {
                $between = $this->parseValue($data[0], $field) . ' AND ' . $this->parseValue($data[1], $field);
            }
            $whereStr .= $key . ' ' . $exp . ' ' . $between;
        } elseif (in_array($exp, ['NOT EXISTS', 'EXISTS'])) {
            // EXISTS Inquire
            if ($value instanceof \Closure) {
                $whereStr .= $exp . ' ' . $this->parseClosure($value);
            } else {
                $whereStr .= $exp . ' (' . $value . ')';
            }
        } elseif (in_array($exp, ['< TIME', '> TIME', '<= TIME', '>= TIME'])) {
            $whereStr .= $key . ' ' . substr($exp, 0, 2) . ' ' . $this->parseDateTime($value, $field, $options, $bindName, $bindType);
        } elseif (in_array($exp, ['BETWEEN TIME', 'NOT BETWEEN TIME'])) {
            if (is_string($value)) {
                $value = explode(',', $value);
            }

            $whereStr .= $key . ' ' . substr($exp, 0, -4) . $this->parseDateTime($value[0], $field, $options, $bindName . '_between_1', $bindType) . ' AND ' . $this->parseDateTime($value[1], $field, $options, $bindName . '_between_2', $bindType);
        }
        return $whereStr;
    }

    // Execute closure subquery
    protected function parseClosure($call, $show = true)
    {
        $query = new Query($this->connection);
        call_user_func_array($call, [ & $query]);
        return $query->buildSql($show);
    }

    /**
     * Date and time condition analysis
     * @access protected
     * @param string    $value
     * @param string    $key
     * @param array     $options
     * @param string    $bindName
     * @param integer   $bindType
     * @return string
     */
    protected function parseDateTime($value, $key, $options = [], $bindName = null, $bindType = null)
    {
        // Get time field type
        if (strpos($key, '.')) {
            list($table, $key) = explode('.', $key);
            if (isset($options['alias']) && $pos = array_search($table, $options['alias'])) {
                $table = $pos;
            }
        } else {
            $table = $options['table'];
        }
        $type = $this->query->getTableInfo($table, 'type');
        if (isset($type[$key])) {
            $info = $type[$key];
        }
        if (isset($info)) {
            if (is_string($value)) {
                $value = strtotime($value) ?: $value;
            }

            if (preg_match('/(datetime|timestamp)/is', $info)) {
                // Date and timestamp type
                $value = date('Y-m-d H:i:s', $value);
            } elseif (preg_match('/(date)/is', $info)) {
                // Date and timestamp type
                $value = date('Y-m-d', $value);
            }
        }
        $bindName = $bindName ?: $key;

        if ($this->query->isBind($bindName)) {
            $bindName .= '_' . str_replace('.', '_', uniqid('', true));
        }

        $this->query->bind($bindName, $value, $bindType);
        return ':' . $bindName;
    }

    /**
     * limit analysis
     * @access protected
     * @param mixed $limit
     * @return string
     */
    protected function parseLimit($limit)
    {
        return (!empty($limit) && false === strpos($limit, '(')) ? ' LIMIT ' . $limit . ' ' : '';
    }

    /**
     * join analysis
     * @access protected
     * @param array $join
     * @param array $options Query conditions
     * @return string
     */
    protected function parseJoin($join, $options = [])
    {
        $joinStr = '';
        if (!empty($join)) {
            foreach ($join as $item) {
                list($table, $type, $on) = $item;
                $condition               = [];
                foreach ((array) $on as $val) {
                    if ($val instanceof Expression) {
                        $condition[] = $val->getValue();
                    } elseif (strpos($val, '=')) {
                        list($val1, $val2) = explode('=', $val, 2);
                        $condition[]       = $this->parseKey($val1, $options) . '=' . $this->parseKey($val2, $options);
                    } else {
                        $condition[] = $val;
                    }
                }

                $table = $this->parseTable($table, $options);
                $joinStr .= ' ' . $type . ' JOIN ' . $table . ' ON ' . implode(' AND ', $condition);
            }
        }
        return $joinStr;
    }

    /**
     * order analysis
     * @access protected
     * @param mixed $order
     * @param array $options Query conditions
     * @return string
     */
    protected function parseOrder($order, $options = [])
    {
        if (empty($order)) {
            return '';
        }

        $array = [];
        foreach ($order as $key => $val) {
            if ($val instanceof Expression) {
                $array[] = $val->getValue();
            } elseif ('[rand]' == $val) {
                $array[] = $this->parseRand();
            } else {
                if (is_numeric($key)) {
                    list($key, $sort) = explode(' ', strpos($val, ' ') ? $val : $val . ' ');
                } else {
                    $sort = $val;
                }
                $sort    = strtoupper($sort);
                $sort    = in_array($sort, ['ASC', 'DESC'], true) ? ' ' . $sort : '';
                $array[] = $this->parseKey($key, $options, true) . $sort;
            }
        }
        $order = implode(',', $array);

        return !empty($order) ? ' ORDER BY ' . $order : '';
    }

    /**
     * group analysis
     * @access protected
     * @param mixed $group
     * @return string
     */
    protected function parseGroup($group)
    {
        return !empty($group) ? ' GROUP BY ' . $this->parseKey($group) : '';
    }

    /**
     * having analysis
     * @access protected
     * @param string $having
     * @return string
     */
    protected function parseHaving($having)
    {
        return !empty($having) ? ' HAVING ' . $having : '';
    }

    /**
     * comment analysis
     * @access protected
     * @param string $comment
     * @return string
     */
    protected function parseComment($comment)
    {
        if (false !== strpos($comment, '*/')) {
            $comment = strstr($coment, '*/', true);
        }
        return !empty($comment) ? ' /* ' . $comment . ' */' : '';
    }

    /**
     * distinct analysis
     * @access protected
     * @param mixed $distinct
     * @return string
     */
    protected function parseDistinct($distinct)
    {
        return !empty($distinct) ? ' DISTINCT ' : '';
    }

    /**
     * union analysis
     * @access protected
     * @param mixed $union
     * @return string
     */
    protected function parseUnion($union)
    {
        if (empty($union)) {
            return '';
        }
        $type = $union['type'];
        unset($union['type']);
        foreach ($union as $u) {
            if ($u instanceof \Closure) {
                $sql[] = $type . ' ' . $this->parseClosure($u);
            } elseif (is_string($u)) {
                $sql[] = $type . ' ( ' . $this->parseSqlTable($u) . ' )';
            }
        }
        return ' ' . implode(' ', $sql);
    }

    /**
     * Index analysis, you can specify the mandatory index in the operation chain
     * @access protected
     * @param mixed $index
     * @return string
     */
    protected function parseForce($index)
    {
        if (empty($index)) {
            return '';
        }

        return sprintf(" FORCE INDEX ( %s ) ", is_array($index) ? implode(',', $index) : $index);
    }

    /**
     * Set up the lock mechanism
     * @access protected
     * @param bool|string $lock
     * @return string
     */
    protected function parseLock($lock = false)
    {
        if (is_bool($lock)) {
            return $lock ? ' FOR UPDATE ' : '';
        } elseif (is_string($lock)) {
            return ' ' . trim($lock) . ' ';
        }
    }

    /**
     * Generate query SQL
     * @access public
     * @param array $options expression
     * @return string
     */
    public function select($options = [])
    {
        $sql = str_replace(
            ['%TABLE%', '%DISTINCT%', '%FIELD%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%', '%UNION%', '%LOCK%', '%COMMENT%', '%FORCE%'],
            [
                $this->parseTable($options['table'], $options),
                $this->parseDistinct($options['distinct']),
                $this->parseField($options['field'], $options),
                $this->parseJoin($options['join'], $options),
                $this->parseWhere($options['where'], $options),
                $this->parseGroup($options['group']),
                $this->parseHaving($options['having']),
                $this->parseOrder($options['order'], $options),
                $this->parseLimit($options['limit']),
                $this->parseUnion($options['union']),
                $this->parseLock($options['lock']),
                $this->parseComment($options['comment']),
                $this->parseForce($options['force']),
            ], $this->selectSql);
        return $sql;
    }

    /**
     * Generate insert SQL
     * @access public
     * @param array     $data data
     * @param array     $options expression
     * @param bool      $replace Whether to replace
     * @return string
     */
    public function insert(array $data, $options = [], $replace = false)
    {
        // Analyze and process data
        $data = $this->parseData($data, $options);
        if (empty($data)) {
            return 0;
        }
        $fields = array_keys($data);
        $values = array_values($data);

        $sql = str_replace(
            ['%INSERT%', '%TABLE%', '%FIELD%', '%DATA%', '%COMMENT%'],
            [
                $replace ? 'REPLACE' : 'INSERT',
                $this->parseTable($options['table'], $options),
                implode(' , ', $fields),
                implode(' , ', $values),
                $this->parseComment($options['comment']),
            ], $this->insertSql);

        return $sql;
    }

    /**
     * Generate insertall SQL
     * @access public
     * @param array     $dataSet data set
     * @param array     $options expression
     * @param bool      $replace Whether to replace
     * @return string
     * @throws Exception
     */
    public function insertAll($dataSet, $options = [], $replace = false)
    {
        // Get legal fields
        if ('*' == $options['field']) {
            $fields = array_keys($this->query->getFieldsType($options['table']));
        } else {
            $fields = $options['field'];
        }

        foreach ($dataSet as $data) {
            foreach ($data as $key => $val) {
                if (!in_array($key, $fields, true)) {
                    if ($options['strict']) {
                        throw new Exception('fields not exists:[' . $key . ']');
                    }
                    unset($data[$key]);
                } elseif (is_null($val)) {
                    $data[$key] = 'NULL';
                } elseif (is_scalar($val)) {
                    $data[$key] = $this->parseValue($val, $key);
                } elseif (is_object($val) && method_exists($val, '__toString')) {
                    // Object data write
                    $data[$key] = $val->__toString();
                } else {
                    // Filter out non-scalar data
                    unset($data[$key]);
                }
            }
            $value    = array_values($data);
            $values[] = 'SELECT ' . implode(',', $value);

            if (!isset($insertFields)) {
                $insertFields = array_keys($data);
            }
        }

        foreach ($insertFields as $field) {
            $fields[] = $this->parseKey($query, $field, true);
        }

        return str_replace(
            ['%INSERT%', '%TABLE%', '%FIELD%', '%DATA%', '%COMMENT%'],
            [
                $replace ? 'REPLACE' : 'INSERT',
                $this->parseTable($options['table'], $options),
                implode(' , ', $insertFields),
                implode(' UNION ALL ', $values),
                $this->parseComment($options['comment']),
            ], $this->insertAllSql);
    }

    /**
     * Generate select insert SQL
     * @access public
     * @param array     $fields data
     * @param string    $table data sheet
     * @param array     $options expression
     * @return string
     */
    public function selectInsert($fields, $table, $options)
    {
        if (is_string($fields)) {
            $fields = explode(',', $fields);
        }

        $fields = array_map([$this, 'parseKey'], $fields);
        $sql    = 'INSERT INTO ' . $this->parseTable($table, $options) . ' (' . implode(',', $fields) . ') ' . $this->select($options);
        return $sql;
    }

    /**
     * Generate update SQL
     * @access public
     * @param array     $data data
     * @param array     $options expression
     * @return string
     */
    public function update($data, $options)
    {
        $table = $this->parseTable($options['table'], $options);
        $data  = $this->parseData($data, $options);
        if (empty($data)) {
            return '';
        }
        foreach ($data as $key => $val) {
            $set[] = $key . '=' . $val;
        }

        $sql = str_replace(
            ['%TABLE%', '%SET%', '%JOIN%', '%WHERE%', '%ORDER%', '%LIMIT%', '%LOCK%', '%COMMENT%'],
            [
                $this->parseTable($options['table'], $options),
                implode(',', $set),
                $this->parseJoin($options['join'], $options),
                $this->parseWhere($options['where'], $options),
                $this->parseOrder($options['order'], $options),
                $this->parseLimit($options['limit']),
                $this->parseLock($options['lock']),
                $this->parseComment($options['comment']),
            ], $this->updateSql);

        return $sql;
    }

    /**
     * Generate delete SQL
     * @access public
     * @param array $options expression
     * @return string
     */
    public function delete($options)
    {
        $sql = str_replace(
            ['%TABLE%', '%USING%', '%JOIN%', '%WHERE%', '%ORDER%', '%LIMIT%', '%LOCK%', '%COMMENT%'],
            [
                $this->parseTable($options['table'], $options),
                !empty($options['using']) ? ' USING ' . $this->parseTable($options['using'], $options) . ' ' : '',
                $this->parseJoin($options['join'], $options),
                $this->parseWhere($options['where'], $options),
                $this->parseOrder($options['order'], $options),
                $this->parseLimit($options['limit']),
                $this->parseLock($options['lock']),
                $this->parseComment($options['comment']),
            ], $this->deleteSql);

        return $sql;
    }
}
