<?php
/**
 * 客户端注册登录接口
 */
namespace Api\Controller;

use Think\Controller;
use Common\Controller\ApibaseController;

class ReglogController extends ApibaseController {
	
	//构造函数
	public function __construct(){
		parent::__construct();
	}
	
	//weChat APP登录接口
    public function weChat() {
		if(IS_POST){
    		$weChatDataArr = I('post.','','trim,addslashes');
			//对键名进行排序然后拼接密钥并组装成字符串
			$weChatDataArr_ = $weChatDataArr;
			unset($weChatDataArr_['sign']);
			ksort($weChatDataArr_);
			$weChatDataStr = http_build_query($weChatDataArr_).'&'.sp_get_option('api_setting')['key'];
			$signType = strtolower(sp_get_option('api_setting')['signType']);
			if($signType == 'sha256'){
				$sign_ = hash('sha256',$weChatDataStr);
			} else {
				$sign_ = md5($weChatDataStr);	
			}
			$sign = $weChatDataArr['sign'];
			//echo $sign_;
			if($sign !== $sign_){
				exit(json_encode(array('status'=>-1,'msg'=>'签名不一致'),JSON_UNESCAPED_UNICODE));
			}
			//根据weChat unionId判断是新注册用户还是登录
			$unionId = $weChatDataArr['unionid'];
			if(empty($unionId)){
				exit(json_encode(array('status'=>-2,'msg'=>'参数有误,缺少unionid'),JSON_UNESCAPED_UNICODE));
			}
			$oauthUserMdl = M('oauthUser');
			$userExist = $oauthUserMdl->where(" `unionid` = '".$unionId."' AND `type`=1 ")->field('id,status')->find();
			//dump($userExist);
			//exit();
			if(empty($userExist)){
				//注册
				$weChatDataArr_['last_login_ip'] = get_client_ip();						//最新登录ip
				$weChatDataArr_['last_login_time'] = date('Y-m-d H:i:s',time());		//最新登录时间
				$weChatDataArr_['create_time'] = $weChatDataArr_['last_login_time'];	//注册时间
				$weChatDataArr_['login_times'] = 1;										//登录次数
				$weChatDataArr_['type'] = 1;											//帐号类型 1 wechat 2 qq 3 mobile注册
				$weChatDataArr_['isApp'] = 1;											//是否APP登录
				$addResult = $oauthUserMdl->add($weChatDataArr_);
				//dump($addResult);
			} else {
				//登录
				if($userExist['status'] == 1){
					//帐号可用
					$updateData['nickname'] = $weChatDataArr_['nickname'];
					$updateData['headimgurl'] = $weChatDataArr_['headimgurl'];
					$updateData['sex'] = $weChatDataArr_['sex'];
					$updateData['last_login_ip'] = get_client_ip();
					$updateData['last_login_time'] = date('Y-m-d H:i:s',time());
					$updateData['login_times'] = ['exp','login_times+1'];
	
					$saveResult = $oauthUserMdl->where(" `id`=".$userExist['id'])->save($updateData);
					if($saveResult){
						//登录成功
						//TODO uid写cookie
						$loginTrack = M('loginTrack');
						$loginData['login_uid'] = $userExist['id'];
						$loginData['login_device'] = $weChatDataArr_['device_id'];
						$loginData['login_cdate'] = $updateData['last_login_time'];
						$loginData['login_platform'] = $weChatDataArr_['device_platform'];
						$loginData['isApp'] = 1;
						
						$loginTrack->add($loginData);
						exit(json_encode(array('status'=>1,'msg'=>'登录成功'),JSON_UNESCAPED_UNICODE));
					} else {
						//登录失败 TODO 写系统异常表
						exit(json_encode(array('status'=>-5,'msg'=>'登录失败'),JSON_UNESCAPED_UNICODE));
					}
				} else {
					//帐号不可用 TODO 写系统异常表
					exit(json_encode(array('status'=>-3,'msg'=>'帐号进入黑名单,请联系客服人员'),JSON_UNESCAPED_UNICODE));
				}
			}
			
		}
    }
    
	//QQ APP登录接口
	public function qq(){
		if(IS_POST){
    		$qqDataArr = I('post.','','trim,addslashes');
			//对键名进行排序然后拼接密钥并组装成字符串
			$qqDataArr_ = $qqDataArr;
			unset($qqDataArr_['sign']);
			ksort($qqDataArr_);
			$qqDataStr = http_build_query($qqDataArr_).'&'.sp_get_option('api_setting')['key'];
			$signType = strtolower(sp_get_option('api_setting')['signType']);
			if($signType == 'sha256'){
				$sign_ = hash('sha256',$qqDataStr);
			} else {
				$sign_ = md5($qqDataStr);	
			}
			$sign = $qqDataArr['sign'];
			//echo $sign_;
			if($sign !== $sign_){
				exit(json_encode(array('status'=>-1,'msg'=>'签名不一致'),JSON_UNESCAPED_UNICODE));
			}
			//根据qq unionId判断是新注册用户还是登录
			$unionId = $qqDataArr['unionid'];
			if(empty($unionId)){
				exit(json_encode(array('status'=>-2,'msg'=>'参数有误,缺少unionid'),JSON_UNESCAPED_UNICODE));
			}
			$oauthUserMdl = M('oauthUser');
			$userExist = $oauthUserMdl->where(" `unionid` = '".$unionId."' AND `type`=2 ")->field('id,status')->find();
			//dump($userExist);
			//exit();
			if(empty($userExist)){
				//注册
				$qqDataArr_['last_login_ip'] = get_client_ip();						//最新登录ip
				$qqDataArr_['last_login_time'] = date('Y-m-d H:i:s',time());		//最新登录时间
				$qqDataArr_['create_time'] = $qqDataArr_['last_login_time'];		//注册时间
				$qqDataArr_['login_times'] = 1;										//登录次数
				$qqDataArr_['type'] = 2;											//帐号类型 1 wechat 2 qq 3 mobile注册
				
				//写登录轨迹表
				
				$addResult = $oauthUserMdl->add($qqDataArr_);
				//dump($addResult);
			} else {
				//登录
				if($userExist['status'] == 1){
					//帐号可用
					$updateData['nickname'] = $qqDataArr_['nickname'];
					$updateData['headimgurl'] = $qqDataArr_['headimgurl'];
					$updateData['sex'] = $qqDataArr_['sex'];
					$updateData['last_login_ip'] = get_client_ip();
					$updateData['last_login_time'] = date('Y-m-d H:i:s',time());
					$updateData['login_times'] = ['exp','login_times+1'];
	
					$saveResult = $oauthUserMdl->where(" `id`=".$userExist['id'])->save($updateData);
					if($saveResult){
						//登录成功
						//TODO uid写cookie
						$loginTrack = M('loginTrack');
						$loginData['login_uid'] = $userExist['id'];
						$loginData['login_device'] = $qqDataArr_['device_id'];
						$loginData['login_cdate'] = $updateData['last_login_time'];
						$loginData['login_platform'] = $qqDataArr_['device_platform'];
						$loginData['isApp'] = 1;
						
						$loginTrack->add($loginData);
						exit(json_encode(array('status'=>1,'msg'=>'登录成功'),JSON_UNESCAPED_UNICODE));
					} else {
						//登录失败 TODO 写系统异常表
						exit(json_encode(array('status'=>-5,'msg'=>'登录失败'),JSON_UNESCAPED_UNICODE));
					}
				} else {
					//帐号不可用 TODO 写系统异常表
					exit(json_encode(array('status'=>-3,'msg'=>'帐号进入黑名单,请联系客服人员'),JSON_UNESCAPED_UNICODE));
				}
			}
			
		}
	}
	
	//手机 APP登录接口
	public function mobile(){
		if(IS_POST){
			$mobileDataArr = I('post.','','trim,addslashes');
			if(empty($mobileDataArr['mobile']) && empty($mobileDataArr['verify']) && empty($mobileDataArr['device_id'])){
				
			}
		}
	}
	
	//获取手机验证码
	public function getMobileVerifyCode(){
		
	}
	
	//微信公众号登录
	public function weChatPublic(){
		
	}
}

