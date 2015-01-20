<?php
defined('WEKIT_VERSION') || exit('Forbidden');


/**
 * 生活服务表dao服务
 *
 * @fileName: PwForumLifeDao.php
 * @author: yuliang<yuliang.lyl@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-16 19:08:17
 * @desc: 
 * */


class PwForumLifeDao extends PwBaseDao {
	
	protected $_table = 'bbs_forum_life';
	protected $_pk = 'fid';
	protected $_dataStruct = array('fid','address','url');
	
        /**
         * 根据fid获取forum_life数据
         */
	public function getForumLife($fid) {
		return $this->_get($fid);
	}
        /**
         * 批量获取forum_life数据
         */
	public function fetchForumLife($fids) {
		return $this->_fetch($fids, 'fid');
	}
         /**
         * 批量获取forum & forum_life数据
         */
	public function fetchForumLifeList() {
            $dao = $GLOBALS['acloud_object_dao'];
            $prefix = $dao->getDB()->getTablePrefix();
            $sql = "SELECT f.`fid`,f.`name`,f.`manager`,f.`vieworder` 
                    FROM `{$prefix}bbs_forum` f 
                    JOIN `{$prefix}bbs_forum_life` l 
                    ON f.`fid`=l.`fid` 
                    WHERE f.`type`='forum'
                    ORDER BY f.`vieworder` ASC";
            return $dao->fetchAll($sql,'fid');
        }

        /**
         * 添加生活服务商家
         */
	public function addForumLife($fields) {
		return $this->_add($fields);
	}
        
        /**
         * 批量添加生活服务商家
         */
	public function batchAddForumLife($forums) {
            foreach($forums as $v){
                $this->_add($v);
            }
        }
        
        /**
         * 修改生活服务商家
         */
	public function updateForumLife($fid, $fields, $increaseFields = array()) {
		return $this->_update($fid, $fields);
	}
        
        /**
         * 删除生活服务商家
         */
	public function deleteForumLife($fid) {
		return $this->_delete($fid);
	}
}