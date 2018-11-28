## 目录结构

``` html
/Includes                  # 框架库
|-set.php                  # 框架设置
|-default.php              # 框架配置
|-/common                       
|--function.php            # 系统函数
|-/library                 # 框架依赖  
|-/vender                   # 第三方类库
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
|--/views                 # 模板
|--/controller           # 控制器
|-/home                  # 前台
/View                    # 默认模板路径
index.php                # 入口文件
```
## 全局结构
- 配置文件
在根目录/Includes 下
框架set.php，针对本项目的一些常量，目录文件等，默认可不修改
项目配置 default.php，配置该项目使用的路径

- 路由
按照目录及方法区分路由
例如 项目名/模块名/控制器名/方法名，例如 http://localhost/admin/index/index
实际访问的是admin模块下/Controller文件夹下的IndexController的index方法

- 数据库
链接数据表
在 模块/Model 目录，生成表的类，例如 /admin/Model/Table1SourceModel.class.php
其中 $tablePrefix 代表的是表前缀，例如 Table1Model.class.php。其中表的声明类属性 $tablePrefix = "prefix_" 选择的表就是 prefix_table1，另外还有 $_validate = array(); 里面的字段代表的是表字段，填入 TIME 则会自动回填当前时间戳，例如  $_validate = array( 'GmtCreate' => 'TIME'); 
```
<?php
namespace admin\Model;
use BYS\Model;
class Table1Model extends Model {
  protected $tablePrefix = "prefix_";
  protected $_validate = array(
    'GmtCreate' => 'TIME'
  );
}
```


## 框架配置文件 Includes/default.php
* `default`属性：配置默认APP`app`、控制器`controller`、方法`action`
* `core`属性：指向框架核心文件，用于更换框架核心。
* `vender` 属性：默认加载/vender目录中的库，格式：
```json
框架名（文件夹名）=> array(
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
* `COMMON` 表示所有项目均可使用的配置。如果仅需要在admin下使用，则相关配置移至`APP_admin`，其他APP，则用APP_前缀区分相关项目。
* `TPL_VAR` 表示模板中替换的变量，例如在index.html中 __VAR__ 替换成 var。
* `DB_CONFIG_LIST` 允许多个数据库，`DEFAULT` 表示默认的数据库参数，其他参数以数据库名作为key值
```json
// 模板替换，到模板中，例如 <script src='__PUBLIC__'> 会变成 <script src='public/'>
'TPL_VAR' => array( 
  '__PUBLIC__' => 'public/',
),

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

此时如果全部都可以用则
```
return array(
  'COMMON'=> array(
    'TPL_VAR' => array( 
      '__PUBLIC__' => 'public/',
    ),
  ),
  'APP_home'=>array(
    'TPL_VAR' => array( 
      '__PRIVATE__' => 'private/',
    ),
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
// 语法：cookie(String $coockieName, String $coockieValue, [mix $options]);
```

* redirect() 重定向，指定一定时间后跳转

```
// redirect( String $url [, Number $time = 0, String $msg = ''] )
redirect('login.php', 3, '登录');
redirect('login.php'); 
```

* File() 文件对象，用于存储本地文件
```
  $logFile = new \File('a','./file', 'logFiles', '.txt');
  $logFile->write('download',true, 'TIME');
  // 打印上一次的存储内容
  logFile->printFile();
  $logFile->close();

```

## 核心功能
* Model，通过model()方法实例化了表单后就可以使用其中的方法
  * prepare
  * execute

在方法中使用
```
$table = model('table_name');
```

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

```

  - display 模板渲染

```
// 自动寻找view层对应的位置：例如上例找寻 /admin/Index/index.html
$this->display();
// 也可以指定其他模板，后面可以省略html后缀
$this->display('User/login'); // 模板为 /admin/User/login.html
```

  - assign 变量分配

```
$this->assign('path', APP_PATH);
// 在模板*.html中就可以使用该变量，例如此处配置左右边界符为"<{","}>"
<{path}>
```

	- display assgin 均继承自 smarty，因此配饰smarty.config.php 即可生成相应配置

  * controller 的CURD条件模块
```
/*
  $modelName 数据表名
  $config 配置
    conditions: 条件，Array类型，Key为字段，值对应着条件，其中值如果想匹配范围（<或>，不包括<=和>=）则在值中添加相应符号即可，例如 'conditions'=>array("time"=> "<".time())。
*/
$this->query('table_1', array('conditions'=>array('Id'=>1), 'select'=>array('Name'), 'orderby'=>array('time'=>'ASC'),'limit'=>10), true);

// 如果在conditions 的匹配字段中修改带有匹配关系的字段例如 OR，则代表与后面部分为 OR 关系，
$this->query('table_1', array('conditions'=>array('Id OR'=>1, 'time'=>'110'));
```

	- query 表查询

```
// 语法：$this->query(String $modelName, Array $config, Bool $return);
/*
  $modelName 数据表名
  $config 配置
    conditions: 条件，Array类型，Key为字段，值对应着条件，其中值如果想匹配范围（<或>，不包括<=和>=）则在值中添加相应符号即可，例如 'conditions'=>array("time"=> "<".time())
    distinct：去重，Array类型，值为字段
    select：字段选择，Array类型，值为字段，默认不填时为*
    orderby: 排序，Array类型，Key为排序字段，若值为空默认降序DESC，若指定则修改值为ASC
    limit：数据量限制，Number类型，值为限制条数，
  $return 是否返回数据结果，
    true：返回查询结果数组且如果数组为一条也会已以key为0的数组返回。
    false：输出一个转化成json的数组，数组格式：array('result'=>array(), 'msg'=>'', 'status'=>1) 'result' 数据库操作结果，'msg'是在查询过程中出现的警示，'status'数据结果为非false 则为1，否则为0。
*/
$this->query('table_1', array('conditions'=>array('Id'=1), 'select'=>array('Name'), 'orderby'=>array('time'=>'ASC'),'limit'=>10), true);
```

  - update 表更新

```
// 语法：$this->update(String $modelName, Array $config, Bool $return);
/*
  $modelName 数据表名
  $config 配置
    conditions: 条件，Array类型，Key为字段，值对应着条件，其中值如果想匹配范围（<或>，不包括<=和>=）则在值中添加相应符号即可，例如 'conditions'=>array("time"=> "<".time())
    values: 更新字段，Array类型，Key为字段，值对应着更新值
  $return 是否返回数据结果，数据返回的结果是更新了几条数据
    true：返回查询结果数组且如果数组为一条也会已以key为0的数组返回。
    false：输出一个转化成json的数组，数组格式：array('result'=>array(), 'msg'=>'', 'status'=>1) 'result' 数据库操作结果，'msg'是在查询过程中出现的警示，'status'数据结果为非false 则为1，否则为0。
*/
$this->update('table_1', array('conditions'=>array('time'=>'<'.time()), 'values'=>array('status'=>0)));
```

  - updates 对同一表批量更新

```
// 语法：$this->updates(String $modelName, Array $config, Bool $return);
/*
  $modelName 数据表名
  $config 配置
    内容是一个数组，数组中有各个不同更新的语句。
    数组内容： array(
      'where'=> 更新条件
      'value'=> 更新字段
    )
  $return 是否返回数据结果，数据返回的结果是更新了几条数据
    true：返回查询结果数组且如果数组为一条也会已以key为0的数组返回。
    false：输出一个转化成json的数组，数组格式：array('result'=>array(), 'msg'=>'', 'status'=>1) 'result' 数据库操作结果，'msg'是在查询过程中出现的警示，'status'数据结果为非false 则为1，否则为0。
*/
$this->updates('table_1', array(
  array('where'=>array('time'=>'<'.time()), 'value'=>array('status'=>0)), 
  array('where'=>array('Id'=>'1'), 'value'=>array('status'=>0)), 
), true);
```

  - add 批量新增数据

```
// 语法：$this->add(String $modelName, Array $config, Bool $return);
/*
  $modelName 数据表名
  $config 配置
    内容是一个key值为values的数组。
    数组内容： 'values' => array(
      字段 => 新增值
    )
  $return 是否返回数据结果，数据返回的结果是插入后最后的主键值（默认为id）
    true：返回查询结果数组且如果数组为一条也会已以key为0的数组返回。
    false：输出一个转化成json的数组，数组格式：array('result'=>array(), 'msg'=>'', 'status'=>1) 'result' 数据库操作结果，'msg'是在查询过程中出现的警示，'status'数据结果为非false 则为1，否则为0。
*/
$this->add('table_1', array('values'=>array(
  array('id'=>1, 'name'=>'aa'),
  array('id'=>2, 'name'=>'bb'),
)), ture);
```

  - delete 删除数据

```
// 语法：$this->delete(String $modelName, Array $config, Bool $return);
/*
  $modelName 数据表名
  $config 配置
    conditions: 条件，Array类型，Key为字段，值对应着条件，其中值如果想匹配范围（<或>，不包括<=和>=）则在值中添加相应符号即可，例如 'conditions'=>array("time"=> "<".time())
  $return 是否返回数据结果，数据返回的结果是删除了几条数据
    true：返回查询结果数组且如果数组为一条也会已以key为0的数组返回。
    false：输出一个转化成json的数组，数组格式：array('result'=>array(), 'msg'=>'', 'status'=>1) 'result' 数据库操作结果，'msg'是在查询过程中出现的警示，'status'数据结果为非false 则为1，否则为0。
*/
$this->delete('table_1', array('conditions'=>array('time'=>'<'.time()), ture);
```

* Report
  * Report::log()
  * Report::printLog()
  * Report::error()

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

## 其他
* 目前对服务器限制为POST, GET的http请求


