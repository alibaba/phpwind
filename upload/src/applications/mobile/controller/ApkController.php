<?php
/**
 * apk生成支持相关接口
 *
 * @fileName: ApkController.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-15 19:08:17
 * @desc: 
 **/
defined('WEKIT_VERSION') || exit('Forbidden');

class ApkController extends PwBaseController {

	public function beforeAction($handlerAdapter) {
		parent::beforeAction($handlerAdapter);
//		if (!$this->loginUser->isExists()) $this->showError('VOTE:user.not.login');
	}
	
	public function run() {


    }

    /**
     * 验证api vesion && session key 
     * @access public
     * @return void
     */
    public function doValidateAction(){

    }

}
