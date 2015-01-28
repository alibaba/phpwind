<?php
defined('WEKIT_VERSION') || exit('Forbidden');


/**
 * 热帖权重计算表dao服务
 *
 * @fileName: PwThreadsWeightDao.php
 * @author: yuliang<yuliang.lyl@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-16 19:08:17
 * @desc: 
 * */


class PwThreadsWeightDao extends PwBaseDao {
	
	protected $_table = 'bbs_threads_weight';
	protected $_pk = 'tid';
	protected $_dataStruct = array('tid','weight','create_time','create_userid','create_username','isenable');
	
        /**
         * 根据tid获取帖子数据
         */
        public function getByTid($tid) {
            $sql = $this->_bindSql('SELECT tid FROM %s WHERE tid=%s;', $this->getTable(),$tid);
            $smt = $this->getConnection()->query($sql);//WindResultSet
            return $smt->fetch();
	}
        /**
         * 获取最后一条记录的插入时间
         * lyl
         */
        public function getMaxCreateTime(){
            $sql = $this->_bindSql('SELECT MAX(`create_time`) last_create_time FROM `%s`', $this->getTable());
            $smt = $this->getConnection()->query($sql);
            return $smt->fetchAll();
        }
        
        /**
         * 删除整表数据
         * lyl
         */
        public function deleteAll(){
            $sql = $this->_bindSql('DELETE FROM `%s`', $this->getTable());
            return $this->getConnection()->execute($sql);
        }
        
        /**
         * 删除作业计算的热帖数据
         * lyl
         */
        public function deleteAutoData(){
            $sql = $this->_bindSql('DELETE FROM `%s` WHERE create_userid=0', $this->getTable());
            return $this->getConnection()->execute($sql);
        }
        
        /**
         * 删除管理推荐的过期热帖数据
         * lyl
         */
        public function deleteUserData($end_time){
            $sql = $this->_bindSql('DELETE FROM `%s` WHERE `create_userid`>0 AND `create_time`<%s;', $this->getTable(),$end_time);
            return $this->getConnection()->execute($sql);
        }
        
        /**
         * 根据tids删除权重表数据
         * lyl
         */
        public function deleteByTids($tids){
            $sql = $this->_bindSql('DELETE FROM `%s` WHERE `tid` IN (%s)', $this->getTable(),$tids);
            return $this->getConnection()->execute($sql);
        }
        /**
         * 根据权重来批量删除非用户推荐数据
         */
        public function deleteByWeight($weight){
            $sql = $this->_bindSql('DELETE FROM `%s` WHERE `weight`<%s AND `create_userid`=0;', $this->getTable(),$weight);
            return $this->getConnection()->execute($sql);
        }
        /**
         * 插入多条数据忽略已存在数据
         * lyl
         */
        public function insertValues($values){
            $sql = $this->_bindSql('INSERT IGNORE INTO `%s` (`tid`,`weight`,`create_time`,`isenable`) VALUES %s', $this->getTable(),$values);
            return $this->getConnection()->execute($sql);
        }
        
        /**
         * 批量覆盖多条数据
         * lyl
         */
        public function replaceValues($values){
            $sql = $this->_bindSql('REPLACE INTO `%s` (`tid`,`weight`,`create_time`,`create_userid`,`create_username`,`isenable`) VALUES %s', $this->getTable(),$values);
            return $this->getConnection()->execute($sql);
        }
        
        /**
         * 获取指定偏移量非用户推荐热帖的权重值
         * lyl
         */
        public function getWeightByPos($pos=499){
            $sql = $this->_bindSql('SELECT `weight` FROM `%s` WHERE `create_userid`=0 ORDER BY `weight` DESC LIMIT %s,1', $this->getTable(),$pos);
            $smt = $this->getConnection()->query($sql);
            return $smt->fetch();
        }
        
         /**
         * 更新设置热帖数据
         * 返回影响记录的条数
         * lyl
         */
        public function updateValue($data){
            $tid = isset($data['tid']) && $data['tid'] ? $data['tid'] : 0;
            $create_time = isset($data['create_time']) && $data['create_time'] ? $data['create_time'] : 0;
            $weight = isset($data['weight']) && $data['weight'] ? $data['weight'] : 0;
            $create_userid = isset($data['create_userid']) && $data['create_userid'] ? $data['create_userid'] : 0;
            $create_username = isset($data['create_username']) && $data['create_username'] ? $data['create_username'] : '';
            if(!$tid) return 0;
            $sql = $this->_bindSql("UPDATE %s SET `create_time`=%s,`weight`=%s,`create_userid`=%s,`create_username`='%s' WHERE tid=%s", $this->getTable(),$create_time,$weight,$create_userid,$create_username,$tid);
            return $this->getConnection()->execute($sql);
//            var_dump($sql,$res);exit;
        }
        
        /**
         * 更新热帖权重值
         */
        public function updateWeight($data){
            $tid = isset($data['tid']) && $data['tid'] ? $data['tid'] : 0;
            $weight = isset($data['weight']) && $data['weight'] ? $data['weight'] : 0;
            if(!$tid) return 0;
            $sql = $this->_bindSql("UPDATE %s SET `weight`=%s WHERE tid=%s", $this->getTable(),$weight,$tid);
            return $this->getConnection()->execute($sql);
        }
        
        /**
         * 批量更新用户设置的热帖权重值
         */
        public function batchUpdateUserWeight($end_time,$weight){
            $sql = $this->_bindSql("UPDATE %s SET `weight`=%s WHERE `create_time`>%s AND `create_userid`>0;", $this->getTable(),$weight,$end_time);
            return $this->getConnection()->execute($sql);
        }
        
        /**
         * 插入设置热帖数据
         * 返回影响记录的条数
         * lyl
         */
        public function insertValue($data){
            $tid = isset($data['tid']) && $data['tid'] ? $data['tid'] : 0;
            $create_time = isset($data['create_time']) && $data['create_time'] ? $data['create_time'] : 0;
            $weight = isset($data['weight']) && $data['weight'] ? $data['weight'] : 0;
            $create_userid = isset($data['create_userid']) && $data['create_userid'] ? $data['create_userid'] : 0;
            $create_username = isset($data['create_username']) && $data['create_username'] ? $data['create_username'] : '';
            if(!$tid || !$create_userid) return 0;
            $sql = $this->_bindSql("INSERT INTO %s (`tid`,`weight`,`create_time`,`create_userid`,`create_username`)
                                    VALUES (%s,%s,%s,%s,'%s')", $this->getTable(),$tid,$weight,$create_time,$create_userid,$create_username);
//            $res = $this->getConnection()->execute($sql);
//            var_dump($sql,$res);exit;
            return $this->getConnection()->execute($sql);
        }
        
        /**
         * 获取热帖池中最大权重值
        */
        public function getMaxWeight(){
            $sql = $this->_bindSql('SELECT MAX(`weight`) weight FROM `%s`;', $this->getTable());
            $smt = $this->getConnection()->query($sql);//WindResultSet
            return $smt->fetch();
        }
        
        /**
         * 获取管理员设置的热帖数量
         */
        public function getUserDataCount(){
            $sql = $this->_bindSql('SELECT COUNT(*) count FROM `%s` WHERE `create_userid`>0;', $this->getTable());
            $smt = $this->getConnection()->query($sql);//WindResultSet
            return $smt->fetch();
        }
        
        /**
         * 获取管理员设置的热帖的基础数据，用于计算权重
         */
        public function fetchUserThreadsData($start,$num){
            $dao = $GLOBALS['acloud_object_dao'];
            $prefix = $dao->getDB()->getTablePrefix();
            $sql = "SELECT t.`tid`,t.`replies`,t.`like_count`,t.`lastpost_time`,w.`create_time`,w.`create_userid`,w.`create_username`,w.`isenable`,IFNULL(SUM(p.`like_count`),0) reply_like_count 
                    FROM `%s` w
                    JOIN `${prefix}bbs_threads` t
                    ON w.`tid`=t.`tid` 
                    LEFT JOIN `${prefix}bbs_posts` p 
                    ON w.`tid`=p.`tid` 
                    WHERE t.`disabled`=0 AND w.`create_userid`>0
                    GROUP BY t.`tid`
                    LIMIT %s,%s";
            $sql = $this->_bindSql($sql, $this->getTable(),$start,$num);
//            echo $sql;exit;
            $smt = $this->getConnection()->query($sql);
            return $smt->fetchAll();
        }
        
}
