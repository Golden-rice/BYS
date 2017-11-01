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

## 服务器
* /data/ 不识别 .php 文件，必须显式书写index.php  -> url 解析问题，有可能存在nginx缓存？
* 项目大小写问题，在linux下会区分，导致读不出文件

## 系统架构
* xs fsu1, 获得详细运价，放返点用
* xfsd 精简不够准确：增加适用日期不相同的区分
* xfsd 价格直接添加进去 -> 仅混舱选中的（当回填完后，选择某些条混舱）
* 不同回程的组合（携程不允许非单点往返，但是可作为线下使用）
* 6000 页面会卡（数据精简后，无那么多数据，混舱时应把单点往返，回程、去程中转点是否有区分出来）
* 用户系统：每个人设置的政策独立保留
* 混舱后的价格会保留至另外一个数据表中（上传到携程上的政策数据保留 ctrip_private_rt_policy）
* 营销策略：设置成本返点、投放返点
* BUG:
	* 摘录跑出数据是否合理，利用xfsd每日更新验证？
	* 同一个主机会共享session?
* OTA政策比对，分出优势政策、劣势政策
* 劣势政策的功能模块：降舱、赌退改
* 线下政策：投放返点

目标：能作为一个做政策的系统（自己可以独立做政策）

## 系统优化
* 为了节约端口及响应速度，所有的请求结果均先来自于sql。
* 优化 
	* fsd check：不在以命令作为录入标准，而是用 result ：先command 再 地点 日期等查询
	* 当录入格式不合理是，直接屏蔽不用再查数据库
* 长时间请求：查询199 aa 单程 request fail，大批量 request 是否会导致 plan 失败？利用守护进程，开启两台服务器运行？
* 报错的时候会吧，数据库密码等暴露出来。warning 警告 隐藏

## 框架功能：
* 内置一套UI框架
* 一套CMS系统模板
* 压缩html？
* 公共方法：functin.class.php 定义
* 去除index.php后nginx的适配
* trace模式
* 控制器常用的操作：可以直接使用smarty框架
* 404内置
