<?php
/**
 * @fileName: PwNativeThreadDisplay.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2015-01-23 14:32:18
 * @desc: 
 **/
defined('WEKIT_VERSION') || exit('Forbidden');

Wind::import('SRV:forum.srv.PwThreadDisplay');

class PwNativeThreadDisplay extends PwThreadDisplay {

    public function __construct($tid, PwUserBo $user) {
		parent::__construct($tid, $user);
    }

    protected function _bulidContent($read) {
        return self::escapeSpace($read['content']);
/*
		if (!$read['useubb']) {
			return self::escapeSpace($read['content']);
		}
        $ubb = new PwUbbCodeConvertThread($this->thread, $read, $this->user);
        $ubb->isConvertPost = false;
        $ubb->isconverthide = false;
        $ubb->isconvertsell = false;
        $ubb->isconverttao  = false;


		$ubb->setImgLazy($this->imgLazy);
		$this->attach && $this->attach->has($read['pid']) && $ubb->setAttachParser($this->attach);
		$read['reminds'] && $ubb->setRemindUser($read['reminds']);
        return PwUbbCode::convert($read['content'], $ubb);
 */
    }

}
