<?php
/**
 * Created by PhpStorm
 * @desc: SinglePHP框架
 * @package: Single
 * @author: leandre <nly92@foxmail.com>
 * @copyright: copyright(2014) leandre.cn
 * @version: 14/10/27
 */

namespace Single;

use Lib\Util\Context;

register_shutdown_function('Single\shutdown');
date_default_timezone_set('Asia/Shanghai');

/**
 * 获取和设置配置参数 支持批量定义
 * 如果$key是关联型数组，则会按K-V的形式写入配置
 * 如果$key是数字索引数组，则返回对应的配置数组
 * @param $key string 配置变量
 * @param null $value 配置值
 * @return array|bool|null
 * @throws \Exception
 */
function C($key, $value = null)
{
    static $_config = array();
    if ($value === null) {
        if (is_string($key)) {
            //如果传入的key是字符串
            return isset($_config[$key]) ? $_config[$key] : null;
        }
        if (is_array($key)) {
            if (array_keys($key) !== range(0, count($key) - 1)) {
                //如果传入的key是关联数组
                $_config = array_merge($_config, $key);
            } else {
                $ret = array();
                foreach ($key as $k) {
                    $ret[$k] = isset($_config[$k]) ? $_config[$k] : null;
                }
                return $ret;
            }
        }
    } else {
        if (is_string($key)) {
            $_config[$key] = $value;
        } else {
            throw new \Exception('params is not correct');
        }
    }
    return true;
}

/**
 * 调用Widget
 * @param $name string widget名
 * @param array $data 传递给widget的变量列表，key为变量名，value为变量值
 * @throws \Exception
 */
function W($name, $data = array())
{
    $fullName = 'Widget\\' . $name;
    if (!class_exists($fullName)) {
        throw new \Exception('Widget ' . $name . ' not exists');
    }
    $widget = Register::get($fullName);
    $widget->invoke($data);
}

function shutdown()
{
    if (C('DEBUG_MODE')) {
        $errorInfo = error_get_last();
        if ($errorInfo !== null) {
            Log::fatal($errorInfo['message'] . ' in ' . $errorInfo['file'] . ' at ' . $errorInfo['line']);
            echo "<br /><br /><font color='red'>Exception Message：" . $errorInfo['message'] . '</font><br />';
            echo 'Exception Line：', $errorInfo['line'], '<br/>';
            echo 'Exception File：', $errorInfo['file'], '<br/>';
        }
    }
}

/**
 * 总控类
 */
class SinglePHP
{
    /**
     * 控制器
     * @var string
     */
    private $c;
    /**
     * 单例
     * @var SinglePHP
     */
    private static $_instance;

    /**
     * 构造函数，初始化配置
     * @param array $conf
     */
    private function __construct($conf)
    {
        C($conf);
    }

    /**
     * 私有化克隆函数，防止被克隆
     */
    private function __clone()
    {
    }

    /**
     * 获取单例
     * @param array $conf
     * @return SinglePHP
     */
    public static function getInstance($conf)
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self($conf);
        }
        return self::$_instance;
    }

    /**
     * 运行应用实例
     * @access public
     * @return void
     */
    public function run()
    {
        try {
            if (C('USE_SESSION') == true) {
                session_start();
            }
            require C('APP_PATH') . DS . 'common.php';
            spl_autoload_register(array('\Single\SinglePHP', 'autoload'));
            if (CLI) {
                global $argv;
                $pathInfo = isset($argv[1]) ? $argv[1] : '';
            } else {
                $uri = parse_url($_SERVER['REQUEST_URI']);
                $pathInfo = $uri['path'];
            }
            $pathInfoArr = array_filter(explode('/', trim($pathInfo, '/')));
            $length = count($pathInfoArr);
            if ($length == 0) {
                $this->c = 'index';
            } else {
                $this->c = array_pop($pathInfoArr);
            }
            $dirStr = empty($pathInfoArr) ? '' : implode('\\', $pathInfoArr) . '\\';
            $namespace = '\Controller\\' . $dirStr;
            $controllerClass = $namespace . $this->c;
            if (!class_exists($controllerClass)) {
                throw new \Exception('Controller ' . $controllerClass . ' does not exist');
            }
            $class = new \ReflectionClass($controllerClass);
            if ($class->isAbstract()) {
                throw new \Exception('can not create instances of abstract controller');
            }
            $controller = $class->newInstance();
            if (!method_exists($controller, 'run')) {
                throw new \Exception('Controller ' . $controllerClass . ' does not has run() method');
            }
            $begin = microtime(true);
            $class->getMethod('run')->invoke($controller); // main
            $end = microtime(true);
            if (C('SHOW_LOAD_TIME')) {
                echo sprintf('<br />Time： %.4f ms', ($end - $begin) * 1000);
            }
        } catch (\Exception $e) {
            if (C('DEBUG_MODE') && !CLI) {
                echo "<br /><br /><br /><font color='red'>Exception Message：" . $e->getMessage() . '</font><br />';
                echo 'Exception File：', $e->getFile(), '<br/>';
                echo 'Exception Line：', $e->getLine(), '<br/>';
                echo '<pre>Exception Code：<br/>' . $e->getTraceAsString() . '</pre>';
            } else {
                print($e->getMessage() . "\n");
            }
            Log::fatal($e->getMessage());
            die;
        }

    }

    /**
     * 自动加载函数
     * @param $class string 类名
     * @throws \Exception
     */
    public static function autoload($class)
    {
        $classFile = strtolower(str_replace('\\', DS, $class));
        $file = C('APP_PATH') . DS . $classFile . '.php';
        require $file;

    }

}

/**
 * 控制器类
 */
class Controller
{
    /**
     * 视图实例
     * @var View
     */
    private $_view;

    /**
     * 构造函数，初始化视图实例，调用hook,_run
     */
    public function __construct()
    {
        if (C('INIT_CONTEXT')) {
            Context::init();
        }
        $this->_view = Register::get('Single\View');
        $this->_init();
    }

    /**
     * 前置hook
     */
    protected function _init()
    {
    }

    /**
     * 渲染模板并输出
     * @param null|string $tpl 模板文件路径
     * 参数为相对于App/View/文件的相对路径，不包含后缀名，例如index/index
     * 如果参数为空，则默认使用$controller/$action.php
     * 如果参数不包含"/"，则默认使用$controller/$tpl
     * @return void
     */
    protected function display($tpl = '')
    {
        if ($tpl === '') {
            $trace = debug_backtrace();
            $controller = strtolower(str_replace('\\', DS, substr($trace[1]['class'], 11)));
            $tpl = $controller;
        }
        $this->_view->display($tpl);
    }

    /**
     * 为视图引擎设置一个模板变量
     * @param string $name 要在模板中使用的变量名
     * @param mixed $value 模板中该变量名对应的值
     * @return void
     */
    protected function assign($name, $value)
    {
        $this->_view->assign($name, $value);
    }

    /**
     * 将数据用json格式输出至浏览器，并停止执行代码
     * @param array $data 要输出的数据
     */
    protected function ajaxReturn($data)
    {
        header("Content-type:text/html; charset=utf-8");
        echo json_encode($data);
        exit;
    }

    /**
     * 重定向至指定url
     * @param string $url 要跳转的url
     * @param void
     */
    protected function redirect($url)
    {
        header("Location: $url");
        exit;
    }

}

/**
 * 视图类
 */
class View
{
    /**
     * 视图文件目录
     * @var string
     */
    private $_tplDir;
    /**
     * 视图文件路径
     * @var string
     */
    private $_viewPath;
    /**
     * 视图变量列表
     * @var array
     */
    private $_data = array();
    /**
     * 给tplInclude用的变量列表
     * @var array
     */
    private static $tmpData;

    /**
     * @param string $tplDir
     */
    public function __construct($tplDir = '')
    {
        if ($tplDir == '') {
            $this->_tplDir = C('APP_PATH') . DS . 'tpl' . DS;
        } else {
            $this->_tplDir = $tplDir;
        }

    }

    /**
     * 为视图引擎设置一个模板变量
     * @param string $key 要在模板中使用的变量名
     * @param mixed $value 模板中该变量名对应的值
     * @return void
     */
    public function assign($key, $value)
    {
        $this->_data[$key] = $value;
    }

    /**
     * 渲染模板并输出
     * @param null|string $tplFile 模板文件路径，相对于App/View/文件的相对路径，不包含后缀名，例如index/index
     * @return void
     */
    public function display($tplFile)
    {
        $this->_viewPath = $this->_tplDir . $tplFile . '.html';
        unset($tplFile);
        extract($this->_data);
        $template = C('OUTPUT_ENCODE') ? str_replace(array("\n", "\t", "    "), '', file_get_contents($this->_viewPath)) : file_get_contents($this->_viewPath);
        eval('?>' . $template);
    }

    /**
     * 用于在模板文件中包含其他模板
     * @param string $path 相对于View目录的路径
     * @param array $data 传递给子模板的变量列表，key为变量名，value为变量值
     * @return void
     */
    public static function tplInclude($path, $data = array())
    {
        self::$tmpData = array('path' => C('APP_PATH') . DS . 'tpl' . DS . $path . '.html', 'data' => $data);
        unset($path);
        unset($data);
        extract(self::$tmpData['data']);
        $template = C('OUTPUT_ENCODE') ? str_replace(array("\n", "\t", "    "), '', file_get_contents(self::$tmpData['path'])) : file_get_contents(self::$tmpData['path']);
        eval('?>' . $template);
    }

}

/**
 * Widget类
 * 使用时需继承此类，重写invoke方法，并在invoke方法中调用display
 */
class Widget
{
    /**
     * 视图实例
     * @var View
     */
    protected $_view;
    /**
     * Widget名
     * @var string
     */
    protected $_widgetName;

    /**
     * 构造函数，初始化视图实例
     */
    public function __construct()
    {
        $this->_widgetName = get_class($this);
        $dir = C('APP_PATH') . DS . 'tpl' . DS . 'widget' . DS;
        $this->_view = $this->_view = Register::get('Single\View', array($dir));
    }

    /**
     * 处理逻辑
     * @param mixed $data 参数
     */
    public function invoke($data)
    {
    }

    /**
     * 渲染模板
     * @param string $tpl 模板路径，如果为空则用类名作为模板名
     */
    protected function display($tpl = '')
    {
        if ($tpl == '') {
            $tpl = strtolower(substr($this->_widgetName, 7));
        }
        $this->_view->display($tpl);
    }

    /**
     * 为视图引擎设置一个模板变量
     * @param string $name 要在模板中使用的变量名
     * @param mixed $value 模板中该变量名对应的值
     * @return void
     */
    protected function assign($name, $value)
    {
        $this->_view->assign($name, $value);
    }

}

/**
 * 日志类
 * 使用方法：Log::fatal('error msg');
 * 保存路径为 logs，按天存放
 * fatal和warning会记录在.log.wf文件中
 */
class Log
{
    /**
     * 打日志
     * @param string $msg 日志内容
     * @param string $level 日志等级
     * @param bool $wf 是否为错误日志
     */
    public static function write($msg, $level = 'DEBUG', $wf = false)
    {
        $msg = str_replace(array("\n", "\t"), ' ', $msg); //日志中不能有换行符
        $msg = date('[Y-m-d H:i:s]') . " [{$level}] " . $msg . "\r\n";
        $logPath = C('LOG_PATH') . DS . date('Ymd') . '.log';
        if ($wf) {
            $logPath .= '.wf';
        }
        file_put_contents($logPath, $msg, FILE_APPEND);
    }

    /**
     * 打印fatal日志
     * @param string $msg 日志信息
     */
    public static function fatal($msg)
    {
        self::write($msg, 'FATAL', true);
    }

    /**
     * 打印warning日志
     * @param string $msg 日志信息
     */
    public static function warn($msg)
    {
        self::write($msg, 'WARN', true);
    }

    /**
     * 打印notice日志
     * @param string $msg 日志信息
     */
    public static function notice($msg)
    {
        self::write($msg, 'NOTICE');
    }

    /**
     * 打印debug日志
     * @param string $msg 日志信息
     */
    public static function debug($msg)
    {
        self::write($msg, 'DEBUG');
    }

    /**
     * 打印sql日志
     * @param string $msg 日志信息
     */
    public static function sql($msg)
    {
        self::write($msg, 'SQL');
    }

}

/**
 * 对象注册数
 * Class Register
 * @package Single
 */
class Register
{
    /**
     * @var array object tree
     */
    protected static $register_global = array();

    /**
     * get or set an object
     * @param $key
     * @param array $args
     * @return static
     * @throws SingleException
     */
    public static function get($key, array $args = array())
    {
        $key = trim($key, '\\');
        $unique_key = $key . json_encode($args);
        if (!isset(self::$register_global[$unique_key])) {
            $class = new \ReflectionClass($key);
            if ($class->isAbstract()) {
                throw new SingleException("class {$key} can not be abstruct");
            }
            self::$register_global[$unique_key] = $class->newInstanceArgs($args);

        }
        return self::$register_global[$unique_key];
    }

    /**
     * delete an object
     * @param $key
     */
    public static function del($key)
    {
        unset(self::$register_global[$key]);
    }

    /**
     * count all objects
     * @return int
     */
    public static function count()
    {
        return count(self::$register_global);
    }
}

/**
 * SingleException，记录额外的异常信息
 */
class SingleException extends \Exception
{
    /**
     * @var array
     */
    protected $extra;

    /**
     * @param string $message
     * @param array $extra
     * @param int $code
     * @param null $previous
     */
    public function __construct($message = "", array $extra = array(), $code = 0, $previous = null)
    {
        $this->extra = $extra;
        parent::__construct($message, $code, $previous);
    }

    /**
     * 获取额外的异常信息
     * @return array
     */
    public function getExtra()
    {
        return $this->extra;
    }

}
