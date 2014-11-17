<?php
/**
 * Created by PhpStorm
 * @desc: 数据写入抽象类
 * @package: Dw
 * @author: leandre <nly92@foxmail.com>
 * @copyright: copyright(2014) leandre.cn
 * @version: 14/11/10
 */
namespace Dw;

use Lib\Db\Db;
use Lib\Exception\Dw_Exception;
use Tools\Config;

abstract class Dw_Abstruct
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
     * @param $dbAlia 数据库别名
     * @param $tableName 数据表名
     * @param array $config 配置项
     */
    public function __construct($dbAlia, $tableName, $config = array())
    {
        $dbconf = Config::get('db_pool.' . $dbAlia)['write'];
        $this->config = array_merge($dbconf, $config);
        $this->tableName = $tableName;
        if (!$this->db) {
            $this->db = Db::getInstance($this->config);
        }
        return $this->db;
    }

    /**
     * 利用__call方法实现一些特殊的Model方法
     * @param $method 方法名称
     * @param $args 调用参数
     * @return $this|array|mixed|null
     * @throws Dr_Exception
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
        } elseif (isset($this->_scope[$method])) { // 命名范围的单独调用支持
            return $this->scope($method, $args[0]);
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
     * 新增数据
     * @param array $data 数据
     * @param array $options 表达式
     * @param bool $replace 是否replace
     * @return bool
     * @throws \Lib\Exception\Dw_Exception
     */
    public function insert(array $data, $options = array(), $replace = false)
    {
        if (!is_array($data) || empty($data)) {
            throw new Dw_Exception('INSERT DATA CAN NOT BE EMPTY');
        }
        $options = $this->parseOptions($options);
        $result = $this->db->insert($data, $options, $replace);
        if (false !== $result) {
            $insertId = $this->getLastInsId();
            return $insertId;
        }
        return false;
    }

    /**
     * 通过Select方式添加记录
     * @param string $fields 要插入的数据表字段名
     * @param string $table 要插入的数据表名
     * @param array $options 表达式
     * @return bool
     */
    public function selectInsert($fields = '', $table = '', $options = array())
    {
        $options = $this->parseOptions($options);
        if (false === $result = $this->db->selectInsert($fields ? : $options['field'], $table ? : $this->tableName, $options)) {
            return false;
        } else {
            return $result;
        }
    }

    /**
     * 更新保存数据
     * @param string $data
     * @param array $options
     * @return bool
     */
    public function update($data = '', $options = array())
    {
        if (empty($data)) { // 没有传递数据，获取当前数据对象的值
            if (!empty($this->data)) {
                $data = $this->data;
                $this->data = array();
            } else {
                return false;
            }
        }
        // 分析表达式
        $options = $this->parseOptions($options);
        if (!isset($options['where'])) {
            if (isset($data[$this->incKey])) { // 如果存在主键数据 则自动作为更新条件
                $where[$this->incKey] = $data[$this->incKey];
                $options['where'] = $where;
                unset($data[$this->incKey]);
            } else { // 如果没有任何更新条件则不执行
                return false;
            }
        }
        if (is_array($options['where']) && isset($options['where'][$this->incKey])) {
            $incValue = $options['where'][$this->incKey];
        }
        $result = $this->db->update($data, $options);
        if (false !== $result) {
            if (isset($incValue)) {
                $this->data[$this->incKey] = $incValue;
            }
        }
        return $result;
    }

    /**
     * 删除数据
     * @param array $options 表达式
     * @return bool
     */
    public function delete($options = array())
    {
        if (empty($options) && empty($this->options['where'])) {
            // 如果删除条件为空 则删除当前数据对象所对应的记录
            if (!empty($this->data) && isset($this->data[$this->incKey])) {
                return $this->delete($this->data[$this->incKey]);
            } else {
                return false;
            }
        }
        if (is_numeric($options) || is_string($options)) {
            // 根据主键删除记录
            if (strpos($options, ',')) {
                $where[$this->incKey] = array('IN', $options);
            } else {
                $where[$this->incKey] = $options;
            }
            $options = array();
            $options['where'] = $where;
        }
        // 分析表达式
        $options = $this->parseOptions($options);
        if (empty($options['where'])) {
            // 如果条件为空 不进行删除操作 除非设置 1=1
            return false;
        }
        if (is_array($options['where']) && isset($options['where'][$this->incKey])) {
            $incValue = $options['where'][$this->incKey];
        }
        $result = $this->db->delete($options);
        if (false !== $result) {
            if (isset($incValue)) {
                $this->data[$this->incKey] = $incValue;
            }
        }
        // 返回删除记录个数
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
     * 返回最后插入数据的id
     * @return mixed
     */
    public function getLastInsId()
    {
        return $this->db->getLastInsId();
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
     * @throws Dr_Exception
     */
    public function union($union, $all = false)
    {
        if (empty($union)) return $this;
        if ($all) {
            $this->options['union']['_all'] = true;
        }
        if (is_object($union)) {
            $union = get_object_vars($union);
        }
        if (is_string($union)) {
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
     * @param bool $except
     * @return $this
     */
    public function field($field, $except = false)
    {
        if (true === $field) {
            $fields = $this->getDbFields();
            $field = $fields ? $fields : '*';
        } elseif ($except) {
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
     * @param $where 条件表达式
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
     * @param $offset 起始位置
     * @param null $length 查询数量
     * @return $this
     */
    public function limit($offset, $length = null)
    {
        $this->options['limit'] = is_null($length) ? $offset : $offset . ',' . $length;
        return $this;
    }

    /**
     * 生成查询SQL 可用于子查询
     * @param array $options 表达式参数
     * @return string
     */
    public function buildSql($options = array())
    {
        $options = $this->parseOptions($options);
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
            $options['table'] = $this->tableName;
        }
        $this->options = array();
        return $options;
    }

    /**
     * 设置记录的某个字段值
     * 支持使用数据库字段和方法
     * @param $field 字段名
     * @param string $value 字段值
     * @return bool
     */
    public function setField($field, $value = '')
    {
        if (is_array($field)) {
            $data = $field;
        } else {
            $data[$field] = $value;
        }
        return $this->update($data);
    }

    /**
     * 字段值增长
     * @param $field 字段名
     * @param int $step 增长值
     * @return bool
     */
    public function setInc($field, $step = 1)
    {
        return $this->setField($field, array('exp', $field . '+' . $step));
    }

    /**
     * 字段值减少
     * @param $field 字段名
     * @param int $step 减少值
     * @return bool
     */
    public function setDec($field, $step = 1)
    {
        return $this->setField($field, array('exp', $field . '-' . $step));
    }

    /**
     * 执行SQL语句
     * @param $sql SQL指令
     * @param bool $parse 是否需要解析SQL
     * @return mixed
     */
    public function execute($sql, $parse = false)
    {
        if (!is_bool($parse) && !is_array($parse)) {
            $parse = func_get_args();
            array_shift($parse);
        }
        $sql = $this->parseSql($sql, $parse);
        return $this->db->execute($sql);
    }

    /**
     * 解析SQL语句
     * @param $sql SQL指令
     * @param $parse 是否需要解析SQL
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
            $sql = strstr($sql, array('__TABLE__' => $this->tableName));
            $sql = preg_replace_callback('/__([A-Z_-]+)__/sU', function ($match) {
                return strtolower($match[1]);
            }, $sql);
        }
        return $sql;
    }


}