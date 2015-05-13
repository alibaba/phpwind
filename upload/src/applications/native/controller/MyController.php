<?php
/**
 * 关于我的相关
 * @fileName: MyController.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2015-01-06 15:51:07
 * @desc: 
 **/
defined('WEKIT_VERSION') || exit('Forbidden');

Wind::import('APPS:native.controller.NativeBaseController');
Wind::import('SRV:like.PwLikeContent');

class MyController extends NativeBaseController {

    protected $userInfo = array();

    /**
     * global post: securityKey
     */
    public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);
//        $this->uid = 1;
        $this->checkUserSessionValid();
        //
        $this->userInfo = $this->_getUserAllInfo(PwUser::FETCH_MAIN);
    }
    
    /**
     * 关注一个人 
     * 
     * @access public
     * @return void
     * @example
     * <pre>
     * /index.php?m=native&c=Space&a=addFollow <br>
     * post: securityKey&uid
     * </pre>
     */
    public function addFollowAction(){
        $uid = $this->getInput('uid');
        if (!$uid) {
            $this->showError('operate.select');
        }   
        $private = Wekit::load('user.PwUserBlack')->checkUserBlack($this->uid, $uid);
        if ($private) {
            $this->showError('USER:attention.private.black');
        }   
        $result = $this->_getAttentionService()->addFollow($this->uid, $uid);

        if ($result instanceof PwError) {
            $this->showError($result->getError());
        }   

        // 发送通知给被关注的人。关于 type 请查看sendNotification的注释。
        PwLaiWangSerivce::sendNotification($uid,
            array('type' => 1, 'message' => $this->userInfo['username'] . ' 关注了您。--系统消息，回复无效。'));

        //
        $this->showMessage('success');
    }

    /**
     * 取消关注一个人 
     * 
     * @access public
     * @return void
     * @example
     * <pre>
     * /index.php?m=native&c=Space&a=unFollow <br>
     * post: securityKey&uid
     * </pre>
     */
    public function unFollowAction(){
        $uid = $this->getInput('uid');
		if (!$uid) {
			$this->showError('operate.select');
        }
		$result = $this->_getAttentionService()->deleteFollow($this->uid, $uid);
		if ($result instanceof PwError) {
			$this->showError($result->getError());
		}
        // 取消关注不发送通知
        /*
        PwLaiWangSerivce::sendNotification($uid, array(
            'type' => 1, 'message' => $this->userInfo['username'] . ' 取消关注了您。--系统消息，回复无效。'));
        */

        //
		$this->showMessage('success');
    }

    /**
     * 加入黑名单 
     * 
     * @access public
     * @return void
     * @example
     * <pre>
     * /index.php?m=native&c=Space&a=addBlack <br>
     * post: securityKey&uid
     * </pre>
     */
    public function addBlackAction(){
        $uid = (int)$this->getInput('uid');
        if ($uid) {
            $user = $this->_getUserDs()->getUserByUid($uid);
            $uid = $user['uid'];
        }   
        $uid or $this->showError('MESSAGE:id.empty');
        $this->_getUserBlack()->setBlacklist($this->uid,$uid);                                                                        
        //同时取消关注
        $this->_getAttentionService()->deleteFollow($this->uid, $uid);
        //同时让对方取消关注
        $this->_getAttentionService()->deleteFollow($uid, $this->uid);
        $this->showMessage('success');
    }

    /**
     * 我关注的人
     * @access public
     * @return void
     * @example
     * <pre>
     * /index.php?m=native&c=Space&a=follow&page=1 <br>
     * </pre>
     */
    public function followAction(){
        $page = intval($this->getInput('page','get'));
		$page < 1 && $page = 1;
		$perpage = 20;
		list($start, $limit) = Pw::page2limit($page, $perpage);
		
        $typeCounts = $this->_getAttentionTypeDs()->countUserType($this->uid);
        $follows = $this->_getPwAttentionDs()->getFollows($this->uid, $limit, $start);
        //
        $userBo = new PwUserBo($this->uid);
        $followsCount  = $userBo->info['follows']; 

        $uids = array_keys($follows);
		$fans = $this->_getPwAttentionDs()->fetchFans($this->uid, $uids);
        $userList = Wekit::load('user.PwUser')->fetchUserByUid($uids, PwUser::FETCH_MAIN );
        if( $userList ){
            foreach($userList as $key=>$user){
                $userList[] = array(
                    'uid'       =>$user['uid'],
                    'username'  =>$user['username'],
                    'avatar'    =>Pw::getAvatar($user['uid'],'small'),
                );
                unset($userList[$key]);
            }
        }
        $data = array(
            'pageCount'=>ceil($followsCount/$perpage),
            'userList'=>$userList,
        );
        $this->setOutput($data, 'data');
        $this->showMessage('success');
    }


    /**
     * 我关注的话题 
     * 
     * @access public
     * @return void
     * @example
     * <pre>
     * /index.php?m=native&c=My&a=tag&page=1 <br>
     * </pre>
     */
    public function tagAction(){
        $page = intval($this->getInput('page','get'));
		$page < 1 && $page = 1;
		$perpage = 20;
        $tags = $this->_getTagService()->getAttentionTags($this->uid,($page-1)*$perpage,$perpage);
        // 
        $tagdata = array();
        if( isset($tags[1]) ){
            foreach ($tags[1] as $key=>$tag) {
                $tagdata[] = $tag['tag_name'];
            }
        }
        $data = array(
            'pageCount'=>ceil(intval($tags[0])/$perpage),
            'tagsList'=>$tagdata,
        );
        $this->setOutput($data, 'data');
        $this->showMessage('success');
    }

    /**
     * 喜欢一个贴子 
     * 
     * @access public
     * @return void
     * @example
     * <pre>
     * /index.php?m=native&c=My&a=doLike <br>
     * post: typeid=(1主贴2回复)&fromid
     * </pre>
     */
    public function doLikeAction() {
        $typeid = (int) $this->getInput('typeid');
        $fromid = (int) $this->getInput('fromid');
        if ($typeid < 1 || $fromid < 1) $this->showError('BBS:like.fail');
        //
        if( $this->uid<1 ){
            $this->showError('login.not');
        }
        //
        $userBo = new PwUserBo();
        $userBo->uid=$this->uid;
        //
        $resource = $this->_getLikeService()->addLike($userBo, $typeid, $fromid);
        if ($resource instanceof PwError) $this->showError($resource->getError());

        //
        $thread = Wekit::load('forum.PwThread')->getThread($tid);
        PwLaiWangSerivce::sendNotification($thread['created_userid'],
            array('type'    => 1,
                  'message' => '《'.$thread['subject'].'》已被 '.$this->userInfo['username'].' 喜欢。--系统消息，回复无效。'));

        //
        $needcheck = false;
        if($resource['extend']['needcheck'])  $needcheck = false;
        $data['likecount'] = $resource['likecount'];
        $data['needcheck'] = $needcheck;
        $this->setOutput($data, 'data');
        $this->showMessage('BBS:like.success');
    }  

    /**
     * 取消喜欢的贴子 //暂时取消不了,需要logid
     * @access public
     * @return void
     */
    public function doDelLikeAction(){
        $logid = (int) $this->getInput('logid');
        if (!$logid) $this->showError('BBS:like.fail');
        $resource = $this->_getLikeService()->delLike($this->uid, $logid);
        
        if ($resource) {
            $this->showMessage('BBS:like.success');
        }
        $this->showError('BBS:like.fail');
    }


    /**
     * 我发布的帖子 
     * @access public
     * @return void
     * @example
     * <pre>
     * /index.php?m=native&c=My&a=article&page=1 
     * <pre>
     */
    public function articleAction(){
        $page = $this->getInput('page','get');
        //
        $tids           = $this->_getPwNativeThreadDs()->getThreadListByUid($this->uid, $page, 'my');
        $myThreadList   = $this->_getPwNativeThreadDs()->getThreadContent($tids);
        //pids 默认是0； 
        $attList        = $this->_getPwNativeThreadDs()->getThreadAttach($tids, array(0) );
        $threadList     = $this->_getPwNativeThreadDs()->gather($myThreadList, $attList);
        //
        $data = array(
            'pageCount'=>$this->_getPwNativeThreadDs()->getThreadPageCount(),
            'threadList'=>$threadList,
        );
        $this->setOutput($data, 'data');
        $this->showMessage('success');
    }

    /**
     * 我回复的帖子 
     * @access public
     * @return void
     * @example
     * <pre>
     * /index.php?m=native&c=My&a=post&page=1 
     * </pre>
     */
    public function postAction(){
        $page = $this->getInput('page','get');
        $perpage = 20;
        //
        $postCount = $this->_getNativePostExpandDao()->countDisabledPostByUidAndFids($this->uid, $this->_getForumService()->fids);
        $pageCount = count($postCount/$perpage);
        $page = $page ? $page : 1;
        $page>$pageCount && $page = $pageCount;

        list($start, $limit) = Pw::page2limit($page, $perpage);
        //
        $tids = array();
        $threads = $this->_getNativePostExpandDao()->getDisabledPostByUid($this->uid, $this->_getForumService()->fids, $limit, $start);
        foreach ($threads as $thread) {
            $tids[] = $thread['tid'];
        }   
 
        //获得登陆用户是否喜欢过帖子|回复
        $_tids = $tids;
        $threadLikeData = array();
        if( $this->uid && $_tids ){
            $_threadLikeData = $this->_getLikeReplyService()->getAllLikeUserids(PwLikeContent::THREAD, $_tids );
            foreach($_tids as $v){
                if( isset($_threadLikeData[$v]) ){
                    $threadLikeData[$v] = array_search($this->uid, $_threadLikeData[$v])===false?0:1;
                }
            }
        }
        //
        $myThreadList = $this->_getPwNativeThreadDs()->getThreadContent($tids);
        $attList        = $this->_getPwNativeThreadDs()->getThreadAttach($tids, array(0));
        $threadList     = $this->_getPwNativeThreadDs()->gather($myThreadList, $attList);
        //
        $postCount = $this->_getNativePostExpandDao()->countDisabledPostByUidAndFids($this->uid, $this->_getForumService()->fids);
        $data = array(
            'pageCount' =>$pageCount,
            'threadList'=>$threadList,
            'threadLikeData'=>$threadLikeData,
        );
        $this->setOutput($data, 'data');
        $this->showMessage('success');
    }


    /**
     * 收藏 
     * 
     * @access public
     * @return void
     * @example
     * <pre>
     * /index.php?m=native&c=My&a=addCollect <br>
     * post|get : fid&tid 
     * </pre>
     */
    public function addCollectAction(){
        $data = array(
            'created_userid'=>$this->uid,
            'fid'=>intval($this->getInput('fid')),
            'tid'=>intval($this->getInput('tid')),
            'created_time'=>time(),
        );
        if ($this->_getCollectService()->addCollect($data) !== false) {
            $thread = Wekit::load('forum.PwThread')->getThread($tid);
            PwLaiWangSerivce::sendNotification($thread['created_userid'],
            array('type'    => 1,
                  'message' => '《'.$thread['subject'].'》已被 '.$this->userInfo['username'].' 收藏。--系统消息，回复无效。'));
            //
            $this->showMessage('success');
        }
        $this->showError('fail');
    }

    /**
     * 取消收藏 
     * 
     * @access public
     * @return void
     * @example
     * <pre>
     * /index.php?m=native&c=My&a=delCollect <br>
     * post|get : tid 
     * </pre>
     */
    public function delCollectAction(){
        $tid = intval($this->getInput('tid'));
        if ($this->_getCollectService()->delCollect($this->uid, $tid) !== false) {
            $thread = Wekit::load('forum.PwThread')->getThread($tid);
            PwLaiWangSerivce::sendNotification($thread['created_userid'],
            array('type'    => 1,
                  'message' => '《'.$thread['subject'].'》已被 '.$this->userInfo['username'].' 取消收藏。--系统消息，回复无效。'));
            //
            $this->showMessage('success');
        }
        $this->showError('fail');
    }

    /**
     * 收藏的贴子列表 
     * 
     * @access public
     * @return void
     */
    public function collectAction(){
        $page = $this->getInput('page','get');
        $perpage = 20;
        //
        $collectCount = $this->_getCollectService()->countCollectByUidAndFids($this->uid, $this->_getForumService()->fids); 
        $collectCount = count($collectCount/$perpage);
        $page = $page ? $page : 1;
        $page>$collectCount && $page = $collectCount;

        list($start, $limit) = Pw::page2limit($page, $perpage);

        $tids = array();
        $collectList = $this->_getCollectService()->getCollectByUidAndFids($this->uid, $this->_getForumService()->fids, $limit, $offset);
        foreach ($collectList as $collect) {
            $tids[] = $collect['tid'];
        }

        //获得登陆用户是否喜欢过帖子|回复
        $_tids = $tids;
        $threadLikeData = array();
        if( $this->uid && $_tids ){
            $_threadLikeData = $this->_getLikeReplyService()->getAllLikeUserids(PwLikeContent::THREAD, $_tids );
            foreach($_tids as $v){
                if( isset($_threadLikeData[$v]) ){
                    $threadLikeData[$v] = array_search($this->uid, $_threadLikeData[$v])===false?0:1;
                }
            }
        }

        //帖子发布来源
        $threadFromtypeList = $this->_getThreadsPlaceService()->getThreadFormTypeByTids($_tids);

        $myThreadList   = $this->_getPwNativeThreadDs()->getThreadContent($tids);
        $attList        = $this->_getPwNativeThreadDs()->getThreadAttach($tids, array());
        $threadList     = $this->_getPwNativeThreadDs()->gather($myThreadList, $attList);

        //
        $data = array(
            'pageCount' =>$collectCount,
            'threadList'=>$threadList,
            'threadLikeData'=>$threadLikeData,
            'threadFromtypeList'=>$threadFromtypeList,
        );
        $this->setOutput($data, 'data');
        $this->showMessage('success');


    }

    private function _getAttentionService() {                                                                                                                    
        return Wekit::load('attention.srv.PwAttentionService');
    }

    private function _getUserBlack() {
        return Wekit::load('user.PwUserBlack');
    }

	private function _getAttentionTypeDs() {
		return Wekit::load('attention.PwAttentionType');
	}
	
	private function _getPwAttentionDs() {
		return Wekit::load('attention.PwAttention');
	}

    private function _getTagDs() {
        return Wekit::load('tag.PwTag');
    }

    private function _getTagService(){
        return Wekit::load('tag.srv.PwTagService');
    }

    private function _getForumService(){
        return Wekit::load('native.srv.PwForumService');
    }

    private function _getPwNativeThreadDs(){
        return Wekit::load('native.PwNativeThread');
    }

    private function _getLikeService() {                                                                                                                     
        return Wekit::load('like.srv.PwLikeService');
    }

    public function _getNativePostExpandDao(){
        return Wekit::loadDao('native.dao.PwNativePostExpandDao');
    }
    
    private function _getCollectService(){
        return Wekit::load('native.srv.PwNativeCollectService');
    }
    
    private function _getLikeReplyService() {
        return Wekit::load('like.srv.reply.do.PwLikeDoReply');
    }

    private function _getThreadsPlaceService(){
        return Wekit::load('native.srv.PwNativeThreadsPlace');
    }

}
