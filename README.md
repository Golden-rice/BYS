## 目录结构

``` html
/Includes                  # 框架库
|-set.php                  # 框架设置
|-default.php              # 框架配置
|-/common                       
|--function.php            # 系统函数
|-/library                 # 框架依赖  
|-/vender  				         # 第三方类库
|-/core                    # 框架核心
|-/config
|--config.php               # 默认应用配置
|--smarty.config.php        # smarty应用配置
/Public                     # 静态资源
|-/css
|-/js
|-/fonts
/app                     # 应用开发
|-/admin                 # 后台
|--/model                # Model 数据库模型
|--/views				         # 模板
|--/controller           # 控制器
|-/home                  # 前台
/View                    # 默认模板路径
index.php                # 入口文件
```

## 框架配置文件 Includes/default.php
* `default`属性：配置默认APP`app`、控制器`controller`、方法`action`
* `core`属性：指向框架核心文件，用于更换框架核心。
* `vender` 属性：默认加载/vender目录中的库，格式：
```json
框架名（文件夹名）   => array(
				// 应用配置
				'path'    => 路径,
				'config' => array( 
					'file'   => 入口文件,
					'path'   => 配置目录 // 一般是在/config目录，以config.php结尾
				),
			)
```

## 项目配置文件 Includes/config/config.php
* 项目默认加载 `config.php` 如果没有则会报错
* `COMMON` 表示所有项目均可使用的配置。如果仅需要在admin下使用，则相关配置移至`APP_admin`。
* `TPL_VAR` 表示模板中替换的变量，例如在index.html中 __VAR__ 替换成 var。
* `DB_CONFIG_LIST` 允许多个数据库，`DEFAULT` 表示默认的数据库参数，其他参数以数据库名作为key值
```json
			// 数据库设置
			'DB_CONFIG_LIST' => array(
				'db_1'=>array(                              // 默认连接
					'DB_TYPE'   => 'mysql',                   // 数据库类型
					'DB_HOST'   => 'localhost',               // 服务器地址
					'DB_NAME'   => 'localhost',               // 数据库名
					'DB_USER'   => 'root',                    // 用户名
					'DB_PWD'    => 'root',                    // 密码
					'DB_PORT'   => 3305,                      // 端口
					'DB_CHARSET'=> 'utf8',                    // 字符集
				)
				'DEFAULT' => array(                         // 默认连接
					'DB_TYPE'   => 'mysql',                   // 数据库类型
					'DB_HOST'   => 'localhost',               // 服务器地址
					'DB_NAME'   => 'localhost',               // 数据库名
					'DB_USER'   => 'root',                    // 用户名
					'DB_PWD'    => 'root',                    // 密码
					'DB_PORT'   => 3306,                      // 端口
					'DB_CHARSET'=> 'utf8',                    // 字符集
				)
			)
```


## 全局函数 Includes/common/function.php
* model()  应用模板，支持 C语言风格的表名（下划线）和JAVA风格的表名（驼峰输入法）
```
// 数据表模型
$m = model('table_name');
```
* import() 引用第三方类库，起引用地址指向 `Includes/libaray`
```
// 临时引用第三方类库，如长期引用可以在配置文件中配置
// 语法：import(String $path);
import('vender/chart/app.php');
```
* reflect() 跨控制器引用，参数即为控制器名
```
// 引用其他控制器，仅取控制器名即可，后面即可使用该方法
// 语法：reflect(String $controllerName);
$user = reflect('user'); // userController 
$user->login(); // userController->login()
```
* connect() 链接某个数据库，例如 connect('db_1')，后面的数据库操作均可以指向这个数据库，切换原DEFAULT数据库，使用函数 reset_connect()
```
// 语法：connect(String $dbName);
// 语法：reset_connect();
    connect('db_1');
   	$result = $this->query('table_1', array('conditions'=>array('id'=>1)));
   	reset_connect();
```

* cookie() 存储cookie，对原生的cookie做封装，主要解决跨控制器cookie无法共享问题。
```
// 语法：cookie(String $coockieName, String $coockieValue, [mix $options])
```

## 核心功能
* Model
* Controller
```
// App名：admin，Controller控制器：Index ，action方法：index 
namespace admin\Controller;
use BYS\Controller;
class IndexController extends Controller {
	public function index(){
		$this->display();
	}
}

// 内置方法 display 模板渲染
// 自动寻找view层对应的位置：例如上例找寻 /admin/Index/index.html
$this->display();
// 也可以指定其他模板，后面可以省略html后缀
$this->display('User/login'); // 模板为 /admin/User/login.html

// 内置方法 assign 变量分配
$this->assign('path', APP_PATH);
// 在模板*.html中就可以使用该变量，例如此处配置左右边界符为"<{","}>"
<{path}>

// display assgin 均继承自 smarty，因此配饰smarty.config.php 即可生成相应配置

// 内置方法 query 数据库查询
$this->query($modelName = '', $config = array(), $return = false);


```
* Report

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