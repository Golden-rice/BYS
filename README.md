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

## 常用配置文件 config.php
* 根据不同模块增加设置
* 常用 html 替换标签
* 数据库设置

## 全局函数
* model()  应用模板，支持 C语言风格的表名（下划线）和JAVA风格的表名（驼峰输入法）
* import() 引用第三方类库，起引用地址指向 `Includes/libaray`
* reflect() 跨控制器引用，参数即为控制器名


## 前端请求优化
* 所有的数据库操作可以用json控制，访问任意控制下的 `assignAction` 传递的参数action指向方法名即可访问对应的方法。目前已经支持的方法有 `query` 查询, `updates` 批量更新
```
    语法（隐式）：
    "query":
    {
      "modelName": 'table_name',
      config: {
        "conditions":{  
          "dep": "BJS",
          "arr": "MIA",
          "airline": "UA"
        }, 
        "select": ['dep', 'arr'],
        "orderby":[{"column":"gmtCreate","asc":"true"}]
      }
    }
    语法（显式）：
    action: "query"

	// 更新
	{
		'update':{
			'model': 'sale_policy',
			'config': {
				'conditions':{'Id':1},
				'value': {
					'Name': 'zzz'
				}
			}
		}
	}

	// 批量更新：更新多个条件组合
	{
		'update':{
			'model': 'sale_policy',
			'config': [{
				'where':{'Id':1},
				'value': {
					'Name': 'zzz'
				}
			}]
		}
	}
	// 删除
	{
		'delete':{
			'model': 'sale_policy',
			'config': {
				'conditions':{'Id':1}
			}
		}
	}


	// 新增
	{
		'add':{
			'model': 'sale_policy',
			'config': {
				'values':[{'Id':1}]
			}
		}
	}


```

## 公共函数 function.php
* 多个数据库连接，在 config/config.php 中的 DB_CONFIG_LIST 新增数组，key 为链接名
```
  connect('config_name'); // 链接新的数据库
  echo json_encode(array('result'=>$this->query('table_name', array('conditions'=>array()), true )));
  reset_connect(); // 恢复链接
```

## 日志
* 增加数据库异常抛出
* 增加数据库事务处理
* 防SQL注入处理


## 未完成功能：
* 前端数据表隐式书写
* 调整默认文件样式
* 调整文件夹及文件名大小写，适配Linux系统
* 内置一套UI框架
* 一套CMS系统模板
* 压缩html？
* 公共方法：functin.class.php 定义
* 去除index.php后nginx的适配
* trace模式
* 控制器常用的操作：可以直接使用smarty框架
* 404内置