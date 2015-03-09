<?php

/**
 * 生活服务相关接口
 *
 * @fileName: LifeController.php
 * @author: yuliang<yuliang.lyl@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-16 19:08:17
 * @desc: 
 * */
defined('WEKIT_VERSION') || exit('Forbidden');

Wind::import('APPS:native.controller.NativeBaseController');

class LifeController extends NativeBaseController {

    private $perpage = 30;

    public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);
//		if (!$this->loginUser->isExists()) $this->showError('VOTE:user.not.login');
//        $this->uid = 1;//测试uid
        $this->loginUser = new PwUserBo($this->uid);
        $this->loginUser->resetGid($this->loginUser->gid);
    }

   
    /**
     * 生活服务板块列表
     * @access public
     * @return string
     <pre>
     /index.php?m=native&c=life&page=1&_json=1
     response: 
     </pre>
     */
    public function run(){
        list($page) = $this->getInput(array('page'), 'get');
        $page = intval($page) > 1 ? intval($page) : 1;
        $pos = ($page-1)*$this->perpage;
        //获取生活服务版块列表
        $forum_list = Wekit::loadDao('native.dao.PwNativeForumDao')->fetchForumLifeList($pos,1000);
        if(empty($forum_list)){
            $data = array('user_info'=>array('uid'=>$this->uid),'forum_list'=>array());
            $this->setOutput($data,'data');
            $this->showMessage('NATIVE:data.success');
        }
        $fids = array_keys($forum_list);
        //生活服务扩展表数据
        $forum_life_list = Wekit::loadDao('native.dao.PwForumLifeDao')->fetchForumLife($fids);
        $join_forum_life = $unjoin_forum_life = $user_fids = $join_vieworder = $unjoin_vieworder = array();
        if ($this->uid) {//此处涉及用户登录状态判断,获取用户关注的版块
            $forumUserDao = Wekit::loadDao('forum.dao.PwForumUserDao');
            $user_fids = $forumUserDao->getFroumByUid($this->uid);
        }
        foreach($forum_list as $k=>$v){
            $forum_list[$k]['name'] = strip_tags($v['name']);
            $forum_list[$k]['descrip'] = strip_tags($v['descrip']);
            $forum_list[$k]['address'] = isset($forum_life_list[$k]['address']) ? $forum_life_list[$k]['address'] : '';
            $forum_list[$k]['url'] = isset($forum_life_list[$k]['url']) ? $forum_life_list[$k]['url'] : '';
            if (array_key_exists($k, $user_fids)) {//版面fid是用户关注的
                $join_vieworder[] = $v['vieworder'];
                $join_forum_life[$k] = $forum_list[$k];
                $join_forum_life[$k]['isjoin'] = true;
            }else{
                $unjoin_vieworder[] = $v['vieworder'];
                $unjoin_forum_life[$k] = $forum_list[$k];
                $unjoin_forum_life[$k]['isjoin'] = false;
            }
        }
        $join_forum_life && array_multisort($join_vieworder, SORT_ASC, $join_forum_life);
        $unjoin_forum_life && array_multisort($unjoin_vieworder, SORT_ASC, $unjoin_forum_life);
        $forum_list_merge = array_merge($join_forum_life, $unjoin_forum_life);
//        var_dump($join_forum_life,$unjoin_forum_life,$forum_list_merge);exit;
//        var_dump($fids_native,$user_fids,$forum_list_merge);
        $data = array('user_info'=>array('uid'=>$this->uid),'forum_list'=>$forum_list_merge);
        $this->setOutput($data,'data');
        $this->showMessage('NATIVE:data.success');   
    }
   
    /**
     * 获取某个生活服务版块下的帖子列表
     * @access public
     * @return string
     <pre>
     /index.php?m=native&c=life&a=life&fid=生活服务分类id&page=1&_json=1
     response: 
     </pre>
     */
    public function lifeAction(){
        list($page,$fid) = $this->getInput(array('page','fid'), 'get');
        !$fid && $this->showError('NATIVE:args.error');
        $page = intval($page) > 1 ? intval($page) : 1;
        $pos = ($page-1)*$this->perpage;
        //获取单个版块信息
        $forum = Wekit::loadDao('forum.dao.PwForumDao')->getForum($fid);
        $forum_life = Wekit::loadDao('native.dao.PwForumLifeDao')->getForumLife($fid);
        $forum['address'] = isset($forum_life['address']) ? $forum_life['address'] : '';
        $forum['url'] = isset($forum_life['url']) ? $forum_life['url'] : '';
        
        $user_fids = array();
        if ($this->uid) $user_fids = Wekit::loadDao('forum.dao.PwForumUserDao')->getFroumByUid($this->uid);
        $isjoin = array_key_exists($fid,$user_fids) ? true : false;
        $count = Wekit::loadDao('forum.dao.PwThreadsDao')->countThreadByFidAndType($fid, 0);
        $threads = Wekit::loadDao('forum.dao.PwThreadsDao')->getThreadByFid($fid, $this->perpage, $pos);
        $tids = array_keys($threads);
        $threads_list = Wekit::load('native.srv.PwDynamicService')->fetchThreadsList($tids,$this->uid);
//        $result = array('forumInfo'=>$forum,'threadsList'=>$threads_list);
//        var_dump($result);exit;
        ($max_page = ceil($count/$this->perpage))||$max_page=1;
        $page_info = array('page'=>$page,'perpage'=>$this->perpage,'count'=>$count,'max_page'=>$max_page);
        $data = array('page_info'=>$page_info,'user_info'=>array('uid'=>$this->uid,'isjoin'=>$isjoin),'forum_info'=>($page==1?$forum:''),'threads_list'=>$threads_list);
        $this->setOutput($data,'data');
        $this->showMessage('NATIVE:data.success');
        exit;
        /* 测试pw提供获取帖子内容service */
        $thread_content = Wekit::load('forum.PwThread')->fetchThread(array(26,45,43,44), PwThread::FETCH_ALL);//PW 提供的获取帖子内容方法
        var_dump($thread_content);exit;PW::getTime();//PW工具类
//        $thread_content = Wekit::loadDao('forum.dao.PwThreadsContentDao')->getThread(81);
//        var_dump($thread_content);exit;
    }
    
    

}
