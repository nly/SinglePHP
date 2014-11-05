<?php
/**
 * Created by PhpStorm
 * @desc: 数据传输对象模型封装
 * @package: dto_abstract.php
 * @author: leandre <nly92@foxmail.com>
 * @copyright: copyright(2014) leandre.cn
 * @version: 14/10/30
 */
namespace DTO;

use Lib\Exception\Dto_Exception;

abstract class Dto_Abstract extends \ArrayObject
{
    /**
     * 字段信息
     * @var array
     */
    protected $fields = array();

    /**
     * 输入模式。用于在数据从创建到写入存储的过程中使用。在该模式下，会调用规则检查，子对象会递归创建，同时进行数据检查。
     */
    const MODE_INPUT = 'input';

    /**
     * 输出模式。用于在数据从存储读出到过程处理和展示的过程中使用。在该模式下，创建和写入时信任数据，不会调用规则检查，子对象仍然会递归创建，同时信任数据。
     */
    const MODE_OUTPUT = 'output';

    protected $mode;

    protected $argchecker_ns = 'Lib\Argchecker\\';

    public function __construct($init_data = NULL, $mode = self::MODE_INPUT)
    {
        parent::setFlags(\ArrayObject::ARRAY_AS_PROPS);
        $this->set_dto_mode($mode);
        if (!is_null($init_data)) {
            $this->init_data($init_data);
        }

    }

    /**
     * 初始化数据
     * @param $data
     * @throws Dto_Exception
     */
    protected function init_data($data)
    {
        if (is_object($data) || is_array($data)) {
            foreach ($data as $key => $value) {
                $this->offsetSet($key, $value);
            }
        } else {
            throw new Dto_Exception('data must be an object or array');
        }
    }

    /**
     * 设置数据对象模式
     * @param $mode
     * @throws Dto_Exception
     */
    public function set_dto_mode($mode)
    {
        if ($mode != self::MODE_INPUT && $mode != self::MODE_OUTPUT) {
            throw new Dto_Exception('mode error');
        }
        $this->mode = $mode;
    }

    /**
     * 获取数据对象模式
     * @return mixed
     */
    public function get_dto_mode()
    {
        return $this->mode;
    }

    /**
     * 设置数据,未定义的字段不允许设置
     * 对未设置项赋值抛出异常，不讲就
     * 对于定义了set_*方法的项，直接返回set_*方法的结果
     * @param mixed $field_name
     * @param mixed $value
     * @throws Dto_Exception
     */
    public function offsetSet($field_name, $value)
    {
        if (!isset($this->fields[$field_name])) {
            throw new Dto_Exception('field ' . $field_name . ' does not exists');
        }
        $method = 'set_' . $field_name;
        if (method_exists($this, $method)) {
            return $this->$method($value);
        } else {
            parent::offsetSet($field_name, $this->apply_rule($field_name, $value));
        }
    }

    /**
     * 获取数据
     * 对于未定义的项，认为是可设置但是未设置的像，返回null
     * 对于定义了get_*方法的项，则会直接返回调用get_*的结果
     * 对于定义了得想，认为是必须设置项，在input模式下，未被初始化则抛出异常
     * @param mixed $field_name
     * @return mixed|null
     * @throws Dto_Exception
     */
    public function offsetGet($field_name)
    {
        if (!isset($this->fields[$field_name])) {
            return null;
        }
        $method = 'get_' . $field_name;
        if (method_exists($this, $method)) {
            return $this->$method($field_name);
        } else {
            if (!$this->fields[$field_name] && !parent::offsetExists($field_name)) {
                if ($this->mode == self::MODE_OUTPUT) {
                    return null;
                } else {
                    throw new Dto_Exception('fields ' . $field_name . ' does not set');
                }
            }
            return parent::offsetExists($field_name) ? parent::offsetGet($field_name) : null;
        }
    }

    /**
     * 规则校验
     * @param $field_name
     * @param $value
     * @return mixed
     */
    public function apply_rule($field_name, $value)
    {
        $valid_value = $value;
        if (is_array($this->fields[$field_name])) {
            if ($this->mode === self::MODE_INPUT) {
                $arg_args = $this->fields[$field_name];
                $arg_type = $arg_args[0];
                $arg_args['0'] = $value;
                $valid_value = call_user_func_array(array($this->argchecker_ns . 'Argchecker', $arg_type), $arg_args);
            }
        } elseif ($this->fields[$field_name]) {
            $class = $this->fields[$field_name];
            if (!class_exists($class)) {
                throw new Dto_Exception('field ' . $field_name . ' class ' . $class . 'not exists');
            }
            if (!is_object($value) || get_class($value) !== $class) {
                $valid_value = new $class($valid_value, $this->mode);
            }
        }
        return $valid_value;
    }

    /**
     * 返回当前数据对象的数组格式
     * @param bool $recursive 是否对子对象进行递归调用,默认不递归
     * @return array
     */
    public function to_array($recursive = false)
    {
        if (!$recursive) {
            return parent::getArrayCopy();
        }
        $array = parent::getArrayCopy();
        foreach ($array as $k => $v) {
            if (is_object($v) && method_exists($v, 'to_array')) {
                $array[$k] = $v->to_array();
            }
        }
        return $array;
    }

    /**
     * 是否设置
     * @param $field_name
     * @return bool
     */
    public function _isset($field_name)
    {
        return $this->offsetExists($field_name);
    }

    /**
     * 删除
     * @param $field_name
     */
    public function _unset($field_name)
    {
        return $this->offsetUnset($field_name);
    }

    /**
     * 获取
     * @param $field_name
     * @return mixed|null
     * @throws Dto_Exception
     */
    public function _get($field_name)
    {
        return $this->offsetGet($field_name);
    }

    /**
     * 设置
     * @param $field_name
     * @param $value
     * @throws Dto_Exception
     */
    public function _set($field_name, $value)
    {
        return $this->offsetSet($field_name, $value);
    }

    /**
     * 获取所有字段名
     * @return array
     */
    public function get_fields()
    {
        return array_keys($this->fields);
    }

    /**
     * 删除所有引用，释放对象
     */
    public function __destruct()
    {
        parent::exchangeArray(array());
    }
}

