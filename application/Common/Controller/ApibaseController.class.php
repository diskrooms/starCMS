<?php

/**
 * Api模块的基类
 */
namespace Common\Controller;
use Think\Controller;

class ApibaseController extends Controller {
	private $ua;
	private $isIos;
	private $isAndroid;
	private $isWeChat;
	private $isQQ;
	private $version;
	
	//基类构造函数
	//获取客户端UA对环境进行检测
	public function __construct() {
		$ua = strtolower(addslashes(trim($_SERVER['HTTP_USER_AGENT'])));
		$this->ua = $ua;
		if(strpos($ua,'iphone') || strpos($ua,'ipad')){
			$this->isIos = true;
		} else {
			$this->isIos = false;
		}
		if(strpos($ua,'android')){
			$this->isAndroid = true;
		} else {
			$this->isAndroid = false;
		}
		if(strpos($ua,'micromessenger')){
			$this->isWeChat = true;
		} else {
			$this->isWeChat = false;
		}
		if(strpos($ua,'qq')){
			$this->isQQ = true;
		} else {
			$this->isQQ = false;
		}
	}
	
	//返回IOS设备环境结果
	public function isIos(){
		return $this->isIos;
	}
	
	//返回Android设备环境结果
	public function isAndroid(){
		return $this->isAndroid;
	}
	
	//返回weChat环境结果
	public function isWeChat(){
		return $this->isWeChat;
	}
	
	//返回QQ结果
	public function isQQ(){
		return $this->isQQ;
	}
	
	//返回客户端UA
	public function ua(){
		return $this->ua;
	}
	
	//TODO 客户端版本号由用户扩展
	public function version(){
		return;
	}
	
	//TODO 获取客户端推广渠道ID
	public function channel(){
		return;
	}
}