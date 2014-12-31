<?php
/**
 * 移动版基本信息
 *
 * @fileName: IndexController.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-31 16:36:23
 * @desc: 
 **/

Wind::import('ADMIN:library.AdminBaseController');

class IndexController extends AdminBaseController {

	/* (non-PHPdoc)
	 * @see WindController::run()
	 */
	public function run() {
		$db = Wind::getComponent('db');
		$sysinfo = array(
            'wind_version' => 'phpwind v' . NEXT_VERSION . ' ' . NEXT_RELEASE,
            'native_version' => 'native api v' . NATIVE_VERSION,
			'php_version' => PHP_VERSION, 
			'server_software' => str_replace('PHP/' . PHP_VERSION, '', 
				$this->getRequest()->getServer('SERVER_SOFTWARE')), 
            'mysql_version' => $db->getDbHandle()->getAttribute(PDO::ATTR_SERVER_VERSION), 
        );
		$this->setOutput($sysinfo, 'sysinfo');

    }

}
