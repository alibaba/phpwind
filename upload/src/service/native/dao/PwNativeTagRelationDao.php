<?php
Wekit::loadDao('tag.dao.PwTagRelationDao');

class PwNativeTagRelationDao extends PwTagRelationDao {

    protected $fids = '';

    public function __construct() {
        parent::__construct();
        /*
        $configs = Wekit::C()->getValues('native');
        $fids = isset($configs['forum.fids']) && $configs['forum.fids'] ? $configs['forum.fids'] : array();
        $fids = array_keys($fids);//移动端可展示的一级版块
        //获取生活服务的一级版块
        $forum_lifes = Wekit::loadDao('native.dao.PwForumLifeDao')->fetchForumLifeList();
        $fids = array_merge($fids,array_keys($forum_lifes));
        $this->fids = implode(',', $fids);
         */
        $this->fids = implode(',', $this->_getForumService()->fids );
    }

    /**
     * 根据话题tag_ids批量获得帖子ids,按照主贴最后回复时间排序
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
            ORDER BY t.`lastpost_time` DESC
            LIMIT %s,%s;";//默认展示最近7天数据
        $sql = $this->_bindSql($sql, $this->getTable(),$tag_ids_str,$time_point,$pos,$num);
        $smt = $this->getConnection()->query($sql);
        return $smt->fetchAll();
    }


    /**
     * 根据话题tag_ids批量获得帖子总数
     */
    public function getCount($tag_ids,$time_point=0){
        if(!$this->fids) return 0;
        $tag_ids_str = '';
        is_array($tag_ids) && $tag_ids_str = implode(',', $tag_ids);
        if(!$tag_ids_str) return 0;
        $dao = $GLOBALS['acloud_object_dao'];
        $prefix = $dao->getDB()->getTablePrefix();
        $sql = "SELECT COUNT(DISTINCT r.`param_id`) count 
            FROM `%s` r
            LEFT JOIN `${prefix}bbs_threads` t ON r.`param_id`=t.`tid` 
            WHERE r.`tag_id` IN (%s) AND r.`created_time`>=%s AND t.`disabled`=0 AND t.`fid` IN ($this->fids);";//默认展示最近7天数据
        $sql = $this->_bindSql($sql, $this->getTable(),$tag_ids_str,$time_point);
        $smt = $this->getConnection()->query($sql);
        $res = $smt->fetch();
        return $res['count'];
    }

    private function _getForumService(){
        return Wekit::load('native.srv.PwForumService');
    }

}
