## 目录结构

```
/includes                  # 框架库
	set.php                  # 框架设置
	default.php              # 框架配置
	/common                       
		function.php           # 系统函数
	/library                 # 框架依赖  
		/vender  
			/smarty              # smarty 框架
			/eterm               # 编写 eterm 命令
				app.php            # 启动程序
				av.command.php   
		/core                  # 框架核心
			App.class.php        # App 启动
	    BYS.class.php        # 初始化
	    Cache.class.php      # 框架缓存
			Controller.class.php # 控制器
			Dispatcher.class.php # 路由
			Drive.class.php      # 各应用驱动
			Model.class.php      # 数据模型
			Report.class.php     # 报告
	/api                     # 前台调用接口
	/config
		config.php             # 默认应用配置
		smarty.config.php      # smarty应用配置
	set.php                  # 运行环境
	defult.php               # 框架配置
/public                    # 静态资源
	/css
	/js
	/fonts
/app                       # 应用开发
	/admin                   # 后台
		/model                 # Model 数据库模型
		/views					       # 模板
		/ctroller              # 控制器
	/home                  * # 前台
/View                      # 模板
/webpage                 * # 生成前台模板
index.php                  # 入口文件
```

## 说明

* 目前仅支持将所有的模板放至统一的`View`文件夹汇总
* 自定义配置文件`Includes/config/config.php`

	* 替换模板中的变量，通常用大写和__组合使用
	* 使用其他类型的模板引擎，例如smarty，配置文件用类名和.config.php组合，并在 `Includes/default.php` 增加相关配置

* `Includes/default.php` 是本框架的相关设置
* 根据app名称生成相应目录及文件，例如admin

===

## 待完成
* 政策：根据录入的xfsd精简的结果，检验fsi，录入退改规则 -price_source-> 混舱后，生成 price_result
* debug模式
	* 屏蔽 notice 错误
* 生成 热门城市表：筛选 xfsd_result表，并保存，更新则清空表插入全部数据
	* 优化：增加国家对区域的划分显示
* 计划任务：
	* 固定了查询日期，改为自动更改
	* 计划任务的运价，增加客户代码标示，方便查询运价
* 利用fsd检验价格，并每天查询所有的`航路政策`
* 优化 
	* fsd check：不在以命令作为录入标准，而是用 result ：先command 再 地点 日期等查询
	* 当录入格式不合理是，直接屏蔽不用再查数据库
* 长时间请求：查询199 aa 单程 request fail，大批量 request 是否会导致 plan 失败？利用守护进程，开启两台服务器运行？
* 框架功能：

	* 内置一套UI框架
	* 一套CMS系统模板
	* 压缩html？
	* 公共方法：functin.class.php 定义
	* 去除index.php后nginx的适配

	* trace模式
	* 控制器常用的操作：可以直接使用smarty框架

		* 404内置
