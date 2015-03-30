<?php
defined('WEKIT_VERSION') || exit('Forbidden');


/**
 * 移动端回帖位置扩展表
 *
 * @fileName: PwPostsPlaceDao.php
 * @author: yuliang<yuliang.lyl@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-16 19:08:17
 * @desc: 
 * */


class PwPostsPlaceDao extends PwBaseDao {
	
	protected $_table = 'bbs_posts_place';
	protected $_pk = 'pid';
	protected $_dataStruct = array('pid','created_address','area_code');
	
        /**
         * 新增一条数据
         */
        public function insertValue($data){
            $pid = isset($data['pid']) ? intval($data['pid']) : 0;
            $created_address = isset($data['created_address']) ? $data['created_address'] : '';
            $area_code = isset($data['area_code']) ? $data['area_code'] : '';
            if(!$pid) return 0;
            $sql = $this->_bindSql('INSERT INTO %s (`pid`,`created_address`,`area_code`) VALUES (%s,"%s","%s")', $this->getTable(),$pid,$created_address,$area_code);
            return $this->getConnection()->execute($sql);
        }
        
        /**
         * 根据回帖的pids批量获取数据
         */
        public function fetchByPids($pids){
            $pids_str = '';
            is_array($pids) && $pids_str = implode(',', $pids);
            if(!$pids_str) return array();
            $sql = $this->_bindSql('SELECT * FROM `%s` WHERE `pid` IN (%s);', $this->getTable(),$pids_str);
            $smt = $this->getConnection()->query($sql);//WindResultSet
            return $smt->fetchAll('pid');
        }
        
        /**
         * 根据帖子的pid获取单条数据
         */
        public function getByPid($pid){
            if(!$pid) return array();
            $sql = $this->_bindSql('SELECT * FROM `%s` WHERE `pid` IN (%s);', $this->getTable(),intval($pid));
            $smt = $this->getConnection()->query($sql);//WindResultSet
            return $smt->fetch();
        }
        
        /**
         * 根据用户id获取回帖时的位置信息
         */
        public function getCityByUid($uid){
            if(!$uid) return "";
            $dao = $GLOBALS['acloud_object_dao'];//ACloudVerCoreDao
            $prefix = $dao->getDB()->getTablePrefix();          
            $sql = "SELECT p.`created_address` 
                    FROM `%s` p 
                    LEFT JOIN `{$prefix}bbs_posts` t 
                    ON p.`pid`=t.`pid` 
                    WHERE t.`created_userid`=%s AND p.`created_address`!='' 
                    ORDER BY t.`created_time` DESC 
                    LIMIT 1;";
            $sql = $this->_bindSql($sql, $this->getTable(),$uid);
            $smt = $this->getConnection()->query($sql);
            $res = $smt->fetch();
            return $res['created_address'];
        }
        
}