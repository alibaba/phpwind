<?php

/**
 * 版块下帖子列表相关
 *
 * @fileName: ThreadController.php
 * @author: yuliang<yuliang.lyl@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-16 19:08:17
 * @desc: 
 * */

defined('WEKIT_VERSION') || exit('Forbidden');

Wind::import('SRV:forum.bo.PwForumBo');
//Wind::import('SRV:forum.srv.PwThreadList');
Wind::import('SRV:native.srv.PwNativeThreadList');
Wind::import('APPS:native.controller.NativeBaseController');

/**
 * 获取某个板块下的帖子列表接口
 *
 * @fileName: ThreadController.php
 * @author: yuliang<yuliang.lyl@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-16 19:08:17
 * @desc: 
 **/

class ThreadController extends NativeBaseController {

    protected $topictypes;
    protected $perpage = 30;

    public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);
        $this->loginUser = new PwUserBo($this->uid);
        $this->loginUser->resetGid($this->loginUser->gid);
    }

    /**
     * 获取某个板块下的帖子列表（标准、精华）
     * @access public
     * @return string
     <pre>
     /index.php?m=native&c=thread&fid=板块id
     获取精华帖：
     /index.php?m=native&c=thread&fid=板块id&tab=digest
     response: html
     </pre>
     */
    public function run() {
        //某个模板下的帖子列表页
        $tab = $this->getInput('tab');//是否是精华帖
        $fid = intval($this->getInput('fid'));//分类id
        $type = intval($this->getInput('type','get')); //主题分类ID
        $page = $this->getInput('page', 'get');
        !$page && $page=1;
        $orderby = $this->getInput('orderby', 'get');
        // 		var_dump($tab,$fid,$type,$page,$orderby);exit;//null,2,0,null,null
        $pwforum = new PwForumBo($fid, true);//板块信息
        //		var_dump($pwforum);exit;
        if (!$pwforum->isForum()) {
            $this->showError('BBS:forum.exists.not');
        }
        if ($pwforum->allowVisit($this->loginUser) !== true) {
            $this->showError(array('BBS:forum.permissions.visit.allow', array('{grouptitle}' => $this->loginUser->getGroupInfo('name'))));
        }
        if ($pwforum->forumset['jumpurl']) {
            $this->forwardRedirect($pwforum->forumset['jumpurl']);
        }
//        var_dump($pwforum);exit;
        if ($pwforum->foruminfo['password']) {//设置了版块访问密码
            if (!$this->loginUser->isExists()) {
//                $this->forwardAction('u/login/run', array('backurl' => WindUrlHelper::createUrl('bbs/cate/run', array('fid' => $fid))));
                $this->showError('该版块为加密版块您需要先登录才能访问！');
            } else if(!isset($_GET['password'])||empty($_GET['password'])){//提示输入密码
                $data = array('page_info'=>array(),'user_info'=>array('uid'=>$this->uid,'isjoin'=>$forum_isjoin,'forum_login'=>0),'forum_info'=>'','threads_list'=>array());
                $this->setOutput($data,'data');
                $this->showMessage('NATIVE:data.success');
            }elseif (Pw::getPwdCode($pwforum->foruminfo['password']) != Pw::getPwdCode(md5($_GET['password']))) {//密码错误
//                $this->forwardAction('bbs/forum/password', array('fid' => $fid));
                $data = array('page_info'=>array(),'user_info'=>array('uid'=>$this->uid,'isjoin'=>$forum_isjoin,'forum_login'=>1),'forum_info'=>'','threads_list'=>array());
                $this->setOutput($data,'data');
                $this->showMessage('NATIVE:data.success');
            } 
        }
        $isBM = $pwforum->isBM($this->loginUser->username);//检测用户是否是版主
        if ($operateThread = $this->loginUser->getPermission('operate_thread', $isBM, array())) {
            $operateThread = Pw::subArray($operateThread, array('topped', 'digest', 'highlight', 'up', 'copy', 'type', 'move', /*'unite',*/ 'lock', 'down', 'delete', 'ban'));
        }
        $this->_initTopictypes($fid, $type);

        $threadList = new PwNativeThreadList();//帖子列表对象
        //		var_dump($threadList);exit;
        $this->runHook('c_thread_run', $threadList);

        $threadList->setPage($page)
            ->setPerpage($this->perpage)//帖子列表页一页展示30条
            //			->setPerpage($pwforum->forumset['threadperpage'] ? $pwforum->forumset['threadperpage'] : Wekit::C('bbs', 'thread.perpage'))
            ->setIconNew($pwforum->foruminfo['newtime']);
        //		var_dump($page,$pwforum);exit;//null,
        $defaultOrderby = $pwforum->forumset['threadorderby'] ? 'postdate' : 'lastpost';
        !$orderby && $orderby = $defaultOrderby;

        if ($tab == 'digest') {
            Wind::import('SRV:forum.srv.threadList.PwDigestThread');
            $dataSource = new PwDigestThread($pwforum->fid, $type, $orderby);
        } elseif ($type) {
            Wind::import('SRV:forum.srv.threadList.PwSearchThread');
            $dataSource = new PwSearchThread($pwforum);
            $dataSource->setOrderby($orderby);
            $dataSource->setType($type, $this->_getSubTopictype($type));
        } elseif ($orderby == 'postdate') {
            Wind::import('SRV:forum.srv.threadList.PwNewForumThread');
            $dataSource = new PwNewForumThread($pwforum);
        } else {
//            Wind::import('SRV:forum.srv.threadList.PwCommonThread');
            Wind::import('SRV:native.srv.PwNativeCommonThread');
            $dataSource = new PwNativeCommonThread($pwforum);//帖子列表数据接口
        }
        //                 var_dump($dataSource);exit;//PwCommonThread对象
        $orderby != $defaultOrderby && $dataSource->setUrlArg('orderby', $orderby);
        $threadList->execute($dataSource);
        //需要合并移动端扩展表数据以及内容数据
        $tids = $topped_tids = array();
        foreach($threadList->threaddb as $v){
            $tids[] = $v['tid'];
            strpos($v['icon'], headtopic_)!==false && $topped_tids[$v['tid']] = '';
        }
        $threads_list = Wekit::load('native.srv.PwDynamicService')->fetchThreadsList($tids,$this->uid,"NUM");
        
        foreach($threads_list as $k => $v){
            if($v['topped'] > 0 && array_key_exists($v['tid'], $topped_tids)){
                $threads_list[$k]['topped_priority'] = 1;
                unset($topped_tids[$v['tid']]);
            }else{
                $threads_list[$k]['topped_priority'] = 0;
            }
        }
        $count = $threadList->total;
        $forum_isjoin = $pwforum->isJoin($this->uid);
        $pwforum->foruminfo['name'] = strip_tags($pwforum->foruminfo['name']);
        //                var_dump($forum_isjoin);exit;
        //                var_dump($threadList);exit;//帖子列表数据
        //                var_dump(get_class($pwforum),get_class_methods($pwforum));exit;
        //                var_dump($pwforum);exit;
        //                var_dump($pwforum->foruminfo);exit;//获得版块数据$pwforum->isJoin($loginUser->uid)
        //                var_dump($tids,$threads_list);exit;//置顶帖子包含在通用帖子当中
        // 		var_dump($threadList->threaddb);exit;//获得帖子数据
        ($max_page = ceil($count/$this->perpage))||$max_page=1;
        $page_info = array('page'=>$page,'perpage'=>$this->perpage,'count'=>$count,'max_page'=>$max_page);
        $data = array('page_info'=>$page_info,'user_info'=>array('uid'=>$this->uid,'isjoin'=>$forum_isjoin),'forum_info'=>($page==1?$pwforum->foruminfo:''),'threads_list'=>$threads_list);
        $this->setOutput($data,'data');
        $this->showMessage('NATIVE:data.success');
    }

    private function _initTopictypes($fid, &$type) {
        $this->topictypes = $this->_getTopictypeService()->getTopicTypesByFid($fid);
        if (!isset($this->topictypes['all_types'][$type])) $type = 0;
    }

    private function _getSubTopictype($type) {
        if (isset($this->topictypes['sub_topic_types']) && isset($this->topictypes['sub_topic_types'][$type])) {
            return array_keys($this->topictypes['sub_topic_types'][$type]);
        }
        return array();
    }

    private function _getSubTopictypeName($type) {
        return isset($this->topictypes['all_types'][$type]) ? $this->topictypes['all_types'][$type]['name'] : '';
    }

    private function _formatTopictype($type) {
        $topictypes = $this->topictypes;
        if (isset($topictypes['all_types'][$type]) && $topictypes['all_types'][$type]['parentid']) {
            $topictypeService = Wekit::load('forum.srv.PwTopicTypeService');
            $topictypes = $topictypeService->sortTopictype($type, $topictypes);
        }
        return $topictypes;
    }

    private function _getTopictypeService(){
        return Wekit::load('forum.PwTopicType');
    }

    private function allowPost(PwForumBo $forum) {
        return $forum->foruminfo['allow_post'] ? $forum->allowPost($this->loginUser) : $this->loginUser->getPermission('allow_post');
    }
}
