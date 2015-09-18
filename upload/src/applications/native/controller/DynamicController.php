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
//ini_set("display_errors", "On");
//error_reporting(E_ALL & ~E_STRICT);
//error_reporting(E_ERROR | E_PARSE);
//error_reporting(E_ALL | E_STRICT);
//error_reporting(E_ALL);
class DynamicController extends NativeBaseController {

    private $perpage = 30;

    public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);
        $this->loginUser = new PwUserBo($this->uid);
        $this->loginUser->resetGid($this->loginUser->gid);
    }
    
    /**
     * 获取最热帖子列表
     * @access public
     * @return string
      <pre>
      /index.php?m=native&c=dynamic&a=hot&page=1&timestamp=1000000&_json=1
      response: {err:"",data:""}
      </pre>
     */
    public function hotAction() {
        $num = $this->perpage;//一页显示记录数
        $page = isset($_GET['page']) && intval($_GET['page'])>=1 ? intval($_GET['page']) : 1;//第几页，从请求参数获取
        $timestamp = isset($_GET['timestamp']) && intval($_GET['timestamp'])>0 ? intval($_GET['timestamp']) : 0;//作业最近一次执行的时间
        $timestamp = 0;//去掉时间戳，暂时关闭前端缓存
        $start_pos = ($page-1)*$num;
        $modified_time = 0;
        if($timestamp){
            $weight_cron = Wekit::loadDao('native.dao.PwNativeCronDao')->getByFilename("PwCronDoUpdateWeight");
            $modified_time = isset($weight_cron['modified_time']) ? intval($weight_cron['modified_time']) : 0;
        }
        
        $count = Wekit::loadDao('native.dao.PwNativeThreadsDao')->fetchHotThreadCount();
        if($timestamp && $timestamp==$modified_time){//没有数据更新
            $threads_list = array();
        }else{//有数据更新
            $tids = Wekit::loadDao('native.dao.PwNativeThreadsDao')->fetchHotThreadTids($start_pos,$num);//按最后回复时间排序、只展示移动端可显示版块数据，不包含生活服务
            $threads_list = Wekit::load('native.srv.PwDynamicService')->fetchThreadsList($tids,$this->uid);
        }
        ($max_page = ceil($count/$num))||$max_page=1;
        $page_info = array('page'=>$page,'perpage'=>$num,'count'=>$count,'max_page'=>$max_page);
        $data = array('page_info'=>$page_info,'user_info'=>array('uid'=>$this->uid),'threads_list'=>$threads_list,'modified_time'=>$modified_time);
        $this->setOutput($data,'data');
        $this->showMessage('NATIVE:data.success');
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

        if ($this->uid && $tid && array_search(3,$this->loginUser->groups)!==false ) {
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
                $thread = Wekit::load('forum.PwThread')->getThread($tid);
                $push_msg = '《'.$thread['subject'].'》已被推荐热贴'; 
                PwLaiWangSerivce::pushMessage($thread['created_userid'], $push_msg, $push_msg); 
                //
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
            $page_info = array('page'=>1,'perpage'=>$this->perpage,'count'=>0,'max_page'=>1);
            $data = array('page_info'=>$page_info,'user_info'=>array('uid'=>0),'tag_info'=>$hotTags,'threads_list'=>array());
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
        
        $threads_list = Wekit::load('native.srv.PwDynamicService')->fetchThreadsList($tids,$this->uid);
//        var_dump($tids,$threads_list);exit;
        $hot_tags = array();
        if(count($res)<5 && $page==1){
            $hot_tags = $this->getHotTags();
        }
        ($max_page = ceil($count/$num))||$max_page=1;
        $page_info = array('page'=>$page,'perpage'=>$num,'count'=>$count,'max_page'=>$max_page);
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
      /index.php?m=native&c=dynamic&a=new&page=1&maxid=100&_json=1
      response: {err:"",data:""}
      </pre>
     */
    public function newAction() {
        //分页计算
        $num = $this->perpage;//一页显示记录数
        $page = isset($_GET['page']) && intval($_GET['page'])>=1 ? intval($_GET['page']) : 1;//第几页，从请求参数获取
        $pos = ($page-1)*$num;
        $maxid = isset($_GET['maxid']) && $_GET['maxid'] ? intval($_GET['maxid']) : 0;
//        $nativeThreadsDao = Wekit::loadDao('native.dao.PwNativeThreadsDao');
//        $threads = $nativeThreadsDao->fetchNewThreadTids($pos,$num);
        $count = Wekit::loadDao('native.dao.PwNativeThreadsDao')->getNewThreadCount();
        $tids = Wekit::loadDao('native.dao.PwNativeThreadsDao')->fetchNewThreadTids($pos,$num);//按照发帖时间排序，只筛选移动端版块展示数据
        if($maxid && $maxid==max($tids)){//没有新数据
            $threads_list = array();
            $updated = false;
        }else{
            $threads_list = Wekit::load('native.srv.PwDynamicService')->fetchThreadsList($tids,$this->uid);
            $updated = true;
        }
          
        ($max_page = ceil($count/$num))||$max_page=1;
        $page_info = array('page'=>$page,'perpage'=>$num,'count'=>$count,'max_page'=>$max_page);
        $data = array('page_info'=>$page_info,'user_info'=>array('uid'=>$this->uid),'threads_list'=>$threads_list,'updated'=>$updated);
//        print_r($data);exit;
        $this->setOutput($data,'data');
        $this->showMessage('NATIVE:data.success');
        
        
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
        $threads_list = Wekit::load('native.srv.PwDynamicService')->fetchThreadsList($tids,$this->uid);
//        var_dump($tids,$threads_list);exit;
        ($max_page = ceil($count/$num))||$max_page=1;
        $page_info = array('page'=>$page,'perpage'=>$num,'count'=>$count,'max_page'=>$max_page);
        $data = array('page_info'=>$page_info,'user_info'=>array('uid'=>$this->uid),'threads_list'=>$threads_list);
        $this->setOutput($data,'data');
        $this->showMessage('NATIVE:data.success');
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
