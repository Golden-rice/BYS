<?php
namespace admin\Controller;
use BYS\Controller;
class UserController extends Controller {
    public function index(){
    	$this->display('User/login');
    }

    public function login(){
    		if(isset($_POST["dosubmit"])){

				$url = "http://eterm.cctba.com:8350/COMMAND?USER=".$_POST['name']."&PASSWORD=".$_POST['password']."&RESOURCEETERM-SHARE-".$_POST['resource']."=&COMMAND=ddi:";
				if(@file_get_contents($url)){
					$result = file_get_contents($url);
				
					preg_match_all("/\[CDATA\[(.*?)\]\]/is",$result,$arr);
					preg_match_all("/OFFICE\s*\:\s*([A-Z0-9]*)\s/is",$arr[1][0], $eterm);

					if($eterm[1][0]){
						$_SESSION['name'] = $_POST['name'];
						$_SESSION['password'] = $_POST['password'];	
						// 根据反馈重置resource
						if(!empty($eterm[1][0]) && $eterm[1][0] != $_POST['resource']){
							$_SESSION['resource'] = $eterm[1][0];
						}
						else{
							$_SESSION['resource'] = $_POST['resource'];	
						}
						
			  		echo json_encode(array('status'=>1,'res'=>array('name'=>$_POST['name'],'resource'=>$eterm[1][0])));
		  		}
				}else{
					echo json_encode(array('status'=>0));
				}

			}else{
					$smarty->display('admin/user.html');
			}

    }
}