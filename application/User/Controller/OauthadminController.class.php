<?php
/**
 * 参    数：
 * 作    者：lht
 * 功    能：OAth2.0协议下第三方登录数据报表
 * 修改日期：2013-12-13
 */
namespace User\Controller;

use Common\Controller\AdminbaseController;

class OauthadminController extends AdminbaseController {
	
	//用户列表
	public function index(){
		/*$oauth_user_model=M('OauthUser');
		$count=$oauth_user_model->where(array("status"=>1))->count();
		$page = $this->page($count, 20);
		$lists = $oauth_user_model
		->where(array("status"=>1))
		->order("create_time DESC")
		->limit($page->firstRow . ',' . $page->listRows)
		->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign('lists', $lists);*/
		$this->display();
	}
	
	// 后台删除第三方用户绑定
	/*public function delete(){
		$id = I('get.id',0,'intval');
		if(empty($id)){
			$this->error('非法数据！');
		}
		$result = M("OauthUser")->where(array("id"=>$id))->delete();
		if ($result!==false) {
			$this->success("删除成功！", U("oauthadmin/index"));
		} else {
			$this->error('删除失败！');
		}
	}*/
	
	//返送用户信息的后台数据接口
	public function ajaxList(){
		header("Content-type:text/html;charset=utf-8");
		$oauth_user_model=M('OauthUser');
		$count=$oauth_user_model->where(array("status"=>1))->count();
		$page = $this->page($count, 20);
		$lists = $oauth_user_model->where("1=1")->order("create_time DESC")->limit($page->firstRow . ',' . $page->listRows)->select();
		
		$ids = array_column($lists,'id');
		/*$packageStatus = $this->spreadPackage($ids);
		if($packageStatus == -1){
			foreach($lists as &$list){
				$list['spreadPackage'] = -1;
				$list['uid'] = $list['id'];
			}
		} elseif(is_array($packageStatus)) {
			foreach($lists as &$list){
				$list['spreadPackage'] = $packageStatus[$list['id']];
				$list['uid'] = $list['id'];
			}
		}*/
		foreach($lists as &$list){
			$list['uid'] = $list['id'];
		}
		//print_r($lists);
		//exit();
		echo json_encode(array('rel'=>true,'msg'=>'返回成功','list'=>$lists,'count'=>count($lists)),JSON_UNESCAPED_UNICODE);
	}
	
	//用 ftp 检测推广包状态,并进行上传、下载、删除等维护
	public function spreadPackage(){
		$uids = json_decode(I('post.uids','','trim'),true);	//json字符串不能 addslashes 否则无法解析
		//print_r($uids);
		$res = vendor('starFtp.starFtp');
		if($res){
			$ftp = new \starFtp();
			$ftp_config_ = sp_get_option('ftp_setting');
			$down_config_ = sp_get_option('down_setting');
			$site_config_ = sp_get_option('site_setting');
			$config = array(
				'hostname' => $ftp_config_['ftp_host'],
				'username' => $ftp_config_['ftp_user'],
				'password' => $ftp_config_['ftp_pwd'],
				'port' => $ftp_config_['ftp_port'],
				'passive'=> $ftp_config_['ftp_mode']		//true 被动模式  false 主动模式
			);
			//print_r($config);
			$conn_res = $ftp->connect($config);
			//print($conn_res);
			//exit();
			if($conn_res){
				$res = $ftp->filelist($down_config_['down_ftp_folder']);
				
				$uids_ = array();
				foreach($res as &$v){
					$filename = pathinfo($v)['filename'];
					$temp = explode('_',$filename);
					$uids_[] = $temp[1];
				}
				$result = array();
				foreach($uids as $uid){
					if(in_array($uid,$uids_)){
						$result['data'][$uid]['packageStatus'] = 1;
					} else {
						$result['data'][$uid]['packageStatus'] = 0;
					}
					//生成推广包下载地址
					$now = time();
					$key = $site_config_['site_api_key'];
					$sgin_type = $site_config_['site_sign_type'];
					if(strtolower($sign_type) == 'sha256'){
						$sign = hash('sha256',$uid.$now.$key);
					} else {
						$sign = md5($uid.$now.$key);
					}
					
					$result['data'][$uid]['down_url'] = $down_config_['down_url'].'?uid='.$uid.'&time='.$now.'&sign='.$sign;
				}
				$result['down_domain'] = $down_config_['down_domain'];
				$result['down_down_folder'] = $down_config_['down_down_folder'];
				$result['down_prefix'] = $down_config_['down_prefix'] ? $down_config_['down_prefix'].'_' : '';
				
				echo json_encode($result);
			} else {
				echo -1;
			}
			//$ftp->upload('localfile.log','remotefile.log');
		}
	}
	
	//添加虚拟推广用户
	public function addSpreadUser(){
		$spreadChannelName = I('spreadChannelName','','trim,addslashes');
		if(empty($spreadChannelName)){
			exit(json_encode(array('status'=>0,'msg'=>'参数有误'),JSON_UNESCAPED_UNICODE));
		}
		$spreadUserDataArr = array(
			'type'=>10,
			'nickname'=>$spreadChannelName,
			'create_time'=>date('Y-m-d H:i:s',time()),
			'create_ip'=>get_client_ip(),
			'status'=>1,
			'unionid'=>md5(time().mt_rand(10000,99999))
		);
		$oauthUserMdl = M('oauthUser');
		$addResult = $oauthUserMdl->add($spreadUserDataArr);
		if($addResult){
			echo json_encode(array('status'=>1,'msg'=>'添加成功','spreadUserId'=>$addResult),JSON_UNESCAPED_UNICODE);
		} else {
			echo json_encode(array('status'=>-1,'msg'=>'添加失败'),JSON_UNESCAPED_UNICODE);
		}
	}
	
	//删除用户
	public function delUser(){
		$uid = I('uid','','intval');
		if(empty($uid)){
			exit(json_encode(array('status'=>0,'msg'=>'参数有误'),JSON_UNESCAPED_UNICODE));
		}
		$oauthUserMdl = M('oauthUser');
		$result = $oauthUserMdl->where('id='.$uid)->delete();
		if($result > 0){
			echo json_encode(array('status'=>1,'msg'=>'删除成功'),JSON_UNESCAPED_UNICODE);
		} else {
			if($result === 0){
				echo json_encode(array('status'=>0,'msg'=>'没有可删除的记录'),JSON_UNESCAPED_UNICODE);
			} elseif($result === false){
				echo json_encode(array('status'=>-1,'msg'=>'删除出错'),JSON_UNESCAPED_UNICODE);
			} else {
				echo json_encode(array('status'=>-2,'msg'=>'其他错误'),JSON_UNESCAPED_UNICODE);
			}
		}
	}
	
	//返送单条用户记录
	public function userInfo(){
		$uid = I('uid','','intval');
		if(empty($uid)){
			exit(json_encode(array('status'=>-1,'msg'=>'参数有误'),JSON_UNESCAPED_UNICODE));
		}
		$oauthUserMdl = M('oauthUser');
		$userInfo = $oauthUserMdl->where('id='.$uid)->find();
		if(empty($userInfo)){
			echo json_encode(array('status'=>0,'msg'=>'没有找到相关用户'),JSON_UNESCAPED_UNICODE);
		} else {
			echo json_encode(array('status'=>1,'msg'=>'返送成功','data'=>$userInfo),JSON_UNESCAPED_UNICODE);
		}
	}
	
	//编辑用户
}