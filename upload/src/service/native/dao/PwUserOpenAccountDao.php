<?php
/**
 * @fileName: PwUserOpenAccountDao.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-18 19:06:41
 * @desc: 开放平台帐号与论坛帐号关系表 
 **/
class PwUserOpenAccountDao extends PwBaseDao {
	protected $_table = 'user_open_account';
	protected $_dataStruct = array('uid', 'account', 'type');


    /**
     * @access public
     * @return void
     */
    public function addUser($data){
        if (!($data = $this->_filterStruct($data))) return false;
        $sql = $this->_bindSql('INSERT INTO %s SET %s', $this->getTable(), $this->sqlSingle($data));
        $smt = $this->getConnection()->execute($sql);
        return $this->getConnection()->lastInsertId();
    }

    /**
     * @param mixed $account 
     * @param mixed $type 
     * @access public
     * @return array
     */
    public function getUid($account,$type){
        $sql = $this->_bindTable('SELECT uid FROM %s WHERE account=? AND type=?', $this->getTable());
        $smt = $this->getConnection()->createStatement($sql);
        return $smt->getOne(array($account, $type)); 
    }

//	public function getBanInfo($uid) {
//		$sql = $this->_bindTable('SELECT * FROM %s WHERE uid = ?');
//		$smt = $this->getConnection()->createStatement($sql);
//		return $smt->queryAll(array($uid), 'typeid');
//	}

}
