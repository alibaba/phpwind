<?php
defined('WEKIT_VERSION') || exit('Forbidden');

Wekit::loadDao('like.dao.PwLikeContentDao');

class PwNativeLikeContentDao extends PwLikeContentDao {
    
        public function getLikeidByFromid($fromid,$typeid=1){
            if(!$fromid) return array();
            $sql = $this->_bindTable("SELECT likeid FROM %s WHERE `typeid`={$typeid} AND `fromid`={$fromid}");
            $smt = $this->getConnection()->query($sql);
            return $smt->fetch();
        }
	
        
}