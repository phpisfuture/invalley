### invalley

### 使用php的swoole扩展与yaf扩展编写的高性能web框架

### 内置smarty模版引擎

### 使用，终端运行 php ./server/server.php

### 测试，浏览器打开访问 http://127.0.0.1:9501

### 注意
	1、默认开启smarty，缓存文件在application/cache下（配置中改），确保拥有写权限。
	2、默认后台运行，测试环境将swoole_server中daemonize改为false。
	3、启动如果提示smarty写文件失败，重新启动即可。