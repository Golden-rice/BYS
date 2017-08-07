// require.config({
//   baseUrl: "/eterm/public/js/lib", 
// 　paths: {
//       "jquery": "jquery.min"
//    }
// });
define('progress',['jquery'], function ($) {
  // 重置函数
  // $ = $.jquery

  var progress = function(option){
    return progress.prototype.init(option);
  }
  progress.fn = progress.prototype = {
    init: function(option){
    	// 初始化
      this.__totel = 0;   // 全部进程数
      this.__index = 0;   // 执行顺序
      this.__fnList = []; // 执行队列

      this.option = this.setOption({
        style: 'bootsrap',
        context: document
      }, option);


      // this.create(this.option.context);
      return this;
    },
    setOption: function(obj, option){
    	var i;
      option = option || {};
      		
      for (i in obj){
      	if(option[i]){
      		obj[i] = option[i] !== obj[i]? option[i] :obj[i];
      	}
      }

      return obj;
    },
    create: function(context){
      $(context).html('<div class="progress progress-striped active"> <div class="progress-bar" style="width: 2%"></div></div>')
      this.option.progress = $('.progress');
      this.option.bar = $('.progress>.progress-bar');
    },
    after: function(before){
      var that = this;
      this.__index ++;
      setTimeout(function(){
    	  before()
        that.complete()
      }, 0)
      return this;
    },
    push: function(fn){
      this.__totel ++;
      this.__fnList.push(fn);
      return this;
    },

    finished: function(){

      for(; this.__index < this.__totel;){
        var a = this.__fnList.shift().call(this, this.__index);
        // console.log(this.__index*1000)
        // setTimeout(function(){
          console.log('re:'+a)
          if(a !== undefined){  
            
            this.__index++;            
            this.haved(this.__index);
          }
        // }, 1)
      }

    },
    haved: function(i){
      console.log('__index:'+this.__index+',totol:'+this.__totel);
      var progress = this.option.progress,
          bar = this.option.bar;
      bar.css({'width': i/(this.__totel)*100+'%'});

      if(this.__index === 0){
        setTimeout(function(){
          // progress.remove();
        }, 600)
      }
    },
    have: function(i, total){
      var _self = this;

      _self.option.bar.css({'width': i/total*100+'%'});
      if(i=== total){
        setTimeout(function(){
          _self.option.progress.remove();
        }, 600)
      }
    },
    complete: function(){
     var progress = this.option.progress,
          bar = this.option.bar;

      bar.css({'width': 100+'%'});
      if(this.__index === 0){
      	setTimeout(function(){
      		progress.remove();
      	}, 600)
      }
    }
  }
  window.progress = progress;

  return {
    progress: progress
  }

})
 // Progress Plug : A animate progress-bar in Bootstrap Style, rely on jQuery
/* Q1: init() 后 this 并不是指代 prototype对象
 * A1: return 中不包含new 

 * Q2: 未增加 new 会产生什么区别？大家都公用一个对象吗？需查看jq源码课程
 * A2: 测试是不会公用对象，不知道区别，需查看jq源码课程

 * Q3: 会删除全部的progress
 * A3: 目前只会生产一个progress ,所有的进程都在这个progress下进行
 */
 