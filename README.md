# Webworker Extension For ThinkPHP

## 介绍
一个类似于webman的ThinkPHP Webworker扩展库

## 安装
~~~
composer require axguowen/think-webworker
~~~

## 配置
修改config目录的webworker.php配置文件，设置好监听地址跟端口，默认为监听网卡，端口8989

## 启动
命令行执行以下代码：
~~~
php think webworker
~~~

启动成功后在浏览器访问127.0.0.1:8989即可