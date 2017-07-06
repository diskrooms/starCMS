<?php
namespace Admin\Controller;
use Common\Controller\AdminbaseController;

class ShareController extends AdminbaseController{

	protected $shareMdl;
	public function _initialize() {
		parent::_initialize();
		$this->shareMdl = M('share');
	}
	
	//分享列表
	public function index(){
		
	}
}