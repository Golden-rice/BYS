require.config({
	baseUrl: "/eterm/public/js/lib", 
　paths: {
 			"jquery": "jquery.min",
      "bootstrap": "bootstrap.min",
      "progress": "bootstrap.progress",
			"action": "action",
			'extend': "extend"
   }
});

require(['jquery', 'bootstrap', 'progress', "action", 'extend'], function ($, bootstrap, progress, action, extend){
	alert('加载main成功')

});

define('a', function(){
	console.log('a');
	var test = function(){
		console.log('test')
	}
	return {
		test: test
	}
})
require(["a"], function(a){
	console.log(a.test)
})

