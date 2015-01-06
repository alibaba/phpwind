<?php
/**
 * 获取我的帖子-我的主题列表-移动端版块
 *
 * @fileName: PwNativeMyThread.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2015-01-06 18:53:14
 * @desc: 
 **/
defined('WEKIT_VERSION') || exit('Forbidden');

Wind::import('SRV:forum.srv.threadList.PwThreadDataSource');

class PwNativeMyThread extends PwThreadDataSource {
	/**
	 * @var int
	 */
	private $uid = 0;
    /**
     * @var array
     */
    private $fids = 0;
	
	/**
	 * 构造函数
	 *
	 * @param int $spaceid
	 * @param int $loginUid
	 */
	public function __construct($uid,$fids) {
		$this->uid = $uid;
		$this->fids= $fids;
	}
	
	/* (non-PHPdoc)
	 * @see PwThreadDataSource::getTotal()
	 */
	public function getTotal() {
		return $this->_getThreadExpandDs()->countDisabledThreadByUidAndFids($this->uid, $this->fids);
	}
	
	/* (non-PHPdoc)
	 * @see PwThreadDataSource::getData()
	 */
	public function getData($limit, $offset) {
		return $this->_getThreadExpandDs()->getDisabledThreadByUidAndFids($this->uid, $this->fids, $limit, $offset);
	}
	
	/**
	 *
	 * @return PwThreadExpand
	 */
    protected function _getThreadExpandDs() {
		return Wekit::load('native.PwNativeThreadExpand');
	}
}
