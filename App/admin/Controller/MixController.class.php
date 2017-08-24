<?php
namespace admin\Controller;
use BYS\Controller;
class MixController extends Controller {
    public function policy(){
    	$this->display();
    }

    public function composePolicy(){
    	$xfsd = model('XfsdResult');
    }

    // 混合查询：
    // xfsd from result 
    // avh from result delete by day
    // 组合 routing 
    // 携程 底价api -> 抓取 routing 
    // routing 保存
}