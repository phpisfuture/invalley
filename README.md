### invalley使用php的swoole扩展与yaf扩展编写的高性能web框架

### 内置smarty模版引擎

### 交流，欢迎留言到https://message.phpisfuture.com

### 使用，终端运行 php ./server/server.php

### 测试，浏览器打开访问 http://127.0.0.1:9501

### 注意
	1、默认开启smarty，缓存文件在application/cache下（配置中改），确保拥有写权限。
	2、默认后台运行，测试环境将swoole_server中daemonize改为false。
	3、启动如果提示smarty写文件失败，重新启动即可。

### 目录结构
	+ application
		|+ controllers 			//控制器
			|- Common.php 			// 公共控制器
			|- Index.php 			// 首页默认控制器
		|+ library 				// 本地类库
			|+ smarty				// smarty插件
			|- Cache.php 			// redis操作单例
			|- Mysql.php 			// mysql操作单例
		|+ models 				// 模型
			|- Common.php 			// 公共模型（self::调用mysql）
		|+ modules 				// 其他模块
		|+ plugins 				// 插件
		|+ views 				// 视图
			|+ index
				|- index.phtml 		// 首页默认视图
		|- Bootstap.php 		// 引导程序
		|- functions.php 		// 自定义函数
	+ conf
		|- application.init 	// 配置文件
	+ server
		|- server.php 			// swoole_http_server
