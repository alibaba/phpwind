<?php
/**
 * 查看帖子相关
 *
 * @fileName: ReadController.php
 * @author: yuliang<yuliang.lyl@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-16 19:08:17
 * @desc: 
 **/
defined('WEKIT_VERSION') || exit('Forbidden');

Wind::import('APPS:native.controller.NativeBaseController');
//查看帖子引入类库
Wind::import('SRV:forum.srv.PwThreadDisplay');
Wind::import('SRV:credit.bo.PwCreditBo');

class ReadController extends NativeBaseController {
    protected $perpage = 30; 

    public function beforeAction($handlerAdapter) {
		parent::beforeAction($handlerAdapter);
                $this->uid = 1; //测试uid
                $this->loginUser = new PwUserBo($this->uid);
                $this->loginUser->resetGid($this->loginUser->gid);
//		if (!$this->loginUser->isExists()) $this->showError('VOTE:user.not.login');
	}


    /**
     * 查看帖子
     * @access public
     * @return string
     * @example
     <pre>
     /index.php?m=native&c=read&a=read&tid=21&fid=8&page=1
     cookie:usersession
     response: {err:"",data:""}  
     </pre>
     */ 
    public function readAction(){
        $tid = intval($this->getInput('tid'));
        list($page, $uid, $desc) = $this->getInput(array('page', 'uid', 'desc'), 'get');
        !$page && $page==1;
        $threadDisplay = new PwThreadDisplay($tid, $this->loginUser);
        $this->runHook('c_read_run', $threadDisplay);

        if (($result = $threadDisplay->check()) !== true) {
            $this->showError($result->getError());
        }
        $_cache = Wekit::cache()->fetch(array('level', 'group_right'));

        $pwforum = $threadDisplay->getForum();
        if ($pwforum->foruminfo['password']) {
            if (!$this->uid) {
                $this->forwardAction('u/login/run', array('backurl' => WindUrlHelper::createUrl('bbs/cate/run', array('fid' => $$pwforum->fid))));
            } elseif (Pw::getPwdCode($pwforum->foruminfo['password']) != Pw::getCookie('fp_' . $pwforum->fid)) {
                $this->forwardAction('bbs/forum/password', array('fid' => $pwforum->fid));
            }
        }
        if ($uid) {
            Wind::import('SRV:forum.srv.threadDisplay.PwUserRead');
            $dataSource = new PwUserRead($threadDisplay->thread, $uid);
        } else {
            Wind::import('SRV:forum.srv.threadDisplay.PwCommonRead');
            $dataSource = new PwCommonRead($threadDisplay->thread);
        }
        $dataSource->setPage($page)
//                ->setPerpage($pwforum->forumset['readperpage'] ? $pwforum->forumset['readperpage'] : Wekit::C('bbs', 'read.perpage'))
                ->setPerpage($this->perpage)
                ->setDesc($desc);

        $threadDisplay->setImgLazy(Wekit::C('bbs', 'read.image_lazy'));
//        var_dump($threadDisplay);exit;
        $threadDisplay->execute($dataSource);

        $operateReply = $operateThread = array();
        $isBM = $pwforum->isBM($this->loginUser->username);
        if ($threadPermission = $this->loginUser->getPermission('operate_thread', $isBM, array())) {
            $operateReply = Pw::subArray(
                            $threadPermission, array('toppedreply', /* 'unite', 'split',  */ 'remind', 'shield', 'delete', 'ban', 'inspect', 'read')
            );
            $operateThread = Pw::subArray(
                            $threadPermission, array(
                        'digest', 'topped', 'up', 'highlight',
                        'copy',
                        'type', 'move', /* 'unite', 'print' */ 'lock',
                        'down',
                        'delete',
                        'ban'
                            )
            );
        }
                
//        $threadInfo = $threadDisplay->getThreadInfo();//获取帖子详细内容
//        $thread_list = $threadDisplay->getList();
        
        
        
        $posts_num = $page==1 ? $this->perpage - 1 : $this->perpage;
        $start_floor = ($page-1)*$this->perpage;
        $start_pos = ($page-1)*$this->perpage - 1;
        $start_pos < 0 && $start_pos = 0;
        if($uid){//只看楼主回复
            $posts_list = Wekit::load('forum.PwThread')->getPostByTidAndUid($tid,$uid,$posts_num,$start_pos);//获取帖子的回复
        }else{
            $posts_list = Wekit::load('forum.PwThread')->getPostByTid($tid,$posts_num,$start_pos);//获取帖子的回复
        }
        $thread_info = '';
        if($page==1){//第一页展示主贴
            $thread_info = Wekit::load('forum.PwThread')->getThread($tid,PwThread::FETCH_ALL);
            $thread_place = Wekit::loadDao('native.dao.PwThreadsPlaceDao')->getByTid($tid);//获取发帖的位置信息
            $posts_list[0] = $thread_info;
            ksort($posts_list);
        }
        $pids = array_keys($posts_list);
        $PwThreadService = Wekit::load('forum.srv.PwThreadService');
        $imgs_list = Wekit::load('native.PwNativeThread')->getThreadAttach(array($tid),$pids);//获取主贴和回帖的所有图片信息
        $posts_place = Wekit::loadDao('native.dao.PwPostsPlaceDao')->fetchByPids($pids);//获取回帖的位置信息
        foreach($posts_list as $k=>$v){
            $content = $v['content'];
            $posts_list[$k]['created_time'] = Pw::time2str($v['created_time'],'auto');
            $posts_list[$k]['avatar'] = Pw::getAvatar($v['created_userid'],'small');
            $posts_list[$k]['floor'] = $start_floor++;//楼层
            $imgs = isset($imgs_list[$tid.'_'.$k]) ? $imgs_list[$tid.'_'.$k] : array();
            ksort($imgs);
            
            if($k){//回帖
                $text = str_replace(array('[视频]','[音乐]','[附件]'),array('','',''),trim($PwThreadService->displayContent($content,1,array(),strlen($content)),'.'));//帖子内容文本
                $posts_list[$k]['created_address'] = isset($posts_place[$k]['created_address']) ? $posts_place[$k]['created_address'] : '';
                $posts_list[$k]['area_code'] = isset($posts_place[$k]['area_code']) ? $posts_place[$k]['area_code'] : '';
            }else{//主贴
                $posts_list[$k]['lastpost_time'] = Pw::time2str($v['lastpost_time'],'auto');//最后回复时间
                $v['tags'] && $posts_list[$k]['tags'] = explode(',', $v['tags']);//帖子话题
                $text = str_replace(array('[视频]','[音乐]','[附件]'),array('','',''),trim($PwThreadService->displayContent($content,1,array(),strlen($content)),'.'));//帖子内容文本
                $posts_list[$k]['from_type'] = isset($thread_place['from_type']) ? $thread_place['from_type'] : 0;
                $posts_list[$k]['created_address'] = isset($thread_place['created_address']) ? $thread_place['created_address'] : '';
                $posts_list[$k]['area_code'] = isset($thread_place['area_code']) ? $thread_place['area_code'] : '';
            }
            
            preg_match("/\[mp3.*?\](.*?)\[\/mp3\]/i",$content, $mp3);
            preg_match("/\[flash.*?\](.*?)\[\/flash\]/i",$content, $flash);
            $posts_list[$k]['content'] = array(
                                            'text'=>$text,
                                            'flash'=>isset($flash[1]) ? $flash[1] : '',
                                            'mp3'=>isset($mp3[1]) ? $mp3[1] : '',
                                            'imgs'=>$imgs,//获取内容图片
                                            'share'=>'',//帖子分享链接中的内容(待定)
                                            'product'=>'',//推广待定
                                            );
            $posts_list[$k]['content_origin'] = $content;
        }
        $count = $threadDisplay->total;
        $page_info = array('page'=>$page,'perpage'=>$this->perpage,'count'=>$count,'max_page'=>ceil($count/$this->perpage));
        $data = array('page_info'=>$page_info,'user_info'=>array('uid'=>$this->uid),'forum_info'=>($page==1?$pwforum->foruminfo:''),'posts_list'=>$posts_list);
        $this->setOutput($data,'data');
        $this->showMessage('NATIVE:data.success');
    }
    
    /* 此方法暂时没用 */
    public function readAction_tmp(){
//        echo "readAction";exit;
        $tid = intval($this->getInput('tid'));
        list($page, $uid, $desc) = $this->getInput(array('page', 'uid', 'desc'), 'get');
//        var_dump($this->uid ,$this->loginUser);exit;
        $threadDisplay = new PwThreadDisplay($tid, $this->loginUser);
        $this->runHook('c_read_run', $threadDisplay);

        if (($result = $threadDisplay->check()) !== true) {
            $this->showError($result->getError());
        }
        $_cache = Wekit::cache()->fetch(array('level', 'group_right'));

        $pwforum = $threadDisplay->getForum();
        if ($pwforum->foruminfo['password']) {
            if (!$this->uid) {
                $this->forwardAction('u/login/run', array('backurl' => WindUrlHelper::createUrl('bbs/cate/run', array('fid' => $$pwforum->fid))));
            } elseif (Pw::getPwdCode($pwforum->foruminfo['password']) != Pw::getCookie('fp_' . $pwforum->fid)) {
                $this->forwardAction('bbs/forum/password', array('fid' => $pwforum->fid));
            }
        }
        if ($uid) {
            Wind::import('SRV:forum.srv.threadDisplay.PwUserRead');
            $dataSource = new PwUserRead($threadDisplay->thread, $uid);
        } else {
            Wind::import('SRV:forum.srv.threadDisplay.PwCommonRead');
            $dataSource = new PwCommonRead($threadDisplay->thread);
        }
        $dataSource->setPage($page)
//                ->setPerpage($pwforum->forumset['readperpage'] ? $pwforum->forumset['readperpage'] : Wekit::C('bbs', 'read.perpage'))
                ->setPerpage($this->perpage)
                ->setDesc($desc);

        $threadDisplay->setImgLazy(Wekit::C('bbs', 'read.image_lazy'));
//        var_dump($threadDisplay);exit;
        $threadDisplay->execute($dataSource);

        $operateReply = $operateThread = array();
        $isBM = $pwforum->isBM($this->loginUser->username);
        if ($threadPermission = $this->loginUser->getPermission('operate_thread', $isBM, array())) {
            $operateReply = Pw::subArray(
                            $threadPermission, array('toppedreply', /* 'unite', 'split',  */ 'remind', 'shield', 'delete', 'ban', 'inspect', 'read')
            );
            $operateThread = Pw::subArray(
                            $threadPermission, array(
                        'digest', 'topped', 'up', 'highlight',
                        'copy',
                        'type', 'move', /* 'unite', 'print' */ 'lock',
                        'down',
                        'delete',
                        'ban'
                            )
            );
        }
        
        var_dump($threadDisplay);exit;
        
        $threadInfo = $threadDisplay->getThreadInfo();//获取帖子详细内容
        $thread_list = $threadDisplay->getList();
        $pids = $posts_list = array();
        foreach($thread_list as $v){
            if(!$v['pid']) continue;
            $pids[] = $v['pid'];
            $posts_list[$v['pid']] = $v;
        }
        $thread_place = Wekit::loadDao('native.dao.PwThreadsPlaceDao')->getByTid($tid);//获取发帖的位置信息
        $posts_place = Wekit::loadDao('native.dao.PwPostsPlaceDao')->fetchByPids($pids);//获取发帖的位置信息
        $threadInfo['from_type'] = isset($thread_place['from_type']) ? $thread_place['from_type'] : 0;
        $threadInfo['created_address'] = isset($thread_place['created_address']) ? $thread_place['created_address'] : '';
        $threadInfo['area_code'] = isset($thread_place['area_code']) ? $thread_place['area_code'] : '';
//        var_dump($pids);exit;
        foreach($posts_place as $k=>$v){
            $posts_list[$k]['created_address'] = $v['created_address'];
            $posts_list[$k]['area_code'] = $v['area_code'];
        }
        
//        var_dump($threadDisplay);exit;
        var_dump($threadInfo,$posts_list);exit;
        $data = array(
                       'tid'=>$tid,
                       'threadDisplay'=>$threadDisplay,
                       'fid'=>$threadDisplay->fid,
                       'threadInfo'=>$threadInfo,
                       'readdb'=>$threadDisplay->getList(),
                       'users'=>$threadDisplay->getUsers(),
                       'pwforum'=>$pwforum,
                    );
//        var_dump($thread_place);exit;
//        var_dump($threadInfo);exit;
        var_dump(1,$threadDisplay,$thread_place);exit;
        
        $this->setOutput($threadDisplay, 'threadDisplay');
        $this->setOutput($tid, 'tid');
        $this->setOutput($threadDisplay->fid, 'fid');
        $this->setOutput($threadInfo, 'threadInfo');
        $this->setOutput($threadDisplay->getList(), 'readdb');
        $this->setOutput($threadDisplay->getUsers(), 'users');
        $this->setOutput($pwforum, 'pwforum');
        $this->setOutput(PwCreditBo::getInstance(), 'creditBo');
        $this->setOutput($threadDisplay->getHeadguide(), 'headguide');
        $this->setOutput(Wekit::C('bbs', 'read.display_member_info'), 'displayMemberInfo');
        $this->setOutput(Wekit::C('bbs', 'read.display_info'), 'displayInfo');
        $this->setOutput(Wekit::C('bbs', 'thread.hotthread_replies'), 'hotIcon');

        $this->setOutput($threadPermission, 'threadPermission');
        $this->setOutput($operateThread, 'operateThread');
        $this->setOutput($operateReply, 'operateReply');
        $this->setOutput((!$this->loginUser->uid && !$this->allowPost($pwforum)) ? ' J_qlogin_trigger' : '', 'postNeedLogin');
        $this->setOutput((!$this->loginUser->uid && !$this->allowReply($pwforum)) ? ' J_qlogin_trigger' : '', 'replyNeedLogin');

        $this->setOutput($_cache['level']['ltitle'], 'ltitle');
        $this->setOutput($_cache['level']['lpic'], 'lpic');
        $this->setOutput($_cache['level']['lneed'], 'lneed');
        $this->setOutput($_cache['group_right'], 'groupRight');

        $this->setOutput($threadDisplay->page, 'page');
        $this->setOutput($threadDisplay->perpage, 'perpage');
        $this->setOutput($threadDisplay->total, 'count');
        $this->setOutput($threadDisplay->maxpage, 'totalpage');
        $this->setOutput($threadDisplay->getUrlArgs(), 'urlargs');
        $this->setOutput($threadDisplay->getUrlArgs('desc'), 'urlDescArgs');
        $this->setOutput($this->loginUser->getPermission('look_thread_log', $isBM, array()), 'canLook');
        $this->setOutput($this->_getFpage($threadDisplay->fid), 'fpage');

        //版块风格
        if ($pwforum->foruminfo['style']) {
            $this->setTheme('forum', $pwforum->foruminfo['style']);
            //$this->addCompileDir($pwforum->foruminfo['style']);
        }

        // seo设置
        Wind::import('SRV:seo.bo.PwSeoBo');
        $seoBo = PwSeoBo::getInstance();
        $lang = Wind::getComponent('i18n');
        $threadDisplay->page <= 1 && $seoBo->setDefaultSeo($lang->getMessage('SEO:bbs.read.run.title'), '', $lang->getMessage('SEO:bbs.read.run.description'));
        $seoBo->init('bbs', 'read');
        $seoBo->set(
                array(
                    '{forumname}' => $threadDisplay->forum->foruminfo['name'],
                    '{title}' => $threadDisplay->thread->info['subject'],
                    '{description}' => Pw::substrs($threadDisplay->thread->info['content'], 100, 0, false),
                    '{classfication}' => $threadDisplay->thread->info['topic_type'],
                    '{tags}' => $threadInfo['tags'],
                    '{page}' => $threadDisplay->page
                )
        );
        Wekit::setV('seo', $seoBo);
        //是否显示回复
        $showReply = true;
        //锁定时间
        if ($pwforum->forumset['locktime'] && ($threadInfo['created_time'] + $pwforum->forumset['locktime'] * 86400) < Pw::getTime()) {
            $showReply = false;
        } elseif (Pw::getstatus($threadInfo['tpcstatus'], PwThread::STATUS_LOCKED) && !$this->loginUser->getPermission('reply_locked_threads')) {
            $showReply = false;
        }
        $this->setOutput($showReply, 'showReply');
        $this->runReadDesign($threadDisplay->fid);
        $this->updateReadOnline($threadDisplay->fid, $tid);
    }
    
}
