<?php
/**
 * Created by PhpStorm
 * @desc: pdo驱动支持
 * @package: Lib\Db
 * @author: leandre <nly92@foxmail.com>
 * @copyright: copyright(2014) leandre.cn
 * @version: 14/11/6
 */
namespace Lib\Db;

use Single\Log;
use Single\Register;
use Single\SingleException;

final class Pdo extends Db
{
    protected $PdoStatement = null;
    private $table = '';

    /**
     * 构造方法 读取数据库配置信息
     * @param string $config
     */
    public function __construct($config = '')
    {
        $this->config = $config;
        $this->dbType = $this->getDsnType($this->config['dsn']);
        if (empty($this->config['params'])) {
            $this->config['params'] = array();
        }
    }

    /**
     * 连接数据库
     * @return null|\PDO
     * @throws \Single\SingleException
     */
    public function connect()
    {
        if (!isset($this->link)) {
            if ($this->pconnect) {
                $this->config['params'][\PDO::ATTR_PERSISTENT] = true;
            }
            try {
                $this->link = Register::get('PDO', array($this->config['dsn'], $this->config['username'], $this->config['password'], $this->config['params']));
            } catch (\PDOException $e) {
                LOG::fatal('Can\'t connect to DB : ' . $e->getMessage());
            }
            if (in_array($this->dbType, array('MSSQL', 'ORACLE', 'IBASE', 'OCI'))) {
                throw new SingleException('PDO CAN NOT SUPPORT ' . $this->dbType . ' PERFECTLY, PLEASE USE THE OFFICIAL DRIVER INSTEAD');
            }
            $this->link->exec('SET NAMES ' . $this->config['charset']);
            $this->connected = true;
            unset($this->config);
        }
        return $this->link;
    }

    /**
     * 释放查询结果
     */
    public function free()
    {
        $this->PdoStatement = null;
    }

    /**
     * 执行查询 返回数据集
     * @param $str sql指令
     * @param array $bind 参数绑定
     * @return bool
     */
    public function query($str, $bind = array())
    {
        $this->initConnect();
        if (!$this->link) return false;
        $this->queryStr = $str;
        if (!empty($bind)) {
            $this->queryStr .= '[ ' . print_r($bind, true) . ' ]';
        }
        // 释放前次的查询结果
        if (!empty($this->PdoStatement)) $this->free();
        // 记录开始执行时间
        $begin = microtime(true);
        $this->PdoStatement = $this->link->prepare($str);
        if (false === $this->PdoStatement) {
            $this->error();
        }
        // 参数绑定
        $this->bindParams($bind);
        $result = $this->PdoStatement->execute();
        $end = microtime(true);
        if (false === $result) {
            $this->error();
            return false;
        } else {
            // 记录SQL日志
            Log::sql(sprintf('%s RunTime ：%.4f ms', $this->getLastSql(), ($end - $begin) * 1000));
            return $this->getAll();
        }
    }

    /**
     * 执行语句
     * @param $str sql指令
     * @param array $bind 参数绑定
     * @return bool
     */
    public function execute($str, $bind = array())
    {
        $this->initConnect();
        if (!$this->link) return false;
        $this->queryStr = $str;
        if (!empty($bind)) {
            $this->queryStr .= '[ ' . print_r($bind, true) . ' ]';
        }
        // 释放前次的查询结果
        if (!empty($this->PdoStatement)) $this->free();
        // 记录开始执行时间
        $begin = microtime(true);
        $this->PdoStatement = $this->link->prepare($str);
        if (false === $this->PdoStatement) {
            $this->error();
        }
        // 参数绑定
        $this->bindParams($bind);
        $result = $this->PdoStatement->execute();
        $end = microtime(true);
        if (false === $result) {
            $this->error();
            return false;
        } else {
            // 记录SQL日志
            Log::sql(sprintf('%s RunTime ：%.4f ms', $this->getLastSql(), ($end - $begin) * 1000));
            $this->numRows = $this->PdoStatement->rowCount();
            if (preg_match("/^\s*(INSERT\s+INTO|REPLACE\s+INTO)\s+/i", $str)) {
                $this->lastInsId = $this->getLastInsertId();
            }
            return $this->numRows;
        }
    }

    /**
     * 参数绑定
     * @param $bind
     */
    protected function bindParams($bind)
    {
        foreach ($bind as $k => $v) {
            if (is_array($v)) {
                array_unshift($v, $k);
            } else {
                $v = array($k, $v);
            }
            call_user_func_array(array($this->PdoStatement, 'bindValue'), $v);
        }
    }

    /**
     * 启动事务
     * @return bool
     */
    public function startTrans()
    {
        $this->initConnect();
        if ($this->link) return false;
        if ($this->transTimes == 0) {
            $this->link->beginTransation();
        }
        $this->transTimes++;
        return true;
    }

    /**
     * 用于非自动提交状态下面的查询提交
     * @return bool
     */
    public function commit()
    {
        if ($this->transTimes > 0) {
            $begin = microtime(true);
            $result = $this->link->commit();
            $end = microtime(true);
            $this->transTimes = 0;
            if (!$result) {
                $this->error();
                return false;
            } else {
                Log::sql(sprintf('%s RunTime ：%.4f ms', $this->getLastSql(), ($end - $begin) * 1000));
            }
        }
        return true;
    }

    /**
     * 事务回滚
     * @return bool
     */
    public function rollback()
    {
        if ($this->transTimes > 0) {
            $result = $this->link->rollback();
            $this->transTimes = 0;
            if (!$result) {
                $this->error();
                return false;
            }
        }
        return true;
    }

    /**
     * 获得所有的查询数据
     * @return mixed
     */
    public function getAll()
    {
        $result = $this->PdoStatement->fetchAll(\PDO::FETCH_ASSOC);
        $this->numRows = count($result);
        return $result;
    }

    /**
     * 取得数据表的字段信息
     * @param $tableName 表名
     * @return array
     */
    public function getFields($tableName)
    {
        switch ($this->dbType) {
            case 'MSSQL':
            case 'SQLSRV':
                $sql = "SELECT column_name as 'Name',data_type as 'Type',column_default as 'Default',is_nullable as 'Null' FROM information_schema.tables AS t JOIN information_schema.columns AS c ON t.table_catalog = c.table_catalog AND t.table_schema = c.table_schema AND t.table_name = c.table_name WHERE t.table_name = '$tableName'";
                break;
            case 'SQLITE':
                $sql = 'PRAGMA table_info (' . $tableName . ') ';
                break;
            case 'ORACLE':
            case 'OCI':
                $sql = "SELECT a.column_name \"Name\",data_type \"Type\",decode(nullable,'Y',0,1) notnull,data_default \"Default\",decode(a.column_name,b.column_name,1,0) \"pk\" "
                    . "FROM user_tab_columns a,(SELECT column_name FROM user_constraints c,user_cons_columns col "
                    . "WHERE c.constraint_name=col.constraint_name AND c.constraint_type='P' and c.table_name='" . strtoupper($tableName)
                    . "') b where table_name='" . strtoupper($tableName) . "' and a.column_name=b.column_name(+)";
                break;
            case 'PGSQL':
                $sql = 'select fields_name as "Name",fields_type as "Type",fields_not_null as "Null",fields_key_name as "Key",fields_default as "Default",fields_default as "Extra" from table_msg(' . $tableName . ');';
                break;
            case 'IBASE':
                break;
            case 'MYSQL':
            default:
                $sql = 'DESCRIBE ' . $tableName; // 驱动类不只针对mysql，不能加``
        }
        $result = $this->query($sql);
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $val = array_change_key_case($val);
                $val['name'] = isset($val['name']) ? $val['name'] : "";
                $val['type'] = isset($val['type']) ? $val['type'] : "";
                $name = isset($val['field']) ? $val['field'] : $val['name'];
                $info[$name] = array(
                    'name' => $name,
                    'type' => $val['type'],
                    'notnull' => (bool)(((isset($val['null'])) && ($val['null'] === '')) || ((isset($val['notnull'])) && ($val['notnull'] === ''))), // not null is empty, null is yes
                    'default' => isset($val['default']) ? $val['default'] : (isset($val['dflt_value']) ? $val['dflt_value'] : ""),
                    'primary' => isset($val['key']) ? strtolower($val['key']) == 'pri' : (isset($val['pk']) ? $val['pk'] : false),
                    'autoinc' => isset($val['extra']) ? strtolower($val['extra']) == 'auto_increment' : (isset($val['key']) ? $val['key'] : false),
                );
            }
        }
        return $info;
    }

    /**
     * 取得数据库的表信息
     * @param string $dbName 数据库名
     * @return array
     */
    public function getTables($dbName = '')
    {
        switch ($this->dbType) {
            case 'ORACLE':
            case 'OCI':
                $sql = 'SELECT table_name FROM user_tables';
                break;
            case 'MSSQL':
            case 'SQLSRV':
                $sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'";
                break;
            case 'PGSQL':
                $sql = "select tablename as Tables_in_test from pg_tables where  schemaname ='public'";
                break;
            case 'IBASE':
                // 暂时不支持
                E(L('_NOT_SUPPORT_DB_') . ':IBASE');
                break;
            case 'SQLITE':
                $sql = "SELECT name FROM sqlite_master WHERE type='table' "
                    . "UNION ALL SELECT name FROM sqlite_temp_master "
                    . "WHERE type='table' ORDER BY name";
                break;
            case 'MYSQL':
            default:
                if (!empty($dbName)) {
                    $sql = 'SHOW TABLES FROM ' . $dbName;
                } else {
                    $sql = 'SHOW TABLES ';
                }
        }
        $result = $this->query($sql);
        $info = array();
        foreach ($result as $key => $value) {
            $info[$key] = current($value);
        }
        return $info;
    }

    /**
     * limit分析
     * @param $limit
     * @return string
     */
    protected function parseLimit($limit)
    {
        $limitStr = '';
        if (!empty($limit)) {
            switch ($this->dbType) {
                case 'PGSQL' :
                case 'SQLITE':
                    $limit = explode(',', $limit);
                    if (count($limit) > 1) {
                        $limitStr .= ' LIMIT ' . $limit[1] . ' OFFSET ' . $limit[0] . ' ';
                    } else {
                        $limitStr .= ' LIMIT ' . $limit[0] . ' ';
                    }
                    break;
                case 'MSSQL' :
                case 'SQLSRV' :
                case 'IBASE' :
                case 'ORACLE' :
                case 'OCI' :
                    break;
                case 'MYSQL' :
                default:
                    $limitStr .= ' LIMIT ' . $limit . ' ';
            }
        }
        return $limitStr;
    }

    /**
     * 字段和表名处理
     * @param $key
     * @return mixed|string
     */
    protected function parseKey(&$key)
    {
        if (!is_numeric($key) && $this->dbType == 'MYSQL') {
            $key = trim($key);
//            if (!preg_match('/[,\'\"\*\(\)`.\s]/', $key)) {
//                //$key = '`' . $key . '`';
//            }
            return $key;
        } else {
            return parent::parseKey($key);
        }
    }

    /**
     * value分析
     * @param $value
     * @return array|mixed|string
     */
    protected function parseValue($value)
    {
        if (is_string($value)) {
            $value = strpos($value, ':') === 0 ? $this->escapeString($value) : '\'' . $this->escapeString($value) . '\'';
        } elseif (isset($value[0]) && is_string($value[0]) && strtolower($value[0] == 'exp')) {
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
     * 关闭数据库
     */
    public function close()
    {
        $this->link = null;
    }

    /**
     * 数据库错误信息
     * 记录日志
     * @return string
     */
    public function error()
    {
        if ($this->PdoStatement) {
            $error = $this->PdoStatement->errorInfo();
            $this->error = $error[1] . ':' . $error[2];
        } else {
            $this->error = '';
        }
        if ($this->queryStr) {
            $this->error .= ' [ SQL ERROR ] : ' . $this->queryStr;
        }
        Log::fatal($this->error);
        return $this->error;
    }

    /**
     * SQL指令安全过滤
     * @param SQL字符串 $str SQL指令
     * @return mixed|string
     */
    public function escapeString($str)
    {
        switch ($this->dbType) {
            case 'MSSQL':
            case 'SQLSRV':
            case 'MYSQL':
                return addslashes($str);
            case 'PGSQL':
            case 'IBASE':
            case 'SQLITE':
            case 'ORACLE':
            case 'OCI':
                return str_ireplace("'", "''", $str);
        }
    }

    /**
     * 获取最后插入id
     * @return int
     */
    public function getLastInsertId()
    {
        switch ($this->dbType) {
            case 'PGSQL':
            case 'SQLITE':
            case 'MSSQL':
            case 'SQLSRV':
            case 'IBASE':
            case 'MYSQL':
                return $this->link->lastInsertId();
            case 'ORACLE':
            case 'OCI':
                $sequenceName = $this->table;
                $vo = $this->query("SELECT {$sequenceName}.currval currval FROM dual");
                return $vo ? $vo[0]["currval"] : 0;
        }
    }
}
