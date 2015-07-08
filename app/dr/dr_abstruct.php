<?php
/**
 * Created by PhpStorm
 * @desc: 数据读取抽象类 实现了简单的ORM和ActiveRecords
 * @package: Dr
 * @author: leandre <nly92@foxmail.com>
 * @copyright: copyright(2014) leandre.cn
 * @version: 14/11/10
 */
namespace Dr;

use Lib\Db\Db;
use Lib\Exception\Dr_Exception;
use Tools\Config;

abstract class Dr_Abstruct
{
    /**
     * 当前数据库操作对象
     * @var mixed|null
     */
    protected $db = null;
    /**
     * 主键是否自动增长
     * @var bool
     */
    protected $autoinc = false;
    /**
     * 主键名称
     * @var string
     */
    protected $incKey = 'id';
    /**
     * 数据表名
     * @var string
     */
    protected $tableName = '';
    /**
     * 数据库配置
     * @var array|string
     */
    protected $config = '';
    /**
     * 字段信息
     * @var array
     */
    protected $fields = array();
    /**
     * 数据信息
     * @var array
     */
    protected $data = array();
    /**
     * 查询表达式参数
     * @var array
     */
    protected $options = array();
    /**
     * 链操作方法列表
     * @var array
     */
    protected $methods = array('order', 'alias', 'having', 'group', 'lock', 'distinct');

    /**
     * 构造方法 取得DB类的实例对象
     * @param $dbAlia string 数据库别名
     * @param $tableName string 数据表名
     * @param array $config 配置项
     */
    public function __construct($dbAlia, $tableName, $config = array())
    {
        $dbconf = Config::get('db_pool.' . $dbAlia)['read'];
        $this->config = array_merge($dbconf, $config);
        $this->tableName = $tableName;
        if (!$this->db) {
            // 数据库初始化操作
            $this->db = Db::getInstance($this->config);
        }
        return $this->db;
    }

    /**
     * 利用__call方法实现一些特殊的Model方法
     * @param $method string 方法名称
     * @param $args array 调用参数
     * @return $this|array|mixed|null
     * @throws \Lib\Exception\Dr_Exception
     */
    public function __call($method, $args)
    {
        if (in_array(strtolower($method), $this->methods, true)) {
            // 连贯操作的实现
            $this->options[strtolower($method)] = $args[0];
            return $this;
        } elseif (in_array(strtolower($method), array('count', 'sum', 'min', 'max', 'avg'), true)) {
            // 统计查询的实现
            $field = isset($args[0]) ? $args[0] : '*';
            return $this->getField(strtoupper($method) . '(' . $field . ') AS single_' . $method);
        } elseif (strtolower(substr($method, 0, 5)) == 'getby') {
            // 根据某个字段获取记录
            $field = parse_name(substr($method, 5));
            $where[$field] = $args[0];
            return $this->where($where)->find();
        } elseif (strtolower(substr($method, 0, 10)) == 'getfieldby') {
            // 根据某个字段获取记录的某个值
            $name = parse_name(substr($method, 10));
            $where[$name] = $args[0];
            return $this->where($where)->getField($args[1]);
        } else {
            throw new Dr_Exception(__CLASS__ . ' : ' . $method . 'NOT EXIST');
        }
    }

    /**
     * 设置数据对象的值
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    /**
     * 获取数据对象的值
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * 查询数据集
     * @param array $options 表达式参数
     * @return bool|null|string
     */
    public function select($options = array())
    {
        if (is_string($options) || is_numeric($options)) {
            // 根据主键查询
            $pk = $this->incKey;
            $where = array();
            if (strpos($options, ',')) {
                $where[$pk] = array('IN', $options);
            } else {
                $where[$pk] = $options;
            }
            $options = array();
            $options['where'] = $where;
        } elseif (false === $options) {
            // 用于子查询 只返回SQL不查询
            $options = array();
            // 分析表达式
            $options = $this->parseOptions($options);
            return '( ' . $this->db->buildSelectSql($options) . ' )';
        }
        $options = $this->parseOptions($options);
        $result = $this->db->select($options);
        if (false === $result) {
            return false;
        }
        if (empty($result)) {
            // 查询结果为空
            return null;
        }
        return $result;
    }

    /**
     * 启动事务
     */
    public function startTrans()
    {
        $this->commit();
        $this->db->startTrans();
    }

    /**
     * 提交事务
     */
    public function commit()
    {
        $this->db->commit();
    }

    /**
     * 事务回滚
     */
    public function rollback()
    {
        $this->db->rollback();
    }

    /**
     * 返回错误信息
     * @return mixed
     */
    public function getError()
    {
        return $this->db->getError();
    }

    /**
     * 返回最后执行的sql语句
     * @return mixed
     */
    public function getLastSql()
    {
        return $this->db->getLastSql();
    }

    /**
     * 查询SQL组装 join
     * @param $join
     * @param string $type JOIN类型
     * @return $this
     */
    public function join($join, $type = 'INNER')
    {
        if (is_array($join)) {
            foreach ($join as $key => &$_join) {
                $_join = preg_replace_callback('__([a-z_-]+)__//sU', function ($match) {
                    return strtolower($match[1]);
                }, $_join);
                $_join = false !== stripos($join, 'JOIN') ? $_join : $type . ' JOIN ' . $_join;
            }
            $this->options['join'] = $join;
        } elseif (!empty($join)) {
            // 将__TABLE_NAME__字符串替换成带前缀的表名
            $join = preg_replace_callback('/__([A-Z_-]+)__/sU', function ($match) {
                return strtolower($match[1]);
            }, $join);
            $this->options['join'][] = false !== stripos($join, 'JOIN') ? $join : $type . ' JOIN ' . $join;
        }
        return $this;
    }

    /**
     * 查询SQL组装 union
     * @param $union
     * @param bool $all
     * @return $this
     * @throws \Lib\Exception\Dr_Exception
     */
    public function union($union, $all = false)
    {
        if (empty($union)) {
            return $this;
        }

        if ($all) {
            $this->options['union']['_all'] = true;
        }
        if (is_object($union)) {
            $union = get_object_vars($union);
        }
        // 转换union表达式
        if (is_string($union)) {
            // 将__TABLE_NAME__字符串替换成带前缀的表名
            $options = preg_replace_callback('/__([a-z_-]+)__/sU', function ($match) {
                return strtolower($match[1]);
            }, $union);
        } elseif (is_array($union)) {

        } else {
            throw new Dr_Exception('UNION DATA TYPE INVALID');
        }
        $this->options['union'][] = $options;
        return $this;
    }

    /**
     * 指定查询字段 支持字段排除
     * @param $field
     * @param bool $except 是否排除
     * @return $this
     */
    public function field($field, $except = false)
    {
        if (true === $field) {
            //获取全部字段
            $fields = $this->getDbFields();
            $field = $fields ? $fields : '*';
        } elseif ($except) {
            //字段排除
            if (is_string($field)) {
                $field = explode(',', $field);
            }
            $fields = $this->getDbFields();
            $field = $fields ? array_diff($fields, $field) : $field;
        }
        $this->options['field'] = $field;
        return $this;
    }

    /**
     * 指定查询条件 支持安全过滤
     * @param $where mixed 条件表达式
     * @param null $parse 预处理参数
     * @return $this
     */
    public function where($where, $parse = null)
    {
        if (!is_null($parse) && is_string($where)) {
            if (!is_array($parse)) {
                $parse = func_get_args();
                array_shift($parse);
            }
            $parse = array_map(array($this->db, 'escapeString'), $parse);
            $where = vsprintf($where, $parse);
        } elseif (is_object($where)) {
            $where = get_object_vars($where);
        }
        if (is_string($where) && '' != $where) {
            $map = array();
            $map['_string'] = $where;
            $where = $map;
        }
        if (isset($this->options['where'])) {
            $this->options['where'] = array_merge($this->options['where'], $where);
        } else {
            $this->options['where'] = $where;
        }
        return $this;
    }

    /**
     * 指定查询数量
     * @param $offset int 起始位置
     * @param null $length 查询数量
     * @return $this
     */
    public function limit($offset, $length = null)
    {
        $this->options['limit'] = is_null($length) ? $offset : $offset . ',' . $length;
        return $this;
    }

    /**
     * 指定分页
     * @param $page int 页数
     * @param null $ListRows 每页数量
     * @return $this
     */
    public function page($page, $ListRows = null)
    {
        $this->options['page'] = is_null($ListRows) ? $page : $page . ',' . $ListRows;
        return $this;
    }

    /**
     * 查询单条数据
     * @param array $options 表达式参数
     * @return bool|null
     */
    public function find($options = array())
    {
        if (is_numeric($options) || is_string($options)) {
            $where[$this->incKey] = $options;
            $options = array();
            $options['where'] = $where;
        }
        $options['limit'] = 1;
        $options = $this->parseOptions($options);
        $resultSet = $this->db->select($options);
        if (false === $resultSet) {
            return false;
        }
        if (empty($resultSet)) {
            return null;
        }
        return $resultSet[0];
    }

    /**
     * 生成查询SQL 可用于子查询
     * @param array $options 表达式参数
     * @return string
     */
    public function buildSql($options = array())
    {
        $options = $this->parseOptions($options); // 分析表达式
        return '( ' . $this->db->buildSelectSql($options) . ' )';
    }

    /**
     * 分析表达式
     * @param array $options 表达式参数
     * @return array
     */
    protected function parseOptions($options = array())
    {
        if (is_array($options)) {
            $options = array_merge($this->options, $options);
        }
        if (!isset($options['table'])) {
            // 自动获取表名
            $options['table'] = $this->tableName;
        }
        $this->options = array();
        return $options;
    }

    /**
     * 获取一条记录的某个字段值
     * @param $field string 字段名
     * @param null $sepa 字段数据间隔符号 NULL返回数组
     * @return array|mixed|null
     */
    public function getField($field, $sepa = null)
    {
        $options['field'] = $field;
        $options = $this->parseOptions($options);
        $field = trim($field);
        if (strpos($field, ',')) {
            // 多字段
            if (!isset($options['limit'])) {
                $options['limit'] = is_numeric($sepa) ? $sepa : '';
            }
            $resultSet = $this->db->select($options);
            if (!empty($resultSet)) {
                $_field = explode(',', $field);
                $field = array_keys($resultSet[0]);
                $key = array_shift($field);
                $key2 = array_shift($field);
                $cols = array();
                $count = count($_field);
                foreach ($resultSet as $result) {
                    $name = $result[$key];
                    if (2 == $count) {
                        $cols[$name] = $result[$key2];
                    } else {
                        $cols[$name] = is_string($sepa) ? implode($sepa, array_slice($result, 1)) : $result;
                    }
                }
                return $cols;
            }
        } else {
            // 查找一条记录
            // 返回数据个数
            if (true !== $sepa) {
                // 当sepa指定为true的时候 返回所有数据
                $options['limit'] = is_numeric($sepa) ? $sepa : 1;
            }
            $result = $this->db->select($options);
            if (!empty($result)) {
                if (true !== $sepa && 1 == $options['limit']) {
                    $data = reset($result[0]);
                    return $data;
                }
                foreach ($result as $val) {
                    $array[] = $val[$field];
                }
                return $array;
            }
        }
        return null;
    }

    /**
     * 获取数据表字段信息
     * @return array|bool
     */
    public function getDbFields()
    {
        if (isset($this->options['table'])) {
            // 动态指定表名
            $array = explode(' ', $this->options['table']);
            $fields = $this->db->getFields($array[0]);
            return $fields ? array_keys($fields) : false;
        }
        if ($this->fields) {
            $fields = $this->fields;
            unset($fields['_type'], $fields['_pk']);
            return $fields;
        }
        return false;
    }

    /**
     * SQL查询
     * @param $sql string SQL指令
     * @param bool $parse 是否需要解析SQL
     * @return mixed
     */
    public function query($sql, $parse = false)
    {
        if (!is_bool($parse) && !is_array($parse)) {
            $parse = func_get_args();
            array_shift($parse);
        }
        $sql = $this->parseSql($sql, $parse);
        return $this->db->query($sql);
    }

    /**
     * 解析SQL语句
     * @param $sql string SQL指令
     * @param $parse boolean 是否需要解析SQL
     * @return mixed|string
     */
    public function parseSql($sql, $parse)
    {
        if (true === $parse) {
            $options = $this->parseOptions();
            $sql = $this->db->parseSql($sql, $options);
        } elseif (is_array($parse)) {
            $parse = array_map(array($this->db, 'escapeString'), $parse);
            $sql = vsprintf($sql, $parse);
        } else {
            $sql = strtr($sql, array('__TABLE__' => $this->tableName));
            $sql = preg_replace_callback('/__([A-Z_-]+)__/sU', function ($match) {
                return strtolower($match[1]);
            }, $sql);
        }
        return $sql;
    }
}
