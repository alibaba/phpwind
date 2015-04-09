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

    const USERTYPE_NAME = 1;
    const USERTYPE_ID   = 2;

    /**
     * (non-PHPdoc)
	 * @see WindController::run()
	 */
    public function run()
    {
        $config = Wekit::C()->getValues('notifier');
        if (empty($config)) {
            $config['usertype'] = self::USERTYPE_NAME;
            $config['userid']   = 1;
            $config['username'] = 'admin';
            $config['avatar']   = '';
            $config['nickname'] = '小助手';
        }
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
        $usertype = '';

        $username = '';
        $userid   = '';

        $config = new PwConfigSet('notifier');
        $config
            ->set('nickname', $nickname)
            ->set('avatar', $avatar)
            ->flush();

        $this->showMessage('ADMIN:success','native/notifier/run/',true);
    }
}
