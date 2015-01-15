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

//查看帖子引入类库
Wind::import('SRV:forum.srv.PwThreadDisplay');
Wind::import('SRV:credit.bo.PwCreditBo');

class ReadController extends PwBaseController {
    
	public function beforeAction($handlerAdapter) {
		parent::beforeAction($handlerAdapter);
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
//        echo "readAction";exit;
        $tid = intval($this->getInput('tid'));
        list($page, $uid, $desc) = $this->getInput(array('page', 'uid', 'desc'), 'get');

        $threadDisplay = new PwThreadDisplay($tid, $this->loginUser);
        $this->runHook('c_read_run', $threadDisplay);

        if (($result = $threadDisplay->check()) !== true) {
            $this->showError($result->getError());
        }
        $_cache = Wekit::cache()->fetch(array('level', 'group_right'));

        $pwforum = $threadDisplay->getForum();
        if ($pwforum->foruminfo['password']) {
            if (!$this->loginUser->isExists()) {
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
                ->setPerpage(30)
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
        
        $data = array(
                       'tid'=>$tid,
                       'threadDisplay'=>$threadDisplay,
                       'fid'=>$threadDisplay->fid,
                       'threadInfo'=>$threadInfo,
                       'readdb'=>$threadDisplay->getList(),
                       'users'=>$threadDisplay->getUsers(),
                       'pwforum'=>$pwforum,
                       'threadInfo'=>$threadInfo,
                    );
        $thread_place = Wekit::loadDao('native.dao.PwThreadsPlaceDao')->getByTid($tid);
        var_dump($thread_place);exit;
        var_dump($threadInfo);exit;
        var_dump(1,$threadDisplay);exit;
        
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
