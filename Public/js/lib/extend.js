define('extend', function () {

//
  // $.fn.extend({
  //   // unique(A)检查数组A否存在重复，如果存在，返回去除重复后的数组
  //     unique: function(target, callback){
  //       var tmp, cur,
  //           copy = target;

  //       if(target.length > 0){
  //         for (var j = 0; j < target.length ; j++) {
  //             if(target[j]){

  //               if(typeof callback === 'function'){
  //                 cur = callback.call(target[j]);
  //               }else{
  //                 cur = target[j];
  //               }

  //               for ( var i = j+1; i < target.length; i++ ) {
  //                 if( typeof callback === 'function' ){
  //                   if( target[i] && cur === callback.call(target[i])){
  //                     target[i] = null;
  //                   }
  //                 }else{
  //                   if( target[i] && cur === target[i]){
  //                     target[i] = null;
  //                   }                  
  //                 }
  //               };

  //             }
  //         };
  //         for(var o = 0, copy = []; o < target.length; o++){
  //         	if(target[o]){
  //         		copy.push(target[o]);
  //         	}
  //         }
  //       }
  //       return copy;
  //     }
  // })
//



// 解析url
function parseURL(url) {
    var a =  document.createElement('a');
    a.href = url;
    return {
        source: url,
        protocol: a.protocol.replace(':',''),
        host: a.hostname,
        port: a.port,
        query: a.search,
        params: (function(){
            var ret = {},
                seg = a.search.replace(/^\?/,'').split('&'),
                len = seg.length, i = 0, s;
            for (;i<len;i++) {
                if (!seg[i]) { continue; }
                s = seg[i].split('=');
                ret[s[0]] = s[1];
            }
            return ret;
        })(),
        file: (a.pathname.match(/\/([^\/?#]+)$/i) || [,''])[1],
        hash: a.hash.replace('#',''),
        path: a.pathname.replace(/^([^\/])/,'/$1'),
        relative: (a.href.match(/tps?:\/\/[^\/]+(.+)/) || [,''])[1],
        segments: a.pathname.replace(/^\//,'').split('/')
    };
}

var isArray  = isType('Array');
var isString = isType('String');
var isNumber = isType('Number');
var isObject = isType('Object');
var isFunction = isType('Function');


function checkbox(obj){
  // 增加健壮性，数据监测，宽容性（非正常数据时）
  var check = {}

  if( !isString(obj.all) && !isString(obj.select) ){
    return;
  }
  
  try{
    check.all = document.getElementById(obj.all);
    check.select = getElementsByClassName(obj.select, 'input');

    if(check.all === null || check.select === null){
      throw new Error('没有正确的类名导致无法获得节点');
    }
  }catch(err){
    console.log(err.message)
    return;
  }

  check.all.onclick = function(){
    for(var i = 0; i < check.select.length; i++){
      check.select[i].checked = check.select[i].checked === false ? true : false;
    }
  }
  
  for(var i = 0; i < check.select.length; i++){
    check.select[i].onclick = function(){
      var selected = 0;
      for(var j = 0; j < check.select.length; j++){
        if(check.select[j].checked){ 
          selected ++;
        }
      }
      check.all.checked = selected > 0 ? true: false;
    }
  }

}

function isType(type){
  // 类型判断
  return function(obj){
    return Object.prototype.toString.call(obj) === '[object '+type+']';
  } 
}


// var Type = {}
// for(var i = 0, type; type = ['String', 'Array', 'Number'][i++];){
//  !function(type){
//    Type['is'+type] = function(){
//      return Object.prototype.toString.call(obj) === '[object '+type+']';
//    }
//  }(type)
// }

function getElementsByClassName(clName, tagName, context){
    var node, elements, o,
      ret = [],
      i , j ,
      tag = tagName || "*",
      p = context || document;

    // 支持getElementByClassName的浏览器
    if(document.getElementsByClassName){
      node = p.getElementsByClassName(clName);
      ret = node;

    // 不支持的浏览器  
    }else{
      node = p.getElementsByTagName(tag);

      for (i = 0; i < node.length; i++) {
        o = node[i].className.split(/\s+/);
        if(o[0]){
          for(j = 0; j < o.length; j++){
            console.log(o[j])
            if(o[j] == clName){
              console.log(node[i].className)
              ret.push(node[i]);
              break;
            }
          }
        }
      };
    }
    return ret;
  }

// 格式化日期
Date.prototype.format = function(format){
  // 格式化当前日期
  // new Date().format("yyyy-MM-dd hh:mm:ss");
   var date = {
        "M+" : this.getMonth()+1,                 //月份 
        "d+" : this.getDate(),                    //日 
        "h+" : this.getHours(),                   //小时 
        "m+" : this.getMinutes(),                 //分 
        "s+" : this.getSeconds(),                 //秒 
        "q+" : Math.floor((this.getMonth()+3)/3), //季度 
        "S"  : this.getMilliseconds()             //毫秒 
   };
   if (/(y+)/i.test(format)) {
          format = format.replace(RegExp.$1, (this.getFullYear() + '').substr(4 - RegExp.$1.length));
   }
   for (var k in date) {
          if (new RegExp("(" + k + ")").test(format)) {
                 format = format.replace(RegExp.$1, RegExp.$1.length == 1
                        ? date[k] : ("00" + date[k]).substr(("" + date[k]).length));
          }
   }
   return format;
}

// 两个日期比较，返回较大的
Date.prototype.max = function(date1, date2){
  return (new Date(date1)).valueOf() > (new Date(date2)).valueOf() ? date1 : date2;
}


// 清除两边空格 
String.prototype.trim = function(Symbol) {
  if(Symbol){
    var patten = new RegExp("/(^"+Symbol+"*)|("+Symbol+"*$)/",'g');
    return this.replace(patten, ''); 
  }
  return this.replace(/(^\s*)|(\s*$)/g, ''); 
}; 

// AOP 
Function.prototype.after = function(fn){
  var _self = this;
  return function(){
    var ret = _self.apply(this, arguments);
    // if(ret === "success"){
      fn.apply(this, arguments);
    // }
    return ret;
  }
}

Function.prototype.before = function(fn){
  var _self = this;
  return function(){
    fn.apply(this, arguments);
    return _self.apply(this, arguments);
  }
}

// 全局事件
window.Event = !function(){
  var clientList = {},  // 订阅客户
      listen,           // 监听事件
      trigger,          // 触发事件
      remove;           // 移除事件 

  listen = function(key, fn){  // 监听客户端状态
    if(!clientList[key]){
      clientList[key] = [];
    }
    clientList[key] = fn;
  }

  trigger = function(){
    var key = Array.prototype.shift.call(arguments),
        fns = clientList[key];
    if(!fns || fns.length === 0){
      return false;
    }
    for(var i, fn; fn = fns[i++];){
      fn.apply(this, argumetns);
    }
  }

  remove = function(key, fn){
    var fns = clientList[key];

    if(!fns){
      return false;
    }
    if(!fn){
      fns && (fns.length = 0);
    }else{
      for(var l = fns.length - 1; l>=0; l--){
        var _fn = fns[l]
        if(_fn === fn){
          fns.splice(l, 1);
        }
      }
    }
  }

  return {
    listen: listen,
    trigger: trigger,
    remove: remove
  }

}()

function copy(obj){
  // 复制元素
  if(isArray(obj)){
    var tmp = [];  
  }
  if(isObject(obj)){
    var tmp = {};
  }
  for(var i in obj){
    tmp[i] = obj[i]
  }
  return tmp;
}

return {
  parseURL: parseURL,
  isArray: isArray,
  isString: isString,
  isNumber: isNumber,
  isObject: isObject,
  checkbox: checkbox,
  getElementsByClassName: getElementsByClassName,
  copy: copy,
}

});
