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

#### 8/23 - 25

* 数据采集：

	* rounting 一般不变，但是航班回经常变，如果以航班作为routing的组合，那要经常更替航班数据
	
* 合成政策：

	* 生产 Routing 表(A)，目前可根据 flight 表生产 -> 如何检验航路的有效性？-> 保存航路
	* A -> 查询 avh 获得舱位数据 + 查询 xfsd 获得运价数据，得出那些舱位可用，第二舱位梯队 -> 合成混舱数据(B) -> 检验fsi  -> 去携程上检验低价，可以验证混舱的准确性，当达到 100% 时，可以先去携程上检验低价，再去fsi 检验

#### 

* 系统应用：

	* 用户系统

#### 8/25 - 

* 将程序备注写清晰
* 重写 eterm.class.php
* 重写 xfsd.command.php
* 重写 av.command.php

* 大数据量请求结果
* 用REACT 或者 VUE 重写 前端
* 支持前端开发框架：react.js
* 内置 f2e 脚手架工具
* 支持前端脚手架：webpack/gulp/grunt
* 支持AMD规范：require.js


* 业务流程：
	
	* 提交 -> source 生成 -> 将所有数据收集全后-> update source表 -> 分解生成 result，返回 result 结果 -> 同基础数据合并至 PriceSource 中 -> 生成混舱数据 PriceResult : 用pdo事务来写
	* 当xfsd出现不同时，更新操作？

* 支持引用其他框架
* 支持引用其他控制器

* 框架功能：

	* 内置一套UI框架
	* 一套CMS系统模板
	* 压缩html？
	* 公共方法：functin.class.php 定义
	* 去除index.php后nginx的适配
	* debug模式
	* trace模式
	* 控制器常用的操作：可以直接使用smarty框架

		* 404内置
