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

* 增加基础数据：中国出发城市，美国中可飞城市
* xfsd展示：体验优化
* 工具：字符转换
* 合成政策：屏蔽不需要填写的信息
* 合成政策：回填航班号？
* 混舱后：价格设置
* 利用fsd检验价格，并每天查询所有的`航路政策`
* xfsd 404 丢失
* 如果进行大批量的录入工作，则服务器崩溃，无法响应操作
* debug模式

	* 屏蔽 notice 错误

* 支持引用其他框架
* 支持引用其他控制器

* 框架功能：

	* 内置一套UI框架
	* 一套CMS系统模板
	* 压缩html？
	* 公共方法：functin.class.php 定义
	* 去除index.php后nginx的适配

	* trace模式
	* 控制器常用的操作：可以直接使用smarty框架

		* 404内置
