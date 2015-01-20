<?php
defined('WEKIT_VERSION') || exit('Forbidden');


/**
 * 移动端发帖扩展表
 *
 * @fileName: PwThreadsPlaceDao.php
 * @author: yuliang<yuliang.lyl@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-16 19:08:17
 * @desc: 
 * */


class PwThreadsPlaceDao extends PwBaseDao {
	
	protected $_table = 'bbs_threads_place';
	protected $_pk = 'tid';
	protected $_dataStruct = array('tid','from_type','created_address','area_code');
	
        /**
         * 新增一条数据
         */
        public function insertValue($data){
            $tid = isset($data['tid']) ? intval($data['tid']) : 0;
            $from_type = isset($data['from_type']) ? intval($data['from_type']) : 0;
            $created_address = isset($data['created_address']) ? $data['created_address'] : '';
            $area_code = isset($data['area_code']) ? $data['area_code'] : '';
            if(!$tid) return 0;
            $sql = $this->_bindSql('INSERT INTO %s (`tid`,`from_type`,`created_address`,`area_code`) VALUES (%s,%s,"%s","%s")', $this->getTable(),$tid,$from_type,$created_address,$area_code);
            return $this->getConnection()->execute($sql);
        }
        
        /**
         * 根据帖子的tids批量获取数据
         */
        public function fetchByTids($tids){
            $tids_str = '';
            is_array($tids) && $tids_str = implode(',', $tids);
            if(!$tids_str) return array();
            $sql = $this->_bindSql('SELECT * FROM `%s` WHERE `tid` IN (%s);', $this->getTable(),$tids_str);
            $smt = $this->getConnection()->query($sql);//WindResultSet
            return $smt->fetchAll('tid');
        }
        
        /**
         * 根据帖子的tid获取单条数据
         */
        public function getByTid($tid){
            if(!$tid) return array();
            $sql = $this->_bindSql('SELECT * FROM `%s` WHERE `tid` IN (%s);', $this->getTable(),intval($tid));
            $smt = $this->getConnection()->query($sql);//WindResultSet
            return $smt->fetch();
        }
        
}