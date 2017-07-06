<?php
/**
 * 后台首页
 */
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class IndexController extends AdminbaseController {
	
	public function _initialize() {
	    empty($_GET['upw'])?"":session("__SP_UPW__",$_GET['upw']);//设置后台登录加密码	    
		parent::_initialize();
		$this->initMenu();
	}
	
    /**
     * 后台首页
     */
    public function index() {
		header('content-type:text/html;charset="utf-8"');
        $this->load_menu_lang();
    	//$menus = D("Common/Menu")->menu_json();
		//dump($menus);
		//exit();
		$menuMdl = M('Menu');
		$menus = $menuMdl->field("id,path,name as title,icon,app,model,action")->where("status=1")->order("path ASC")->select();
		//echo $menuMdl->getLastSQL();
		//dump($menus);
		//exit();
		$_tree_arr = array();
		foreach($menus as $menu){
			$path = explode('-',$menu['path']);
			$menu['href'] = $menu['app'].'/'.$menu['model'].'/'.$menu['action'];
			//dump($path);
			$_tree_arr = $this->addLeaf($_tree_arr,$path,$menu);
		}
		if($_GET['debug'] == 1){
			dump($_tree_arr);
			exit();
		}
		$_tree_arr = $this->_array_values($_tree_arr);		//重新索引
		
		$_tree_arr = $this->_array_filter($_tree_arr);		//去除被禁用的节点
		
		$_tree_json = json_encode($_tree_arr,JSON_UNESCAPED_UNICODE);
		$this->assign("_tree_json", $_tree_json);
       	$this->display();
    }
	
	//增加一个叶子节点
	private function addLeaf($_menus,$path,$data){
		$str = '';
		foreach ($path as $k=>$v){
			if($k < (count($path) - 1)){
				$str .= '['.$v.']["children"]';
			} else {
				$str .= '['.$v.']';
			}
		}
		eval("\$_menus$str = \$data;");
		return $_menus;
	}
	
	//去除数组的原索引 重新加入顺序索引
	private function _array_values($arr = array()){
		if(empty($arr)){
			return;	
		}
		$arr = array_values($arr);
		if(count($arr) > 0){
			foreach($arr as &$v){
				if(is_array($v['children'])){
					$v['children'] = $this->_array_values($v['children']);
				}
			}
		}
		return $arr;
	}
	
	//清除数组树中没有title的节点
	private function _array_filter($arr = array()){
		if(empty($arr)){
			return;
		}
		foreach($arr as $k=>&$v){
		    if(!isset($v['title'])){
		        unset($arr[$k]);
		    } 
			if(is_array($v['children'])){
				$v['children'] = $this->_array_filter($v['children']);
			}
		}
		return $arr;
	}
	
	/**
	 * 后台框架主页
	 */
	public function main(){
		$mysql= M()->query("select VERSION() as version");
    	$mysql=$mysql[0]['version'];
    	$mysql=empty($mysql)?L('UNKNOWN'):$mysql;
    	
    	//server infomaions
    	$info = array(
    			L('OPERATING_SYSTEM') => PHP_OS,
    			L('OPERATING_ENVIRONMENT') => $_SERVER["SERVER_SOFTWARE"],
    	        L('PHP_VERSION') => PHP_VERSION,
    			L('PHP_RUN_MODE') => php_sapi_name(),
				L('PHP_VERSION') => phpversion(),
    			L('MYSQL_VERSION') =>$mysql,
    			L('UPLOAD_MAX_FILESIZE') => ini_get('upload_max_filesize'),
    			L('MAX_EXECUTION_TIME') => ini_get('max_execution_time') . "s",
    			L('DISK_FREE_SPACE') => round((@disk_free_space(".") / (1024 * 1024)), 2) . 'M',
    	);
    	$this->assign('server_info', $info);
    	$this->display();
	}
    
    private function load_menu_lang(){
        $default_lang=C('DEFAULT_LANG');
        
        $langSet=C('ADMIN_LANG_SWITCH_ON',null,false)?LANG_SET:$default_lang;
        
	    $apps=sp_scan_dir(SPAPP."*",GLOB_ONLYDIR);
	    $error_menus=array();
	    foreach ($apps as $app){
	        if(is_dir(SPAPP.$app)){
	            if($default_lang!=$langSet){
	                $admin_menu_lang_file=SPAPP.$app."/Lang/".$langSet."/admin_menu.php";
	            }else{
	                $admin_menu_lang_file=SITE_PATH."data/lang/$app/Lang/".$langSet."/admin_menu.php";
	                if(!file_exists_case($admin_menu_lang_file)){
	                    $admin_menu_lang_file=SPAPP.$app."/Lang/".$langSet."/admin_menu.php";
	                }
	            }
	            
	            if(is_file($admin_menu_lang_file)){
	                $lang=include $admin_menu_lang_file;
	                L($lang);
	            }
	        }
	    }
    }
	
	//
	public function menuPath(){
		$menuMdl = M('Menu');
		//$menus = $menuMdl->query("SELECT id,parentid FROM cmf_menu ORDER BY id ASC");
		$menus =  $menuMdl->field('id,parentid')->order('id ASC')->select();
		//dump($menus);
		//exit();
		foreach($menus as $menu){
			if($menu['parentid'] == 0){
				$emptyMdl->execute("UPDATE starcms_menu SET path='".$menu['id']."' WHERE id=".$menu['id']);	
			} else {
				$parentid = $emptyMdl->query("SELECT path FROM starcms_menu WHERE id=".$menu['parentid']);
				$emptyMdl->execute("UPDATE starcms_menu SET path='".$parentid[0]['path'].'-'.$menu['id']."' WHERE id=".$menu['id']);
			}
		}
	}

}

