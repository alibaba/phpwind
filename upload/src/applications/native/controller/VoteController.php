<?php
defined('WEKIT_VERSION') || exit('Forbidden');

/**
 * 投票帖子的接收投票接口
 *
 * @fileName: VoteController.php
 * @author: yuliang<yuliang.lyl@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-16 19:08:17
 * @desc: 
 * */
Wind::import('APPS:native.controller.NativeBaseController');

class VoteController extends NativeBaseController {

	public function beforeAction($handlerAdapter) {
		parent::beforeAction($handlerAdapter);
//                $this->uid = 1; //测试uid
                $this->loginUser = new PwUserBo($this->uid);
                $this->loginUser->resetGid($this->loginUser->gid);
		if (!$this->uid) $this->showError('VOTE:user.not.login');
	}
        
        /* 测试投票接口表单 */
        public function voteFormAction(){}
        
      /**
        * 投票帖子的接收投票接口
        * @access public
        * @return string
         <pre>
         /index.php?m=native&c=vote&_json=1
         post: apptype=0&typeid=帖子id&optionid[]=投票值
         response: {err:"",data:""}
         </pre>
        */
        public function run() {
            
//            var_dump($_POST);exit;
		if (!$this->loginUser->getPermission('allow_participate_vote')) $this->showError('VOTE:group.not.allow.participate');
		
		list($appType, $typeid, $optionid) = $this->getInput(array('apptype', 'typeid', 'optionid'));
		if (empty($optionid) || !is_array($optionid)) $this->showError('VOTE:not.select.option');

		$poll = $this->_serviceFactory($appType, $typeid);

		if ( ($result = $poll->check()) !== true) {
			$this->showError($result->getError());
		}
		
		if (!$poll->isInit()) $this->showError('VOTE:thread.not.exist');
		if ($poll->isExpired()) $this->showError('VOTE:vote.activity.end');
		$regtimeLimit = $poll->getRegtimeLimit();
		if ($regtimeLimit && $this->loginUser->info['regdate']  > $regtimeLimit) $this->showError(array('VOTE:vote.regtime.limit', array('{regtimelimit}'=> pw::time2str($regtimeLimit, 'Y-m-d'))));

		if ( ($result = $this->_getPollService()->doVote($this->loginUser->uid, $poll->info['poll_id'], $optionid)) !== true) {
			$this->showError($result->getError());
		}
		
		$this->showMessage('VOTE:vote.success');
	}
	
	public function forumlistAction() {
		$forums = Wekit::load('forum.PwForum')->getForumList(PwForum::FETCH_ALL);
		$service = Wekit::load('forum.srv.PwForumService');
		$map = $service->getForumMap();
		$cate = array();
		$forum = array();
		foreach ($map[0] as $key => $value) {
			if (!$value['isshow']) continue;
			$array = $service->findOptionInMap($value['fid'], $map, array('sub' => '--', 'sub2' => '----'));
			$tmp = array();
		
			foreach ($array as $k => $v) {
				$forumset = $forums[$k]['settings_basic'] ? unserialize($forums[$k]['settings_basic']) : array();
				$isAllowPoll = isset($forumset['allowtype']) && is_array($forumset['allowtype']) && in_array('poll', $forumset['allowtype']);
				 
				if ($forums[$k]['isshow'] && $isAllowPoll && (!$forums[$k]['allow_post'] || $this->loginUser->inGroup(explode(',', $forums[$k]['allow_post'])))) {
					$tmp[$k] = strip_tags($v);
				}
			}
			
			if ($tmp) {
				$cate[$value['fid']] = $value['name'];
				$forum[$value['fid']] = $tmp;
			}
		}
		
		$response = array(
			'cate' => $cate,
			'forum' => $forum
		);
		
		$this->setOutput(Pw::jsonEncode($response), 'data');
		$this->showMessage('success');
	}
	
	private function _serviceFactory($appType, $typeid) {
		switch ($appType) {
			case '0' : 
				Wind::import('SRV:poll.bo.PwThreadPollBo');
				$bo =  new PwThreadPollBo($typeid);
				break;
	
			default:
				Wind::import('SRV:poll.bo.PwThreadPollBo');
				$bo =  new PwThreadPollBo($typeid);
				break;
		}
		
		return $bo;
	}
	
	/**
	 * get PwPollService
	 *
	 * @return PwPollService
	 */
	private function _getPollService() {
		return Wekit::load('poll.srv.PwPollService');
	}
}