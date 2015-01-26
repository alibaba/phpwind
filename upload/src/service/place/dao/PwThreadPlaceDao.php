<?php
defined('WEKIT_VERSION') || exit('Forbidden');


/**
 * 移动端发帖扩展表
 *
 * @fileName: PwThreadPlaceDao.php
 * @author: yuliang<yuliang.lyl@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-16 19:08:17
 * @desc: 
 * */
class PwThreadPlaceDao extends PwBaseDao {

    protected $_table = 'bbs_threads_place';
    protected $_pk = 'tid';
    protected $_dataStruct = array('tid','from_type','created_address','area_code');


    /**
     * 新增一条数据
     */
    public function insertValue($data){
        $sql = $this->_bindSql('INSERT INTO %s (`tid`,`from_type`,`created_address`,`area_code`) VALUES (%s,%s,"%s","%s")', $this->getTable(),$tid,$from_type,$created_address,$area_code);
        return $this->getConnection()->execute($sql);
    }

    /**
     * 根据帖子的tids批量获取数据
     */
    public function fetchByTids($tids){
        $sql = $this->_bindSql('SELECT * FROM `%s` WHERE `tid` IN %s', $this->getTable(), $this->sqlImplode($tids) );
        $smt = $this->getConnection()->createStatement($sql);                                                                                             
        return $smt->queryAll();
    }

}
