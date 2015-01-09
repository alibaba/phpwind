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

class MyController extends NativeBaseController {

    /**
     * global post: securityKey
     */
    public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);
        //$this->checkUserSessionValid();
        $this->uid=3;
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
        $uid = $this->getInput('uid', 'post');
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
        $uid = $this->getInput('uid', 'post');
		if (!$uid) {
			$this->showError('operate.select');
        }
		$result = $this->_getAttentionService()->deleteFollow($this->uid, $uid);
		if ($result instanceof PwError) {
			$this->showError($result->getError());
		}
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
        $uid = (int)$this->getInput('uid', 'post');
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
		$url = $classCurrent = array();
		
        $typeCounts = $this->_getAttentionTypeDs()->countUserType($this->uid);
        $follows = $this->_getPwAttentionDs()->getFollows($this->uid, $limit, $start);
        $count = $this->info['follows'];
        $classCurrent[0] = 'current';
        $uids = array_keys($follows);
		$fans = $this->_getPwAttentionDs()->fetchFans($this->uid, $uids);
        $userList = Wekit::load('user.PwUser')->fetchUserByUid($uids, PwUser::FETCH_MAIN );
        //
        if( $userList ){
            foreach($userList as $key=>$user){
                $userList[] = array(
                    'uid'       =>$user['uid'],
                    'username'  =>$user['username'],
                    'avatar'    =>Pw::getAvatar($user['uid'],'big'),
                );
                unset($userList[$key]);
            }
        }
        $this->setOutput($userList, 'data');
        $this->showMessage('success');
    }


    /**
     * 我关注的话题 
     * 
     * @access public
     * @return void
     * @example
     * <pre>
     * /index.php?m=native&c=Space&a=tag&page=1 <br>
     * </pre>
     */
    public function tagAction(){
        $page = intval($this->getInput('page','get'));
		$page < 1 && $page = 1;
		$perpage = 20;
        //
        $tags = $this->_getTagDs()->getAttentionByUid($this->uid,($page-1)*$perpage,$perpage);
        if($tags){
            foreach ($tags as $key=>$tag) {
                $tags[] = $tag['tag_name'];
                unset($tags[$key]);
            }
        }
        $this->setOutput($tags, 'data');
        $this->showMessage('success');
    }

    /**
     * 我关注的频道 
     * @access public
     * @return void
     * @example
     * <pre>
     * /index.php?m=native&c=My&a=forum
     * </pre>
     */
    public function forumAction(){
        $userInfo = $this->_getUserDs()->getUserByUid($this->uid, PwUser::FETCH_MAIN+PwUser::FETCH_INFO+PwUser::FETCH_DATA);
        //
        $_fids = array();
        $userInfo['join_forum'] && $_fids = self::splitStringToArray($userInfo['join_forum']); 
        //
        $forumList=$this->_getForumService()->fetchForum( array_intersect($_fids,$this->_getForumService()->fids) );
        $this->setOutput($forumList,'data');
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
        $typeid = (int) $this->getInput('typeid', 'post');
        $fromid = (int) $this->getInput('fromid', 'post');
        if ($typeid < 1 || $fromid < 1) $this->showError('BBS:like.fail');
        //
        $userBo = new PwUserBo();
        $userBo->uid=$this->uid;
        //
        $resource = $this->_getLikeService()->addLike($userBo, $typeid, $fromid);
        if ($resource instanceof PwError) $this->showError($resource->getError());

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
        $logid = (int) $this->getInput('logid', 'post');
        if (!$logid) $this->showError('BBS:like.fail');
        $resource = $this->_getLikeService()->delLike($this->uid, $logid);
        if ($resource) $this->showMessage('BBS:like.success');
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
        $array          = $this->_getPwNativeThreadDs()->getThreadListByUid($this->uid, $page, 'my');
        $myThreadList   = $this->_getPwNativeThreadDs()->getThreadContent($array['tids']);
        $attList        = $this->_getPwNativeThreadDs()->getThreadAttach($array['tids'], $array['pids']);
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
        $tids = $pids = array();
        $threads = $this->_getNativePostExpandDao()->getDisabledPostByUid($this->uid, $this->_getForumService()->fids, $limit, $start);
        foreach ($threads as $thread) {
            $tids[] = $thread['tid'];
            $thread['aids'] && $pids[] = $thread['aids'];
        }   
        $array = array(
            'tids'=>$tids,
            'pids'=>$pids,
        );
        $myThreadList   = $this->_getPwNativeThreadDs()->getThreadContent($array['tids']);
        $attList        = $this->_getPwNativeThreadDs()->getThreadAttach($array['tids'], $array['pids']);
        $threadList     = $this->_getPwNativeThreadDs()->gather($myThreadList, $attList);
        //
        $postCount = $this->_getNativePostExpandDao()->countDisabledPostByUidAndFids($this->uid, $this->_getForumService()->fids);
        $data = array(
            'pageCount'=>$pageCount,
            'threadList'=>$threadList,
        );
        $this->setOutput($data, 'data');
        $this->showMessage('success');
    }


    /**
     * 格式化数据  把字符串"1,版块1,2,版块2"格式化为数组
     *
     * @param string $string
     * @return array
     */
    protected static function splitStringToArray($string) {                                                                                                     
        $a = explode(',', $string);
        $l = count($a);
        $l % 2 == 1 && $l--;
        $r = array();
        for ($i = 0; $i < $l; $i+=2) {
            $r[$a[$i]] = $a[$i];
        }
        return $r;
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

}
