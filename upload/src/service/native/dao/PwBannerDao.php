<?php
/**
 * @fileName: PwBannerDao.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-29 19:44:14
 * @desc: 
 **/
class PwBannerDao extends PwBaseDao {
    protected $_pk = 'banner_id';
	protected $_table = 'banner';
	protected $_dataStruct = array('banner_id', 'banner_type', 'type', 'title', 'href','img', 'vieworder');

    /**
     * @access public
     * @return void
     */
    public function addBanner($data){
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
    public function getBanner($banner_type){
        $sql = $this->_bindTable('SELECT banner_id,type,title,href,img,vieworder FROM %s WHERE banner_type=? order by vieworder asc', $this->getTable());
        $smt = $this->getConnection()->createStatement($sql);
        return $smt->queryAll(array($banner_type)); 
    }

    /**
     * getOneBanner 
     * 
     * @param mixed $banner_id 
     * @access public
     * @return void
     */
    public function getOneBanner($banner_id){
        $sql = $this->_bindTable('SELECT banner_id,type,title,href,img,vieworder FROM %s WHERE banner_id=?', $this->getTable());
        $smt = $this->getConnection()->createStatement($sql);
        return $smt->getOne(array($banner_id)); 
    }

    /**
     * getMaxId 
     * 
     * @access public
     * @return void
     */
    public function getMaxId(){
        $sql = $this->_bindTable('SELECT banner_id from %s order by banner_id desc', $this->getTable());
        $smt = $this->getConnection()->createStatement($sql);
        return $smt->getOne();
    }

    /**
     * updateBanner 
     * 
     * @param mixed $banner_id 
     * @param mixed $data 
     * @access public
     * @return void
     */
    public function updateBanner($banner_id,$data){
        if (!($data = $this->_filterStruct($data))) return false;
        $sql = $this->_bindSql('UPDATE %s SET %s WHERE %s=?', $this->getTable(), $this->sqlMerge($data), $this->_pk);
        $smt = $this->getConnection()->createStatement($sql);
        $result = $smt->update(array($banner_id));
        return $result;
    }

    /**
     * delete 
     * 
     * @param mixed $banner_id 
     * @access public
     * @return void
     */
    public function delete($banner_id){
        return $this->_delete($banner_id);
    }

}
