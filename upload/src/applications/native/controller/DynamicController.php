<?php

/**
 * 动态帖子列表相关接口
 *
 * @fileName: DynamicController.php
 * @author: yuliang<yuliang.lyl@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-16 19:08:17
 * @desc: 
 * */
defined('WEKIT_VERSION') || exit('Forbidden');

Wind::import('APPS:native.controller.NativeBaseController');

class DynamicController extends NativeBaseController {

    private $perpage = 30;

    public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);
//		if (!$this->loginUser->isExists()) $this->showError('VOTE:user.not.login');
        $this->uid = 1;//测试uid
        $this->loginUser = new PwUserBo($this->uid);
        $this->loginUser->resetGid($this->loginUser->gid);
    }

    
    
    /**
     * 获取最热帖子列表
     * @access public
     * @return string
      <pre>
      /index.php?m=native&c=dynamic&a=hot&page=1&_json=1
      response: {err:"",data:""}
      </pre>
     */
    public function hotAction() {
        $num = $this->perpage;//一页显示记录数
        $page = isset($_GET['page']) && intval($_GET['page'])>=1 ? intval($_GET['page']) : 1;//第几页，从请求参数获取
        $start_pos = ($page-1)*$num;
        $count = Wekit::loadDao('native.dao.PwNativeThreadsDao')->fetchHotThreadCount();
        $tids = Wekit::loadDao('native.dao.PwNativeThreadsDao')->fetchHotThreadTids($start_pos,$num);//按最后回复时间排序、只展示移动端可显示版块数据，不包含生活服务
        $threads_list = Wekit::load('native.srv.PwDynamicService')->fetchThreadsList($tids);
        $page_info = array('page'=>$page,'perpage'=>$num,'count'=>$count,'max_page'=>ceil($count/$num));
        $data = array('page_info'=>$page_info,'user_info'=>array('uid'=>$this->uid),'threads_list'=>$threads_list);
        $this->setOutput($data,'data');
        $this->showMessage('NATIVE:data.success');
        
        /*
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
            $this->setOutput($res,'data');
            $this->showMessage('NATIVE:data.success');
//            $this->showError('USER:verifycode.error');
        }else{//没有最热帖子
            $this->setOutput(array(),'data');
            $this->showMessage('NATIVE:data.empty');
        }
         * 
         */
        
    }

    /**
     * 管理员设置最热帖子
     * @access public
     * @return void
      <pre>
      /index.php?m=native&c=dynamic&a=sethot&_json=1
      post: tid=1&starttime=2011-1-1&endtime=2016-1-1
      response: {err:"",data:""}
      </pre>
     */
    public function setHotAction() {
        $tid = $this->getInput('tid');
        $tid = (int)$tid;
        if ($this->uid && $tid) {
            $threadsWeightDao = Wekit::loadDao('native.dao.PwThreadsWeightDao');

            //获取帖子最高权重，将其作为管理员推送帖子的初始权重置顶
            $weightData = $threadsWeightDao->getMaxWeight();
            isset($weightData['weight']) ? $max_weight = intval($weightData['weight'])+1:1;

            //
            $data = array(
                'create_time'   =>time(),
                'weight'        =>$max_weight,
                'create_userid' =>$this->uid,
                'create_username'=>$this->loginUser->username,
                'tid'           =>$tid,
            );
            $threadWeight = $threadsWeightDao->getByTid($tid);
            if($threadWeight){//更新数据
                $res = $threadsWeightDao->updateValue($data);
            }else{//新增数据
                $res = $threadsWeightDao->insertValue($data);
            }
            if($res){
                $this->showMessage('NATIVE:sethot.success');
            }else{
                $this->showMessage('NATIVE:sethot.failed');
            }
        } else {//传参有误
            $this->showError('NATIVE:args.error');
        }
        $this->showError('fail');
    }

    /**
     * 获取我关注的话题相关帖子列表,帖子数不足时展示话题
     * @access public
     * @return string
     * @example
      <pre>
      /index.php?m=native&c=dynamic&a=my&page=1&_json=1
      cookie:usersession
      response: {err:"",data:""}
      </pre>
     */
    public function myAction() {
        if ($this->uid < 1) {//用户未登陆展示20个热点话题
//            $this->forwardRedirect(WindUrlHelper::createUrl('u/login/run', array('backurl' => WindUrlHelper::createUrl('tag/index/my'))));
            $hotTags = $this->getHotTags();
            $data = array('page_info'=>array(),'user_info'=>array(),'tag_info'=>$hotTags,'threads_list'=>array());
            $this->setOutput($data,'data');
            $this->showMessage('NATIVE:data.success');
            exit;
        }
        $uid = $this->uid;     
        $tagAttentionDao = Wekit::loadDao('tag.dao.PwTagAttentionDao');
        $res = $tagAttentionDao->getByUid($uid,50);//获得用户关注的话题最大50个
        $tag_ids = array();
        foreach($res as $v){
            $tag_ids[] = $v['tag_id'];
        }
        $joined_tag = $tag_ids ? true : false;
//        $tag_ids = implode(',', $tag_ids);
        $time_point = time()-604800;
        //分页计算
        $num = $this->perpage;//一页显示记录数
        $page = isset($_GET['page']) && intval($_GET['page'])>=1 ? intval($_GET['page']) : 1;//第几页，从请求参数获取
        $pos = ($page-1)*$num;
        $count = Wekit::loadDao('native.dao.PwNativeTagRelationDao')->getCount($tag_ids,$time_point);
        $res = Wekit::loadDao('native.dao.PwNativeTagRelationDao')->fetchTids($tag_ids,$time_point,$pos,$num);//根据用户关注的话题tag_id获取文章tids，只筛选移动端可展示版块数据
        $tids = array();
        foreach($res as $v){
            $tids[] = $v['param_id'];
        }
        
        $threads_list = Wekit::load('native.srv.PwDynamicService')->fetchThreadsList($tids);
//        var_dump($tids,$threads_list);exit;
        $hot_tags = array();
        if(count($res)<5 && $page==1){
            $hot_tags = $this->getHotTags();
        }
        $page_info = array('page'=>$page,'perpage'=>$num,'count'=>$count,'max_page'=>ceil($count/$num));
        $data = array('page_info'=>$page_info,'user_info'=>array('uid'=>$this->uid,'joined_tag'=>$joined_tag),'tag_info'=>$hot_tags,'threads_list'=>$threads_list);
        $this->setOutput($data,'data');
        $this->showMessage('NATIVE:data.success');

    }
    

    /**
     * 获取最新帖子列表
     * @access public
     * @return string
     * @example
      <pre>
      /index.php?m=native&c=dynamic&a=new&page=1&_json=1
      response: {err:"",data:""}
      </pre>
     */
    public function newAction() {
        //分页计算
        $num = $this->perpage;//一页显示记录数
        $page = isset($_GET['page']) && intval($_GET['page'])>=1 ? intval($_GET['page']) : 1;//第几页，从请求参数获取
        $pos = ($page-1)*$num;
//        $nativeThreadsDao = Wekit::loadDao('native.dao.PwNativeThreadsDao');
//        $threads = $nativeThreadsDao->fetchNewThreadTids($pos,$num);
        $count = Wekit::loadDao('native.dao.PwNativeThreadsDao')->getNewThreadCount();
        $tids = Wekit::loadDao('native.dao.PwNativeThreadsDao')->fetchNewThreadTids($pos,$num);//按照发帖时间排序，只筛选移动端版块展示数据
        $threads_list = Wekit::load('native.srv.PwDynamicService')->fetchThreadsList($tids);
        
        $page_info = array('page'=>$page,'perpage'=>$num,'count'=>$count,'max_page'=>ceil($count/$num));
        $data = array('page_info'=>$page_info,'user_info'=>array('uid'=>$this->uid),'threads_list'=>$threads_list);
//        print_r($data);exit;
        $this->setOutput($data,'data');
        $this->showMessage('NATIVE:data.success');
        
        /*
        if($res){//列表页不展示回帖信息
            
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
            
//            var_dump($threads);exit;
//            echo json_encode(array('error'=>'','data'=>$threads));
//            var_dump($res);exit;
            $this->setOutput($res,'data');
            $this->showMessage('NATIVE:new.threads');
        }else{//没有数据
//            echo json_encode(array('error'=>'','data'=>array()));
            $this->setOutput(array(),'data');
            $this->showMessage('NATIVE:data.empty');
        }
         * 
         */
    }

    /**
     * 获取同城帖子列表
     * @access public
     * @return string
     * @example
      <pre>
      /index.php?m=native&c=dynamic&a=city&city=aaa&page=1&_json=1
      cookie:usersession
      response: {err:"",data:""}
      </pre>
     */
    public function cityAction() {
        //分页计算
        $num = $this->perpage;//一页显示记录数
        $page = isset($_GET['page']) && intval($_GET['page'])>=1 ? intval($_GET['page']) : 1;//第几页，从请求参数获取
        $city = isset($_GET['city']) ? $_GET['city'] : '';
        $pos = ($page-1)*$num;
        $count = Wekit::loadDao('native.dao.PwNativeThreadsDao')->getCityThreadCount($city);
        $tids = Wekit::loadDao('native.dao.PwNativeThreadsDao')->fetchCityThreadTids($city,$pos,$num);//按照最后回复时间排序，只筛选移动端版块数据
        $threads_list = Wekit::load('native.srv.PwDynamicService')->fetchThreadsList($tids);
//        var_dump($tids,$threads_list);exit;
        $page_info = array('page'=>$page,'perpage'=>$num,'count'=>$count,'max_page'=>ceil($count/$num));
        $data = array('page_info'=>$page_info,'user_info'=>array('uid'=>$this->uid),'threads_list'=>$threads_list);
        $this->setOutput($data,'data');
        $this->showMessage('NATIVE:data.success');
    }
    
    
    /**
     * 每天定时计算帖子的权重值作业，此方法没用，已加入计划任务
     * @access public
     * @return string
     * @example
      <pre>
      /index.php?m=mobile&c=dynamic&a=weight
      post:
      response: {err:"",data:""}
      </pre>
     */
    public function weightAction(){
//        echo ini_get('date.timezone')."<br>";
//        echo date_default_timezone_get()."<br>";
//        echo time()."<br>";
//        echo date("Y-m-d H:i:s",time())."<br>";
//        date_default_timezone_set('Asia/Shanghai');
//        ini_set('date.timezone','Asia/Shanghai');
//        echo ini_get('date.timezone')."<br>";
//        echo date_default_timezone_get()."<br>";
//        echo time()."<br>";
//        echo date("Y-m-d H:i:s",time())."<br>";
//        exit;
//        echo date("Y-m-d H:i:s",1419302691);exit;
//        var_dump(101/20,ceil(101/20));exit;
        set_time_limit(0);
        ignore_user_abort(true);
        date_default_timezone_set('Asia/Shanghai');
        $threadsWeightDao = Wekit::loadDao('native.dao.PwThreadsWeightDao');
        $res = $threadsWeightDao->getMaxCreateTime();
        $last_create_time = isset($res['last_create_time']) && $res['last_create_time'] ? $res['last_create_time'] : 0;
        $current_hour = intval(date("H"));
//        if($current_hour >= 1 && $current_hour<= 8 && time() > ($last_create_time+36000)){//作业的触发距离最后一条记录生成要大于10小时
        if(1){//测试
            //执行权重计算逻辑
            $current_time = time();
            $stop_time = $current_time-604800;//获取7天前的数据进行计算
            $threadsWeightDao->deleteAutoData();//删除自动生成热帖数据
            $threadsWeightDao->deleteUserData($stop_time);//删除推荐的过期热帖数据
//            $stop_time = $current_time-1604800;//获取更早前的数据
//            echo $stop_time;exit;
            $nativeThreadsDao = Wekit::loadDao('native.dao.PwNativeThreadsDao');
            //从论坛帖子列表获取指定时间内的帖子条数
            $res = $nativeThreadsDao->getCountByTime($stop_time);
            $threads_count = intval($res['count']);
            $threads_count = $threads_count>1000 ? 1000 : $threads_count;//权重计算默认只取1000条
            $num = 50;//一次处理的记录数
            $pages = ceil($threads_count/$num);
            //计算热帖的自然权重值，并将结果插入权重表
            for($i=1;$i<=$pages;$i++){
//                $starttime_test = time();
                $page = $i;
                $start = ($page-1)*$num;//开始位置偏移
                $res = $nativeThreadsDao->fetchThreadsData($stop_time,$start,$num);
                $weight_values = array();
                if($res){
                    foreach($res as $k=>$v){
                        $weight = $v['like_count']*2+
                                  $v['replies']*4+
                                  $v['reply_like_count']+
                                  floor(($current_time-$v['lastpost_time'])/86400)*-4+
                                  floor(($current_time-$v['created_time'])/86400)*-20;
//                        $res[$k]['weight'] = $weight;
                        $weight_values[] = "({$v['tid']},$weight,$current_time,1)";
                    }
                    $weight_values = implode(',', $weight_values);
                    //将权重计算结果插入权重表,表中已有数据不再重复插入
                    $threadsWeightDao->insertValues($weight_values);
                }
            }
            //获取权重表中管理员设置的热帖数量
            $threads_count = $threadsWeightDao->getUserDataCount();
            $threads_count = isset($threads_count['count']) ? intval($threads_count['count']) : 0;
            $pages = ceil($threads_count/$num);
            //将管理员设置的热帖进行自然权重计算并更新数据
            for($i=1;$i<=$pages;$i++){
//                $starttime_test = time();
                $page = $i;
                $start = ($page-1)*$num;//开始位置偏移
                $res = $threadsWeightDao->fetchUserThreadsData($start,$num);//获取管理员设置的热帖数据计算权重
                $weight_values = array();
                if($res){
                    foreach($res as $k=>$v){
                        $weight = $v['like_count']*2+
                                  $v['replies']*4+
                                  $v['reply_like_count']+
                                  floor(($current_time-$v['lastpost_time'])/86400)*-4+
                                  floor(($current_time-$v['create_time'])/86400)*-20;
                        $weight_values[] = "({$v['tid']},$weight,{$v['create_time']},{$v['create_userid']},'{$v['create_username']}',{$v['isenable']})";
                    }
                    $weight_values = implode(',', $weight_values);
                    //将权重计算结果插入权重表,表中已有数据不再重复插入
                    $threadsWeightDao->replaceValues($weight_values);
                }
            }
            //对推荐不到2小时的数据继续置顶
            $max_weight = $threadsWeightDao->getMaxWeight();
            $max_weight = isset($max_weight['weight']) ? intval($max_weight['weight'])+1 : 1;
            $threadsWeightDao->batchUpdateUserWeight($current_time-7200,$max_weight);
            //只保留100条非用户推荐的自然计算数据
            $res = $threadsWeightDao->getWeightByPos(99);    
            if($res){
                $weight = $res['weight'];
                $threadsWeightDao->deleteByWeight($weight);
            }
            echo "SCRIPT EXCUTE FINISHED";
        }else{
            echo "SCRIPT IS EXCUTED TODAY";
        }
        exit;
    }
    
    private function cmp($a, $b) {
        return strcmp($b["weight"], $a["weight"]);
    }
    
    private function getHotTags() {
        $hotTags = Wekit::load('tag.srv.PwTagService')->getHotTagsNoCache(0, 20);
        $tagIds = array();
        foreach ($hotTags as $k => $v) {
            $attentions = Wekit::load('tag.PwTagAttention')->getAttentionUids($k, 0, 5);
            $hotTags[$k]['weight'] = 0.7 * $v['content_count'] + 0.3 * $v['attention_count'];
            $hotTags[$k]['attentions'] = array_keys($attentions);
            $tagIds[] = $k;
        }
        usort($hotTags, array($this, 'cmp'));
        return $hotTags;
    }
	
   

}
