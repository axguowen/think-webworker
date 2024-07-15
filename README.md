# Webworker Extension For ThinkPHP

## 介绍
一个类似于Workerman的ThinkPHP Webworker扩展库

## 特点
1、深度兼容ThinkPHP，现有ThinkPHP业务代码几乎不用做任何更改即可使用

2、性能比传统php-fpm环境高10-20倍

3、支持ThinkPHP的Cookie使用

4、支持ThinkPHP的Session使用

5、支持ThinkPHP的多应用模式

6、支持ThinkPHP的多应用中间件

7、多应用模式支持配置应用入口文件

8、多应用模式支持配置应用目录以支持composer依赖包的应用

## 安装
~~~
composer require axguowen/think-webworker
~~~

## 配置
修改config目录的webworker.php配置文件，设置好监听地址跟端口，默认为监听网卡，端口8989

多应用模式说明：
如果安装了ThinkPHP官方的多应用依赖包，则默认自动多应用识别，也可以通过应用入口文件的方式来访问指定应用，如果需要设置应用入口文件与应用名不一样，可以配置app_entrance_files参数，
例如你的后台admin应用，入口文件名使用了test.php，那么入口文件配置参数如下：
~~~php
'app_entrance_files' => [
    'test.php' => 'admin',
]
~~~
如果需要给test.php入口文件指定应用目录，则配置参数如下：
~~~php
'app_entrance_files' => [
    'test.php' => [
        'app_name' => 'admin',
        'app_path' => '/www/wwwroot/your_project/your_app/',
    ],
],
~~~
上面的应用入口文件只是url访问上的入口文件，并不真实存在，也不需要自己创建。

## 注意
ThinkPHP内置的\think\Request类已经做了别名映射到\think\webworker\support\Request类，如果需自定义Request类，则自定义Request类需要继承\think\webworker\support\Request类，并在app目录下的provider.php文件里面将\think\webworker\support\Request类映射到自定义Request类。

## 启动
命令行执行以下代码：
~~~
php think webworker
~~~

启动成功后在浏览器访问127.0.0.1:8989即可

Linux下支持以守护进程模式启动：
~~~
php think webworker start -d
~~~

## 停止
守护进程运行模式下在命令行执行以下代码停止服务：
~~~
php think webworker stop
~~~