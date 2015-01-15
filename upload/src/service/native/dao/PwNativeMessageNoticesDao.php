<?php

Wekit::loadDao('message.dao.PwMessageNoticesDao');

class PwNativeMessageNoticesDao extends PwMessageNoticesDao {
	
        /**
         * 根据typeids获取用户消息列表,按照未读优先排序
         */
        public function getNoticesByTypeIds($uid,$typeids,$offset = 0,$num = 20,$exclude=false){
            $typeids = implode(',', $typeids);
            $condition = $exclude ? 'NOT IN' : 'IN';
            $sql = $this->_bindTable("SELECT * FROM %s WHERE uid=? AND `typeid` $condition ($typeids) ORDER BY is_read ASC,modified_time DESC LIMIT ?,?");
//            var_dump($sql,$uid,$typeids,$offset,$num);exit;
            $smt = $this->getConnection()->createStatement($sql);
            return $smt->queryAll(array($uid,$offset,$num));
        }
        
        /**
         * 根据typeids获取用户未读消息数
         */
        public function getUnreadCountByTypeIds($uid,$typeids,$exclude=false){
            $typeids = implode(',', $typeids);
            $condition = $exclude ? 'NOT IN' : 'IN';
            $sql = $this->_bindTable("SELECT COUNT(*) FROM %s WHERE uid=? AND `is_read`=0 AND `typeid` $condition ($typeids)");
//            var_dump($sql,$uid,$typeids);
            $smt = $this->getConnection()->createStatement($sql);
            return $smt->getValue(array($uid));
        }
}