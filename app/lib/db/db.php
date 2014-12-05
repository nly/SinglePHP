<?php
/**
 * Created by PhpStorm
 * @desc: DB中间层
 * @package: Lib\Db
 * @author: leandre <nly92@foxmail.com>
 * @copyright: copyright(2014) leandre.cn
 * @version: 14/11/6
 */
namespace Lib\Db;

use Single\Register;
use Single\SingleException;

class Db
{
    // 数据库类型
    protected $dbType = null;

    // 是否自动释放查询结果
    protected $autoFree = false;

    // 是否使用持久链接
    protected $pconnect = false;

    // 当前SQL
    protected $queryStr = '';

    // 最后插入的id
    protected $lastInsId = null;

    //影响记录数
    protected $numRows = 0;

    // 事务指令数
    protected $transTimes = 0;

    // 错误信息
    protected $error = '';

    // 数据库连接
    protected $link = null;

    // 当前查询id
    protected $queryId = null;

    // 是否已经连接数据库
    protected $connected = false;

    // 数据库链接参数配置
    protected $config = '';

    // 数据库表达式
    protected $comparison = array('eq' => '=', 'neq' => '<>', 'gt' => '>', 'egt' => '>=', 'lt' => '<', 'elt' => '<=', 'notlike' => 'NOT LIKE', 'like' => 'LIKE', 'in' => 'IN', 'notin' => 'NOT IN');

    // 查询表达式
    protected $selectSql = 'SELECT%DISTINCT% %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT% %UNION%%COMMENT%';

    //参数绑定
    protected $bind = array();

    /**
     * 数据库链接实例
     * @param array $db_config
     * @return mixed 返回数据库驱动类
     */
    public static function getInstance(array $db_config)
    {
        $instance = array();
        $guid = to_guid_string($db_config);
        if (!isset($instance[$guid])) {
            $obj = Register::get(__CLASS__);
            $instance[$guid] = $obj->factory($db_config);
        }
        return $instance[$guid];
    }

    /**
     * 加载实际的数据库驱动类
     * @param array $db_config 数据库配置信息
     * @return mixed
     * @throws \Single\SingleException
     */
    public function factory(array $db_config)
    {
        $db_config = $this->parseConfig($db_config);
        $class = 'Lib\Db\Pdo';
        //$class = 'Lib\Db\\' . $db_config['dbtype'];
        if (!class_exists($class)) {
            throw new SingleException($class . ' is not defined');
        }
        $db = Register::get($class, array($db_config));
        return $db;
    }

    /**
     * 分析数据库配置信息
     * @param array $db_config 数据库配置信息
     * @return array
     * @throws \Single\SingleException
     */
    private function parseConfig(array $db_config)
    {
        if (!is_array($db_config) || empty($db_config)) {
            throw new SingleException('DB_CONFIG must be an valid array');
        }
        $db_config = array_change_key_case($db_config, CASE_LOWER);
        $db_config = array(
            'dsn' => $db_config['db_dsn'],
            'username' => $db_config['db_user'],
            'password' => $db_config['db_pwd'],
            'charset' => isset($db_config['db_charset']) ? $db_config['db_charset'] : 'utf8',
            'params' => $db_config['db_params'],
        );
        return $db_config;
    }

    /**
     * 根据DSN获取数据库类型 返回大写
     * @param $dsn dsn字符串
     * @return string
     */
    protected function getDsnType($dsn)
    {
        $match = explode(':', $dsn);
        $db_type = strtoupper(trim($match[0]));
        return $db_type;
    }

    /**
     * 初始化数据库连接
     */
    protected function initConnect()
    {
        if (!$this->connected) $this->link = $this->connect();
    }

    /**
     * 设置锁机制
     * @param bool $lock
     * @return string
     */
    protected function parseLock($lock = false)
    {
        if (!$lock) return '';
        if ('ORACLE' == $this->dbType) {
            return ' FOR UPDATE NOWAIT ';
        }
        return ' FOR UPDATE ';
    }

    /**
     * set分析
     * @param $data
     * @return string
     */
    protected function parseSet($data)
    {
        foreach ($data as $key => $val) {
            if (is_array($val) && 'exp' == $val[0]) {
                $set[] = $this->parseKey($key) . '=' . $val[1];
            } elseif (is_scalar($val) || is_null($val)) { // 过滤非标量数据
                $set[] = $this->parseKey($key) . '=' . $this->parseValue($val);
            }
        }
        return ' SET ' . implode(',', $set);
    }

    /**
     * 参数绑定
     * @param $name 绑定参数名
     * @param $value 绑定值
     */
    protected function bindParam($name, $value)
    {
        $this->bind[':' . $name] = $value;
    }

    /**
     * 参数绑定分析
     * @param $bind
     * @return array
     */
    protected function parseBind($bind)
    {
        $bind = array_merge($this->bind, $bind);
        $this->bind = array();
        return $bind;
    }

    /**
     * 字段名分析
     * @param $key
     * @return mixed
     */
    protected function parseKey(&$key)
    {
        return $key;
    }

    /**
     * value分析
     * @param $value
     * @return array|string
     */
    protected function parseValue($value)
    {
        if (is_string($value)) {
            $value = '\'' . $this->escapeString($value) . '\'';
        } elseif (isset($value[0]) && is_string($value[0] && strtolower($value[0]) == 'exp')) {
            $value = $this->escapeString($value[1]);
        } elseif (is_array($value)) {
            $value = array_map(array($this, 'parseValue'), $value);
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif (is_null($value)) {
            $value = 'null';
        }
        return $value;
    }

    /**
     * field分析
     * @param $fields
     * @return mixed|string
     */
    protected function parseField($fields)
    {
        if (is_string($fields) && strpos($fields, ',')) {
            $fields = explode(',', $fields);
        }
        if (is_array($fields)) {
            $array = array();
            // 完善数组方式传字段名的支持
            // 支持 'field1'=>'field2' 这样的字段别名定义
            foreach ($fields as $key => $value) {
                if (!is_numeric($key)) {
                    $array[] = $this->parseKey($key) . ' AS ' . $this->parseKey($value);
                } else {
                    $array[] = $this->parseKey($value);
                }
            }
            $fieldsStr = implode(',', $array);
        } elseif (is_string($fields) && !empty($fields)) {
            $fieldsStr = $this->parseKey($fields);
        } else {
            $fieldsStr = '*';
        }
        return $fieldsStr;
    }

    /**
     * table分析
     * @param $tables
     * @return array|string
     */
    protected function parseTable($tables)
    {
        if (is_array($tables)) {
            $array = array();
            foreach ($tables as $table => $alias) {
                if (!is_numeric($tables)) {
                    $array[] = $this->parseKey($table) . ' ' . $this->parseKey($alias);
                } else {
                    $array[] = $this->parseKey($table);
                }
            }
            $tables = $array;
        } elseif (is_string($tables)) {
            $tables = explode(',', $tables);
            array_walk($tables, array($this, 'parseKey'));
        }
        $tables = implode(',', $tables);
        return $tables;
    }

    /**
     * where分析
     * @param $where
     * @return string
     */
    protected function parseWhere($where)
    {
        $whereStr = '';
        if (is_string($where)) {
            // 直接使用字符串条件
            $whereStr = $where;
        } else { // 使用数组表达式
            $operate = isset($where['_logic']) ? strtoupper($where['_logic']) : '';
            if (in_array($operate, array('AND', 'OR', 'XOR'))) {
                // 定义逻辑运算规则 例如 OR XOR AND NOT
                $operate = ' ' . $operate . ' ';
                unset($where['_logic']);
            } else {
                // 默认进行 AND 运算
                $operate = ' AND ';
            }
            foreach ($where as $key => $val) {
                if (is_numeric($key)) {
                    $key = '_complex';
                }
                if (0 === strpos($key, '_')) {
                    // 解析特殊条件表达式
                    $whereStr .= $this->parseSingleWhere($key, $val);
                } else {
                    // 查询字段的安全过滤
                    if (!preg_match('/^[A-Z_\|\&\-.a-z0-9\(\)\,]+$/', trim($key))) {
                        throw new SingleException('EXPRESS ERROR : ' . $key);
                    }
                    // 多条件支持
                    $multi = is_array($val) && isset($val['_multi']);
                    $key = trim($key);
                    if (strpos($key, '|')) { // 支持 name|title|nickname 方式定义查询字段
                        $array = explode('|', $key);
                        $str = array();
                        foreach ($array as $m => $k) {
                            $v = $multi ? $val[$m] : $val;
                            $str[] = $this->parseWhereItem($this->parseKey($k), $v);
                        }
                        $whereStr .= '( ' . implode(' OR ', $str) . ' )';
                    } elseif (strpos($key, '&')) {
                        $array = explode('&', $key);
                        $str = array();
                        foreach ($array as $m => $k) {
                            $v = $multi ? $val[$m] : $val;
                            $str[] = '(' . $this->parseWhereItem($this->parseKey($k), $v) . ')';
                        }
                        $whereStr .= '( ' . implode(' AND ', $str) . ' )';
                    } else {
                        $whereStr .= $this->parseWhereItem($this->parseKey($key), $val);
                    }
                }
                $whereStr .= $operate;
            }
            $whereStr = substr($whereStr, 0, -strlen($operate));
        }
        return empty($whereStr) ? '' : ' WHERE ' . $whereStr;
    }

    /**
     * where子单元分析
     * @param $key
     * @param $value
     * @return string
     * @throws \Single\SingleException
     */
    protected function parseWhereItem($key, $value)
    {
        $whereStr = '';
        if (is_array($value)) {
            if (is_string($value[0])) {
                if (preg_match('/^(EQ|NEQ|GT|EGT|LT|ELT)$/i', $value[0])) { // 比较运算
                    $whereStr .= $key . ' ' . $this->comparison[strtolower($value[0])] . ' ' . $this->parseValue($value[1]);
                } elseif (preg_match('/^(LIKE|NOTLIKE)$/i', $value[0])) { // 模糊查找
                    if (is_array($value[1])) {
                        $likeLogic = isset($value[2]) ? strtoupper($value[2]) : 'OR';
                        if (in_array($likeLogic, array('AND', 'OR', 'XOR'))) {
                            $likeStr = $this->comparison[strtolower($value[0])];
                            $like = array();
                            foreach ($value[1] as $item) {
                                $like[] = $key . ' ' . $likeStr . ' ' . $this->parseValue($item);
                            }
                            $whereStr .= '(' . implode(' ' . $likeStr . ' ', $like) . ')';
                        }
                    } else {
                        $whereStr .= $key . ' ' . $this->comparison[strtolower($value[0])] . ' ' . $this->parseValue($value[1]);
                    }
                } elseif ('exp' == strtolower($value[0])) { // 使用表达式
                    $whereStr .= ' (' . $key . ' ' . $value[1] . ') ';
                } elseif (preg_match('/IN/i', $value[0])) { // IN 运算
                    if (isset($value[2]) && 'exp' == $value[2]) {
                        $whereStr .= $key . ' ' . strtoupper($value[0]) . ' ' . $value[1];
                    } else {
                        if (is_string($value[1])) {
                            $value[1] = explode(',', $value[1]);
                        }
                        $zone = implode(',', $this->parseValue($value[1]));
                        $whereStr .= $key . ' ' . strtoupper($value[0]) . ' (' . $zone . ')';
                    }
                } elseif (preg_match('/BETWEEN/i', $value[0])) { // BETWEEN运算
                    $data = is_string($value[1]) ? explode(',', $value[1]) : $value[1];
                    $whereStr .= ' (' . $key . ' ' . strtoupper($value[0]) . ' ' . $this->parseValue($data[0]) . ' AND ' . $this->parseValue($data[1]) . ' )';
                } else {
                    throw new SingleException('EXPRESS ERROR : ' . $value[0]);
                }
            } else {
                $count = count($value);
                $rule = isset($value[$count - 1]) ? (is_array($value[$count - 1])) ? strtoupper($value[$count - 1][0]) : strtoupper($value[$count - 1]) : '';
                if (in_array($rule, array('AND', 'OR', 'XOR'))) {
                    $count--;
                } else {
                    $rule = 'AND';
                }
                for ($i = 0; $i < $count; $i++) {
                    $data = is_array($value[$i]) ? $value[$i][1] : $value[$i];
                    if ('exp' == strtolower($value[$i][0])) {
                        $whereStr .= '(' . $key . ' ' . $data . ') ' . $rule . ' ';
                    } else {
                        $whereStr .= '(' . $this->parseWhereItem($key, $value[$i]) . ') ' . $rule . ' ';
                    }
                }
                $whereStr = substr($whereStr, 0, -4);
            }
        } else {
            // 对字符串类型字段采用模糊匹配
            $whereStr .= $key . ' = ' . $this->parseValue($value);
        }
        return $whereStr;
    }

    /**
     * 特殊条件分析
     * @param $key
     * @param $val
     * @return string
     */
    protected function parseSingleWhere($key, $val)
    {
        $whereStr = '';
        switch ($key) {
            case '_string':
                // 字符串模式查询条件
                $whereStr = $val;
                break;
            case '_complex':
                // 复合查询条件
                $whereStr = is_string($val) ? $val : substr($this->parseWhere($val), 6);
                break;
            case '_query':
                // 字符串模式查询条件
                parse_str($val, $where);
                if (isset($where['_logic'])) {
                    $op = ' ' . strtoupper($where['_logic']) . ' ';
                    unset($where['_logic']);
                } else {
                    $op = ' AND ';
                }
                $array = array();
                foreach ($where as $field => $data)
                    $array[] = $this->parseKey($field) . ' = ' . $this->parseValue($data);
                $whereStr = implode($op, $array);
                break;
        }
        return '( ' . $whereStr . ' )';
    }

    /**
     * limit分析
     * @param $limit
     * @return string
     */
    protected function parseLimit($limit)
    {
        return !empty($limit) ? ' LIMIT ' . $limit . ' ' : '';
    }

    /**
     * join分析
     * @param $join
     * @return string
     */
    protected function parseJoin($join)
    {
        return !empty($join) ? ' ' . implode(' ', $join) . ' ' : '';
    }

    /**
     * order分析
     * @param $order
     * @return string
     */
    protected function parseOrder($order)
    {
        if (is_array($order)) {
            $array = array();
            foreach ($order as $key => $value) {
                if (is_numeric($key)) {
                    $array[] = $this->parseKey($value);
                } else {
                    $array[] = $this->parseKey($key) . ' ' . $value;
                }
                $order = implode(',', $array);
            }
        }
        return !empty($order) ? ' ORDER BY ' . $order : '';
    }

    /**
     * group分析
     * @param $group
     * @return string
     */
    protected function parseGroup($group)
    {
        return !empty($group) ? ' GROUP BY ' . $group : '';
    }

    /**
     * having分析
     * @param $having
     * @return string
     */
    protected function parseHaving($having)
    {
        return !empty($having) ? ' HAVING ' . $having : '';
    }

    /**
     * comment分析
     * @param $comment
     * @return string
     */
    protected function parseComment($comment)
    {
        return !empty($comment) ? ' /* ' . $comment . ' */' : '';
    }

    /**
     * distinct分析
     * @param $distinct
     * @return string
     */
    protected function parseDistinct($distinct)
    {
        return !empty($distinct) ? ' DISTINCT ' : '';
    }

    /**
     * union分析
     * @param $union
     * @return string
     */
    protected function parseUnion($union)
    {
        if (empty($union)) return '';
        if (isset($union['_all'])) {
            $str = 'UNION ALL ';
            unset($union['_all']);
        } else {
            $str = 'UNION ';
        }
        foreach ($union as $u) {
            $sql[] = $str . (is_array($u) ? $this->buildSelectSql($u) : $u);
        }
        return implode(' ', $sql);
    }

    /**
     * 插入记录
     * @param $data 数据
     * @param array $options 参数表达式
     * @param bool $replace 是否replace
     * @return mixed
     */
    public function insert($data, $options = array(), $replace = false)
    {
        $values = $fields = array();
        foreach ($data as $Key => $val) {
            if (is_array($val) && 'exp' == $val[0]) {
                $fields[] = $this->parseKey($Key);
                $values[] = $val[1];
            } elseif (is_scalar($val) || is_null($val)) {
                $fields[] = $this->parseKey($Key);
                $name = '_' . $Key . '_';
                $values[] = ':' . $name;
                $this->bindParam($name, $val);
            }
        }
        $sql = ($replace ? 'REPLACE' : 'INSERT') . ' INTO ' . $this->parseTable($options['table']) . ' (' . implode(',', $fields) . ') VALUES (' . implode(',', $values) . ')';
        $sql .= $this->parseLock(isset($options['lock']) ? $options['lock'] : false);
        $sql .= $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
        return $this->execute($sql, $this->parseBind(!empty($options['bind']) ? $options['bind'] : array()));
    }

    /**
     * 通过Select方式插入记录
     * @param $fields 要插入的数据表字段名
     * @param $table 要插入的数据表名
     * @param array $options 查询数据参数
     * @return mixed
     */
    public function selectInsert($fields, $table, $options = array())
    {
        if (is_string($fields)) {
            $fields = explode(',', $fields);
        }
        array_walk($fields, array($this, 'parseKey'));
        $sql = 'INSERT INTO ' . $this->parseTable($table) . ' (' . implode(',', $fields) . ') ';
        $sql .= $this->buildSelectSql($options);
        return $this->execute($sql, $this->parseBind(!empty($options['bind']) ? $options['bind'] : array()));
    }

    /**
     * 更新记录
     * @param $data 数据
     * @param $options 表达式
     * @return mixed
     */
    public function update($data, $options)
    {
        $sql = 'UPDATE '
            . $this->parseTable($options['table'])
            . $this->parseSet($data)
            . $this->parseWhere(!empty($options['where']) ? $options['where'] : '')
            . $this->parseOrder(!empty($options['order']) ? $options['order'] : '')
            . $this->parseLimit(!empty($options['limit']) ? $options['limit'] : '')
            . $this->parseLock(isset($options['lock']) ? $options['lock'] : false)
            . $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
        return $this->execute($sql, $this->parseBind(!empty($options['bind']) ? $options['bind'] : array()));
    }

    /**
     * 删除记录
     * @param array $options 表达式
     * @return mixed
     */
    public function delete($options = array())
    {
        $sql = 'DELETE FROM '
            . $this->parseTable($options['table'])
            . $this->parseWhere(!empty($options['where']) ? $options['where'] : '')
            . $this->parseOrder(!empty($options['order']) ? $options['order'] : '')
            . $this->parseLimit(!empty($options['limit']) ? $options['limit'] : '')
            . $this->parseLock(isset($options['lock']) ? $options['lock'] : false)
            . $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
        return $this->execute($sql, $this->parseBind(!empty($options['bind']) ? $options['bind'] : array()));
    }

    /**
     * 查找记录
     * @param array $options 表达式
     * @return mixed
     */
    public function select($options = array())
    {
        $sql = $this->buildSelectSql($options);
        $result = $this->query($sql, $this->parseBind(!empty($options['bind']) ? $options['bind'] : array()));
        return $result;
    }

    /**
     * 生成查询SQL
     * @param array $options 表达式
     * @return mixed|string
     */
    public function buildSelectSql($options = array())
    {
        if (isset($options['page'])) {
            //根据页数计算limit
            if (strpos($options['page'], ',')) {
                list($page, $listRows) = explode(',', $options['page']);
            } else {
                $page = $options['page'];
            }
            $page = $page ? : 1;
            $listRows = isset($listRows) ? $listRows : (is_numeric($options['limit']) ? $options['limit'] : 20);
            $offset = $listRows * ((int)$page - 1);
            $options['limit'] = $offset . ',' . $listRows;
        }
        $sql = $this->parseSql($this->selectSql, $options);
        $sql .= $this->parseLock(isset($options['lock']) ? $options['lock'] : false);
        return $sql;
    }

    /**
     * 替换SQL语句中表达式
     * @param $sql
     * @param array $options 表达式
     * @return mixed
     */
    public function parseSql($sql, $options = array())
    {
        $sql = str_replace(
            array('%TABLE%', '%DISTINCT%', '%FIELD%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%', '%UNION%', '%COMMENT%'),
            array(
                $this->parseTable($options['table']),
                $this->parseDistinct(isset($options['distinct']) ? $options['distinct'] : false),
                $this->parseField(!empty($options['field']) ? $options['field'] : '*'),
                $this->parseJoin(!empty($options['join']) ? $options['join'] : ''),
                $this->parseWhere(!empty($options['where']) ? $options['where'] : ''),
                $this->parseGroup(!empty($options['group']) ? $options['group'] : ''),
                $this->parseHaving(!empty($options['having']) ? $options['having'] : ''),
                $this->parseOrder(!empty($options['order']) ? $options['order'] : ''),
                $this->parseLimit(!empty($options['limit']) ? $options['limit'] : ''),
                $this->parseUnion(!empty($options['union']) ? $options['union'] : ''),
                $this->parseComment(!empty($options['comment']) ? $options['comment'] : '')
            ), $sql);
        return $sql;
    }

    /**
     * 获取最近一次查询的sql语句
     * @return string
     */
    public function getLastSql()
    {
        return $this->queryStr;
    }

    /**
     * 获取最近插入的ID
     * @return null
     */
    public function getLastInsId()
    {
        return $this->lastInsId;
    }

    /**
     * 获取最近的错误信息
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * SQL指令安全过滤
     * @param $str SQL字符串
     * @return string
     */
    public function escapeString($str)
    {
        return addslashes($str);
    }

    /**
     * 析构方法
     */
    public function __destruct()
    {
        // 释放查询
        if ($this->queryId) {
            $this->free();
        }
        // 关闭连接
        $this->close();
    }

    /**
     * 关闭数据库 由驱动类定义
     */
    public function close()
    {
    }
}