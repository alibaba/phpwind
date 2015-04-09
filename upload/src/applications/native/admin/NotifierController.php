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

    // 也就是 admin 啊啊啊
    const DEFAULT_SENDER_UID = 1;

    public static $defaultNotifier = array(
        'usertype'  => NotifierController::USERTYPE_NAME,
        'userid'    => NotifierController::DEFAULT_SENDER_UID,
        'username'  => 'admin',
        'avatar'    => '',
        'nickname'  => '小助手',
    );

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
            $config = self::$defaultNotifier;
            $config['avatar'] = Pw::getAvatar(self::DEFAULT_SENDER_UID, 'big');
        } else {
            $config['avatar'] = Pw::getPath($config['avatar']);
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
        /*
        $vd = function() {
            foreach(func_get_args() as $arg) {
                error_log(var_export($arg, true));
            }
        };
        */

        $config = new PwConfigSet('notifier');

        /*
         * 如果form的class是J_ajaxForm，可以只按照count($_FILES)来判断；
         * 如果不是，需要判断具体的tmp_name是否为空来判断是否选择了文件。
         *
         */
        if (count($_FILES) && !empty($_FILES['avatar']['tmp_name'])) {
            Wind::import('SRV:upload.action.PwStartUpUpload');
            Wind::import('LIB:upload.PwUpload');
            $bhv = new PwStartUpUpload();
            $bhv->filename = 'avatar';

            $upload = new PwUpload($bhv);
            if ($upload->check() === true) {
                $result = $upload->execute();
            }
            if ($result !== true) {
                $this->showError($result->getError());
            }
            if (!$data = $bhv->getAttachInfo() ) {
                $this->showError('upload.fail');
            }

            // 添加进设置项
            $config->set('avatar', $data['path'].$data['filename']);
        }

        $nickname = $this->getInput('nickname');
        if (empty($nickname)) {
            $nickname = self::$defaultNotifier['nickname'];
        }

        $usertype = intval($this->getInput('usertype'));
        if ($usertype != self::USERTYPE_NAME && $usertype != self::USERTYPE_ID) {
            $usertype = self::USERTYPE_NAME;
        }

        $user = $this->getInput('user');

        if ($usertype == self::USERTYPE_NAME) {
            $userinfo = Wekit::load('user.PwUser')->getUserByName($user, PwUser::FETCH_MAIN);
        } else {
            $userinfo = Wekit::load('user.PwUser')->getUserByUid($user, PwUser::FETCH_MAIN);
        }
        if (empty($userinfo)) {
            $this->showError('NATIVE:user.notfound');
        }

        $config->set('nickname', $nickname)
               ->set('usertype', $usertype)
               ->set('username', $userinfo['username'])
               ->set('userid', $userinfo['uid'])
               ->flush();

        $this->showMessage('ADMIN:success','native/notifier/run/',true);
    }
}
