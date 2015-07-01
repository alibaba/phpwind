<?php
defined('WEKIT_VERSION') || exit('Forbidden');

Wekit::loadDao('forum.dao.PwThreadsBuyDao');

class PwNativeThreadsBuyDao extends PwThreadsBuyDao {
	
        
        /**
         * 获取用户购买帖子记录
         */
        public function getBuyRecord($tid,$uid){
            if(!$tid || !$uid) return array();
            $sql = $this->_bindTable("SELECT tid FROM %s WHERE `tid`={$tid} AND `created_userid`={$uid}");
            $smt = $this->getConnection()->query($sql);
            return $smt->fetch();
        }
	
}