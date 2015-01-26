<?php
/**
 * 移动端回帖位置扩展表
 *
 * @fileName: PwPostPlaceDao.php
 * @author: yuliang<yuliang.lyl@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-16 19:08:17
 * @desc: 
 * */

defined('WEKIT_VERSION') || exit('Forbidden');

class PwPostPlaceDao extends PwBaseDao {
	
	protected $_table = 'bbs_posts_place';
	protected $_pk = 'pid';
	protected $_dataStruct = array('pid','created_address','area_code');
	
    /**
     * 新增一条数据
     * 
     * @param mixed $data 
     * @access public
     * @return void
     */
    public function insertValue($data){
        if (!($data = $this->_filterStruct($data))) return false;
        $sql = $this->_bindSql('INSERT INTO %s SET %s', $this->getTable(), $this->sqlSingle($data));
        $smt = $this->getConnection()->execute($sql);
        return $this->getConnection()->lastInsertId();
    }

    /**
     * 根据回帖的pids批量获取数据
     * 
     * @param mixed $pids 
     * @access public
     * @return void
     */
    public function fetchByPids($pids){
        $sql = $this->_bindSql('SELECT * FROM `%s` WHERE `pid` IN %s', $this->getTable(), $this->sqlImplode($pids) );
        $smt = $this->getConnection()->createStatement($sql);
        return $smt->queryAll();
    }

}
