<?php
/**
 * 帖子dao服务
 *
 * @fileName: PwNativeThreadExpandDao.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2015-01-06 18:03:39
 * @desc: 
 **/
    
Wind::import('SRV:forum.dao.PwThreadExpandDao');

class PwNativeThreadExpandDao extends PwThreadExpandDao {
     
   	/**
	 * 根据uid&fids统计审核过的帖子
	 *
     * @param int $uid
     * @param array $fids
	 * @return int
	 */
    public function countThreadByUidAndFids($uid, $fids) {
        $sql = $this->_bindTable('SELECT COUNT(*) AS sum FROM %s WHERE created_userid=? AND fid IN '. $this->sqlImplode($fids) .' AND disabled = 0');
		$smt = $this->getConnection()->createStatement($sql);
		return $smt->getValue(array($uid));
	}
	
	/**
	 * 根据uid&fids获取审核过的帖子
	 *
	 * @param int $uid
	 * @param array $fids
	 * @param int $limit
	 * @param int $offset
	 * @return int
	 */
    public function getThreadByUidAndFids($uid, $fids, $limit, $offset) {
		$sql = $this->_bindSql('SELECT * FROM %s WHERE created_userid=? AND fid IN %s AND disabled = 0 ORDER BY created_time DESC %s', $this->getTable(), $this->sqlImplode($fids), $this->sqlLimit($limit, $offset));
		$smt = $this->getConnection()->createStatement($sql);
		return $smt->queryAll(array($uid), 'tid');
	}
   
   	/**
	 * 根据uid&fids统计审核和未审核的帖子
	 *
     * @param int $uid
     * @param array $fids
	 * @return int
	 */
    public function countDisabledThreadByUidAndFids($uid, $fids) {
        $sql = $this->_bindTable('SELECT COUNT(*) AS sum FROM %s WHERE created_userid=? AND fid IN '. $this->sqlImplode($fids) .' AND disabled < 2');
		$smt = $this->getConnection()->createStatement($sql);
		return $smt->getValue(array($uid));
	}
	
	/**
	 * 根据uid&fids获取审核和未审核的帖子
	 *
	 * @param int $uid
	 * @param array $fids
	 * @param int $limit
	 * @param int $offset
	 * @return int
	 */
	public function getDisabledThreadByUidAndFids($uid, $fids, $limit, $offset) {
		$sql = $this->_bindSql('SELECT * FROM %s WHERE created_userid=? AND fid IN %s AND disabled < 2 ORDER BY created_time DESC %s', $this->getTable(), $this->sqlImplode($fids), $this->sqlLimit($limit, $offset));
		$smt = $this->getConnection()->createStatement($sql);
		return $smt->queryAll(array($uid), 'tid');
	}
}
