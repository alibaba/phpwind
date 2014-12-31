<?php
/**
 * 阿里妈妈，广告设置
 *
 * @fileName: AdController.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-31 16:36:23
 * @desc: 
 **/

Wind::import('ADMIN:library.AdminBaseController');

class AdController extends AdminBaseController {

	/* (non-PHPdoc)
	 * @see WindController::run()
	 */
	public function run() {
        $config = Wekit::C()->getValues('native');
        $this->setOutput($config, 'config');
    }


    public function addAction(){
        list($status,$code) = $this->getInput(array('ad_status','ad_code'));
        $config = new PwConfigSet('native');
        $config
            ->set('ad.status', $status)
            ->set('ad.code', $code)
            ->flush();
        $this->showMessage('ADMIN:success');
    }


}
