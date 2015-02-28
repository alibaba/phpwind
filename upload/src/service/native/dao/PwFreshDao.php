<?php
/**
 * @fileName: PwFreshDao.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2015-02-27 11:57:16
 * @desc: 
 **/
class PwFreshDao extends PwBaseDao {
    protected $_pk = 'fresh_id';
	protected $_table = 'fresh_site';
	protected $_dataStruct = array('fresh_id', 'title', 'href', 'img', 'des', 'vieworder');

    /**
     * @access public
     * @return void
     */
    public function addFresh($data){
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
    public function getFresh(){
        $sql = $this->_bindTable('SELECT fresh_id,title,href,img,des,vieworder FROM %s order by vieworder asc', $this->getTable());
        $smt = $this->getConnection()->createStatement($sql);
        return $smt->queryAll(array($banner_type)); 
    }

    /**
     * getOneFresh 
     * 
     * @param mixed $fresh_id 
     * @access public
     * @return void
     */
    public function getOneFresh($fresh_id){
        $sql = $this->_bindTable('SELECT fresh_id,title,href,img,des,vieworder FROM %s WHERE fresh_id=?', $this->getTable());
        $smt = $this->getConnection()->createStatement($sql);
        return $smt->getOne(array($fresh_id)); 
    }

    /**
     * getMaxId 
     * 
     * @access public
     * @return void
     */
    public function getMaxId(){
        $sql = $this->_bindTable('SELECT fresh_id from %s order by fresh_id desc', $this->getTable());
        $smt = $this->getConnection()->createStatement($sql);
        return $smt->getOne();
    }

    /**
     * updateFresh 
     * 
     * @param mixed $fresh_id 
     * @param mixed $data 
     * @access public
     * @return void
     */
    public function updateFresh($fresh_id,$data){
        if (!($data = $this->_filterStruct($data))) return false;
        $sql = $this->_bindSql('UPDATE %s SET %s WHERE %s=?', $this->getTable(), $this->sqlMerge($data), $this->_pk);
        $smt = $this->getConnection()->createStatement($sql);
        $result = $smt->update(array($fresh_id));
        return $result;
    }

    /**
     * delete 
     * 
     * @param mixed $fresh_id 
     * @access public
     * @return void
     */
    public function delete($fresh_id){
        return $this->_delete($fresh_id);
    }

}
