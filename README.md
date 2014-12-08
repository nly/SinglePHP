SinglePHP
========================

**说明：**

 1. 首先是vhost、rewrite配置

        Apache：

		<VirtualHost *>
			DocumentRoot "your/path/to/htdocs"
			ServerName single.dev.com
			ServerAlias www.single.dev.com
	    </VirtualHost>

	    <IfModule mod_rewrite.c>
	    	RewriteEngine on
	        RewriteCond %{REQUEST_FILENAME} !-d
	    	RewriteCond %{REQUEST_FILENAME} !-f
	    	RewriteRule ^(.*)$ index.php/$1 [QSA,PT,L]
	    </IfModule>

	    Nginx：

        location / {
            if (!-e $request_filename) {
                rewrite ^/(.*)$ /index.php last;
                break;
            }
        }



 2. 目录结构
 
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
		             |---dts        数据传输对象
		             |---dw         数据写入
		             |---dr         数据读取
		             |---lib        类库
		             |---log        日志
		             |---tpl        模板目录
		             |---widget     widget
		             |---tool       工具
		             |---common.php 通用函数库
		             
		   |---logs-- 日志目录 .wf为错误日志，.log为普通日志

3. 访问路径

		f.e:
		single.dev.com                      --->    app/controller/index.php ->  run()
		single.dev.com/index                --->    app/controller/index.php ->  run()
		single.dev.com/index?id=123         --->    app/controller/index.php ->  run()
		single.dev.com/start                --->    app/controller/start.php ->  run()
		single.dev.com/doc                  --->    app/controller/doc.php   ->  run()
		single.dev.com/event/prize          --->    app/controller/event/prize.php ->  run()
		single.dev.com/event/mobile/index   --->    app/controller/event/mobile/index.php
		 ->  run()
        
4. 如何使用类？

		一般来说，不需要显示的实例化。
		f.e:
		namespace Controller
        {
            class Index extends Base
            {
                public function run()
                {
                    echo "hello world";
                    echo testFunction();
                    new \Dw\Event();
                    \Dw\Event::add();
                    $this->display();
                    //$this->display('index');
                }
            }
        }
		
		namespace Controller\Event
        {
            class Prize  extends \Controller\Base implements \Controller\Inter\Test
            {
                public function run()
                {
                    $this->add();
                    $this->del();
                    $this->modify();
                }

                public function add()
                {
                    echo 'add';
                }

                public function del ()
                {
                    echo 'del';
                }

                public function modify()
                {
                    echo 'modify';
                }
            }
        }
        
5.如何命名？

		类名完全是和目录对应的
		
		f.e:
			Controller\Index ----> 及就是 controller 下的 index.php
			Controller\Base  ----> 是controller下的base.php
			Controller\Interface\Interface  ---> 是controller下interface下的interface.php
			Controller\Event\Mobile\index   ---> 是controller下event目录下mobile下的index.php
			Dw_Event   -->  是 dw下的event.php
		反正大概就是这样子的，目录名，文件名都是小写，namespace。

6.注意事项：

    模板中如果使用php代码，并且使用了模板压缩功能，那么"<?php"后面请输入一个空格，并且不能换行，
    并且必须带有"?>"的结尾.
    f.e:
        <?php $data = array(
            'title' => 'Welcome',
            'body_class' => 'bs-docs-home',
        );
        \Single\View::tplInclude('public/header', $data);
        ?>


