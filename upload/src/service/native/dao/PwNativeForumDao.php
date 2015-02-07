<?php
defined('WEKIT_VERSION') || exit('Forbidden');

Wekit::loadDao('forum.dao.PwForumDao');

class PwNativeForumDao extends PwForumDao {
	
        /**
         * 获取生活服务版块列表
         */
        public function fetchForumLifeList($pos,$num=30){
            //判断公共账号所属的主分类是否存在
            $configs = Wekit::C()->getValues('native');
            $life_fid = isset($configs['forum.life_fid']) && $configs['forum.life_fid'] ? $configs['forum.life_fid'] : 0;
            if(!$life_fid) return array();
            $sql = $this->_bindTable("SELECT * FROM %s WHERE `parentid` IN ($life_fid) ORDER BY vieworder ASC LIMIT ?,?");
            $smt = $this->getConnection()->createStatement($sql);
            return $smt->queryAll(array($pos,$num),'fid');
        }
        
        /**
         * 获取生活服务版块的最大vieworder
         */
        public function getMaxVieworder(){
            //判断公共账号所属的主分类是否存在
            $configs = Wekit::C()->getValues('native');
            $life_fid = isset($configs['forum.life_fid']) && $configs['forum.life_fid'] ? $configs['forum.life_fid'] : 0;
            if(!$life_fid) return array();
            $sql = $this->_bindTable("SELECT MAX(`vieworder`) vieworder FROM %s WHERE `parentid` IN ($life_fid)");
            $smt = $this->getConnection()->query($sql);
            return $smt->fetch();
        }
	
}