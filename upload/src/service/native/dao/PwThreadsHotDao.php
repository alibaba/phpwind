<?php
defined('WEKIT_VERSION') || exit('Forbidden');


/**
 * 热帖设置表dao服务
 *
 * @fileName: PwThreadsHotDao.php
 * @author: yuliang<yuliang.lyl@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-16 19:08:17
 * @desc: 
 * */


class PwThreadsHotDao extends PwBaseDao {
	
	protected $_table = 'bbs_threads_hot';
	protected $_pk = 'id';
	protected $_dataStruct = array('id','tid','srarttime','endtime','createtime','updatetime','created_userid','created_username','isenable');
	
        
        /**
         * 查找设置为热帖的数量
         * lyl
         */
        public function getThreadsHotCount($time){
//            var_dump($this->getConnection());exit;//WindConnection
            $sql = $this->_bindSql('SELECT COUNT(*) count FROM %s WHERE `srarttime`<%s AND `endtime`>%s', $this->getTable(),$time,$time);
            $smt = $this->getConnection()->query($sql);//WindResultSet
            $res = $smt->fetch();
            return isset($res['count']) ? intval($res['count']) : 0;
        }
        
        
        /**
         * 根据tid获得热帖记录id
        */
        public function getThreadsHotId($tid){
            $sql = $this->_bindSql('SELECT id FROM %s WHERE tid=%s LIMIT 1', $this->getTable(),$tid);
            $smt = $this->getConnection()->query($sql);//WindResultSet
            return $smt->fetch();
        }
        
        /**
         * 更新热帖数据
         * 返回影响记录的条数
         * lyl
         */
        public function updateThreadHot($data){
            $id = isset($data['id']) && $data['id'] ? $data['id'] : 0;
            if(!$id) return 0;
            $starttime = isset($data['starttime']) && $data['starttime'] ? $data['starttime'] : 0;
            $endtime = isset($data['endtime']) && $data['endtime'] ? $data['endtime'] : 0;
            $updatetime = isset($data['updatetime']) && $data['updatetime'] ? $data['updatetime'] : 0;
            $sql = $this->_bindSql('UPDATE %s SET `srarttime`=%s,`endtime`=%s,`updatetime`=%s WHERE id=%s', $this->getTable(),$starttime,$endtime,$updatetime,$id);
            return $this->getConnection()->execute($sql);
//            var_dump($sql,$res);exit;
        }
        
        /**
         * 插入设置热帖数据
         * 返回影响记录的条数
         * lyl
         */
        public function insertThreadHot($data){
            $tid = isset($data['tid']) && $data['tid'] ? $data['tid'] : 0;
            $starttime = isset($data['starttime']) && $data['starttime'] ? $data['starttime'] : 0;
            $endtime = isset($data['endtime']) && $data['endtime'] ? $data['endtime'] : 0;
            $createtime = isset($data['createtime']) && $data['createtime'] ? $data['createtime'] : 0;
            $created_userid = isset($data['created_userid']) && $data['created_userid'] ? $data['created_userid'] : 0;
            $created_username = isset($data['created_username']) && $data['created_username'] ? $data['created_username'] : '';
            if(!$tid || !$created_userid) return 0;
            $sql = $this->_bindSql('INSERT INTO %s (`tid`,`srarttime`,`endtime`,`createtime`,`updatetime`,`created_userid`,`created_username`)
                                    VALUES (%s,%s,%s,%s,%s,%s,"%s")', $this->getTable(),$tid,$starttime,$endtime,$createtime,$createtime,$created_userid,$created_username);
//            $res = $this->getConnection()->execute($sql);
//            var_dump($sql,$res);exit;
            return $this->getConnection()->execute($sql);
        }
        
        /**
         * 按时间查找生效的热帖的tids最大取500条
         */
        public function fetchTidsByTime($current_time){
            $sql = $this->_bindSql('SELECT `tid` FROM `%s` WHERE %s>`srarttime` AND %s<`endtime` LIMIT 500', $this->getTable(),$current_time,$current_time);
            $smt = $this->getConnection()->query($sql);//WindResultSet
            return $smt->fetchAll();
        }
        
}