<?php

/**
 * 系统消息设置为已读状态
 *
 * @fileName: NoticeController.php
 * @author: yuliang<yuliang.lyl@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-16 19:08:17
 * @desc: 
 * */
Wind::import('APPS:native.controller.NativeBaseController');

class NoticeController extends NativeBaseController {

	public function beforeAction($handlerAdapter){
		parent::beforeAction($handlerAdapter);
//                $this->uid = 1; //测试uid
                $this->loginUser = new PwUserBo($this->uid);
                $this->loginUser->resetGid($this->loginUser->gid);
		if (!$this->uid) {
			$this->forwardRedirect(WindUrlHelper::createUrl('u/login/run'));
		}
		$action = $handlerAdapter->getAction();
		$controller = $handlerAdapter->getController();
		$this->setOutput($action,'_action');
		$this->setOutput($controller,'_controller');
	}
        
        /**
        * 查看未读系统通知列表，并将已查看的未读消息设为已读
        * @access public
        * @return string
         <pre>
         /index.php?m=native&c=notice&type=(reply|system)&page=1&_json=1
         type:10 未读通知；0 所有系统消息
         response: html
         </pre>
        */
	public function run() {
		list($type,$page) = $this->getInput(array('type','page'));
//                $type = 3;
                if($type=='reply'){
                    $typeids = array(10);//回复提醒
                    $exclude = false;
                }else{
                    $typeids = array(1,10);//排除私信提醒、回复提醒
                    $exclude = true;
                }
                
		$page = intval($page);
		$page < 1 && $page = 1;
		$perpage = 20;
		list($start, $limit) = Pw::page2limit($page, $perpage);
                $notice_list = Wekit::loadDao('native.dao.PwNativeMessageNoticesDao')->getNoticesByTypeIds($this->uid,$typeids,$start,$limit,$exclude);                
		$notice_list = $this->_getNoticeService()->formatNoticeList($notice_list);

//                $noticeList = $this->_getNoticeDs()->getNotices($this->loginUser->uid,$type,$start, $limit);
//		$noticeList = $this->_getNoticeService()->formatNoticeList($noticeList);
//                var_dump($noticeList);exit;
		$typeCounts = $this->_getNoticeService()->countNoticesByType($this->uid);//获取用户通知总数
		//类型
		$typeid = intval($type);
		//获取所有NOTICE未读通知数
		$unreadCount = $this->_getNoticeDs()->getUnreadNoticeCount($this->uid);
//                $unread_notice_cnt = Wekit::loadDao('native.dao.PwNativeMessageNoticesDao')->getUnreadCountByTypeIds($this->loginUser->uid,$typeids,$exclude);
//		$this->_readNoticeList($unreadCount,$noticeList);//将消息设置为已读
                $this->_readNoticeList($unreadCount,$notice_list);//将消息设置为已读
                var_dump($notice_list,$unreadCount);exit;

		//count
		$count = intval($typeCounts[$typeid]['count']);
		$this->setOutput($page, 'page');
		$this->setOutput($perpage, 'perpage');
		$this->setOutput($count, 'count');
		$this->setOutput(ceil($count/$perpage), 'totalpage');
		$this->setOutput(array('type'=>$typeid),'args');
		$this->setOutput($typeid, 'typeid');
		$this->setOutput($typeCounts, 'typeCounts');
		$this->setOutput($noticeList, 'noticeList');

		// seo设置
		Wind::import('SRV:seo.bo.PwSeoBo');
		$seoBo = PwSeoBo::getInstance();
		$lang = Wind::getComponent('i18n');
		$seoBo->setCustomSeo($lang->getMessage('SEO:mess.notice.run.title'), '', '');
		Wekit::setV('seo', $seoBo);
	}

	/**
	 *
	 * 忽略消息
	 */
	public function ignoreAction(){
		list($id,$ignore) = $this->getInput(array('id','ignore'));
		if ($this->_getNoticeService()->ignoreNotice($id,$ignore)) {
			$this->showMessage('操作成功');
		} else {
			$this->showError('操作失败');
		}
	}

	/**
	 *
	 * 删除消息
	 */
	public function deleteAction(){
		list($id,$ids) = $this->getInput(array('id','ids'), 'post');
		if (!$ids && $id) $ids = array(intval($id));
        if(!is_array($ids))$this->showError('操作失败');
		if ($this->_getNoticeDs()->deleteNoticeByIdsAndUid($this->loginUser->uid, $ids)) {
			$this->showMessage('操作成功');
		} else {
			$this->showError('操作失败');
		}
	}

	/**
	 *
	 * 顶部快捷列表
	 */
	public function minilistAction() {
            $perpage = 20;
            $noticeList = $this->_getNoticeDs()->getNoticesOrderByRead($this->loginUser->uid, $perpage);//message.PwMessageNotices;根据用户UID获取通知列表 按未读升序、更新时间倒序
            $noticeList = $this->_getNoticeService()->formatNoticeList($noticeList);//message.srv.PwNoticeService
            //获取用户未读通知数（系统消息+私信消息）
            $unreadCount = $this->_getNoticeDs()->getUnreadNoticeCount($this->loginUser->uid);
//            var_dump($unreadCount);exit;
            $this->_readNoticeList($unreadCount, $noticeList);
            //set layout for common request
            if (!$this->getRequest()->getIsAjaxRequest()) {
                $this->setLayout('layout_notice_minilist');
            }
            $this->setOutput($noticeList, 'noticeList');
        }
        
        /**
         * 设置系统消息全部已读
         * lyl
         */
        public function checkNoticeReadedAction() {
            //获取用户未读通知数（系统消息+私信消息）
            $unreadCount = $this->_getNoticeDs()->getUnreadNoticeCount($this->uid);
            $perpage = intval($unreadCount) ? intval($unreadCount) : 20;
            $noticeList = $this->_getNoticeDs()->getNoticesOrderByRead($this->uid, $perpage);//message.PwMessageNotices;根据用户UID获取通知列表 按未读升序、更新时间倒序
//            var_dump($noticeList);exit;
            $noticeList = $this->_getNoticeService()->formatNoticeList($noticeList);//message.srv.PwNoticeService
//            var_dump($unreadCount,$noticeList);exit;
            //将未读消息标记已读
            $this->_readNoticeList($unreadCount, $noticeList);
            //set layout for common request
            $this->showMessage('success');
//            exit;
//            if (!$this->getRequest()->getIsAjaxRequest()) {
//                $this->setLayout('layout_notice_minilist');
//            }
//            $this->setOutput($noticeList, 'noticeList');
        }

        /**
	 *
	 * 具体通知详细页
	 */
	public function detaillistAction(){
		$id = $this->getInput('id');
		$notice = $this->_getNoticeDs()->getNotice($id);
		if (!$notice || $notice['uid'] != $this->loginUser->uid) {
			$this->showError('获取内容失败');
		}

		$detailList = $this->_getNoticeService()->getDetailList($notice);
		$this->setOutput($notice, 'notice');
		$this->setOutput($detailList,'detailList');
		$typeName = $this->_getNoticeService()->getTypenameByTypeid($notice['typeid']);
		$this->setOutput($typeName, 'typeName');
		//$tpl = $typeName ? sprintf('notice_detail_%s',$typeName) : 'notice_detail';
		//$this->setTemplate($tpl);
	}

	/**
	 *
	 * 具体通知详细页
	 */
	public function detailAction(){
		$id = $this->getInput('id');
		$notice = $this->_getNoticeDs()->getNotice($id);
		if (!$notice || $notice['uid'] != $this->loginUser->uid) {
			$this->showError('获取内容失败');
		}
		$prevNotice = $this->_getNoticeDs()->getPrevNotice($this->loginUser->uid,$id);
		$nextNotice = $this->_getNoticeDs()->getNextNotice($this->loginUser->uid,$id);
		$detailList = $this->_getNoticeService()->getDetailList($notice);
		$this->setOutput($notice, 'notice');
		$this->setOutput($detailList,'detailList');
		$this->setOutput($prevNotice, 'prevNotice');
		$this->setOutput($nextNotice, 'nextNotice');
		$typeName = $this->_getNoticeService()->getTypenameByTypeid($notice['typeid']);
		$this->setOutput($typeName, 'typeName');
		//$tpl = $typeName ? sprintf('notice_detail_%s',$typeName) : 'notice_detail';
		//$this->setTemplate($tpl);
	}

	/**
	 *
	 * Enter description here ...
	 * @return PwMessageNotices
	 */
	protected function _getNoticeDs(){
		return Wekit::load('message.PwMessageNotices');
	}

	/**
	 *
	 * Enter description here ...
	 * @return PwNoticeService
	 */
	protected function _getNoticeService(){
		return Wekit::load('message.srv.PwNoticeService');
	}

	/**
	 *
	 * Enter description here ...
	 * @return PwUser
	 */
	protected function _getUserDs(){
		return Wekit::load('user.PwUser');
	}

	/**
	 *
	 * 设置已读
	 * @param int $unreadCount
	 * @param array $noticeList
	 */
	private function _readNoticeList($unreadCount,$noticeList){
		if ($unreadCount && $noticeList) {
			//更新用户的通知未读数
			$readnum = 0; //本次阅读数
			Wind::import('SRV:message.dm.PwMessageNoticesDm');
			$dm = new PwMessageNoticesDm();
			$dm->setRead(1);
			$ids = array();
			foreach ($noticeList as $v) {//本次一页消息中未读的数量
				if ($v['is_read']) continue;
				$readnum ++;
				$ids[] = $v['id'];
			}
			$ids && $this->_getNoticeDs()->batchUpdateNotice($ids,$dm);//message.PwMessageNotices
			$newUnreadCount = $unreadCount - $readnum;//所有未读消息-本次已读消息
			if ($newUnreadCount != $unreadCount) {
				Wind::import('SRV:user.dm.PwUserInfoDm');
				$dm = new PwUserInfoDm($this->uid);
				$dm->setNoticeCount($newUnreadCount);
				$this->_getUserDs()->editUser($dm,PwUser::FETCH_DATA);
			}
		}
	}
}