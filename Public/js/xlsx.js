require.config({
    // baseUrl: "/eterm/public/js/lib", 
　paths: {
            "shim": "lib/shim",
            "xlsxFull": "lib/xlsx.full.min",
            "jquery": "lib/jquery.min",
            'extend': "lib/extend",
   }
});

define('xlsx', ['jquery', 'shim', 'xlsxFull', "extend"], function ($, shim, xlsxFull, extend) {
// Global

// 表单数据 , 表头标题
var data = [], title;


/*
 * FileReader共有4种读取方法：
 * 1.readAsArrayBuffer(file)：将文件读取为ArrayBuffer。
 * 2.readAsBinaryString(file)：将文件读取为二进制字符串
 * 3.readAsDataURL(file)：将文件读取为Data URL
 * 4.readAsText(file, [encoding])：将文件读取为文本，encoding缺省值为'UTF-8'
 */


 /* 导入 */
function importf(obj, clk) { //导入
    if(!obj.files) {
        return;
    }
    // loading(mainContainer)
    var f = obj.files[0];
    var wb;            // 读取完成的数据
    var rABS = false;  // 是否将文件读取为二进制字符串
    var reader = new FileReader();

    reader.onload = function(e) {
        var data = e.target.result, 
            tmpdata = [],// 储存分配key和val的数组
            result,      // 解析结果
            titleMap = [],
            keyMap = []; // 键值数组 [{中文:英文}] => [英文]
                        
        if(rABS) {
            wb = XLSX.read(btoa(fixdata(data)), { //手动转化
                type: 'base64'
            });
        } else {
            wb = XLSX.read(data, {
                type: 'binary'
            });
        }
        // wb.SheetNames[0]是获取Sheets中第一个Sheet的名字
        // wb.Sheets[Sheet名]获取第一个Sheet的数据
        // document.getElementById("demo").innerHTML= JSON.stringify( XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]]) );
        result = eval( JSON.stringify( XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]])) )

        for(var k in title) {
            keyMap.push(title[k]); // 中文
        }
        for(var k in title) {
            titleMap.push(k); // 中文
        }
        
        // 根据中文识别英文
        result.map((v, i) => keyMap.map((k, j) =>Object.assign({}, {
            val: v[k] || "",
            key: titleMap[j], // 英文
            k: k,
            v: v,
            position: "行i:"+i+",列j:"+j
        
        }))).map((v, k) => {
            var c = "";
            v.map((val, x) => c += val.key+':"'+val.val+'",')
            tmpdata = tmpdata.concat( eval("[{" + c + "}]") )
        });

        // 暴露给全局
        data = tmpdata
        // console.log(data)


        if(typeof clk === "function"){
            clk(data)
        }

    };
    if(rABS) {
        reader.readAsArrayBuffer(f);
    } else {
        reader.readAsBinaryString(f);
    }
    
}

function fixdata(data) { //文件流转BinaryString
    var o = "",
        l = 0,
        w = 10240;
    for(; l < data.byteLength / w; ++l) o += String.fromCharCode.apply(null, new Uint8Array(data.slice(l * w, l * w + w)));
    o += String.fromCharCode.apply(null, new Uint8Array(data.slice(l * w)));
    return o;
}

/* 导出 */
function downloadExl(json, type) {
    var tmpDown; //导出的二进制对象
    var keyMap = [];//获取键
    var tmpdata = [];//用来保存转换好的json 
    // console.log(json)
    for(k in json[0]) {
        keyMap.push(k); // 英文
    }
    json.map((v, i) => keyMap.map((k, j) => Object.assign({}, {
        v: v[k],
        position: (j > 25 ? getCharCol(j) : String.fromCharCode(65 + j)) + (i + 1)
    }))).reduce((prev, next) => prev.concat(next)).forEach((v, i) => tmpdata[v.position] = {
        v: v.v
    });
    // console.log(tmpdata)
    var outputPos = Object.keys(tmpdata); //设置区域,比如表格从A1到D10
    var tmpWB = {
        SheetNames: ['mySheet'], //保存的表标题
        Sheets: {
            'mySheet': Object.assign({},
                tmpdata, //内容
                {
                    '!ref': outputPos[0] + ':' + outputPos[outputPos.length - 1] //设置填充区域
                })
        }
    };

    tmpDown = new Blob([s2ab(XLSX.write(tmpWB, 
        {bookType: (type == undefined ? 'xlsx':type),bookSST: false, type: 'binary'} //这里的数据是用来定义导出的格式类型
        ))], {
        type: ""
    }); // 创建二进制对象写入转换好的字节流

    var href = URL.createObjectURL(tmpDown); //创建对象超链接

		if(document.getElementById("hf") === null){
			// <a href="" download="download.xlsx" id="hf"></a>
			var a = document.createElement('a')
			a.id = "hf";
			a.download = "download.xlsx"; // 下载文件名
			document.body.append(a);

		}

    document.getElementById("hf").href = href; //绑定a标签
    document.getElementById("hf").click(); //模拟点击实现下载
    setTimeout(function() { //延时释放
        URL.revokeObjectURL(tmpDown); //用URL.revokeObjectURL()来释放这个object URL
    }, 100);
}

function s2ab(s) { //字符串转字符流
    var buf = new ArrayBuffer(s.length);
    var view = new Uint8Array(buf);
    for(var i = 0; i != s.length; ++i) view[i] = s.charCodeAt(i) & 0xFF;
    return buf;
}

// 将指定的自然数转换为26进制表示。映射关系：[0-25] -> [A-Z]。
function getCharCol(n) {
    let temCol = '',
        s = '',
        m = 0
    while(n > 0) {
        m = n % 26 + 1
        s = String.fromCharCode(m + 64) + s
        n = (n - m) / 26
    }
    return s
}



function setTitle(t){
    title = t
}

function saveData(a){
    data = a
}

function readData(){
    return data;
}

return {
    data: data,
    title: title,
    importf: importf,
    fixdata: fixdata,
    downloadExl: downloadExl,
    saveData: saveData,
    readData: readData,
    setTitle: setTitle,
    checkbox: extend.checkbox,
}

})