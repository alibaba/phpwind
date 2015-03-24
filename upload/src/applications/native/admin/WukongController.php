<?php
/**
 * 悟空通信秘钥注册
 *
 * @fileName: WukongController.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-17 14:38:18
 * @desc: 
 **/
Wind::import('ADMIN:library.AdminBaseController');

class WukongController extends AdminBaseController {

	/* (non-PHPdoc)
	 * @see WindController::run()
	 */
	public function run() {
        $config = Wekit::C()->getValues('site');
        $this->setOutput($config, 'config'); 
    }
    
    /**
     * App聊天系统通讯秘钥一键注册
     * @access public
     * @return 
     * @example
     * 
     */
    public function doWukongRegistAction(){
        $_securityKey = Wekit::C()->getConfigByName('site', 'securityKey');
        $res = Wekit::load("APPS:native.service.PwLaiWangSerivce")->saveAppekySetting($_securityKey['value']);
        $this->showMessage('ADMIN:success','native/wukong/run/',true);
    }
    
}
