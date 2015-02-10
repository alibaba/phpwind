<?php

Wekit::loadDao('forum.dao.PwSpecialSortDao');

class PwNativeSpecialSortDao extends PwSpecialSortDao {
		
	public function getSpecialSortByTopId($fid,$extra=1) {
                if(!$fid) return array();
		$sql = $this->_bindTable('SELECT * FROM %s WHERE fid=? AND extra=?');
		$smt = $this->getConnection()->createStatement($sql);
		return $smt->queryAll(array($fid,$extra), 'tid');
	}
	
}