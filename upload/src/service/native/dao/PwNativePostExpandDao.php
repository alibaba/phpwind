<?php
/**
 * 查询统计移动端用户回复的贴子dao
 *
 * @fileName: PwPostExpandDao.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2015-01-07 20:38:38
 * @desc: 
 **/

Wind::import('SRV:forum.dao.PwPostExpandDao');

class PwNativePostExpandDao extends PwPostExpandDao {
    
    /**
     * 根据uid&fids统计审核和未审核回复过的帖子
     *
     * @param mixed $uid 
     * @param array $fids 
     * @access public
     * @return void
     */
	public function countDisabledPostByUidAndFids($uid, $fids) {
        $sql = $this->_bindTable('SELECT COUNT(*) as sum FROM %s WHERE created_userid=? AND disabled <2 AND fid IN '. $this->sqlImplode($fids) );
        $smt = $this->getConnection()->createStatement($sql);
		return $smt->getValue(array($uid));
	}

    /**
     * 根据uid&fids统计审核和未审核回复过的帖子
     * 
     * @param mixed $uid 
     * @param mixed $fids 
     * @param mixed $limit 
     * @param mixed $offset 
     * @access public
     * @return void
     */
	public function getDisabledPostByUid($uid, $fids, $limit, $offset) {
		$sql = $this->_bindSql('SELECT * FROM %s WHERE created_userid=? AND disabled<2 AND fid IN %s ORDER BY created_time DESC %s', $this->getTable(), $this->sqlImplode($fids), $this->sqlLimit($limit, $offset));
		$smt = $this->getConnection()->createStatement($sql);
		return $smt->queryAll(array($uid));
	}
}
