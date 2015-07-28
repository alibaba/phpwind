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
     /index.php?m=native&c=read&tid=21&fid=8&page=1&_json=1
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
        isset($operateThread['topped']) && $operateThread['topped'] = $this->loginUser->gid == 3 && $operateThread ? "1" : "0";
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
        $poll = '';        
        if($threadList[0]['special']=='poll'){//是投票帖子
            $res = Wekit::load('poll.PwThreadPoll')->getPoll($tid);
            $poll_id = $res['poll_id'];
            $options = Wekit::load('poll.PwPollOption')->getByPollid($poll_id);
            $vote_total = 0;
            foreach($options as $k=>$v){
                if($v['image']){
                    $options[$k]['image'] = PUBLIC_URL."/attachment/".$v['image'];
                }
                $voted_total += $v['voted_num'];
            }
//            $count = Wekit::load('poll.PwPollOption')->countByPollid($poll_id);
//            var_dump($options);exit;
            $res = Wekit::load('poll.PwPoll')->getPoll($poll_id);
            $poll_state = $this->uid ? Wekit::load('poll.PwPollVoter')->isVoted($this->uid, $poll_id) : true;
            $poll = array(
                        'poll_id'=>$poll_id,
                        'isafter_view'=>(int)$res['isafter_view'],
                        'option_limit'=>(int)$res['option_limit'],
                        'expired_time'=>$res['expired_time'] ? Pw::time2str($res['expired_time']) : "无限期",
                        'poll_state'=>$poll_state,//true:已经投票或者未登陆;false:未投票
                        'voted_total'=>$voted_total,
                        'options'=>$options,//投票项以及各项结果
                    );
        }
//        var_dump($poll);exit;
        
//        var_dump($threadList[0]);exit;
        $sell = false;
        if($this->uid && strpos($threadList[0]['content'], '[sell')!==false && $threadList[0]['created_userid']!=$this->uid){//含有售卖内容,当前用户不是作者
            //获取用户是否购买过帖子
            $res = Wekit::loadDao('native.dao.PwNativeThreadsBuyDao')->getBuyRecord($tid,$this->uid);
            if(!$res){//用户未购买过帖子
                $start = strpos($threadList[0]['content'], '[sell=');
		$start += 6;
		$end = strpos($threadList[0]['content'], ']', $start);
		$cost = substr($threadList[0]['content'], $start, $end - $start);
                list($creditvalue, $credittype) = explode(',', $cost);
                Wind::import('SRV:credit.bo.PwCreditBo');
		$creditBo = PwCreditBo::getInstance();
		isset($creditBo->cType[$credittype]) || $credittype = key($creditBo->cType);
		$creditType = $creditBo->cType[$credittype];
                $myCredit = $this->loginUser->getCredit($credittype);
//                var_dump($cost,$creditvalue,$credittype,$creditType,$myCredit);exit;
                $sell = array('credit_value'=>$creditvalue,'user_credit'=>$myCredit,'credit_name'=>$creditType);
            }
        }
        
        //获取最近点赞的5个人
        $res = Wekit::loadDao('native.dao.PwNativeLikeContentDao')->getLikeidByFromid($tid);
        $likeUsers = array();
        if(isset($res['likeid'])){
            $uids = Wekit::loadDao('native.dao.PwNativeLikeLogDao')->fetchUidsByLikeid($res['likeid']);
            $res = Wekit::loadDao('user.dao.PwUserDao')->fetchUserByUid($uids);
            foreach($res as $v){
                $likeUsers[] = $v['username'];
            }
        }
        
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
            'poll'=>$poll,
            'sell'=>$sell,
            'likeUsers'=>$likeUsers,
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
        preg_match_all('/<div class="J_video" data-url="(.+?\.swf.*?)".*?><\/div>/i',$threadInfo['content'],$matches);
        if(isset($matches[0]) && $matches[0]){
            $count = count($matches[0]);
            for($i=0;$i<$count;$i++){
                $vedio = '<embed src="'.$matches[1][$i].'" allowFullScreen="true" quality="high" width="240" height="200" align="middle" allowScriptAccess="always" type="application/x-shockwave-flash"></embed><br>';
//                echo $vedio."<br>";
                $threadInfo['content'] = str_replace($matches[0][$i],$vedio,$threadInfo['content']);
            }
        }
        //帖子内容音频资源
        preg_match_all('/<div class="J_audio".*?data-url="(.+?)".*?><\/div>/i',$threadInfo['content'],$matches);
        if(isset($matches[0]) && $matches[0]){
            $count = count($matches[0]);
            for($i=0;$i<$count;$i++){
                $audio = '<br><audio controls="controls" src="'.$matches[1][$i].'">不支持音乐</audio><br>';
                $threadInfo['content'] = str_replace($matches[0][$i],$audio,$threadInfo['content']);
            }
        }
        //帖子数据列表
        $threadList = $threadDisplay->getList();
        $threadList = array_slice($threadList,1,3);
        foreach($threadList as $k=>$v){
            preg_match_all('/<div class="J_video" data-url="(.+?\.swf.*?)".*?><\/div>/i',$v['content'],$matches);
            if(isset($matches[0]) && $matches[0]){
                $count = count($matches[0]);
                for($i=0;$i<$count;$i++){
                    $vedio = '<embed src="'.$matches[1][$i].'" allowFullScreen="true" quality="high" width="240" height="200" align="middle" allowScriptAccess="always" type="application/x-shockwave-flash"></embed><br>';
    //                echo $vedio."<br>";
                    $threadList[$k]['content'] = str_replace($matches[0][$i],$vedio,$v['content']);
                }
            }

            preg_match_all('/<div class="J_audio".*?data-url="(.+?)".*?><\/div>/i',$v['content'],$matches);
            if(isset($matches[0]) && $matches[0]){
                $count = count($matches[0]);
                for($i=0;$i<$count;$i++){
                    $audio = '<br><audio controls="controls" src="'.$matches[1][$i].'">不支持音乐</audio><br>';
                    $threadList[$k]['content'] = str_replace($matches[0][$i],$audio,$v['content']);
                }
            }
        }
//var_dump($threadList);exit;
        $this->setOutput(Wekit::getGlobal('url', 'res'), 'resPath');
        $this->setOutput($threadInfo,'threadInfo');
        $this->setOutput($threadList,'threadList');
        $this->setOutput($threadDisplay, 'threadDisplay'); 
        $this->setOutput(PwCreditBo::getInstance(), 'creditBo'); 
    }
    
    /**
     * 购买帖子出售内容
     * @access public
     * @return string
     * @example
     <pre>
     /index.php?m=native&c=read&a=buy&tid=21&_json=1
     cookie:usersession
     response: {err:"",data:""}  
     </pre>
     */
    public function buyAction(){
        $tid = $this->getInput('tid');
        $submit = 1;
        if (!$this->loginUser->isExists()) {
                $this->showError('login.not');
        }
        if (!$tid) {
                $this->showError('data.error');
        }
        if ($pid) {
                $result = Wekit::load('forum.PwThread')->getPost($pid);
        } else {
                $pid = 0;
                $result = Wekit::load('forum.PwThread')->getThread($tid, PwThread::FETCH_ALL);
        }
        if (empty($result) || $result['tid'] != $tid) {
                $this->showError('data.error');
        }
        $start = strpos($result['content'], '[sell=');
        if ($start === false) {
                $this->showError('BBS:thread.buy.error.sell.not');
        }
        $start += 6;
        $end = strpos($result['content'], ']', $start);
        $cost = substr($result['content'], $start, $end - $start);

        list($creditvalue, $credittype) = explode(',', $cost);
        Wind::import('SRV:credit.bo.PwCreditBo');
        $creditBo = PwCreditBo::getInstance();
        isset($creditBo->cType[$credittype]) || $credittype = key($creditBo->cType);
        $creditType = $creditBo->cType[$credittype];
        if ($result['created_userid'] == $this->loginUser->uid) {
                $this->showError('BBS:thread.buy.error.self');
        }
        if (Wekit::load('forum.PwThreadBuy')->get($tid, $pid, $this->loginUser->uid)) {
                $this->showError('BBS:thread.buy.error.already');
        }

        if (($myCredit = $this->loginUser->getCredit($credittype)) < $creditvalue) {
                $this->showError(array('BBS:thread.buy.error.credit.notenough',array('{myCredit}' => $myCredit.$creditType, '{count}' => $creditvalue.$creditType)));
        }

        !$submit && $this->showMessage(array('BBS:thread.buy.message.buy', array('{count}' => $myCredit.$creditType, '{buyCount}' => -$creditvalue.$creditType)));
        Wind::import('SRV:forum.dm.PwThreadBuyDm');
        $dm = new PwThreadBuyDm();
        $dm->setTid($tid)
                ->setPid($pid)
                ->setCreatedUserid($this->loginUser->uid)
                ->setCreatedTime(Pw::getTime())
                ->setCtype($credittype)
                ->setCost($creditvalue);
        Wekit::load('forum.PwThreadBuy')->add($dm);

        $creditBo->addLog('buythread', array($credittype => -$creditvalue), $this->loginUser, array(
                'title' => $result['subject'] ? $result['subject'] : Pw::substrs($result['content'], 20)
        ));
        $creditBo->set($this->loginUser->uid, $credittype, -$creditvalue, true);

        $user = new PwUserBo($result['created_userid']);
        if (($max = $user->getPermission('sell_credit_range.maxincome')) && Wekit::load('forum.PwThreadBuy')->sumCost($tid, $pid) > $max) {

        } else {
                $creditBo->addLog('sellthread', array($credittype => $creditvalue), $user, array(
                        'title' => $result['subject'] ? $result['subject'] : Pw::substrs($result['content'], 20)
                ));
                $creditBo->set($user->uid, $credittype, $creditvalue, true);
        }
        $creditBo->execute();

        if ($pid) {
                Wind::import('SRV:forum.dm.PwReplyDm');
                $dm = new PwReplyDm($pid);
                $dm->addSellCount(1);
                Wekit::load('forum.PwThread')->updatePost($dm);
        } else {
                Wind::import('SRV:forum.dm.PwTopicDm');
                $dm = new PwTopicDm($tid);
                $dm->addSellCount(1);
                Wekit::load('forum.PwThread')->updateThread($dm, PwThread::FETCH_CONTENT);
        }

        $this->showMessage('success');
    }
    
    
    /**
     * 帖子点赞的用户
     * @access public
     * @return string
     * @example
     <pre>
     /index.php?m=native&c=read&a=likeusers&tid=21&page=1&_json=1
     cookie:usersession
     response: {err:"",data:""}  
     </pre>
     */
    public function likeUsersAction(){
        $tid = $this->getInput('tid');
        $page = $this->getInput('page');
        $page || $page = 1;
        $perpage = 30;
        $start = ($page-1)*$perpage;
        $res = Wekit::loadDao('native.dao.PwNativeLikeContentDao')->getLikeidByFromid($tid);
        $likeUsers = array();
        $count = 0;
        if(isset($res['likeid'])){
            $count = Wekit::loadDao('native.dao.PwNativeLikeLogDao')->getLikeCount($res['likeid']);
            $uids = Wekit::loadDao('native.dao.PwNativeLikeLogDao')->fetchUidsByLikeid($res['likeid'],$start,$perpage);
            $res = Wekit::loadDao('user.dao.PwUserDao')->fetchUserByUid($uids);
            foreach($res as $v){
                $likeUsers[] = array('uid'=>$v['uid'],'username'=>$v['username'],'avatar'=>Pw::getAvatar($v['uid'],'small'));
            }
        }
        $data = array(
                    'likeUsers'=>$likeUsers,
                    'pageCount'=>ceil($count/$perpage) > 0 ? ceil($count/$perpage) : 1 ,
                );
        
        $this->setOutput($data,'data');
        $this->showMessage('success');
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
