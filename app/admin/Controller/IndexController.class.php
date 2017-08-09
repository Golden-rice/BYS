<?php
namespace admin\Controller;
use BYS\Controller;
class IndexController extends Controller {
    public function index(){
    	echo "controller method load success!";
    	$this->display();
    }

    

}