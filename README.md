# phpwind

安装步骤
========

1. 解压下载的安装包;
2. 上传upload文件夹中的文件到对应网站根目录;
3. 执行安装文件：您的域名/install.php

bug提交
=======

http://www.phpwind.net/thread-htm-fid-54.html

发展建议
========

http://www.phpwind.net/thread-htm-fid-39.html

关于移动版
=========

http://www.phpwind.net/read/3418959

常见错误
========

1. pdo_mysql未安装

解决方法：修改本地php.ini配置，以Win下的php为例，找到`;extension=php_pdo_mysql.dll ;extension=php_pdo.dll`
去除前面的分号“;”。重启apache或php-fpm服务即可。

