<?php

Wekit::loadDao('forum.dao.PwThreadsDao');

class PwNativeThreadsDao extends PwThreadsDao {
    
    protected $fids = '';//所有的一级版面
    protected $set_fids = '';//管理后台设置的一级版面
    
    public function __construct() {
        parent::__construct();
        
        $configs = Wekit::C()->getValues('native');
        $set_fids = isset($configs['forum.fids']) && $configs['forum.fids'] ? $configs['forum.fids'] : array();
        $set_fids = array_keys($set_fids);
        $this->set_fids = implode(',', $set_fids);
         
        $this->fids = implode(',', $this->_getForumService()->fids );
    }

    /**
     * 获取指定时间之前的帖子条数,用于权重计算
     */
    public function getCountByTime($stop_time){
        if(!$this->set_fids) return 0;
        $sql = $this->_bindSql('SELECT COUNT(*) count FROM `%s` WHERE `created_time`>%s AND `disabled`=0 AND fid IN (%s)' , $this->getTable(),$stop_time,$this->set_fids);
        $smt = $this->getConnection()->query($sql);
//        var_dump($sql,$smt);exit;
        return $smt->fetch();
    }
    /**
     * 获取帖子权重计算时所需的基础数据
     */
    public function fetchThreadsData($stop_time,$start,$num){
        if(!$this->set_fids) return array();
        $dao = $GLOBALS['acloud_object_dao'];
        $prefix = $dao->getDB()->getTablePrefix();
        $sql = "SELECT t.`tid`,t.`replies`,t.`like_count`,t.`lastpost_time`,t.`created_time`,IFNULL(SUM(p.`like_count`),0) reply_like_count 
                FROM `%s` t 
                LEFT JOIN `${prefix}bbs_posts` p 
                ON t.`tid`=p.`tid` 
                WHERE t.`created_time`>%s AND t.`disabled`=0 AND t.`fid` IN ($this->set_fids)
                GROUP BY t.`tid`
                ORDER BY t.`replies` DESC ,t.`lastpost_time` DESC
                LIMIT %s,%s";
        $sql = $this->_bindSql($sql, $this->getTable(),$stop_time,$start,$num);
//        echo $sql;exit;
        $smt = $this->getConnection()->query($sql);
        return $smt->fetchAll();
    }
    /**
     * 获取动态最新的帖子列表
     */
    public function fetchNewThreads($pos=0,$num=30){
        if(!$this->set_fids) return array();
        $dao = $GLOBALS['acloud_object_dao'];
        $prefix = $dao->getDB()->getTablePrefix();
        $sql = "SELECT t.`tid`,t.`fid`,t.`subject`,c.`content`,c.`tags`,t.`lastpost_userid`,t.`lastpost_username`,t.`hits`,t.`replies`,t.`like_count`,t.`lastpost_time` 
                FROM `%s` t 
                LEFT JOIN `${prefix}bbs_threads_content` c 
                ON t.`tid`=c.`tid`
                WHERE t.`disabled`=0 AND t.`fid` IN ($this->set_fids)
                ORDER BY t.`created_time` DESC 
                LIMIT %s,%s";
        $sql = $this->_bindSql($sql, $this->getTable(),$pos,$num);
        $smt = $this->getConnection()->query($sql);
        return $smt->fetchAll('tid');
    }
    
    /**
     * 获取动态最新的帖子tids，按照发帖时间排序
     */
    public function fetchNewThreadTids($pos=0,$num=30){
        if(!$this->set_fids) return array();
        $dao = $GLOBALS['acloud_object_dao'];
        $prefix = $dao->getDB()->getTablePrefix();
        $sql = "SELECT t.`tid`
                FROM `%s` t
                WHERE t.`disabled`=0 AND t.`fid` IN ($this->set_fids)
                ORDER BY t.`created_time` DESC 
                LIMIT %s,%s";
        $sql = $this->_bindSql($sql, $this->getTable(),$pos,$num);
        $smt = $this->getConnection()->query($sql);
        return array_keys($smt->fetchAll('tid'));
    }
    
    /**
     * 获取动态最新的帖子数量
     */
    public function getNewThreadCount(){
        if(!$this->set_fids) return 0;
        $dao = $GLOBALS['acloud_object_dao'];
        $prefix = $dao->getDB()->getTablePrefix();
        $sql = "SELECT COUNT(*) count
                FROM `%s` t
                WHERE t.`disabled`=0 AND t.`fid` IN ($this->set_fids);";
        $sql = $this->_bindSql($sql, $this->getTable());
        $smt = $this->getConnection()->query($sql);
        $res = $smt->fetch();
        return $res['count'];
    }
    
    /**
     * 根据tids获取动态我的关注帖子列表
     */
    public function fetchMyThreads($tids){
        if(!$this->fids) return array();
        $tids_str = '';
        is_array($tids) && $tids_str = implode(',', $tids);
        if(!$tids_str) return array();
        $dao = $GLOBALS['acloud_object_dao'];
        $prefix = $dao->getDB()->getTablePrefix();
        $sql = "SELECT t.`tid`,t.`subject`,c.`content`,c.`tags`,t.`replies`,t.`hits`,t.`like_count`,t.`created_time`,t.`created_userid`,t.`created_username` 
                FROM `%s` t 
                LEFT JOIN `${prefix}bbs_threads_content` c 
                ON t.`tid`=c.`tid` 
                WHERE t.`tid` IN (%s) AND t.`disabled`=0 AND t.`fid` IN ($this->fids)
                ORDER BY t.`created_time` DESC;";
        $sql = $this->_bindSql($sql, $this->getTable(),$tids_str);
        $smt = $this->getConnection()->query($sql);
        return $smt->fetchAll('tid');
    }
    
    /**
     * 获取动态最热帖子列表
     */
    public function fetchHotThreads($hot_count,$time,$page,$num){
        if(!$this->set_fids) return array();
        $start_pos = ($page-1)*$num;//起始数据偏移量
        $end_pos = $page*$num-1;//结束数据偏移量
        $dao = $GLOBALS['acloud_object_dao'];//ACloudVerCoreDao
        $prefix = $dao->getDB()->getTablePrefix();
        if($hot_count-1>=$start_pos){//从hot表取数据
            $sql = "SELECT t.`tid`,t.`fid`,t.`subject`,c.`content`,c.`tags`,t.`lastpost_userid`,t.`lastpost_username`,t.`hits`,t.`replies`,t.`like_count`
                    FROM `${prefix}bbs_threads_hot` h 
                    LEFT JOIN `${prefix}bbs_threads` t
                    ON h.`tid`=t.`tid` 
                    LEFT JOIN `${prefix}bbs_threads_content` c
                    ON h.tid=c.`tid`
                    WHERE h.`srarttime`<=$time AND h.`endtime`>=$time AND t.`disabled`=0 AND t.`fid` IN ($this->set_fids)
                    ORDER BY t.`created_time` DESC
                    LIMIT $start_pos,$num;";
            $res = $dao->fetchAll($sql,'tid');
            if($end_pos > $hot_count-1){//从weight表取后半段数据
                $num = $page*$num-$hot_count;
                $sql = "SELECT t.`tid`,t.`fid`,t.`subject`,c.`content`,c.`tags`,t.`lastpost_userid`,t.`lastpost_username`,t.`hits`,t.`replies`,t.`like_count`
                        FROM `${prefix}bbs_threads_weight` w
                        LEFT JOIN `${prefix}bbs_threads` t
                        ON w.`tid`=t.`tid` 
                        LEFT JOIN `${prefix}bbs_threads_content` c
                        ON w.tid=c.`tid`
                        WHERE t.`disabled`=0 AND t.`fid` IN ($this->set_fids)
                        ORDER BY w.`weight` DESC
                        LIMIT 0,$num;";
                $res_weight = $dao->fetchAll($sql,'tid');
                foreach($res_weight as $k=>$v){
                    $res[$k] = $v;
                }
            }
        }else{//从weight表取数据
            $start_pos = $start_pos-$hot_count;
            $sql = "SELECT t.`tid`,t.`fid`,t.`subject`,c.`content`,c.`tags`,t.`lastpost_userid`,t.`lastpost_username`,t.`hits`,t.`replies`,t.`like_count`
                    FROM  `${prefix}bbs_threads_weight` w
                    LEFT JOIN `${prefix}bbs_threads` t
                    ON w.`tid`=t.`tid` 
                    LEFT JOIN `${prefix}bbs_threads_content` c
                    ON w.tid=c.`tid`
                    WHERE t.`disabled`=0 AND t.`fid` IN ($this->set_fids)
                    ORDER BY w.`weight` DESC
                    LIMIT $start_pos,$num;";
            $res = $dao->fetchAll($sql,'tid');
        }
        
        return $res;
    }
    
    
    /**
     * 获取动态最热帖子tids
     */
    public function fetchHotThreadTids($start_pos, $num) {
        if (!$this->set_fids) return array();
        $dao = $GLOBALS['acloud_object_dao']; //ACloudVerCoreDao
        $prefix = $dao->getDB()->getTablePrefix();
        $sql = "SELECT t.`tid`
                FROM  `${prefix}bbs_threads_weight` w
                LEFT JOIN `${prefix}bbs_threads` t
                ON w.`tid`=t.`tid`
                WHERE t.`disabled`=0 AND t.`fid` IN ($this->set_fids)
                ORDER BY w.`weight` DESC
                LIMIT $start_pos,$num;";
        $res = $dao->fetchAll($sql, 'tid');
        return array_keys($res);
    }
    
    /**
     * 获取动态最热帖子数量
     */
    public function fetchHotThreadCount() {
        if (!$this->set_fids) return 0;
        $dao = $GLOBALS['acloud_object_dao']; //ACloudVerCoreDao
        $prefix = $dao->getDB()->getTablePrefix();
        $sql = "SELECT COUNT(*) count
                FROM  `${prefix}bbs_threads_weight` w
                LEFT JOIN `${prefix}bbs_threads` t
                ON w.`tid`=t.`tid`
                WHERE t.`disabled`=0 AND t.`fid` IN ($this->set_fids);";
        $res = $dao->fetchOne($sql);
        return $res['count'];
    }
    

    /**
     * 获取同城帖子列表
     */
    public function fetchCityThreads($city,$pos,$num){
        if(!$this->fids || !$city) return array();
        $dao = $GLOBALS['acloud_object_dao'];//ACloudVerCoreDao
        $prefix = $dao->getDB()->getTablePrefix();
        $sql = "SELECT t.`tid`,t.`fid`,t.`subject`,c.`content`,c.`tags`,t.`lastpost_userid`,t.`lastpost_username`,t.`hits`,t.`replies`,t.`like_count` 
                FROM `${prefix}bbs_threads_place` n 
                LEFT JOIN `%s` t ON n.`tid`=t.`tid` 
                LEFT JOIN `${prefix}bbs_threads_content` c ON n.`tid`=c.`tid` 
                WHERE t.`disabled`=0 AND t.`fid` IN ($this->fids) AND n.`created_address`='%s'
                ORDER BY t.`created_time` DESC 
                LIMIT %s,%s;";
        $sql = $this->_bindSql($sql, $this->getTable(),$city,$pos,$num);
//        var_dump($sql);exit;
        $smt = $this->getConnection()->query($sql);
        return $smt->fetchAll('tid');
    }
    
    
    /**
     * 获取同城帖子tids
     */
    public function fetchCityThreadTids($city,$pos,$num){
        if(!$this->fids || !$city) return array();
        $dao = $GLOBALS['acloud_object_dao'];//ACloudVerCoreDao
        $prefix = $dao->getDB()->getTablePrefix();
        $sql = "SELECT t.`tid`
                FROM `${prefix}bbs_threads_place` n 
                LEFT JOIN `%s` t 
                ON n.`tid`=t.`tid` 
                WHERE t.`disabled`=0 AND t.`fid` IN ($this->fids) AND n.`created_address`='%s'
                ORDER BY t.`lastpost_time` DESC 
                LIMIT %s,%s;";
        $sql = $this->_bindSql($sql, $this->getTable(),$city,$pos,$num);
//        var_dump($sql);exit;
        $smt = $this->getConnection()->query($sql);
        return array_keys($smt->fetchAll('tid'));
    }
    
    /**
     * 获取同城帖子数量
     */
    public function getCityThreadCount($city){
        if(!$this->fids || !$city) return 0;
        $dao = $GLOBALS['acloud_object_dao'];//ACloudVerCoreDao
        $prefix = $dao->getDB()->getTablePrefix();
        $sql = "SELECT COUNT(*) count
                FROM `${prefix}bbs_threads_place` n 
                LEFT JOIN `%s` t 
                ON n.`tid`=t.`tid` 
                WHERE t.`disabled`=0 AND t.`fid` IN ($this->fids) AND n.`created_address`='%s';";
        $sql = $this->_bindSql($sql, $this->getTable(),$city);
        $smt = $this->getConnection()->query($sql);
        $res = $smt->fetch();
        return $res['count'];
    }
    
    /**
     * 根据用户uid获取用户发帖时所在位置
     */
    public function getCityByUid($uid){
        if(!$this->fids || !$uid) return "";
        $dao = $GLOBALS['acloud_object_dao'];//ACloudVerCoreDao
        $prefix = $dao->getDB()->getTablePrefix();          
        $sql = "SELECT p.`created_address` 
                FROM `{$prefix}bbs_threads_place` p 
                LEFT JOIN `%s` t 
                ON p.`tid`=t.`tid` 
                WHERE p.`created_address`!='' AND t.`created_userid`=%s 
                ORDER BY t.`created_time` DESC 
                LIMIT 1;";
        $sql = $this->_bindSql($sql, $this->getTable(),$uid);
        $smt = $this->getConnection()->query($sql);
        $res = $smt->fetch();
        return $res['created_address'];
    }
    
    public function fetchThreads($tids) {
        //if(!$this->fids || !$tids) return array();
        if( !$tids ) return array();
        $sql = $this->_bindSql("SELECT * FROM %s WHERE tid IN %s AND disabled=0 ORDER BY created_time DESC", $this->getTable(), $this->sqlImplode($tids));
        $smt = $this->getConnection()->createStatement($sql);
        return $smt->queryAll(array(), 'tid');
    }

    private function _getForumService(){
        return Wekit::load('native.srv.PwForumService');
    }

}
