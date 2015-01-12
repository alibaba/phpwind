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
     * 获得版块的收藏贴子总数
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

    /**
     * 获得版块的收藏贴子 
     *
     * @param mixed $uid 
     * @param mixed $fids 
     * @access public
     * @return void
     */
    public function getCollectByUidAndFids($uid, $fids, $limit, $offset){
        $sql = $this->_bindSql('SELECT collect_id,tid FROM %s WHERE created_userid=? AND fid IN %s ORDER BY collect_id DESC %s', $this->getTable(), $this->sqlImplode($fids), $this->sqlLimit($limit, $offset) );
        $smt = $this->getConnection()->createStatement($sql);
        return $smt->queryAll(array($uid)); 
    }


}
