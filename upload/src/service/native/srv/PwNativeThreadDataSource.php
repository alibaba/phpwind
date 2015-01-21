<?php
/**
 * 获取我的帖子-我的主题列表-移动端版块
 *
 * @fileName: PwThreadService.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2015-01-07 19:47:34
 * @desc: 
 **/

defined('WEKIT_VERSION') || exit('Forbidden');

Wind::import('SRV:forum.srv.threadList.PwThreadDataSource');

class PwNativeThreadDataSource extends PwThreadDataSource {
	private $uid = 0;
    private $fids = 0;
    /**
     * 根据 space|my 区分取数据类型
     */
    private $type = 'space';
    
    /**
	 * 构造函数
	 *
	 * @param int $spaceid
	 * @param int $loginUid
	 */
	public function __construct($uid, $fids, $type='space') {
		$this->uid = $uid;
        $this->fids= $fids;
        $this->type = $type;
	}
	
	/* (non-PHPdoc)
	 * @see PwThreadDataSource::getTotal()
	 */
    public function getTotal() {
        if( $this->type=='my' ){
            return $this->_getThreadDao()->countDisabledThreadByUidAndFids($this->uid, $this->fids);
        }
        return $this->_getThreadDao()->countThreadByUidAndFids($this->uid, $this->fids);
	}
	
	/* (non-PHPdoc)
	 * @see PwThreadDataSource::getData()
	 */
    public function getData($limit, $offset) {
        if( $this->type=='my' ){
            return $this->_getThreadDao()->getDisabledThreadByUidAndFids($this->uid, $this->fids, $limit, $offset);
        }
        return $this->_getThreadDao()->getThreadByUidAndFids($this->uid, $this->fids, $limit, $offset);
	}
	
    protected function _getThreadDao() {
        return Wekit::loadDao('native.dao.PwNativeThreadExpandDao');
    } 
}
