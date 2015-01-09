<?php
/**
 * 贴子收藏
 *
 * @fileName: PwNativeCollectDao.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2015-01-09 17:15:50
 * @desc: 
 **/

Wind::import('SRV:collect.dao.PwCollectDao');

class PwNativeCollectDao extends PwCollectDao {

    /**
     * 获得版块的收藏贴子 
     * 
     * @param int $uid 
     * @param array $fids 
     * @access public
     * @return void
     */
    public function countCollectByUidAndFids($uid, $fids) {
        $sql = $this->_bindTable('SELECT COUNT(*) as sum FROM %s WHERE created_userid=? AND fid IN '. $this->sqlImplode($fids) );
        $smt = $this->getConnection()->createStatement($sql);
		return $smt->getValue(array($uid));
	}

    public function getCollectByUidAndFids($uid, $fids){
        $sql = $this->_bindTable('SELECT collect_id,tid FROM %s WHERE created_userid=? AND fid IN ? ORDER BY collect_id DESC', $this->getTable(), $this->sqlImplode($fids), $this->sqlLimit($limit, $offset) );
        $smt = $this->getConnection()->createStatement($sql);
        return $smt->queryAll(array($uid)); 
    }

}
