## 目录结构

```
/includes                # 框架库
	/library               # 框架依赖    
		/smarty
		/eterm               # 编写 eterm 命令
			/command
				av.class.php   
			set.php            # 配置文件 
	/api                   # 前台调用接口
		format.php           # 接口格式
	/config
		config.php           # 默认应用配置
	/core                  # 框架核心
    BYS.class.php        # 初始化
		Model.class.php      # MVC的M
		Db.class.php         # 数据库相关
		Report.class.php     # 报告
		Function.class.php   # 可执行函数
	set.php                # 运行环境
	defult.php             # 框架配置
/public                  # 静态资源
	/css
	/js
	/fonts
/app                     # 应用开发
	/admin                 # 后台
		/model               # Model 数据库模型
		/views					     # 模板
		/ctroller            # 控制器
		/driver              # 控制器驱动器
	/home                  # 前台
/webpage                 # 生成前台模板
index.php                # 入口文件
```

## 说明

* 在`/webpage`中可以使用前端的库生成模板
* 分割后台、前台
* 后台和前台公用接口

## 待完成

* 公共方法：functin.class.php 定义
* url 解析
* 主题更换
* 自动加载command文件
* debug模式
* trace模式
