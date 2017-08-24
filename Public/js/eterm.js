require.config({
	// baseUrl: "/eterm/public/js/lib", 
　paths: {
 			"jquery": "lib/jquery.min",
      "progress": "lib/bootstrap.progress",
			'extend': "lib/extend",
			'bootstrap': "lib/bootstrap.min",

   }
});


define('eterm', ['jquery', 'progress', 'extend', 'bootstrap'], function ($, progress, extend, bootstrap){

// 重置函数	
// var $        = $.jquery;
var	progress = progress.progress,
	parseURL = extend.parseURL,
	isArray  = extend.isArray,
	isString = extend.isString,
	isNumber = extend.isNumber,
	isObject = extend.isObject,
	isFunction = extend.isFunction,
	upper    = extend.upper,
	checkbox = extend.checkbox,
	copy     = extend.copy,
	getElementsByClassName = extend.getElementsByClassName;

var Controller = /^\/(.)+\//.exec(window.location.pathname)[0],
		Project    = /^\/\w*[-|_|.]?\w*\//.exec(window.location.pathname)[0];
// eterm 解析，依赖jQuery
var eterm = {
	data: null,       // 数据
	// target: '',       // 目标名
	// content: '',      // 容器名
	// query: '',        // 查询语句
	progress: null,   // 进度条
	// fareArray: null,  // 筛选fare后的xfsd，后面扩充属性
	// fliterPolicy: null, // 筛选fare后的政策
	// xfsd: null,         // xfsd 数据
	// rate: 0,            // 汇率
	getData: function(url, query, callback, async){

		if(!url || url === ''){return;}
		var that = this; // 绑定到对象上

		return $.ajax({
			url: url,
			type: 'POST',
			async: async === false ? false: true,
			data: query,
			success: function(msg){

				that.data = eval('['+msg+']')[0];

				if(typeof callback === 'function'){
					callback(that.data);
				}
				// console.log(that.data);
			},
			error: function(msg, textStatus){ // XMLHttpRequest, textStatus, errorThrown
				// console.log(textStatus);
				// var error = eval('['+msg+']')[0];
				console.log(msg);
			}
		})
	},
	reqJSONP: function(url, callback){

		if(!url || url === ''){return;}
		var that = this; // 绑定到对象上
		return $.ajax({
			url: url,
			dataType: 'JSONP',
      jsonp: 'callback',
      jsonpCallback: 'json',
			success: function(msg){
				if(typeof callback === 'function'){
					callback(msg);
				}
			},
			error: function(msg, textStatus){ // XMLHttpRequest, textStatus, errorThrown
				console.log(msg);
			}
		})
	},
	link: function(url, query, callback){
		if(!url || url === ''){return;}

		var that = this;
		return $.ajax({
			url: url,
			type: 'POST',
			data: query,
			success: function(msg){

				that.msg = eval('['+msg+']')[0];

				if(typeof callback === 'function'){
					callback(that.msg);
				}

			},
			error: function(msg){
				console.log(msg);
			}
		})
	},
	submit: function(url, query){

		if(!url || url === ''){return;}

		var form = document.createElement('form');
		form.action = url;
		form.method = 'POST';
		form.target = '_blank';
		for(var name in query){
			var input = document.createElement('input');
			input.name = name;
			input.type = 'hidden';

			if(this.isObject(query[name])){
				input.value = JSON.stringify(query[name]);
			}else{
				input.value = query[name];
			}
			form.appendChild(input);
		}
		document.body.appendChild(form);

		form.submit();
	},
	isType: function (type){
		return function(obj){
			return Object.prototype.toString.call(obj) === '[object '+type+']';
		}
	},
	isArray: function(obj){
		return this.isType('Array')(obj)
	},
	isString: function(obj){
		return this.isType('String')(obj)
	},
	isNumber: function(obj){
		return this.isType('Number')(obj)
	},
	isObject: function(obj){
		return this.isType('Object')(obj)
	},
	isFunction: function(obj){
		return this.isType('Function')(obj)
	},
}

var createCommand = function(recevier, tpl){

	// 生成模板
	if(tpl){
		var render       = tpl.render ? tpl.render: undefined; // 非表格渲染方式
		var mkTable      = tpl.mkTable ? tpl.mkTable: undefined; // 各个页面基础 模板 等
		var mkMatchTable = tpl.mkMatchTable ? tpl.mkMatchTable: undefined; // 使用规则模板
		var mkMixTable   = tpl.mkMixTable ? tpl.mkMixTable: undefined; // xfsd 混舱模板
		var mkSeatLevel  = tpl.mkSeatLevel ? tpl.mkSeatLevel: undefined; // 舱位等级
		var mkCabinTpl   = tpl.mkCabinTpl ? tpl.mkCabinTpl: undefined; // 舱位生成产品数据
	}


	var xfsd = function(query){
		// 初始化
		recevier.target = 'table';
		recevier.context = "#content";
		recevier.progress = progress();

		// 备注
		recevier.lab = $('#lab');
		recevier.lab.time = $('.time');
		recevier.lab.command = $('.command');

		// 模板
		if(recevier.isFunction(mkTable) && typeof recevier.isFunction(rmTable)){
			recevier.mkTable = mkTable; 
			recevier.rmTable = rmTable; 
		}else{
			console.log("'mkTable' or 'rmTable' haven't added in ")
		}

		// 请求数据
		var url = '';
		if ( query.dosubmit === 'command' ){
			url = Controller + 'searchXfsdByCommand';
		}else{
			url = Controller + 'searchXfsdByInput';
		}

		recevier.progress.create('#content-progress');
		recevier.getData(url, query,
			// 回调函数
			function(){
				var _arg = arguments;

				recevier.rmTable(recevier.target);
				recevier.lab.show();
				recevier.progress.complete();

				return (function(data){
					// 回填数据

					// 保存xfsd
					recevier.xfsd = data.array;

					var command = "";
					console.log(recevier.xfsd)
					for(var arr in data.array){
						// command += data.array[arr].command.replace(/</,"&lt;")+'<br>';
						command += '<kbd>'+data.array[arr].command.replace(/</,"&lt;")+'</kbd>&nbsp;';
					}

					recevier.lab.time.html(data.time);
					recevier.lab.command.html(command);

					// 回填汇率
					for(var end in recevier.xfsd ){
						
						// ES6 forEach
						Array.prototype.forEach.call(recevier.xfsd[end], function(value, index, array){
							var price = value.singleLineFee - 0 > 0  ? value.singleLineFee : value.backLineFee - 0;
							var rs = price*recevier.rate;
							if(rs) { 
								value.singleLineFee - 0 > 0  ? array[index].singleLineFee = Math.ceil(rs/10)*10 : array[index].backLineFee = Math.ceil(rs/10)*10  ;
							}
						})
						
					}


					// 按照表单模板渲染
					recevier.mkTable(recevier.xfsd, recevier.target, recevier.context, 'a');

					// 多选框
					for(var end in recevier.xfsd){
						checkbox({
							all: 'cAll'+end,
							select: 'cSelect'+end
						})
					}

					// 垃圾回收
					recevier.lab = null;
					recevier.mkTable = null;
					recevier.rmTable = null;
					recevier.progress = null;

				})(_arg[0])
			});
	}

	var selected = function(selectedClassName){
		var selected = {}, target = recevier.target;
		var o = $('.'+target).find('.'+selectedClassName+':checked')

		// for(var i = 0; i < o.length; i++){
		// 	var end = o[i].id.match(/c(\w{3})\d{2}/)[1];
		// 	selected[end] = [];
		// }

		for(var i = 0; i < o.length; i++){
			end = o[i].id.match(/c(\w{3})\d{1,}/)[1];

			if(Object.prototype.toString.call(selected[end]) !== '[object Array]' ){
				selected[end] = [];
			}

			selected[end].push(o[i].value);
		}

		for( var end in selected){
			selected[end] = jQuery.fn.unique(selected[end]);
		}

		recevier.selected = selected;

	}

	var fliterFare = function(){

		if(recevier.target === null){
			console.log('no data to fliter');
			return;
		}

		if(!recevier.xfsd){ return; }

		// 按照fare筛选数据
		var fareJson = {};
		var fareArray = [],
				totalOrgLength = 0,
				totalFliterLength = 0;

		for(var end in recevier.xfsd ){

			// 原数据
			// fareArray[end] = recevier.xfsd[end];

			// 全部选择，筛选相同fare展示
			// fareArray[end] = jQuery.fn.unique(recevier.xfsd[end], function(){
			// 		return this.fare;
			// });

			// 增加选择框
			fareArray[end] = [];
			fareJson[end] = {};

			for( var j in recevier.selected[end]){
				for(var i = 0; i < recevier.xfsd[end].length; i++){

					if(recevier.xfsd[end][i].fare === recevier.selected[end][j]){
						fareArray[end].push(recevier.xfsd[end][i]);
						[].push.call(fareJson[end] ,recevier.xfsd[end][i]);
					}

				}
			}

			// 保留原来字段
			fareArray[end].aircompany = recevier.xfsd[end].aircompany;
			fareArray[end].from = recevier.xfsd[end].from;
			fareArray[end].startDate = recevier.xfsd[end].startDate;

			fareJson[end].aircompany = recevier.xfsd[end].aircompany;
			fareJson[end].from = recevier.xfsd[end].from;
			fareJson[end].startDate = recevier.xfsd[end].startDate;

			// 统计数量
			fareArray[end].orgLength = recevier.xfsd[end].length;
			fareArray[end].fliterLength = Array.prototype.slice.call(fareArray[end]).length; // 实际数据长度
			totalOrgLength += fareArray[end].orgLength;
			totalFliterLength += fareArray[end].fliterLength;

		}


		// 筛选
		console.log('** OrigTotal: '+ totalOrgLength +'- SeletedTotal: ' + totalFliterLength+' **');

		// 缓存筛选fare的xfsd结果
		recevier.fareArray = fareArray;
		recevier.fareJson  = fareJson;
		recevier.fareArray.totalFliterLength = totalFliterLength;
	}

	var getFliterPolicy = function(remove){
		'use strict';

		if( recevier.fareArray.totalFliterLength === 0 ){
			console.log('** Not Fliter Fare Array **')
			return;
		}

		console.log("** Geting Policy ... **")

		// 初始化
		var pi = 1;                         // 当前进度
		recevier.fliterPolicy = [];         // 所有政策
		recevier.progress = progress();
		recevier.progress.create('#content-progress');

		for(var end in recevier.fareArray){
			// 排除无关
			if(end === 'totalFliterLength'){
				continue;
			}

			for(var i = 0 ; i < recevier.fareArray[end].length ; i++){

				var query = {
					dosubmit: 'select',
					fare: recevier.fareArray[end][i].fare,
					start: recevier.fareArray[end][i].start,
					end: recevier.fareArray[end][i].end,
					startDate: recevier.fareArray[end].startDate,
					aircompany: recevier.fareArray[end].aircompany,
					from: recevier.fareArray[end].from,
					remove: remove // 删除缓存开关
				}

				// Controller
				
				!function(end, i){
					recevier.getData( '/admin/fare.php', query, function(){

						var policy = [].shift.call(arguments);

						// console.log(policy.command)

						recevier.fareArray[end][i].policy = policy.data;
						recevier.fareJson[end][i].policy = policy.data;
						recevier.fliterPolicy.push(policy); 
			
						// 进度条
						recevier.progress.have(pi++, recevier.fareArray.totalFliterLength); 

						// 回填
						if(recevier.fareArray.totalFliterLength+1 === pi){
							appendPolicy(recevier);
						}

					})
				}(end, i)
			}

		}
	}


	var mixCabin = function(config){

		// 配置
		var container = config.container,
				deleter = config.deleter,
				modal = config.modal,
				action = "";  // 混舱操作
				recevier.target = 'table';

		for(var end in recevier.xfsd ){
			// 标示航空公司
			var aircompany = recevier.xfsd[end].aircompany;
		}
		
		// 模板
		if(recevier.isFunction(mkMixTable) && typeof recevier.isFunction(rmTable)){
			recevier.mkMixTable = mkMixTable; 
			recevier.rmTable = rmTable; 
		}else{
			console.log("'mkTable' or 'rmTable' haven't added in ")
		}



		if( recevier.fareJson !== undefined ){
			// 有使用规则时
			if( !recevier.fareArray.totalFliterLength > 0 ){
				console.log("** Oh! No fare Policy **")
				return ;
			}

		}else{
			// 不使用使用规则
			recevier.xfsdJson = {};

			// 获取选中的xfsd数据
			var firstDate; // 检测数据
			var aircompany; // 航空公司
			for(var end in recevier.xfsd ){

				recevier.xfsdJson[end] = {};

				if(aircompany === undefined ){
					aircompany = recevier.xfsd[end].aircompany;
				}


				for( var j in recevier.selected[end]){
					for(var i = 0; i < recevier.xfsd[end].length; i++){

						if(recevier.xfsd[end][i].fare === recevier.selected[end][j]){
							[].push.call(recevier.xfsdJson[end], recevier.xfsd[end][i]); // 装载 xfsdJson 函数
						}

					}
				}

				// 增加航空公司属性
				for(var o = 0; o < recevier.xfsdJson[end].length; o++){
					recevier.xfsdJson[end][o].aircompany = aircompany;
				}

				if( firstDate === undefined ){
					firstDate = recevier.xfsdJson[end][0]
				}

				if(recevier.xfsdJson[end].length > 0){
					recevier.xfsdJson[end].from = recevier.xfsd[end].from;
					recevier.xfsdJson[end].startDate = recevier.xfsd[end].startDate;
				}

			}


			// 检测数据
			if(recevier.xfsdJson && firstDate === undefined){
				console.log('** No xfsd Import ! **');
				action = "";
			}else if(recevier.xfsdJson){
				console.log('** Get Cabin For Mixed ! **');
				action = 'add';
			}

		}	

		recevier.link( Controller + 'addMixCabin', {'data': recevier.xfsdJson, 'aricompany': aircompany, 'action': action}, function(msg){
			// '/admin/mixCabin.php?forAction'

			$(container).html("");

			// 展示session
			recevier.mkMixTable(msg, recevier.target, config.container, 'a'); // recevier.xfsdJson 当前选择
			recevier.mkMixTable = null;

		}).done(function(){

			$(config.deleter).click(function(){
				recevier.link( Controller + 'deleteMixCabin?delete='+$(this).attr('num'), {}, function(){
					// '/admin/mixCabin.php?delete='+$(this).attr('num')
						alert('删除成功！')
						$(modal).modal('hide')
						// 关闭模态窗口
				});
			})

		});

	}

	var addCabin = function(json){
		
		var container = '#mixCabinContent';

		recevier.target = 'table';
		var end = "";
		for(var i in json){
			end = i;
		}

		var aircompany = json[end][0].aircompany

		if(json && json[end][0].fare === ""){
			console.log('** No xfsd Import ! **');
			action = "";
		}else{
			console.log('** Get Cabin For Mixed ! **');
			action = 'add';
		}

		// 模板
		if(recevier.isFunction(mkMixTable) && typeof recevier.isFunction(rmTable)){
			recevier.mkMixTable = mkMixTable; 
			recevier.rmTable = rmTable; 
		}else{
			console.log("'mkTable' or 'rmTable' haven't added in ")
		}

		console.log(json)
		recevier.link( Controller + 'addMixCabin', {'data': json, 'aricompany': aircompany, 'action': action}, function(msg){
			// '/admin/mixCabin.php?forAction'

			console.log(msg)
			// 展示session
			recevier.mkMixTable(msg, recevier.target, container, 'w'); // recevier.xfsdJson 当前选择
			recevier.mkMixTable = null;
		})

	}

	var clearMixCabin = function(config){
		var modal = config.modal;

		recevier.link( Controller + 'clearMixCabin', {'clear': ""}, function(){
			// '/admin/mixCabin.php'
			alert('清空成功！');

			// 关闭模态窗口
			$(modal).modal('hide');
		})
	}

	var mixCabinByTpl = function(tpl, tplName, typeName){
		recevier.submit( Controller + 'showMixCabinTpl' , {'tpl': tpl, 'tplName': tplName, 'typeName': typeName});
		// 'mixCabin.php?display=1&action=byTpl'
	}

	var rate = function(){

		recevier.cnyLab = $('#labtoCNY')

		recevier.link(Controller + 'toCNY', {toCNY: "", command: "XS FSC NUC/CNY"}, function(msg){
			recevier.rate =  msg.rate - 0;
			recevier.cnyLab.html("当前汇率："+msg.rate+'<br>');

			recevier.cnyLab = null;
		}); 
	}

	var fare = function(query){

		// 初始化
		recevier.query = query;
		recevier.target = 'table';
		recevier.context = "#content";
		recevier.progress = progress();
		recevier.progress.create('#content-progress');

		// 模板
		if(recevier.isFunction(mkTable) && typeof recevier.isFunction(rmTable)){
			recevier.mkTable = mkTable; 
			recevier.rmTable = rmTable; 
		}else{
			console.log("'mkTable' or 'rmTable' haven't added in ")
		}

		// 备注
		recevier.lab = $('#lab');
		recevier.lab.command = $('.command');

		// 请求数据
		recevier.getData( Controller + 'searchFsd', recevier.query,
			// 回调函数
			function(){
				var _arg = arguments;

				recevier.rmTable(recevier.target);
				recevier.lab.show();
				recevier.progress.complete();

				return (function(data){
					// 回填数据
					recevier.mkTable(data.array, recevier.target, recevier.context, 'w');
					recevier.lab.command.html(data.command);

					// 垃圾回收
					recevier.lab = null;
					recevier.mkTable = null;
					recevier.rmTable = null;

				})(_arg[0])
			});
	}

	var avh = function(query, append){
		recevier.target = 'table';
		recevier.context = "#content";
		recevier.progress = progress();

		// 模板
		if(recevier.isFunction(mkTable) && typeof recevier.isFunction(rmTable)){
			recevier.mkTable = mkTable; 
			recevier.rmTable = rmTable; 
		}else{
			console.log("'mkTable' or 'rmTable' haven't added in ")
		}

		// 请求数据
		recevier.progress.create('#content-progress');

		recevier.getData( Controller + 'searchAvhByInput', query,
			// 回调函数
			function(){
				var _arg = arguments;

				recevier.rmTable(recevier.target);
				recevier.progress.complete();

				return (function(data){

					recevier.cabinArray = data;

					if(append){
						if(data.type === 'array'){
							for(var end in data.array){
								recevier.mkTable(recevier.cabinArray.array[end], recevier.target, recevier.context, 'a');
							}
						}else{
							recevier.mkTable(recevier.cabinArray.array, recevier.target, recevier.context, 'w');
						}
					}


					$('#mkTable').click(function(){
						rmTable(recevier.target);
						
						if(data.type === 'array'){
							for(var end in data.array){
								mkTable(recevier.cabinArray.array[end], recevier.target, recevier.context, 'a'); 
							}
						}else{
							mkTable(recevier.cabinArray.array, recevier.target, recevier.context, 'w'); 
						}
					})

					// 垃圾回收
					recevier.mkTable = null;
					recevier.rmTable = null;
					recevier.progress = null;

				})(_arg[0])
			});
	}

	var avSabre = function(query, append){
		recevier.target = 'table';
		recevier.context = "#content";
		recevier.progress = progress();

		// 模板
		if(typeof mkTable === 'function' && typeof rmTable === 'function'){
			recevier.mkTable = mkTable; 
			recevier.rmTable = rmTable; 
		}else{
			console.log("'mkTable' or 'rmTable' haven't added in ")
		}

		// 请求数据
		recevier.progress.create('#content-progress');
		recevier.getData('/admin/av.sabre.php', query,
			// 回调函数
			function(){
				var _arg = arguments;

				recevier.rmTable(recevier.target);
				recevier.progress.complete();

				return (function(data){

					if(append && data !== undefined){
						mkSeatLevel(data.array);
						recevier.mkTable(data.array, recevier.target, recevier.context, 'w');
					}
					
					recevier.cabinArray = data;

					$('#mkTable').click(function(){
						rmTable(recevier.target);
						mkTable(data.array, recevier.target, recevier.context, 'w'); 
					})

					// 垃圾回收
					recevier.mkTable = null;
					recevier.rmTable = null;
					recevier.progress = null;

				})(_arg[0])
			});
	}


	var planAvSabre = function(query, append){
		recevier.target = 'table-av';
		recevier.context = "#model-content";

		// 模板
		if(recevier.isFunction(mkTable) && typeof recevier.isFunction(rmTable)){
			recevier.mkTable = mkTable; 
			recevier.rmTable = rmTable; 
		}else{
			console.log("'mkTable' or 'rmTable' haven't added in ")
		}

		// 请求数据
		recevier.getData('/admin/av.sabre.php', query,
			// 回调函数
			function(){
				var _arg = arguments;

				recevier.rmTable(recevier.target);

				return (function(data){

					recevier.mkTable(data.array, recevier.target, recevier.context, 'w');
					
					recevier.cabinArray = data;

					$('#mkTable').click(function(){
						rmTable(recevier.target);
						mkTable(data.array, recevier.target, recevier.context, 'w'); 
					})

					// 垃圾回收
					recevier.mkTable = null;
					recevier.rmTable = null;

				})(_arg[0])
			});
	}

	var planAvhEterm = function(query, append){
		recevier.target = 'table-av';
		recevier.context = "#model-content";

		// 模板
		if(recevier.isFunction(mkTable) && typeof recevier.isFunction(rmTable)){
			recevier.mkTable = mkTable; 
			recevier.rmTable = rmTable; 
		}else{
			console.log("'mkTable' or 'rmTable' haven't added in ")
		}

		// 请求数据
		recevier.getData('/admin/avh.php', query,
			// 回调函数
			function(){
				var _arg = arguments;

				recevier.rmTable(recevier.target);

				return (function(data){

					recevier.mkTable(data.array, recevier.target, recevier.context, 'w');
					
					recevier.cabinArray = data;

					$('#mkTable').click(function(){
						rmTable(recevier.target);
						mkTable(data.array, recevier.target, recevier.context, 'w'); 
					})

					// 垃圾回收
					recevier.mkTable = null;
					recevier.rmTable = null;

				})(_arg[0])
			});
	}

	var checkSignin = function(signin){

		var query = {
			dosubmit: "",
			name: signin.name.val() || "", 
			password: signin.password.val() || "",
			resource: signin.resource.val() || ""
		} 
		if (/index.php\/admin\/user/.test(Controller)){
			var url = Controller + 'login',
					location =  Project + 'index.php/admin/eterm/xfsd';
		}else{
			var url = Controller + 'index.php/admin/user/login', 
					location = Controller + "index.php/admin/eterm/xfsd" ;
		}

		recevier.link( url, query, function(data){
			console.log(data)
			if(data !== undefined && data.status === 1){
				alert("登录账号："+data.res.name+"，配置号："+data.res.resource);
				document.location.href = location;
			}else if(data.status === 0){
				alert("登录账号不正确，请重新登录");
				signin.name.val("");
				signin.password.val("");
				signin.resource.val("BJS248");
			}else{
				console.log('data is error')
			}
		});
	}

	var productModify = function(query){
		// 检查 query 合法性
		// ** 

		var xfsd_org,         // 公布运价
			  xfsd_pri,         // 底价
			  avh_plan = [],    // avh 计划任务
			  avh = {},              // avh 舱位数据
			  taobao_data;      // 淘宝运价
 
		
		// 模板
		recevier.target = 'table';
		recevier.context = "#content";
		var container = $('#content');
		if(recevier.isFunction(mkTable) && typeof recevier.isFunction(rmTable)){
			recevier.mkTable = mkTable; 
			recevier.rmTable = rmTable; 
		}else{
			console.log("'mkTable' or 'rmTable' haven't added in ")
		}

		// 初始化
		recevier.progress = progress();
		recevier.progress.create('#content-progress');

		recevier.rmTable(recevier.target);

		query.dosubmit = ""; // xfsd 入口
		query.aircompany = query.arrAirline.substr(0,2);
		var pi = 0,  // 进度条前进部分
				completePi = 2; // 总进度条长度

		// taobao
		var startDateFormate = new Date(/17/.test(query.startDate)?query.startDate: query.startDate+"17").format('yyyy-MM-dd'),
				endDateFormate   = new Date(/17/.test(query.endDate)?query.endDate: query.endDate+"17").format('yyyy-MM-dd');

		// 淘宝 PEK -> BJS
		var airportToCity = {
			'PEK': 'BJS'
		}
		var taobaoGoReq = {
			_ksTS: "", // 1482461772335_250
			callback: "json", // jsonp251
			supportMultiTrip: true,
			searchBy: "",
			searchJourney: '[{'
				+'"arrCityCode":"'+query.end+'",'
				+'"arrCityName":"",'
				+'"depCityCode":"'+ (airportToCity[query.start] ? airportToCity[query.start]: query.start) +'",'
				+'"depCityName":"",'
				+'"depDate":"'+startDateFormate+'",'
				+'"selectedFlights":['+'{"marketFlightNo":"'+ query.depAirline +'", "flightTime":"'+startDateFormate+' '+defaultFlight[query.depAirline].time+'"}'+']'
				+'},{'
				+'"arrCityCode":"'+ (airportToCity[query.start] ? airportToCity[query.start]: query.start)+'",'
				+'"arrCityName":"",'
				+'"depCityCode":"'+query.end+'",'
				+'"depCityName":"",'
				+'"depDate":"'+endDateFormate+'",'
				+'"selectedFlights":['+'{"marketFlightNo":"'+ query.arrAirline +'", "flightTime":"'+endDateFormate+' '+defaultFlight[query.arrAirline].time+'"}'+']'
			+'}]', 
			tripType: 1,
			searchCabinType:0,
			agentId:-1,
			searchMode: 2,
			b2g:0,
			formNo:-1,
			cardId:"",
			needMemberPrice:true
		} 
		// 公布运价
		var queryPuc = query;
		queryPuc.private = ""

		// 舱位数据
		var avhPlanQuery = {
			dosubmit: 'avhDb',
			start: query.start,
			end: query.end,
			startDate: query.startDate,
			depAirline: query.depAirline,
			arrAirline: query.arrAirline,
		}

		var selectCabin = ['X','L','T','Q']; // 组成产品的舱位

		// taobao
		recevier.reqJSONP('https://sijipiao.alitrip.com/ie/flight_search_result_poller.do?'+$.param(taobaoGoReq), function(msg){
			taobao_data = msg
			console.log(msg)

			var html = appendTrSub(msg)
			container.append(html)
		 	recevier.progress.have(pi++, completePi); 
		})

			// console.log(matchCabin)
			mkCabinTpl(matchCabin, recevier.target, recevier.context, 'a');


	}


	var basisAircompany = function(){
		recevier.target = 'table';
		recevier.context = '#content'
		recevier.progress = progress();

		// 模板
		if(recevier.isFunction(mkTable) && typeof recevier.isFunction(rmTable)){
			recevier.mkTable = mkTable; 
			recevier.rmTable = rmTable; 
		}else{
			console.log("'mkTable' or 'rmTable' haven't added in ")
		}

		recevier.progress.create('#content-progress');
		recevier.getData( Controller + 'searchAircompany', {}, function(data){
				recevier.rmTable(recevier.target);
				recevier.progress.complete();
				if(data.status === 'success'){
					recevier.mkTable(data.result, recevier.target, recevier.context, 'w');
				}else{
					alert(data.msg);
				}
					
				// 垃圾回收
				recevier.progress = null;
				recevier.mkTable = null;
				recevier.rmTable = null;
		});
	}

	var findAircompany = function(query){
		recevier.target = 'render'
		recevier.context = '#content'
		recevier.progress = progress();

		// 模板
		if(recevier.isFunction(render) && typeof recevier.isFunction(rmTable)){
			recevier.render = render; 
			recevier.rmTable = rmTable; 
		}else{
			console.log("'mkTable' or 'rmTable' haven't added in ")
		}

		recevier.progress.create('#content-progress');
		recevier.getData( Controller + 'findAircompany', query, function(data){
				recevier.rmTable(recevier.target);
				recevier.progress.complete();
				if(data.status === 'success'){
					recevier.render( {'aircompany' : data.result, 'result_flight': data.result_flight}, recevier.target, recevier.context, 'w');
				}else{
					alert(data.msg);
				}
					
				// 垃圾回收
				recevier.progress = null;
				recevier.render = null;
				recevier.rmTable = null;
		});
	}

	var searchRouting = function (query){
		recevier.target = 'table-routing';
		recevier.context = '#content'
		recevier.progress = progress();

		// 模板
		if(recevier.isFunction(mkTable) && typeof recevier.isFunction(rmTable)){
			recevier.mkTable = mkTable; 
			recevier.rmTable = rmTable; 
		}else{
			console.log("'mkTable' or 'rmTable' haven't added in ")
		}

		recevier.progress.create('#content-progress');
		recevier.getData( Controller + 'searchRouting', query, function(data){
				recevier.rmTable(recevier.target);
				recevier.progress.complete();
				if(data.status === 'success'){
					recevier.mkTable(data, recevier.target, recevier.context, 'w');
				}else{
					alert(data.msg);
				}
					
				// 垃圾回收
				recevier.progress = null;
				recevier.mkTable = null;
				recevier.rmTable = null;
		});
	}

	return {
		xfsd: xfsd,                             // 获得xfsd数据，并用table回填到页面中
		selected: selected,                     // 选择
		fliterFare: fliterFare,                 // 筛选xfsd的fare，并生成fareArray数组
		getFliterPolicy: getFliterPolicy,       // 根据fareArray批量获得政策信息
		appendPolicy: appendPolicy,             // 向XFSD回填使用规则
		fare: fare,                             // fare页面获取使用规则
		rate: rate,                             // 汇率
		mixCabin: mixCabin,                     // 混舱
		clearMixCabin: clearMixCabin,           // 清楚混舱数据
		mixCabinByTpl: mixCabinByTpl,           // 通过模板混舱
		avh: avh,                               // Eterm 的舱位查询 avh 
		avSabre: avSabre,                       // Sabre 的舱位查询 av 
		planAvSabre: planAvSabre,               // sabre计划任务舱位展示
		planAvhEterm: planAvhEterm,             // eterm计划任务舱位展示
		checkSignin: checkSignin,               // 登录配置检查
		productModify: productModify,           // 产品调价（淘宝）
		addCabin: addCabin,                     // 手工添加混舱
		basisAircompany: basisAircompany,       // 基础数据：查询全部航空公司
		findAircompany: findAircompany,         // 基础数据：查询某航空公司
		searchRouting: searchRouting,           // 合成数据：查询航路
	}
}


var airportMatchCity = {
	'PEK': 'BJS'
}

var defaultFlight = {
  'EK307': { // pek-dxb
    'flightNo': 'EK307',
    'time': '23:40:00'
  },
  'EK308': { // dxb-pek
    'flightNo': 'EK308',
    'time': '11:00:00'
  },
  'EK309': { // pek-dxb
    'flightNo': 'EK309',
    'time': '07:25:00'
  },
  'EK306': { // dxb-pek
    'flightNo': 'EK306',
    'time': '03:30:00'
  },
  'EK305': { // pvg-pek
    'flightNo': 'EK305',
    'time': '06:15:00'
  },
  'EK302': { // dxb-pvg
    'flightNo': 'EK302',
    'time': '03:10:00'
  },
  'EK303': { // pvg-dxb
    'flightNo': 'EK303',
    'time': '23:00:00'
  },
  'EK304': { // dxb-pvg
    'flightNo': 'EK304 ',
    'time': '09:15:00'
  },
  'EK363': { // can-dxb
    'flightNo': 'EK363 ',
    'time': '00:15:00'
  },
  'EK362': { // dxb-can
    'flightNo': 'EK363 ',
    'time': '10:50:00'
  },
}

function rmTable(dom){
	$('.'+dom).remove();
}

// function mkTable(array, dom, context, appendType){
		// array: 返回数据中待渲染数组
		// dom: 为渲染模板起名
		// context: 指定渲染容器
		// appendType: 回填模式 w代表覆盖，a代表追加
// }

// taobao tpl
function appendTrSub(data){

  if(data.success === 0){return;}

  var fulldataStart = data.data.flightInfos[0];
  var fulldataEnd = data.data.flightInfos[1];

  // 渲染模板
  var html =  '';
      //- html += '<p>弹出'+trid+'返程数据</p>';
      // 去程信息
      html += '<div class="panel panel-success"> <div class="panel-heading"><h3 class="panel-title">去程:'+fulldataStart.mainAirlineName+fulldataStart.mainAirlineCode+'</h3></div><div class="panel-body">'
        for(var w in fulldataStart.flightSegments){
          html += '<p>'+fulldataStart.flightSegments[w].depAirportCode+'('+fulldataStart.flightSegments[w].depTimeStr+')->'+fulldataStart.flightSegments[w].arrAirportCode+'('+fulldataStart.flightSegments[w].arrTimeStr+')历时:'+fulldataStart.flightSegments[w].duration+'分钟 '+fulldataStart.flightSegments[w].marketingAirlineName+'('+fulldataStart.flightSegments[w].marketingAirlineCode+')'+'-'+fulldataStart.flightSegments[w].marketingFlightNo
          
          if(fulldataStart.flightSegments[w].operatingFlightNo){
            html += ' '+fulldataStart.flightSegments[w].operatingAirlineName+'('+fulldataStart.flightSegments[w].operatingAirlineCode+')-'+fulldataStart.flightSegments[w].operatingFlightNo
          }
        }
      html += '</div></div>';

      // 回程信息
      html += '<div class="panel panel-success"> <div class="panel-heading"><h3 class="panel-title">回程:'+fulldataEnd.mainAirlineName+fulldataEnd.mainAirlineCode+'</h3></div><div class="panel-body">'
        for(var w in fulldataEnd.flightSegments){
          html += '<p>'+fulldataEnd.flightSegments[w].depAirportCode+'('+fulldataEnd.flightSegments[w].depTimeStr+')->'+fulldataEnd.flightSegments[w].arrAirportCode+'('+fulldataEnd.flightSegments[w].arrTimeStr+')历时:'+fulldataEnd.flightSegments[w].duration+'分钟 '+fulldataEnd.flightSegments[w].marketingAirlineName+'('+fulldataEnd.flightSegments[w].marketingAirlineCode+')'+'-'+fulldataEnd.flightSegments[w].marketingFlightNo
          
          if(fulldataEnd.flightSegments[w].operatingFlightNo){
            html += ' '+fulldataEnd.flightSegments[w].operatingAirlineName+'('+fulldataEnd.flightSegments[w].operatingAirlineCode+')-'+fulldataEnd.flightSegments[w].operatingFlightNo
          }
        }
      html += '</div></div>'

      // 舱位和代理人
      var agent = data.data.productItems
      html += '<table class="table table-hover">'
      html += '<tr><th>名称</th><th>成人</th><th>税</th><th>金额</th><th>儿童</th><th>儿童税</th><th>去</th><th>回</th><th>去程日期</th><th>回程日期</th></tr>' // 名称 showName 成人 adultPrice 税  adultTax 金额 totalAdultPricev  儿童 childPrice 儿童税 childTax 舱位 cabinInfo for(var c in cabinInfo){cabinInfo}
      for(var a in agent){ 
        html += '<tr><td>'
        if(agent[a].agentInfo){
          html += agent[a].agentInfo.showName
        }else if(agent[a].firstAgentInfo){
          html += agent[a].firstAgentInfo.showName
        }
        html += '</td><td>'+parseInt(agent[a].adultPrice)/100+'</td><td>'+parseInt(agent[a].adultTax)/100+'</td><td>'+parseInt(agent[a].totalAdultPrice)/100+'</td><td>'+parseInt(agent[a].childPrice)/100+'</td><td>'+parseInt(agent[a].childTax)/100+'</td>'
        if(agent[a].cabinInfo){
          html += '<td>' // ***
          if(agent[a].cabinInfo[0]){
            var c1 = agent[a].cabinInfo[0];
            for(var d in c1){
              //- html += c1[d].cabinClass+':'+c1[d].cabin+'('+c1[d].quantity+') ';
              html += c1[d].cabin;
            }
          }
          html += '</td><td>'
          if(agent[a].cabinInfo[1]){
            var c2 = agent[a].cabinInfo[1];
            for(var d in c2){
              //- html += c2[d].cabinClass+':'+c2[d].cabin+'('+c2[d].quantity+') ';
              html += c2[d].cabin;
            }
          }
          html += '</td>'
        }
        // html += '<td>'+$departDate+'</td><td>'+$arriveDate+'</td>' 
        html += '</tr>';
      }
      html += '</table>'


      html += '</td></tr>';
  //- $('#'+trid).after(html);
 		return html;
}

// 静态方法
function matchXFSD(policyName, matchReg, fixedName, callback){
	// policyName 数据中政策名
	// matchReg 匹配的正则
	// fixedName 回填数据名
	// callback 回填时调用的回调函数

	if( !this.policy[policyName] ) { return; }

	var result;

	// 带匹配的原政策
	// console.log(this.policy[policyName]);

	if( typeof policyName === 'array' && typeof matchReg === 'array' ){

		for( var i = 0; i< policyName.length; i++ ){
			var p = policyName[i], m = matchReg[i];
			result[i] = "".match.call(this.policy[p], m);
		}

	}else{
		result = "".match.call(this.policy[policyName], matchReg);
	}
	
	if( typeof result === 'array' && result.length > 0 ){

		// this[fixedName] = this[fixedName] +'->';

		for( var o = 0; o < result.length; o++ ){
			if( typeof callback === 'function' ){
				!function (data){
					return callback(data);
				}(result[o])
			}else{
				this[fixedName] += result[o]+' ';
			}
		}

	}else if(result){

		if( typeof callback === 'function' ){
			callback(result);
		}else{
			// this[fixedName] =  this[fixedName]+'->'+result;
			this[fixedName] = result;
		}

	}

	// 匹配后回填的数据
	// console.log(result);

}

// 回填数据
function appendPolicy(recevier){

		// 检查数据
		if( !recevier.fliterPolicy ) {
			console.log('** No fliterPolicy **')
			return ;
		}

		if( recevier.fliterPolicy.length !== recevier.fareArray.totalFliterLength ){
			console.log("** Oh! Not Get All Policy! **")
			return ;
		}

		// 解析数据并回填
		console.log("** Success! Got All Policy! **")

		// 所有政策
		// console.log(recevier.fliterPolicy);
		// console.log(recevier.fareArray);

		// 初始化
		// recevier.progress = progress();


		// 模板
		if(typeof mkMatchTable === 'function' && typeof rmTable === 'function'){
			recevier.mkMatchTable = mkMatchTable; 
			recevier.rmTable = rmTable; 
		}else{
			console.log("'mkTable' or 'rmTable' haven't added in ")
		}

		// recevier.progress.create('#content-progress');

		// 政策解析
		var matchReg = {
			// 最短停留
			'minStay': /\d+[\s|\r|\t|\f]*(DAYS|MONTH)/g,
			'maxStay': /\d+[\s|\r|\t|\f]*(DAYS|MONTH)/g,
			'advp': /TICKETING[\s|\r|\t|\f]*MUST[\s|\r|\t|\f]*BE[\s|\r|\t|\f]*COMPLETED[\s|\r|\t|\f]*AT[\s|\r|\t|\f]*LEAST[\s|\r|\t|\f]*(\d+[\s|\r|\t|\f]*DAYS)[\s|\r|\t|\f]*BEFORE[\s|\r|\t|\f]*DEPARTURE/,
			'travelDate': /(\d+[\s|\r|\t|\f]*(JAN|FEB|MAR|APR|MAY|JUN|AUG|SEP|OCT|NOV|DEC)).*THROUGH\s(\d+[\s|\r|\t|\f]*(JAN|FEB|MAR|APR|MAY|JUN|AUG|SEP|OCT|NOV|DEC))/, // (.*OR.*)?(\d+[\s|\r|\t|\f]*(JAN|FEB|MAR|APR|MAY|JUN|AUG|SEP|OCT|NOV|DEC)).*THROUGH.*(\d+[\s|\r|\t|\f]*(JAN|FEB|MAR|APR|MAY|JUN|AUG|SEP|OCT|NOV|DEC))?
			'travelRestrict': /VALID\sFOR\sTRAVEL\sCOMMENCING\sON\/AFTER\s(\d{2}(JAN|FEB|MAR|APR|MAY|JUN|AUG|SEP|OCT|NOV|DEC))\s\d{2}\sAND\sON\/[\s|\r|\t|\f]*\*\*\s{2}BEFORE\s(\d{2}(JAN|FEB|MAR|APR|MAY|JUN|AUG|SEP|OCT|NOV|DEC))\s\d{2}/,
			'fliterDateOutbound': /OUTBOUND.*[\s|\r|\t|\f]*.*NOT\sPERMITTED\s(\d{2}(JAN|FEB|MAR|APR|MAY|JUN|AUG|SEP|OCT|NOV|DEC))\s\d{2}\sTHROUGH\s(\d{2}(JAN|FEB|MAR|APR|MAY|JUN|AUG|SEP|OCT|NOV|DEC))\s\d{2}/,
			'fliterDateInbound': /INBOUND.*[\s|\r|\t|\f]*.*NOT\sPERMITTED\s(\d{2}(JAN|FEB|MAR|APR|MAY|JUN|AUG|SEP|OCT|NOV|DEC))\s\d{2}\sTHROUGH\s(\d{2}(JAN|FEB|MAR|APR|MAY|JUN|AUG|SEP|OCT|NOV|DEC))\s\d{2}/,
			'saleDate': /TICKETS\sMUST\sBE\sISSUED\sON\/AFTER\s(\d+(JAN|FEB|MAR|APR|MAY|JUN|AUG|SEP|OCT|NOV|DEC))\s\d+.*[\s|\r|\t|\f]*.*(\d{2}(JAN|FEB|MAR|APR|MAY|JUN|AUG|SEP|OCT|NOV|DEC))\s\d+/,
			// 'ticketDate': /TICKETS\sMUST\sBE\sISSUED\sON\/AFTER\s(\d{2}(JAN|FEB|MAR|APR|MAY|JUN|AUG|SEP|OCT|NOV|DEC))\s\d{2}\sAND\sON\/BEFORE[\s|\r|\t|\f]*\*\*\s{2}(\d{2}(JAN|FEB|MAR|APR|MAY|JUN|AUG|SEP|OCT|NOV|DEC)\s\d{2})/,
			'change': /CHANGES.*[\s|\r|\t|\f]*.*BEFORE\sDEPARTURE.*[\s|\r|\t|\f]*.*(CNY\s\d+)/,
			'cancel': /CANCELLATIONS.*[\s|\r|\t|\f]*.*BEFORE\sDEPARTURE.*[\s|\r|\t|\f]*.*(CNY\s\d+)/,
			'day': /(MON|TUE|WED|THU|FRI|SAT|SUN)\sTHROUGH\s(MON|TUE|WED|THU|FRI|SAT|SUN)/,
			'addMoney': /(CNY\s\d+)\s.*[\s|\r|\t|\f]*.*ON\s(.*)\./,
		}

		for(var end in recevier.fareArray){
			// 排除无关
			if(end === 'totalFliterLength'){
				continue;
			}
			for(var i = 0 ; i < recevier.fareArray[end].length; i++){ 

				// 新增属性
				recevier.fareArray[end][i]['travelRestrict'] = ""; // 旅行限制 14
				recevier.fareArray[end][i]['fliterDateOutbound'] = "";     // 出境排除日期 11
				recevier.fareArray[end][i]['fliterDateInbound'] = "";     // 入境排除日期 11
				recevier.fareArray[end][i]['saleDate'] = "";       // 销售日期 15
				// recevier.fareArray[end][i]['ticketDate'] = "";     // 出票日期 15
				recevier.fareArray[end][i]['change'] = "";         // 改期 16
				recevier.fareArray[end][i]['cancel'] = "";         // 退票 16
				recevier.fareArray[end][i]['addMoney'] = "";            // 加钱 2

				// 回填属性
				matchXFSD.call(recevier.fareArray[end][i], '06.MINIMUM STAY', matchReg['minStay'], 'minStay', function(result){
					recevier.fareArray[end][i]['minStay'] = result[0].replace(/[\s|\r|\t|\f]/g,"")
				} );
				matchXFSD.call(recevier.fareArray[end][i], '07.MAXIMUM STAY', matchReg['maxStay'], 'maxStay', function(result){
					recevier.fareArray[end][i]['maxStay'] = result[0].replace(/[\s|\r|\t|\f]/g,"")
				} );
				matchXFSD.call(recevier.fareArray[end][i], '05.ADVANCE RES', matchReg['advp'], 'ADVPDay', function(result){
					recevier.fareArray[end][i]['ADVPDay'] = result[1].replace(/[\s|\r|\t|\f]/g,"")
				});
				matchXFSD.call(recevier.fareArray[end][i], '03.SEASONALITY', matchReg['travelDate'], 'travelDate', function(result){
					recevier.fareArray[end][i]['allowDateStart'] =  result[1];
					recevier.fareArray[end][i]['allowDateEnd']   = result[3];
				});
				matchXFSD.call(recevier.fareArray[end][i], '14.TRAVEL RESTRICTIONS', matchReg['travelRestrict'], 'travelRestrict', function(result){
					recevier.fareArray[end][i]['travelRestrict'] = result[1] + ' - ' + result[3];
				});
				matchXFSD.call(recevier.fareArray[end][i], '11.BLACKOUT DATES', matchReg['fliterDateOutbound'], 'fliterDateOutbound', function(result){
					recevier.fareArray[end][i]['fliterDateOutbound'] = result[1] + ' - ' + result[3];
				}); 
				matchXFSD.call(recevier.fareArray[end][i], '11.BLACKOUT DATES', matchReg['fliterDateInbound'], 'fliterDateInbound', function(result){
					recevier.fareArray[end][i]['fliterDateInbound'] = result[1] + ' - ' + result[3];
				}); 
				matchXFSD.call(recevier.fareArray[end][i], '15.SALES RESTRICTIONS', matchReg['saleDate'], 'saleDate', function(result){
					recevier.fareArray[end][i]['saleDate'] = result[1] + ' - ' + result[3];
				});

				// ignore
				// matchXFSD.call(recevier.fareArray[end][i], '15.SALES RESTRICTIONS', matchReg['ticketDate'], 'ticketDate', function(result){
				// 	recevier.fareArray[end][i]['ticketDate'] = result[1] + ' ' + result[2];
				// });


				matchXFSD.call(recevier.fareArray[end][i], '16.PENALTIES-CHANGES', matchReg['change'], 'change', function(result){
					recevier.fareArray[end][i]['change'] = result[1];
				});
				matchXFSD.call(recevier.fareArray[end][i], '16.PENALTIES-CHANGES', matchReg['cancel'], 'cancel', function(result){
					recevier.fareArray[end][i]['cancel'] = result[1];
				});
				matchXFSD.call(recevier.fareArray[end][i], '02.DAY/TIME', matchReg['day'], 'allowWeek', function(result){
					recevier.fareArray[end][i]['allowWeek'] = result[1] + ' - ' + result[2];
				} );

				// 特殊添加
				matchXFSD.call(recevier.fareArray[end][i], '12.SURCHARGES', matchReg['addMoney'], 'addMoney', function(result){
					recevier.fareArray[end][i]['addMoney'] = result[1] + ' ON: ' + result[2];
				});

				recevier.fareJson[end][i] = recevier.fareArray[end][i];
				recevier.fareJson[end][i].policy = null; // 不为数据库传递原政策数据
			}
		}


		recevier.mkMatchTable(recevier.fareArray, recevier.target, recevier.context, 'w');

		recevier.progress.complete();

		// 垃圾回收
		recevier.mkTable = null;
		recevier.rmTable = null;
		recevier.progress = null;
}

return {
	rmTable: rmTable,
	createCommand: createCommand,
	eterm: eterm
}

})