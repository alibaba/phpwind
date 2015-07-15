<?php
defined('WEKIT_VERSION') || exit('Forbidden');

Wekit::loadDao('like.dao.PwLikeLogDao');

class PwNativeLikeLogDao extends PwLikeLogDao {
    
        public function fetchUidsByLikeid($likeid,$start=0,$count=5){
            if(!$likeid) return array();
            $limit = $count ? " LIMIT {$start},{$count}" : "";
            $sql = $this->_bindTable("SELECT uid FROM %s WHERE `likeid`={$likeid} ORDER BY `created_time` DESC{$limit}");
            $smt = $this->getConnection()->query($sql);
            $res = $smt->fetchAll();
            $uids = array();
            foreach($res as $v){
                $uids[] = $v['uid'];
            }
            
            return $uids;
        }
        
        
        public function getLikeCount($likeid){
            if(!$likeid) return 0;
            $sql = $this->_bindTable("SELECT count(*) cnt FROM %s WHERE `likeid`={$likeid}");
            $smt = $this->getConnection()->query($sql);
            $res = $smt->fetch();
            
            return isset($res['cnt']) ? intval($res['cnt']) : 0;
        }
	
        
}