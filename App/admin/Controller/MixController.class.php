<?php
namespace admin\Controller;
use BYS\Controller;
class MixController extends Controller {
    public function policy(){
    	$this->display();
    }

    public function searchComposePolicy(){
    	$xfsd_r     = model('XfsdResult');
        $avh_r      = model('AvhResult');
        $toCity     = model('AirportCityCode');
        $dep        = $_POST['dep']; // 机场代码
        $arr        = $_POST['arr']; // 机场代码
        $date       = $_POST['date'];
        $aircompany = $_POST['aircompany'];
        $tripType   = $_POST['tripType'];

        // 转换城市代码
        $depCityResult = $toCity -> where("`ACC_Code` = '{$dep}'")->select();
        $arrCityResult = $toCity -> where("`ACC_Code` = '{$arr}'")->select();
        // **待完成**:没有则提示需要跑数据 

        $depCity       = $depCityResult[0]['ACC_CityCode'];
        $arrCity       = $arrCityResult[0]['ACC_CityCode'];
        // 查询xfsd
        $xfsd_where    = " `xfsd_dep`= '{$depCity}' AND `xfsd_arr` = '{$arrCity}' AND  `xfsd_DateStart` < '{$date}' AND '{$date}' < `xfsd_DateEnd`".($tripType == 'OW' ? ' AND `xfsd_SingleFee` > 0' : ' AND `xfsd_RoundFee` > 0');
        $xfsd_result   = $xfsd_r -> where($xfsd_where) ->select();

        // 查询avh 
        $avh_where     = " `avh_dep`= '{$dep}' AND `avh_arr` = '{$arr}' AND  `avh_Date` = '{$date}'";
        $avh_result = $avh_r -> where($avh_where) ->select();

        if($avh_result && $xfsd_result ){
            echo json_encode(array('status'=>'success','xfsdResult' => $xfsd_result, 'avhResult'=> $avh_result));
        }else{
            echo json_encode(array('status'=>'error', 'msg'=>'无数据'));
        }
    }

    // 混合查询：
    // xfsd from result 如果没有，则提示没有数据，需要重新查询
    // avh from result delete by day，如果没有，则提示没有数据，需要重新查询
    // 组合 routing 
    // 携程 底价api -> 抓取 routing 
    // routing 保存

}