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
	}

    /**
     * 检查客户端与server通信加密用的key是否正确
     * 
     * @access public
     * @return void
     * @example
     * <pre>
     * post: securityKey
     * </pre>
     */
    public function checkSecurityKeyAction(){
        $securityKey = $this->getInput('securityKey','post');
        $config = Wekit::C()->getConfigByName('site','securityKey');
        if( $securityKey==$config['value'] ){
            $this->setOutput(true, 'data');
            $this->showMessage("NATIVE:app.check.securityKey.success");
        }
        $this->showError("NATIVE:app.check.securityKey.failed");
    }

}
