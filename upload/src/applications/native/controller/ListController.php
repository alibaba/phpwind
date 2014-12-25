<?php

/**
 * 帖子列表相关接口
 *
 * @fileName: ListController.php
 * @author: yuliang<yuliang.lyl@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-16 19:08:17
 * @desc: 
 * */
defined('WEKIT_VERSION') || exit('Forbidden');

class ListController extends PwBaseController {
    private $hotTag = 4;
    private $hotContents = 3;
    private $perpage = 10;
    private $defaultType = 'threads';
    private $attentionTagList = 10;
    private $hotTagList = 10; //热门话题显示数

    public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);
//		if (!$this->loginUser->isExists()) $this->showError('VOTE:user.not.login');
    }

    
    /**
     * 管理员设置热帖表单
     * @access public
     * @return string
      <pre>
      /index.php?m=native&c=list&a=hotform
      response: html
      </pre>
     */
    public function hotFormAction() {}
    
    
    /**
     * 获取最热帖子列表
     * @access public
     * @return string
      <pre>
      /index.php?m=native&c=list&a=hot&page=1&_json=1
      response: {err:"",data:""}
      </pre>
     */
    public function hotAction() {
        $time = time();
        $num = 5;//一页显示记录数
        $page = isset($_GET['page']) && intval($_GET['page'])>=1 ? intval($_GET['page']) : 1;//第几页，从请求参数获取
        $start_pos = ($page-1)*$num;//起始数据偏移量
        $end_pos = $page*$num-1;//结束数据偏移量
        $dao = $GLOBALS['acloud_object_dao'];
        $prefix = $dao->getDB()->getTablePrefix();
        //查找管理员设置热帖数量
        $sql = "SELECT COUNT(*) count 
                FROM `${prefix}bbs_threads_hot` 
                WHERE `srarttime`<$time AND `endtime`>$time;";
        $res = $dao->fetchOne($sql);
        $hot_count = $res['count'];
        if($hot_count-1>=$start_pos){//从hot表取数据
            $sql = "SELECT t.`tid`,t.`fid`,t.`subject`,c.`content`,c.`tags`,t.`lastpost_userid`,t.`lastpost_username`,t.`hits`,t.`replies`,t.`like_count`
                    FROM `${prefix}bbs_threads_hot` h 
                    LEFT JOIN `${prefix}bbs_threads` t
                    ON h.`tid`=t.`tid` 
                    LEFT JOIN `${prefix}bbs_threads_content` c
                    ON h.tid=c.`tid`
                    WHERE h.`srarttime`<=$time AND h.`endtime`>=$time
                    ORDER BY t.`lastpost_time` DESC
                    LIMIT $start_pos,$num;";
            $res = $dao->fetchAll($sql);
            if($end_pos > $hot_count-1){//从weight表取后半段数据
                $num = $page*$num-$hot_count;
                $sql = "SELECT t.`tid`,t.`fid`,t.`subject`,c.`content`,c.`tags`,t.`lastpost_userid`,t.`lastpost_username`,t.`hits`,t.`replies`,t.`like_count`
                        FROM `${prefix}bbs_threads_weight` w
                        LEFT JOIN `${prefix}bbs_threads` t
                        ON w.`tid`=t.`tid` 
                        LEFT JOIN `${prefix}bbs_threads_content` c
                        ON w.tid=c.`tid`
                        ORDER BY w.`weight` DESC
                        LIMIT 0,$num;";
                $res_weight = $dao->fetchAll($sql);
                $res = array_merge($res,$res_weight);
            }
        }else{//从weight表取数据
            $start_pos = $start_pos-$hot_count;
            $sql = "SELECT t.`tid`,t.`fid`,t.`subject`,c.`content`,c.`tags`,t.`lastpost_userid`,t.`lastpost_username`,t.`hits`,t.`replies`,t.`like_count`
                    FROM  `${prefix}bbs_threads_weight` w
                    LEFT JOIN `${prefix}bbs_threads` t
                    ON w.`tid`=t.`tid` 
                    LEFT JOIN `${prefix}bbs_threads_content` c
                    ON w.tid=c.`tid`
                    ORDER BY w.`weight` DESC
                    LIMIT $start_pos,$num;";
            $res = $dao->fetchAll($sql);
        }

        if($res){//筛选帖子的回帖
            $tids = array();
            $threads = array();
            foreach($res as $v){
                $threads[$v['tid']] = $v;
                $threads[$v['tid']]['replies'] = array();
                $tids[] = $v['tid'];
            }
            $tids = implode(",", $tids);
            $sql = "SELECT `tid`,`rpid`,`pid`,`replies`,`subject`,`content`,`like_count`,`sell_count`,`created_username`,`created_userid`,`created_time` 
                    FROM `${prefix}bbs_posts` 
                    WHERE tid IN ($tids) 
                    ORDER BY `tid` ASC,`pid` ASC; ";
            $replies = $dao->fetchAll($sql);
            foreach($replies as $v){
                $threads[$v['tid']]['replies'][] = $v;
            }

            $this->setOutput($threads,'data');
            $this->showMessage('NATIVE:data.success');
        }else{//没有最热帖子
            $this->setOutput(array(),'data');
            $this->showMessage('NATIVE:data.empty');
        }
        exit;
    }

    /**
     * 管理员设置最热帖子
     * @access public
     * @return void
      <pre>
      /index.php?m=native&c=list&a=sethot&_json=1
      post: tid=1&starttime=2011-1-1&endtime=2016-1-1
      response: {err:"",data:""}
      </pre>
     */
    public function setHotAction() {
        $uid = $this->loginUser->uid;
        $username = $this->loginUser->username;
        $tid = isset($_POST['tid']) ? $_POST['tid'] : 0;
        $starttime = isset($_POST['starttime']) && strtotime($_POST['starttime']) ? strtotime($_POST['starttime']) : 0;
        $endtime = isset($_POST['endtime']) && strtotime($_POST['endtime']) ? strtotime($_POST['endtime']) : strtotime("2038-1-19");
        $msg = '';
        if ($uid && $tid && $starttime && $endtime > $starttime) {
            $dao = $GLOBALS['acloud_object_dao'];
            $prefix = $dao->getDB()->getTablePrefix();
            $sql = "SELECT id
                    FROM `${prefix}bbs_threads_hot`
                    WHERE tid = '$tid'
                    LIMIT 1;";
            $res = $dao->fetchOne($sql);
            $id = isset($res['id']) ? $res['id'] : 0;
            if($id){//更新数据
                $updatetime = time();
                $sql = "UPDATE `${prefix}bbs_threads_hot` 
                        SET `srarttime`=$starttime,`endtime`=$endtime,`updatetime`=$updatetime
                        WHERE id=$id;";
                $res = $dao->query($sql);
                $msg = 'Modify';
            }else{//新增数据
                $createtime = time();
                $sql = "INSERT INTO `${prefix}bbs_threads_hot`
                        (`tid`,`srarttime`,`endtime`,`createtime`,`updatetime`,`created_userid`,`created_username`)
                        VALUES
                        ($tid,$starttime,$endtime,$createtime,$createtime,$uid,'$username');";
                $res = $dao->query($sql);
                $msg = 'Add';
            }
            if($res){
                $this->showMessage('NATIVE:sethot.success');
            }else{
                $this->showMessage('NATIVE:sethot.failed');
            }
        } else {
            $this->showError('NATIVE:args.error');
        }
        exit;
//        $this->forwardAction('mobile/test/test', array('arg' => 'arg1'));
    }

    /**
     * 获取我关注的话题相关帖子列表,帖子数不足时展示话题
     * @access public
     * @return string
     * @example
      <pre>
      /index.php?m=native&c=list&a=my&page=1&_json=1
      cookie:usersession
      response: {err:"",data:""}
      </pre>
     */
    public function myAction() {
        if ($this->loginUser->uid < 1) {
//            $this->forwardRedirect(WindUrlHelper::createUrl('u/login/run', array('backurl' => WindUrlHelper::createUrl('tag/index/my'))));
            echo "用户为登录";exit;
        }
        $uid = $this->loginUser->uid;     
        $dao = $GLOBALS['acloud_object_dao'];
        $prefix = $dao->getDB()->getTablePrefix();
        $sql = "SELECT `tag_id` 
                FROM `${prefix}tag_attention` 
                WHERE `uid`=$uid
                ORDER BY `last_read_time` DESC
                LIMIT 0,10;";//默认只展示10个用户关注话题
        $res = $dao->fetchAll($sql);
        $tag_ids = array();
        foreach($res as $v){
            $tag_ids[] = $v['tag_id'];
        }
        $tag_ids = implode(',', $tag_ids);
        $time_point = time()-604800;
        $sql = "SELECT DISTINCT `param_id` 
                FROM `${prefix}tag_relation` 
                WHERE `tag_id` IN ($tag_ids) 
                AND `created_time`>=$time_point;";//默认展示最近7天数据
        $res = $dao->fetchAll($sql);
        $tids = array();
        foreach($res as $v){
            $tids[] = $v['param_id'];
        }
        $tids = implode(',', $tids);
        $sql = "SELECT t.`tid`,t.`subject`,c.`content`,c.`tags`,t.`replies`,t.`hits`,t.`like_count`,t.`created_time`,t.`created_userid`,t.`created_username` 
                FROM `${prefix}bbs_threads` t 
                LEFT JOIN `${prefix}bbs_threads_content` c 
                ON t.`tid`=c.`tid` 
                WHERE t.`tid` IN ($tids)
                ORDER BY t.`created_time` DESC
                LIMIT 0,20;";
        $res = $dao->fetchAll($sql);
        if(count($res)<=3){
            //关注的话题小于3个，展示更多话题，话题的展示规则有待商量
            $sql = "SELECT `tag_id`,`tag_name` 
                    FROM `${prefix}tag` 
                    WHERE `tag_id` NOT IN ($tag_ids) LIMIT 10;";
            $res_tag = $dao->fetchAll($sql);
            $this->setOutput(array('threads'=>$res,'tags'=>$res_tag),'data');
            $this->showMessage('NATIVE:my.threads');
        }else{
            $this->setOutput($res,'data');
            $this->showMessage('NATIVE:my.threads');
        }
        exit;
    }
    
    
    /**
     *   测试用，原我的关注逻辑   
     */
    public function mytestAction() {
        if ($this->loginUser->uid < 1) {
//            $this->forwardRedirect(WindUrlHelper::createUrl('u/login/run', array('backurl' => WindUrlHelper::createUrl('tag/index/my'))));
            echo "用户为登录";exit;
        }
        $typeName = $this->defaultType;//null

        $tagServicer = $this->_getTagService();//PwTagService对象
        //获取我关注的话题列表
        $myTagsCount = $this->_getTagAttentionDs()->countAttentionByUid($this->loginUser->uid);
        if ($myTagsCount) {
            $relations = $this->_getTagDs()->getAttentionByUid($this->loginUser->uid, 0, 50);//关注的话题详细信息
            $relationTagIds = array_keys($relations);
            $myTagList = array_slice($relationTagIds, 0, 10);
            $myTagsList = $this->_getTagDs()->fetchTag($relationTagIds);
            $tmpArray = array();
            foreach ($myTagList as $v) {
                $tmpArray[$v] = $myTagsList[$v];
            }
            $myTags['tags'] = $tmpArray;
            $myTags['step'] = $myTagsCount > $this->attentionTagList ? 2 : '';
            $ifcheck = !$this->_checkAllowManage() ? 1 : '';
            $tagContents = $params = $relatedTags = array();
            $tmpTagContent = $myTags['tags'] ? array_slice($myTags['tags'], 0, 5, true) : array();
            foreach ($tmpTagContent as $k => $v) {
                $contents = $tagServicer->getContentsByTypeName($k, $typeName, $ifcheck, 0, $this->hotContents);
                if ($contents) {
                    $tagContents[$k] = $contents;
                    foreach ($contents as $k2 => $v2) {
                        $params[] = $k2;
                    }
                }
            }
            $moreTags = array_diff_key($myTagsList, $tagContents);
            $params and $relatedTags = $tagServicer->getRelatedTags($typeName, $params);
        }
        //热门话题
        $this->_setHotTagList($tagServicer->getHotTags(0, 20));
        var_dump($tagContents);exit;
        
        $this->setOutput($tagContents, 'tagContents');//相关关注话题的帖子集合
        $this->setOutput($relatedTags, 'relatedTags');
        $this->setOutput($myTags, 'myTags');//我关注的话题
        $this->setOutput($moreTags, 'moreTags');
        $this->setOutput($myTagsList, 'myTagsList');//我关注的话题
        $this->setOutput($myTagsCount, 'myTagsCount');
        //$this->setOutput($page, 'page');
        //$this->setOutput($this->perpage, 'perpage');
        // seo设置
        Wind::import('SRV:seo.bo.PwSeoBo');
        $seoBo = PwSeoBo::getInstance();
        $lang = Wind::getComponent('i18n');
        $seoBo->setCustomSeo($lang->getMessage('SEO:tag.index.my.title'), '', '');
        Wekit::setV('seo', $seoBo);
        
        $this->showError('USER:verifycode.error');
    }

    /**
     * 获取最新帖子列表
     * @access public
     * @return string
     * @example
      <pre>
      /index.php?m=native&c=list&a=new&page=1&_json=1
      response: {err:"",data:""}
      </pre>
     */
    public function newAction() {
        $dao = $GLOBALS['acloud_object_dao'];
        $prefix = $dao->getDB()->getTablePrefix();
        $sql = "SELECT t.`tid`,t.`fid`,t.`subject`,c.`content`,c.`tags`,t.`lastpost_userid`,t.`lastpost_username`,t.`hits`,t.`replies`,t.`like_count`,t.`lastpost_time` 
                FROM `${prefix}bbs_threads` t 
                LEFT JOIN `${prefix}bbs_threads_content` c 
                ON t.`tid`=c.`tid` 
                ORDER BY t.`lastpost_time` DESC 
                LIMIT 10";
        $res = $dao->fetchAll($sql);
        if($res){
            $tids = array();
            $threads = array();
            foreach($res as $v){
                $threads[$v['tid']] = $v;
                $threads[$v['tid']]['replies'] = array();
                $tids[] = $v['tid'];
            }
            $tids = implode(",", $tids);
            $sql = "SELECT `tid`,`rpid`,`pid`,`replies`,`subject`,`content`,`like_count`,`sell_count`,`created_username`,`created_userid`,`created_time` 
                    FROM `${prefix}bbs_posts` 
                    WHERE tid IN ($tids) 
                    ORDER BY `tid` ASC,`pid` ASC; ";
            $replies = $dao->fetchAll($sql);
            foreach($replies as $v){
                $threads[$v['tid']]['replies'][] = $v;
            }

            $this->setOutput($threads,'data');
            $this->showMessage('NATIVE:new.threads');
        }else{//没有数据
            $this->setOutput(array(),'data');
            $this->showMessage('NATIVE:data.empty');
        }
        exit;
    }

    
    
    /**
     * 每天定时计算帖子的权重值作业，触发条件待定
     * @access public
     * @return string
     * @example
      <pre>
      /index.php?m=mobile&c=list&a=weight
      post:
      response: {err:"",data:""}
      </pre>
     */
    public function weightAction(){
        set_time_limit(0);
        ignore_user_abort(true);
        date_default_timezone_set('Asia/Shanghai');
        $dao = $GLOBALS['acloud_object_dao'];
        $prefix = $dao->getDB()->getTablePrefix();
        $sql = "SELECT MAX(`create_time`) last_create_time FROM `${prefix}bbs_threads_weight`;";
        $res = $dao->fetchOne($sql);
        $last_create_time = isset($res['last_create_time']) && $res['last_create_time'] ? $res['last_create_time'] : 0;
        $current_hour = intval(date("H"));
        if($current_hour >= 1 && $current_hour<= 8 && time() > ($last_create_time+36000)){//作业的触发距离最后一条记录生成要大于10小时
//        if(1){//测试
            //执行权重计算逻辑
            $sql = "DELETE FROM `${prefix}bbs_threads_weight`;";//删除旧表数据
            $dao->query($sql);
            $current_time = time();
            $stop_time = $current_time-604800;//获取7天前的数据进行计算
//            $stop_time = $current_time-1604800;//获取更早前的数据
            $sql = "SELECT COUNT(*) count 
                    FROM `${prefix}bbs_threads`
                    WHERE `created_time`>$stop_time;";
            $res = $dao->fetchOne($sql);
            $threads_count = $res['count'];
            $num = 20;//一次处理的记录数
            $pages = ceil($threads_count/$num);        
            for($i=1;$i<=$pages;$i++){
                $page = $i;
                $start = ($page-1)*$num;//开始位置偏移
                $sql = "SELECT t.`tid`,t.`replies`,t.`like_count`,t.`lastpost_time`,t.`created_time`,IFNULL(SUM(p.`like_count`),0) reply_like_count 
                        FROM `${prefix}bbs_threads` t 
                        LEFT JOIN `${prefix}bbs_posts` p 
                        ON t.`tid`=p.`tid` 
                        WHERE t.`created_time`>$stop_time
                        GROUP BY t.`tid`
                        LIMIT $start,$num;";
                $res = $dao->fetchAll($sql);
                $weight_values = array();
                if($res){
                    foreach($res as $k=>$v){
                        $weight = $v['like_count']*2+
                                  $v['replies']*4+
                                  $v['reply_like_count']+
                                  floor(($current_time-$v['lastpost_time'])/86400)*-4+
                                  floor(($current_time-$v['created_time'])/86400)*-20;
                        $weight_values[] = "({$v['tid']},$weight,$current_time,1)";
                    }
                    $weight_values = implode(',', $weight_values);
                    //将权重计算结果插入权重表
                    $sql = "INSERT INTO `${prefix}bbs_threads_weight` (`tid`,`weight`,`create_time`,`isenable`) VALUES $weight_values;";
                    $dao->query($sql);
                }
            }
            //对管理员设置的热帖去重处理
            $sql = "DELETE FROM `${prefix}bbs_threads_weight` 
                    WHERE `tid` IN 
                        (SELECT `tid` 
                         FROM `${prefix}bbs_threads_hot` 
                         WHERE $current_time>`srarttime` AND $current_time<`endtime`);";
            $dao->query($sql);
            //只保留权重最高的500条记录
            $sql = "SELECT `weight` FROM `${prefix}bbs_threads_weight` ORDER BY `weight` DESC LIMIT 499,1";
            $res = $dao->fetchOne($sql);            
            if($res){
                $weight = $res['weight'];
                $sql = "DELETE FROM `${prefix}bbs_threads_weight` WHERE `weight`<$weight;";
                $dao->query($sql);
            }
            echo "SCRIPT EXCUTE FINISHED";
        }else{
            echo "SCRIPT IS EXCUTED TODAY";exit;
        }
        exit;
    }
    
    
    /**
     * @return PwTagService
     */
    private function _getTagService() {
        return Wekit::load('tag.srv.PwTagService');
    }
    
    
    /**
     * 关注DS
     *
     * @return PwTagAttention
     */
    private function _getTagAttentionDs() {
        return Wekit::load('tag.PwTagAttention');
    }

    /**
     * @return PwTag
     */
    private function _getTagDs() {
        return Wekit::load('tag.PwTag');
    }
    
    /**
     * 设置热门话题
     *
     * @return void
     */
    private function _setHotTagList($hotTags) {
        $hotTags = array_slice($hotTags, 0, $this->hotTagList);
        $this->setOutput($hotTags, 'hotTagList');
    }

}
