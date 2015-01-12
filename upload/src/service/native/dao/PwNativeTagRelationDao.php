<?php
Wekit::loadDao('tag.dao.PwTagRelationDao');
 
class PwNativeTagRelationDao extends PwTagRelationDao {
    
    protected $fids = '';
    
    public function __construct() {
        parent::__construct();
        $configs = Wekit::C()->getValues('native');
        $fids = isset($configs['forum.fids']) && $configs['forum.fids'] ? $configs['forum.fids'] : array();
        $fids = array_keys($fids);
        $this->fids = implode(',', $fids);
    }
	
    /**
     * 根据话题tag_ids批量获得帖子ids
     */
    public function fetchTids($tag_ids,$time_point=0,$pos=0,$num=30){
        if(!$this->fids) return array();
        $tag_ids_str = '';
        is_array($tag_ids) && $tag_ids_str = implode(',', $tag_ids);
        if(!$tag_ids_str) return array();
        $dao = $GLOBALS['acloud_object_dao'];
        $prefix = $dao->getDB()->getTablePrefix();
        $sql = "SELECT DISTINCT r.`param_id` 
                FROM `%s` r
                LEFT JOIN `${prefix}bbs_threads` t ON r.`param_id`=t.`tid` 
                WHERE r.`tag_id` IN (%s) AND r.`created_time`>=%s AND t.`disabled`=0 AND t.`fid` IN ($this->fids)
                LIMIT %s,%s;";//默认展示最近7天数据
        $sql = $this->_bindSql($sql, $this->getTable(),$tag_ids_str,$time_point,$pos,$num);
        $smt = $this->getConnection()->query($sql);
	return $smt->fetchAll();
    }
}