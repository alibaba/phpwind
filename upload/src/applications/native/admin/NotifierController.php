<?php

/**
 * 消息通知器的一些设置
 *
 * @fileName: NotifierController.php
 * @author: Shang Yuanchun <yuanchun.syc@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2015-04-09 15:45:20
 * @desc: 
 *
 */
Wind::import('ADMIN:library.AdminBaseController');

class NotifierController extends AdminBaseController {

    /**
     * (non-PHPdoc)
	 * @see WindController::run()
	 */
    public function run()
    {
        $config = Wekit::C()->getValues('notifier');
        $this->setOutput($config, 'config'); 
    }
    
    /**
     *
     * @access public
     * @return 
     * @example
     * 
     */
    public function dosetAction()
    {
        $nickname = '';
        $avatar   = '';

        $config = new PwConfigSet('notifier');
        $config
            ->set('nickname', $nickname)
            ->set('avatar', $avatar)
            ->flush();

        $this->showMessage('ADMIN:success','native/notifier/run/',true);
    }
}
