## 目录结构

```
/includes                  # 框架库
	set.php                  # 框架设置文件 
	default.php              # 框架配置
	/library                 # 框架依赖  
		/vender  
			/smarty
			/eterm               # 编写 eterm 命令
				/command
					av.class.php   
		/core                  # 框架核心
			App.class.php        #  
			Controller.class.php #
			Dispatcher.class.php #
			Drive.class.php      # 
	    BYS.class.php        # 初始化
			Report.class.php     # 报告
	/api                     # 前台调用接口
		format.php             # 接口格式
	/config
		config.php             # 默认应用配置
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
	/home                    # 前台
/View                      # 模板
/webpage                   # 生成前台模板
index.php                  # 入口文件
```

## 说明

* 目前仅支持将所有的模板放至统一的`View`文件夹汇总
* 自定义配置文件`Includes/config/config.php`

	* 替换模板中的变量，通常用大写和__组合使用
	* 使用其他类型的模板引擎，例如smarty，配置文件用类名和.config.php组合，并在 `Includes/default.php` 增加相关配置

* `Includes/default.php` 是本框架的相关设置
* 根据app名称生成相应目录及文件，例如admin

## 待完成

* 支持前端开发框架：react.js
* 支持前端脚手架：webpack/gulp/grunt
* 支持AMD规范：require.js

* 内置一套UI框架
* 一套CMS系统模板
* 压缩html？
* 公共方法：functin.class.php 定义
* 去除index.php后nginx的适配
* debug模式
* trace模式
* 控制器常用的操作：可以直接使用smarty框架
	* 404内置
