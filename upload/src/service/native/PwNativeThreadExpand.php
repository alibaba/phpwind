<?php
/**
 * 帖子扩展服务,不经常用的接口
 *
 * @fileName: PwNativeThreadExpand.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2015-01-06 18:56:39
 * @desc: 
 **/
defined('WEKIT_VERSION') || exit('Forbidden');

Wind::import('SRV:forum.PwThreadExpand');

class PwNativeThreadExpand extends PwThreadExpand {
	/**
	 * 根据uid统计审核和未审核的帖子
	 *
	 * @param int $uid
	 * @param array $fids 版块id
	 * @return int
	 */
	public function countDisabledThreadByUidAndFids($uid, $fids) {
		$uid = intval($uid);
		if ($uid < 1 || empty($fids)) return array();
		return $this->_getThreadDao()->countDisabledThreadByUidAndFids($uid, $fids);
	}

	/**
	 * 根据uid获取审核和未审核的帖子
	 *
	 * @param int $uid 用户id
	 * @param array $fids 版块id
	 * @param int $limit 个数
	 * @param int $offset 起始偏移量
	 * @param int $fetchmode 帖子资料 <必然为FETCH_*的一种或者组合>
	 * return array
	 */
	public function getDisabledThreadByUidAndFids($uid, $fids, $limit=0, $offset=0) {
		$uid = intval($uid);
		if ($uid < 1 || empty($fids)) return array();
		return $this->_getThreadDao()->getDisabledThreadByUidAndFids($uid, $fids, $limit, $offset);
    }

    protected function _getThreadDao() {
        return Wekit::loadDao('native.dao.PwNativeThreadExpandDao');
    } 

}
