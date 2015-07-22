<?php
defined('WEKIT_VERSION') || exit('Forbidden');

Wekit::loadDao('attention.dao.PwAttentionDao');

class PwNativeAttentionDao extends PwAttentionDao {
    
    /* 根据uid获取关注的用户数 */
    public function getAttentionCount($uid){
        if(!$uid) return 0;
        $sql = $this->_bindTable("SELECT count(*) cnt FROM %s WHERE `uid`={$uid}");
        $smt = $this->getConnection()->query($sql);
        $res = $smt->fetch();

        return isset($res['cnt']) ? intval($res['cnt']) : 0;
    }
    
    /* 根据uid获取用户的关注列表 */
    public function fetchAttentionByUid($uid,$pos=0,$num=20){
        if(!$uid) return array();
        $sql = $this->_bindTable("SELECT touid FROM %s WHERE `uid`={$uid} LIMIT {$pos},{$num}");
        $smt = $this->getConnection()->query($sql);
        $res = $smt->fetchAll();
        $uids = array();
        foreach($res as $v){
            $uids[] = $v['touid'];
        }

        return $uids;
    }
    
    /* 根据uid获取粉丝数 */
    public function getFansCount($uid){
        if(!$uid) return 0;
        $sql = $this->_bindTable("SELECT count(*) cnt FROM %s WHERE `touid`={$uid}");
        $smt = $this->getConnection()->query($sql);
        $res = $smt->fetch();

        return isset($res['cnt']) ? intval($res['cnt']) : 0;
    }
    
    
    /* 根据uid获取粉丝列表 */
    public function fetchFansByUid($uid,$pos=0,$num=20){
        if(!$uid) return array();
        $sql = $this->_bindTable("SELECT uid FROM %s WHERE `touid`={$uid} LIMIT {$pos},{$num}");
        $smt = $this->getConnection()->query($sql);
        $res = $smt->fetchAll();
        $uids = array();
        foreach($res as $v){
            $uids[] = $v['uid'];
        }

        return $uids;
    }
    
}