<?php
/**
 * 查看帖子相关接口
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
Wind::import('SRV:native.srv.PwNativeThreadDisplay');
Wind::import('SRV:credit.bo.PwCreditBo');

class ReadController extends NativeBaseController {
    
    public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);
        $this->uid = 3; //测试uid
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
    public function run(){
        $tid = intval($this->getInput('tid','get'));
        list($page, $uid, $desc) = $this->getInput(array('page', 'uid', 'desc'), 'get');
        
        //$threadDisplay = new PwThreadDisplay($tid, $this->loginUser);
        $threadDisplay = new PwNativeThreadDisplay($tid, $this->loginUser);
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
                ->setPerpage(20)
                ->setDesc($desc);

        $threadDisplay->setImgLazy(Wekit::C('bbs', 'read.image_lazy'));
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

        $threadInfo = $threadDisplay->getThreadInfo();//获取帖子详细内容
        $thread_list = $threadDisplay->getList();
        $pids = $posts_list = array();
        foreach($thread_list as $key=>$v){
            $threadInfo['created_user_avatar'] = Pw::getAvatar($v['created_userid'],'small');
            $threadInfo['created_time'] = Pw::time2str($v['created_time'], 'auto');
            //
            if(!$v['pid']) continue;
            $pids[] = $v['pid'];
            $posts_list[$v['pid']] = $v;
        }

        //位置
        $threadPlace = $this->_getThreadPlaceService()->fetchByTids( array($tid) );
        $postPlace = $this->_getPostPlaceService()->fetchByPids( $pids );

        
        //附件
        $threadAttachs = array();
        if( isset($threadDisplay->attach->attachs[0]) ){
            foreach( $threadDisplay->attach->attachs[0] as $key=>$img){
                $threadAttachs[$key]['url'] = $img['url'];
            }
        }
        unset($threadDisplay->attach);

        //
        $data = array(
            'threadInfo'    =>$threadInfo,
            'postList'      =>$posts_list,
            'threadAttachs' =>$threadAttachs,
        );
        $this->setOutput($data,'data');
        $this->showMessage('success');


        /*
        $data = array(
            'tid'=>$tid,
        //    'threadDisplay'=>$threadDisplay,
            'fid'=>$threadDisplay->fid,
            'threadInfo'=>$threadInfo,
            'readdb'=>$threadDisplay->getList(),
            'users'=>$threadDisplay->getUsers(),
            'pwforum'=>$pwforum,
        );
         */ 
//        print_r($threadDisplay->getUsers());
        print_r($threadDisplay->getList());
        //print_r($data);
        //exit;

        
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

        $this->showMessage('success');
        
        //版块风格
        if ($pwforum->foruminfo['style']) {
            $this->setTheme('forum', $pwforum->foruminfo['style']);
            //$this->addCompileDir($pwforum->foruminfo['style']);
        }

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

    protected function _getThreadPlaceService(){
        return Wekit::loadDao('place.srv.PwThreadPlaceService');
    }

    protected function _getPostPlaceService(){
        return Wekit::loadDao('place.srv.PwPostPlaceService');
    }


    
}
