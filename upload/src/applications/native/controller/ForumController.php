<?php

Wind::import('APPS:native.controller.NativeBaseController');
Wind::import('SRV:forum.srv.PwThreadList');

/**
 * 加入版块、退出版块操作接口
 *
 * @fileName: ForumController.php
 * @author: yuliang<yuliang.lyl@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-16 19:08:17
 * @desc: 
 * */

class ForumController extends NativeBaseController {
       
    public function beforeAction($handlerAdapter) {
//        var_dump($this);exit;
        parent::beforeAction($handlerAdapter);
//		if (!$this->loginUser->isExists()) $this->showError('VOTE:user.not.login');
//        $this->uid = 1; //测试uid
        $this->loginUser = new PwUserBo($this->uid);
        $this->loginUser->resetGid($this->loginUser->gid);
    }
    
    public function run(){}

    /**
        * 加入版块
        * @access public
        * @return string
         <pre>
         /index.php?m=native&c=forum&a=join&_json=1
         * post:fid=版块分类id
         response: html
         </pre>
        */
	public function joinAction() {
//		$fid = $this->getInput('fid', 'post');
                $fid = $this->getInput('fid');
		if (!$fid) {
			$this->showError('operate.fail');
		}

		Wind::import('SRV:forum.bo.PwForumBo');
		$forum = new PwForumBo($fid);
		if (!$forum->isForum()) {
			$this->showError('BBS:forum.exists.not');
		}
		if (!$this->uid) {
			$this->showError('login.not');
		}
		if (Wekit::load('forum.PwForumUser')->get($this->uid, $fid)) {
			$this->showError('BBS:forum.join.already');
		}
		Wekit::load('forum.PwForumUser')->join($this->uid, $fid);
		$this->_addJoionForum($this->loginUser->info, $forum->foruminfo);
		$this->showMessage('success');
	}

        /**
        * 退出版块
        * @access public
        * @return string
         <pre>
         /index.php?m=native&c=forum&a=quit&_json=1
         * post:fid=版块分类id
         response: html
         </pre>
        */
	public function quitAction() {
//		$fid = $this->getInput('fid', 'post');
                $fid = $this->getInput('fid');

		if (!$fid) {
			$this->showError('operate.fail');
		}

		Wind::import('SRV:forum.bo.PwForumBo');
		$forum = new PwForumBo($fid);
		if (!$forum->isForum()) {
			$this->showError('BBS:forum.exists.not');
		}
		if (!$this->uid) {
			$this->showError('login.not');
		}
		if (!Wekit::load('forum.PwForumUser')->get($this->uid, $fid)) {
			$this->showError('BBS:forum.join.not');
		}
		Wekit::load('forum.PwForumUser')->quit($this->uid, $fid);
		$this->_removeJoionForum($this->loginUser->info, $fid);
		$this->showMessage('success');
	}

	

	
	
	/**
	 * 格式化数据  把字符串"1,版块1,2,版块2"格式化为数组
	 *
	 * @param string $string
	 * @return array
	 */
	public static function splitStringToArray($string) {
		$a = explode(',', $string);
		$l = count($a);
		$l % 2 == 1 && $l--;
		$r = array();
		for ($i = 0; $i < $l; $i+=2) {
			$r[$a[$i]] = $a[$i+1];
		}
		return $r;
	}
	
	/**
	 * 加入版块 - 更新我的版块缓存数据
	 *
	 * @param array $userInfo
	 * @param array $foruminfo
	 * @return boolean
	 */
	private function _addJoionForum($userInfo,$foruminfo) {
		// 更新用户data表信息
		$array = array();
		$userInfo['join_forum'] && $array = self::splitStringToArray($userInfo['join_forum']);
		$array = array($foruminfo['fid'] => $foruminfo['name']) + $array;
		count($array) > 20 && $array = array_slice($array, 0, 20, true);
		
		$this->_updateMyForumCache($userInfo['uid'], $array);
		return true;
	}
	
	/**
	 * 推出版块 - 更新我的版块缓存数据
	 *
	 * @param array $userInfo
	 * @param int $fid
	 * @return boolean
	 */
	private function _removeJoionForum($userInfo,$fid) {
		// 更新用户data表信息
		$userInfo['join_forum'] && $array = self::splitStringToArray($userInfo['join_forum']);
		unset($array[$fid]);
		
		$this->_updateMyForumCache($userInfo['uid'], $array);
		return true;
	}

	private function _updateMyForumCache($uid, $array) {
		$joinForums = Wekit::load('forum.srv.PwForumService')->getJoinForum($uid);
		$_tmpArray = array();
		foreach ($array as $k => $v) {
			if (!isset($joinForums[$k])) continue;
			$_tmpArray[$k] = strip_tags($joinForums[$k]);
		}
		
		Wind::import('SRV:user.dm.PwUserInfoDm');
		$dm = new PwUserInfoDm($uid);
		$dm->setJoinForum(self::_formatJoinForum($_tmpArray));
		return $this->_getUserDs()->editUser($dm, PwUser::FETCH_DATA);	
	}
	
	/**
	 * 格式化我的版块缓存数据结构
	 *
	 * @param array $array 格式化成"1,版块1,2,版块2"
	 * @return string
	 */
	private static function _formatJoinForum($array) {
		if (!$array) return false;
		$user = '';
		foreach ($array as $fid => $name) {
			$myForum .= $fid . ',' . $name . ',';
		}
		return rtrim($myForum,',');
	}
	
	/**
	 * @return PwUser
	 */
	protected function _getUserDs(){
		return Wekit::load('user.PwUser');
	}
       
	/**
	 * @return PwForum
	 */
	private function _getForumService() {
		return Wekit::load('forum.PwForum');
	}
}