<?php
/**
 * 账号登录安全
 *
 * @fileName: AccountSecurityController.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-17 14:38:18
 * @desc: 
 **/
Wind::import('ADMIN:library.AdminBaseController');

class AccountSecurityController extends AdminBaseController {

	/* (non-PHPdoc)
	 * @see WindController::run()
	 */
	public function run() {
        $config = Wekit::C()->getValues('site');
        $this->setOutput($config, 'config'); 
    }

    /**
     * 显示设置布局 
     * @access public
     * @return void
     */
    public function sessionkeyAction(){

    }

    /**
     * 保存sessionkey；用于客户端加密使用 
     * @access public
     * @return json
     * @example
     *     json{"a":123} 
     */
    public function doSessionKeyAction(){
        $config = new PwConfigSet('site');
        $config->set('securityKey', $this->getInput('securityKey', 'post'))->flush();
        $this->showMessage('ADMIN:success');
    }

}
