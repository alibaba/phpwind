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
Wind::import('SRV:like.PwLikeContent');

class ReadController extends NativeBaseController {
    
    public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);
        $this->loginUser = new PwUserBo($this->uid);
        $this->loginUser->resetGid($this->loginUser->gid);
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
        
        //数据分页
        $perpage = $pwforum->forumset['readperpage'] ? $pwforum->forumset['readperpage'] : Wekit::C('bbs', 'read.perpage');
        $dataSource->setPage($page)
            ->setPerpage($perpage)
            ->setDesc($desc);

        $threadDisplay->setImgLazy(Wekit::C('bbs', 'read.image_lazy'));
        $threadDisplay->execute($dataSource);

        //权限
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

        //主题的信息
        $threadInfo = $threadDisplay->getThreadInfo();
        //帖子收藏统计
        $collectInfo = $this->_getCollectService()->countCollectByTids( array($threadInfo['tid']) );
        //用户是否收藏过帖子
        $collectStatusInfo = array();
        if( $this->uid ){
            $collectStatusInfo = $this->_getCollectService()->getCollectByUidAndTids($this->uid, array($threadInfo['tid']) );
        }

        //获得板块信息
        $forumInfo = $threadDisplay->getForum();
        $simpleForumInfo = array(
            'fid'=>$forumInfo->fid,
            'name'=>preg_replace('/<\/?[^>]+>/i','',$forumInfo->foruminfo['name']),
        );

        //帖子数据列表
        $threadList = $threadDisplay->getList();
        
        //回复帖子列表
        $pids = array();
        foreach($threadList as $key=>$v){
            $threadList[$key]['created_user_avatar'] = Pw::getAvatar($v['created_userid'],'small');
            $threadList[$key]['created_time'] = Pw::time2str($v['created_time'], 'auto');
            //
            if( isset($v['pid']) ){
                $pids[] = $v['pid'];
            }
        }

        //获得用户是否喜欢过帖子|回复
        $threadLikeData = $postLikeData = array();
        if( $this->uid ){
            $threadLikeData = $this->_getLikeReplyService()->getAllLikeUserids(PwLikeContent::THREAD, array($tid) );
            $_postLikeData  = $this->_getLikeReplyService()->getAllLikeUserids(PwLikeContent::POST, $pids);
            if( !empty($pids) ){
                foreach($pids as $v){
                    if( isset($_postLikeData[$v]) ){
                        $postLikeData[$v] = array_search($this->uid, $_postLikeData[$v])===false?0:1;
                    }
                }
            }
        }
        //帖子发布来源
        $threadFromtypeList = $this->_getThreadsPlaceService()->getThreadFormTypeByTids(array($tid));

        //主帖的相关信息
        $simpleThreadInfo = array(
            'uid'   =>$threadInfo['created_userid'],
            'tid'   =>$threadInfo['tid'],
            'fid'   =>$threadInfo['fid'],
            'subject'       =>$threadInfo['subject'],
            'replies'       =>$threadInfo['replies'],
            'like_count'    =>$threadInfo['like_count'],
            'collect_count' =>isset($collectInfo[$threadInfo['tid']])?$collectInfo[$threadInfo['tid']]['sum']:0,
            'like_status'   =>isset($threadLikeData[$tid]) && array_search($this->uid, $threadLikeData[$tid])!==false?1:0,
            'collect_status'=>isset($collectStatusInfo[$tid]) && array_search($this->uid, $collectStatusInfo[$tid])!==false?1:0,
            'display_title' =>isset($threadFromtypeList[$tid]) && $threadFromtypeList[$tid] ? 1:0,
        );
        $simpleForumInfo['display_title'] = $simpleThreadInfo['display_title'];

        //位置
        $threadPlace = $this->_getThreadPlaceService()->fetchByTids( array($tid) );
        $postPlace = $this->_getPostPlaceService()->fetchByPids( $pids );

        //附件
        $threadAttachs = array();
        if( isset($threadDisplay->attach->attachs) ){
            foreach( $threadDisplay->attach->attachs as $k=>$v){
                foreach( $v as $kk=>$vv ){
                    $threadAttachs['attachs'][$k][$kk]=array(
                        'aid'=>$vv['aid'],
                        'name'=>$vv['name'],
                        'type'=>$vv['type'],
                        'url'=>$vv['url'],
                    );
                }
            }
        }
        if( isset($threadDisplay->attach->showlist) ){
            foreach( $threadDisplay->attach->showlist as $k=>$v){
                foreach( $v as $kk=>$vv ){
                    foreach( $vv as $kkk=>$vvv ){
                        $threadAttachs['showlist'][$k][$kk][$kkk]=array(
                            'aid'=>$vvv['aid'],
                            'name'=>$vvv['name'],
                            'type'=>$vvv['type'],
                            'url'=>$vvv['url'],
                        );
                    }
                }
            }
        }
        unset($threadDisplay->attach);
        //
        $data = array(
            'uid'           =>$this->uid,
            'operateReply'  =>$operateReply,
            'operateThread' =>$operateThread,
            'simpleForumInfo'=>$simpleForumInfo,
            'simpleThreadInfo'=>$simpleThreadInfo,
            'threadList'    =>$page<=$perpage?$threadList:array(),
            'pageCount'     =>ceil($threadDisplay->total/$perpage),
            'threadAttachs' =>$threadAttachs,
            'threadPlace'   =>$threadPlace,
            'postPlace'     =>$postPlace,
            'postLikeData'  =>$postLikeData,
        );
        $this->setOutput($data,'data');
        $this->showMessage('success');
    }

    /**
     * 分享到其它平台使用的链接 
     * 
     * @access public
     * @return void
     * @example
     * <pre>
     * /index.php?m=native&c=read&a=sharePage&tid=21
     * </pre>
     */
    public function sharePageAction(){
        $tid = intval($this->getInput('tid','get'));
        list($page, $uid, $desc) = $this->getInput(array('page', 'uid', 'desc'), 'get');
        
         
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
        Wind::import('SRV:forum.srv.threadDisplay.PwCommonRead');
        $dataSource = new PwCommonRead($threadDisplay->thread);

        //数据分页
        $perpage = $pwforum->forumset['readperpage'] ? $pwforum->forumset['readperpage'] : Wekit::C('bbs', 'read.perpage');
        $dataSource->setPage($page)
            ->setPerpage($perpage)
            ->setDesc($desc);

        $threadDisplay->setImgLazy(Wekit::C('bbs', 'read.image_lazy'));
        $threadDisplay->execute($dataSource);

        //主题的信息
        $threadInfo = $threadDisplay->getThreadInfo();
        $threadInfo['content'] = preg_replace('/onload="([^"]+)"/i','',$threadInfo['content']);
        $threadInfo['content'] = preg_replace('/onclick="([^"]+)"/i','',$threadInfo['content']);
        $threadInfo['content'] = str_replace('style="max-width:700px;"','',$threadInfo['content']);
        preg_match_all('/<div class="J_video" data-url="(.+?\.swf)".*?><\/div>/i',$threadInfo['content'],$matches);
        if(isset($matches[0]) && $matches[0]){
            $count = count($matches[0]);
            for($i=0;$i<$count;$i++){
                $vedio = '<embed src="'.$matches[1][$i].'" allowFullScreen="true" quality="high" width="240" height="200" align="middle" allowScriptAccess="always" type="application/x-shockwave-flash"></embed>';
                echo $vedio."<br>";
                $threadInfo['content'] = str_replace($matches[0][$i],$vedio,$threadInfo['content']);
            }
        }
        //帖子数据列表
        $threadList = $threadDisplay->getList();

        $this->setOutput(Wekit::getGlobal('url', 'res'), 'resPath');
        $this->setOutput($threadInfo,'threadInfo');
        $this->setOutput(array_slice($threadList,1,3),'threadList');
        $this->setOutput($threadDisplay, 'threadDisplay'); 
        $this->setOutput(PwCreditBo::getInstance(), 'creditBo'); 
    }

    protected function _getThreadPlaceService(){
        return Wekit::loadDao('place.srv.PwThreadPlaceService');
    }

    protected function _getPostPlaceService(){
        return Wekit::loadDao('place.srv.PwPostPlaceService');
    }

    protected function _getCollectService(){
        return Wekit::load('native.srv.PwNativeCollectService');
    }
    
    private function _getLikeThreadService() {
        return Wekit::load('like.srv.threadDisplay.do.PwThreadDisplayDoLike'); 
    }
  
    private function _getLikeReplyService() {
        return Wekit::load('like.srv.reply.do.PwLikeDoReply');
    } 

    private function _getThreadsPlaceService(){
        return Wekit::load('native.srv.PwNativeThreadsPlace');
    }

}
