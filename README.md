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
* 当其他用户长时间占用服务器时，其他用户无法访问。
* 项目大小写问题，在linux下会区分，导致读不出文件

## 系统架构
* 政策生成：手工填写（销售日期、旅行日期）、固定航班限制
	* 细节
		* xfsd 具有查询当日存在的价格，但不一定适用，例如30号的价格，查询1号仍然有，这是因为你的当天是在30号之前
		* 销售日期是指在当前卖出的价格均可以使用这个价格
	* BUG:
		* 摘录跑出数据是否合理，利用xfsd每日更新验证？
		* 同一个主机会共享session?
* 营销策略：设置成本返点、投放返点
* OTA政策比对，分出优势政策、劣势政策
* 劣势政策的功能模块：降舱、赌退改
* 线下政策：投放返点

## 待完成
* 同航程降舱：
	1. 贴入行程 -> 解析，并展示解析结果 。
	2. 将形成原文件存入source，存入后将查询解析结果下的可降舱舱位，并将解析结果及可降舱位存入result，关联id = sid。
	2. 回填原PNR及价格，rt后QTE:/航空公司
	3. 依次生成 fsi 查询价格并回填。
	4. 比较所有fsi价格，找出最低的价格对应的舱位及farabasis
	5. 当fsi最低价格比原PNR价格低时，回头片result表，并将状态更改为2，生成ss定位，NM1回填乘客姓名，如为往返将去程与回程分开ss定位后在进行\ki封口，回填result中的LC_PNR、LC_Price、LC_Status、，IG清空记录。
	6. 当没价格时，每半小时执行从新执行 3 。
	bug: 生成的fsi 只有一个舱位，group问题

* 不同航程降舱：avh 找舱位，增加新航段后降低票价



* 命令：
	* xs fsi/ua//.05oct17 可以查看指定日期的价格情况
	* avh/出发时间中转城市，即可获得指定中转城市的舱位情况（用于追位子）
xfsd运价时间轴：根据连续的适用日期及销售日期、旅行日期（禁用日期）、提前出票组合而成
fsi后：XS FSU(目标farebasis序号)
运价如何配合舱位使用？
读取相应去程日期及回程日期的舱位，筛选目标舱位中可用的舱位。过滤运价时间轴、15条fsn（销售日期）、14条fsn（旅行日期）


* 为了节约端口及响应速度，所有的请求结果均先来自于sql。
* run的限制再一次1个城市，每次1-2分钟。利用守护进程访问后门。
* plan的精简是正确的结果，访问的仍然是同一个全部结果，通过前台筛选获得。希望price中的精简结果和plan的一致。
* 减少 xfsd 查询访问 eterm 接口
* 政策：
	* 根据录入的xfsd精简的结果，检验fsi，录入退改规则 -price_source-> 混舱后，生成 price_result
	* 会访问xfsd 接口
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
* 报错的时候会吧，数据库密码等暴露出来。warning 警告 隐藏
* 框架功能：

	* 内置一套UI框架
	* 一套CMS系统模板
	* 压缩html？
	* 公共方法：functin.class.php 定义
	* 去除index.php后nginx的适配

	* trace模式
	* 控制器常用的操作：可以直接使用smarty框架

		* 404内置
