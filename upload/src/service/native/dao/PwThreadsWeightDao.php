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
	protected $_dataStruct = array('tid','weight','create_time','isenable');
	
        
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
         * 根据tids删除权重表数据
         * lyl
         */
        public function deleteByTids($tids){
            $sql = $this->_bindSql('DELETE FROM `%s` WHERE `tid` IN (%s)', $this->getTable(),$tids);
            return $this->getConnection()->execute($sql);
        }
        /**
         * 根据权重来批量删除数据
         */
        public function deleteByWeight($weight){
            $sql = $this->_bindSql('DELETE FROM `%s` WHERE `weight`<%s', $this->getTable(),$weight);
            return $this->getConnection()->execute($sql);
        }
        /**
         * 插入多条数据
         * lyl
         */
        public function insertValues($values){
            $sql = $this->_bindSql('INSERT INTO `%s` (`tid`,`weight`,`create_time`,`isenable`) VALUES %s', $this->getTable(),$values);
            return $this->getConnection()->execute($sql);
        }
        
        /**
         * 获取指定偏移量数据的权重值
         * lyl
         */
        public function getWeightByPos($pos=499){
            $sql = $this->_bindSql('SELECT `weight` FROM `%s` ORDER BY `weight` DESC LIMIT %s,1', $this->getTable(),$pos);
            $smt = $this->getConnection()->query($sql);
            return $smt->fetch();
        }
        
}