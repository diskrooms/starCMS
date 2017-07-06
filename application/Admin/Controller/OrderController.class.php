<?php
namespace Admin\Controller;
use Common\Controller\AdminbaseController;

class OrderController extends AdminbaseController{

	protected $orderMdl;
	public function _initialize() {
		parent::_initialize();
		$this->orderMdl = M('order');
	}
	
	//订单列表
	public function index(){
		
	}
}