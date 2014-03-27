<?php
$data = array(
    'title' => '简介',
);
View::tplInclude('Public/header', $data); ?>
    
    <p>原来的文档不要再看了，基本已经把框架改的面目全非了~~~~~~~~~~~~</p>
    <p>
        1、首先是vhost、rewrite配置
        <pre class="prettyprint lang-php">
            &lt;VirtualHost *&gt;
                DocumentRoot "your/path/to/htdocs"
                ServerName single.dev.com
                ServerAlias www.single.dev.com
            &lt;/VirtualHost&gt;
        
            &lt;IfModule mod_rewrite.c&gt;
                RewriteEngine on
                RewriteCond %{REQUEST_FILENAME} !-d
                RewriteCond %{REQUEST_FILENAME} !-f
                RewriteRule ^(.*)$ index.php/$1 [QSA,PT,L]
            &lt;/IfModule&gt;
        </pre>  
        2、目录结构
        <pre class="prettyprint lang-php">
           root
           |
           |---SinglePHP.class.php
           |---htdocs---|
                        |--index.php
                        |--.htaccess
                        |--favicon.ico
           |---app---|
                     |---config     配置文件夹
                     |---controller 控制器文件夹
                     |---do         数据对象
                     |---dw         数据写入
                     |---dr         数据读取
                     |---lib        类库
                     |---log        日志
                     |---tpl        模板目录
                     |---widget     widget
                     |---tool       工具
                     |---common.php 通用函数库
        </pre>
        
        3、访问路径
        <pre class="prettyprint lang-php">
        f.e:
        single.dev.com                      --->    app/controller/index.php ->  _run()
        single.dev.com/index                --->    app/controller/index.php ->  _run()
        single.dev.com/index?id=123         --->    app/controller/index.php ->  _run() 
        single.dev.com/start                --->    app/controller/start.php ->  _run()
        single.dev.com/doc                  --->    app/controller/doc.php   ->  _run()
        single.dev.com/event/prize          --->    app/controller/event/prize.php ->  _run()
        single.dev.com/event/mobile/index   --->    app/controller/event/mobile/index.php ->  _run()
        </pre>
        
        4、如何使用类？
        <pre class="prettyprint lang-php">
                    一般来说，不需要显示的实例化。
        f.e:
        class Controller_Index extends Controller_Base {
            public function _run(){
                //echo testFunction();
                //new Dw_Event();
                //Dw_Event::add();
                $this->display();
                //$tihs->display('index');
            }
        }
        
        class Controller_Event_Prize  extends Controller_Base implements Controller_Interface_Interface{
            public function _run() {
                $this->add();
                $this->del();
                $this->modify();
            }
            
            public function add() {
                echo 'add';
            }
            
            public function del () {
                echo 'del';
            }
            public function modify() {
                echo 'modify';
            }
        }
        </pre>
        5、如何命名？
        <pre class="prettyprint lang-php">
我想聪明的朋友已经看出来端倪了~~~~~~~~~
 
 类名完全是和目录对应的
 
 f.e:
    Controller_Index ----> 及就是 controller 下的 index.php
    Controller_Base  ----> 是controller下的base.php
    Controller_Interface_Interface  ---> 是controller下interface下的interface.php
    Controller_Event_Mobile_index   ---> 是controller下event目录下mobile下的index.php
    Dw_Event   -->  是 dw下的event.php
    反正大概就是这样子的，目录名，文件名都是小写，类名是下划线驼峰式的。
        </pre>
        6、剩下的就只有一个日志类和异常类了，大家自己去看吧~~~~  12点了，洗洗睡觉吧~~~
    </p>

<?php View::tplInclude('Public/footer'); ?>
