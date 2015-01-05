<?php
/**
 * 关于我,空间的所有接口集合
 *
 * @fileName: SpaceController.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-15 19:09:45
 * @desc: 
 **/
defined('WEKIT_VERSION') || exit('Forbidden');

Wind::import('APPS:native.controller.MobileBaseController');

class SpaceController extends MobileBaseController {

    private $uid = 0;

    /**
     * global post: securityKey
     */
	public function beforeAction($handlerAdapter) {
		parent::beforeAction($handlerAdapter);
        //$this->uid=$this->checkUserSessionValid();
        $this->uid=3;
	}

    /**
     * 关注一个人 
     * 
     * @access public
     * @return void
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
     */
    public function unFollowAction(){
        $uid = $this->getInput('uid', 'get');
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
     */
    public function myFollowAction(){
		
		$type = $this->getInput('type');
        $page = intval($this->getInput('page','get'));
		$page < 1 && $page = 1;
		$perpage = 20;
		list($start, $limit) = Pw::page2limit($page, $perpage);
		$url = $classCurrent = array();
		
		$typeCounts = $this->_getAttentionTypeDs()->countUserType($this->uid);
		if ($type) {
			$tmp = $this->_getAttentionTypeDs()->getUserByType($this->uid, $type, $limit, $start);
			$follows = $this->_getPwAttentionDs()->fetchFollows($this->uid, array_keys($tmp));
			$count = $typeCounts[$type] ? $typeCounts[$type]['count'] : 0;
			$url['type'] = $type;
			$classCurrent[$type] = 'current';
		} else {
			$follows = $this->_getPwAttentionDs()->getFollows($this->uid, $limit, $start);
			$count = $this->info['follows'];
			$classCurrent[0] = 'current';
		}
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
     */
    public function myTagAction(){
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
     */
    public function forumAction(){
        
    }

    /**
     * 我关注的话题
     * @access public
     * @return void
     */
    public function tagAction(){

    }

    /**
     * 我收藏的贴子||喜欢的贴子 
     * @access public
     * @return void
     */
    public function likeAction(){

    }

    /**
     * 我发布的帖子 
     * @access public
     * @return void
     */
    public function issuedByIAction(){
        
    }
    
    /**
     * 我回复的帖子 
     * @access public
     * @return void
     */
    public function replyByIAction(){

    }


    private function _getAttentionService() {                                                                                                                    
        return Wekit::load('attention.srv.PwAttentionService');
    }

    private function _getUserBlack() {
        return Wekit::load('user.PwUserBlack');
    }

    private function _getUserDs(){
        return Wekit::load('user.PwUser');
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
/*
    private function _getTagService() {                                                                                                                   
        return Wekit::load('tag.srv.PwTagService');
    }	
    
    private function _getTagAttentionDs(){                                                                                                                
        return Wekit::load('tag.PwTagAttention');
    }
 */
}
