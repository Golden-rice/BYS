## 目录结构

``` html
/Includes                # 框架库
|-set.php                  # 框架设置
|-default.php              # 框架配置
|-/common                       
|--function.php             # 系统函数
|-/library                 # 框架依赖  
|-/vender  				 # 第三方类库
|-/core                    # 框架核心
|-/config
|--config.php               # 默认应用配置
|--smarty.config.php        # smarty应用配置
/Public                  # 静态资源
|-/css
|-/js
|-/fonts
/app                     # 应用开发
|-/admin                 # 后台
|--/model                # Model 数据库模型
|--/views				 # 模板
|--/controller           # 控制器
|-/home                  # 前台
/View                    # 默认模板路径
index.php                # 入口文件
```

## 默认配置文件 default.php
* 配置默认APP、控制器、方法
* 框架核心文件：如果更换框架，更新核心文件路径即可

## 日志
* 调整默认文件样式
* 调整文件夹及文件名大小写，适配Linux系统

## 未完成功能：
* 内置一套UI框架
* 一套CMS系统模板
* 压缩html？
* 公共方法：functin.class.php 定义
* 去除index.php后nginx的适配
* trace模式
* 控制器常用的操作：可以直接使用smarty框架
* 404内置