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
* debug模式

	* 屏蔽 notice 错误

* 生成 热门城市表：筛选 xfsd_result表，并保存，更新则清空表插入全部数据
	
	* 优化：增加国家对区域的划分显示

* 计划任务：定期查询热门城市表，然后查询xfsd

	守护进程不停的查询 hotcity/startplan
	
	startplan -> 检查: checkXfsd checkAvh checkRouting

	HC_XfsdResult_Status,  basis_hot_city
	* 生成计划任务：设定舱位，确认出发地，确认目的地，允许互换，当天往后15日，跑1至1个半月的数据

		-> 跑xfsd 有效期end
		-> continue 有效期end+1 < 一个半月 ？ 会改变 hotcity的 sid存储，多个？
		-> 生成运价：混舱目标舱位（价格最低的，但是隐藏可展示其他价格）
		-> 跑avh 舱位
		-> 跑fsl 航路


* 生成政策：选择：select 热门城市表，checkbox 舱位 -> 获得解析后的xfsd数据，然后经隐藏只展示出最便宜的一条数据舱位数据，自动混舱生成数据，通过下载修改，上传携程数据
* 校检热门城市的fsl 数据
* 混舱后：价格设置

* 利用fsd检验价格，并每天查询所有的`航路政策`
* 如果进行大批量的录入工作，则服务器崩溃，无法响应操作

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
