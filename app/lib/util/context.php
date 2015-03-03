<?php
/**
 * Created by PhpStorm
 * @desc: 上下文相关
 * @package:
 * @author: leandre
 * @copyright: copyright(2014)
 * @version: 14/12/20
 */

namespace Lib\Util;

use Single\SingleException;
use Tools\Util;

class Context
{
    private static $context_data = array();
    private static $has_inited = false;
    protected static $server = array();

    /**
     * 是否保持$_SERVER变量。默认为false，不保持。
     *
     * 注：Context::init()默认行为会在得到$_SERVER的内容后删除。为了保持某些lib的兼容性，添加此开关。
     *
     * @var bool
     */
    public static $keep_server_copy = false;

    /**
     * 初始化context。
     */
    public static function init()
    {
        if (!self::$has_inited) {
            self::$server = $_SERVER;
            if (!self::$keep_server_copy) {
                unset($_SERVER);
            }
            self::$has_inited = true;
        }
    }

    /**
     * filter method for Global vars , controller should exec this
     */
    public static function filter()
    {
        if (is_array($_SERVER)) {
            foreach ($_SERVER as $k => $v) {
                if (isset($_SERVER[$k])) {
                    $_SERVER[$k] = str_replace(array('<', '>', '"', "'", '%3C', '%3E', '%22', '%27', '%3c', '%3e'), '', $v);
                }
            }
        }
        unset($_ENV, $HTTP_GET_VARS, $HTTP_POST_VARS, $HTTP_COOKIE_VARS, $HTTP_SERVER_VARS, $HTTP_ENV_VARS);
        self::filter_slashes($_GET);
        self::filter_slashes($_POST);
        self::filter_slashes($_COOKIE);
        self::filter_slashes($_FILES);
        self::filter_slashes($_REQUEST);
    }

    /**
     * filter slashes,for SQL insert 加反斜杠，防止SQL注入
     * @param $value
     * @return bool
     */
    private static function filter_slashes(&$value)
    {
        if (get_magic_quotes_gpc()) return false; //魔术变量 Always returns FALSE because the magic quotes feature was removed from PHP5.4
        $value = (array)$value;
        foreach ($value as $key => $val) {
            if (is_array($val)) {
                self::filter_slashes($value[$key]);
            } else {
                $value[$key] = addslashes($val);
            }
        }
    }

    /**
     * 安全过滤-过滤javascript,css,iframes,object等不安全参数 过滤级别高
     * @param $value
     * @return mixed
     */
    public static function filter_script($value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = self::filter_script($v);
            }
            return $value;
        } else {
            $parten = array(
                "/(javascript:)?on(click|load|key|mouse|error|abort|move|unload|change|dblclick|move|reset|resize|submit)/i",
                "/<script(.*?)>(.*?)<\/script>/si",
                "/<iframe(.*?)>(.*?)<\/iframe>/si",
                "/<object.+<\/object>/isU"
            );
            $replace = array("\\2", "", "", "");
            $value = preg_replace($parten, $replace, $value, -1, $count);
            if ($count > 0) {
                $value = self::filter_script($value);
            }
            return $value;
        }
    }

    /**
     * 安全过滤-过滤HTML标签
     * @param $value
     * @return mixed|string
     */
    public static function filter_html($value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = self::filter_html($v);
            }
            return $value;
        } else {
            if (function_exists('htmlspecialchars')) return htmlspecialchars($value);
            return str_replace(array("&", '"', "'", "<", ">"), array("&amp;", "&quot;", "&#039;", "&lt;", "&gt;"), $value);
        }
    }

    /**
     * 安全过滤-对进入的数据进行过滤 防止SQL注入 安全级别高
     * @param $value
     * @return mixed
     */
    public static function filter_sql($value)
    {
        $sql = array("select", 'insert', "update", "delete", "\'", "\/\*", "\.\.\/", "\.\/", "union", "into", "load_file", "outfile");
        $sql_re = array("", "", "", "", "", "", "", "", "", "", "", "");
        return str_replace($sql, $sql_re, $value);
    }

    /**
     * 安全过滤-通用数据过滤
     * @param $value
     * @return array|mixed
     */
    public static function filter_escape($value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = self::filter_str($v);
            }
        } else {
            $value = self::filter_str($value);
        }
        return $value;
    }

    /**
     * 安全过滤-字符串过滤 过滤特殊有危害字符
     * @param $value
     * @return mixed
     */
    private static function filter_str($value)
    {
        $value = str_replace(array("\0", "%00", "\r"), '', $value);
        $value = preg_replace(array('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/', '/&(?!(#[0-9]+|[a-z]+);)/is'), array('', '&amp;'), $value);
        $value = str_replace(array("%3C", '<'), '&lt;', $value);
        $value = str_replace(array("%3E", '>'), '&gt;', $value);
        $value = str_replace(array('"', "'", "\t", '  '), array('&quot;', '&#39;', '    ', '&nbsp;&nbsp;'), $value);
        return $value;
    }

    /**
     * 转换过滤字符串
     *
     * @param string $string
     * @return string
     */
    public static function filter_string($string)
    {
        if ($string === NULL) {
            return false;
        }
        return htmlspecialchars($string, ENT_QUOTES);
    }

    /**
     * 从$_GET中获取指定参数的值。
     * 如果指定参数未找到，则会返回默认值$if_not_exist的值。
     * @param $name string 参数名
     * @param null $if_not_exist $if_not_exist 若指定的$name的值不存在的情况下返回的默认值。可选，采用NULL作为默认值。
     * @param bool $is_filter 是否转义
     * @return null
     */
    public static function param($name, $if_not_exist = NULL, $is_filter = false)
    {
        if ($is_filter) {
            return isset($_GET[$name]) ? (self::filter_string($_GET[$name])) : $if_not_exist;
        }
        return isset($_GET[$name]) ? $_GET[$name] : $if_not_exist;
    }

    /**
     * 从$_POST中获取指定参数的值。如果指定参数未找到，则会返回默认值$if_not_exist的值。
     * @param $name string 参数名称
     * @param null $if_not_exist 若指定的$name的值不存在的情况下返回的默认值。可选，采用NULL作为默认值。
     * @param array $filters 参数过滤方法 (script,html,escape,sql)
     * @return mixed
     */
    public static function form($name, $if_not_exist = NULL, $filters = [])
    {
        if (!isset($_POST[$name])) return $if_not_exist;
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $_POST[$name] = call_user_func('self::filter_' . $filter, $_POST[$name]);
            }
        }
        return $_POST[$name];
    }

    private static $req = array();
    private static $inited_req = false;

    /**
     * 从$_POST和$_GET中获取指定参数的值。如果指定参数未找到,则会返回默认值$if_not_exist的值。
     *
     * @param string $name 参数名。
     * @param mixed $if_not_exist 若指定的$name的值不存在的情况下返回的默认值。可选，采用NULL作为默认值。
     * @return string
     */
    public static function r($name, $if_not_exist = NULL, $filters = [])
    {
        if (self::$inited_req === false) {
            self::$req = array_merge($_GET, $_POST);
            self::$inited_req = true;
        }
        if (!isset(self::$req[$name])) return $if_not_exist;
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                self::$req[$name] = call_user_func('self::filter_' . $filter, self::$req[$name]);
            }
        }
        return self::$req[$name];
    }

    /**
     * 得到当前请求的环境变量
     *
     * @param string $name
     * @return string|null 当$name指定的环境变量不存在时，返回null
     */
    public static function get_server($name)
    {
        return isset(self::$server[$name]) ? self::$server[$name] : null;
    }

    /**
     * 根据指定的上下文键名获取一个已经设置过的上下文键值
     * @param $key string|int|float $key 键名
     * @param null $if_not_exist 当键值未设置的时候的默认返回值。可选，默认是Null。如果该值是Null,当键值未设置则会抛出一个异常；否则，返回该值。
     * @return null
     * @throws SingleException
     */
    public static function get($key, $if_not_exist = NULL)
    {
        if (!array_key_exists($key, self::$context_data)) {
            if ($if_not_exist === NULL) {
                throw new SingleException('context has no "' . $key . '" in it');
            } else {
                return $if_not_exist;
            }
        }
        return self::$context_data[$key];
    }

    /**
     * 往一个指定的上下文键名中设置键值。如果该键值已经被设置，则会抛出异常。
     *
     * @param string|int|float $key
     * @param mixed $value
     * @param array $rule
     * @throws SingleException
     */
    public static function set($key, $value, array $rule = array())
    {
        if (array_key_exists($key, self::$context_data)) {
            throw new SingleException('context has been already setted');
        }

        if ($rule) {
            $type = $rule[0];
            $rule[0] = $value;
            $value = call_user_func_array(array('\Lib\Argchecker', $type), $rule);
        }
        self::$context_data[$key] = $value;
    }

    /**
     * 获取当前Referer
     *
     * @return string
     */
    public static function get_referer()
    {
        return self::get_server('HTTP_REFERER');
    }

    /**
     * 获取当前域名
     *
     * @return string
     */
    public static function get_domain()
    {
        $server_name = self::get_server('SERVER_NAME');
        $http_host = self::get_server('HTTP_HOST');
        return empty($http_host) ? $server_name : $http_host;
    }

    /**
     * 获取客户端ip地址
     *
     * This function is copied from login.sina.com.cn/module/libmisc.php/get_ip()
     *
     * @param boolean $to_long 可选。是否返回一个unsigned int表示的ip地址
     * @return string|float        客户端ip。如果to_long为真，则返回一个unsigned int表示的ip地址；否则，返回字符串表示。
     */
    public static function get_client_ip($to_long = false)
    {
        $forwarded = self::get_server('HTTP_X_FORWARDED_FOR');
        if ($forwarded) {
            $ip_chains = explode(',', $forwarded);
            $proxied_client_ip = $ip_chains ? trim(array_pop($ip_chains)) : '';
        }

        if (Util::is_private_ip(self::get_server('REMOTE_ADDR')) && isset($proxied_client_ip)) {
            $real_ip = $proxied_client_ip;
        } else {
            $real_ip = self::get_server('REMOTE_ADDR');
        }

        return $to_long ? Util::ip2long($real_ip) : $real_ip;
    }

    /**
     *
     * 获取http请求方法。
     * @return string GET/POST/PUT/DELETE/HEAD等
     */
    public static function get_http_method()
    {
        return self::get_server('REQUEST_METHOD');
    }

    /**
     * 判断当前请求是否是XMLHttpRequest(AJAX)发起
     * @return boolean
     */
    public static function is_ajax()
    {
        return (self::get_server('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest') ? true : false;
    }

    /**
     * 返回当前url
     * @param bool $urlencode 是否urlencode后返回，默认true
     * @return string
     */
    public static function get_current_url($urlencode = true)
    {
        $req_uri = self::get_server('REQUEST_URI');
        if (NULL === $req_uri) {
            $req_uri = self::get_server('PHP_SELF');
        }

        $https = self::get_server('HTTPS');
        $s = NULL === $https ? '' : ('on' == $https ? 's' : '');

        $protocol = self::get_server('SERVER_PROTOCOL');
        $protocol = strtolower(substr($protocol, 0, strpos($protocol, '/'))) . $s;

        $port = self::get_server('SERVER_PORT');
        $port = ($port == '80') ? '' : (':' . $port);

        $server_name = self::get_domain();
        $current_url = $protocol . '://' . $server_name . $port . $req_uri;

        return $urlencode ? rawurlencode($current_url) : $current_url;
    }

    /**
     * 清除context中的所有内容
     */
    public static function clear()
    {
        //为了防止引用计数产生的内存泄漏，此处显式的unset掉所有set进来的值
        foreach (self::$context_data as $key => $value) {
            self::$context_data[$key] = null;
            $value = null;
        }
        self::$context_data = array();
    }

    /**
     * 更新context中的内容
     * 尽量不要用此方法
     * @param string|int|float $key
     * @param mixed $value
     * @throws SingleException
     */
    public static function update($key, $value)
    {
        if (array_key_exists($key, self::$context_data)) {
            self::$context_data[$key] = $value;
        } else {
            throw new SingleException('context has not set');
        }
    }
}