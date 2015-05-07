<?php
/**
 * 管理功能
 *
 * @fileName: ManageController.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2015-01-08 18:00:09
 * @desc: 
 **/
defined('WEKIT_VERSION') || exit('Forbidden');

Wind::import('SRV:forum.srv.PwThreadManage');
Wind::import('SRV:forum.srv.dataSource.PwFetchTopicByTid');
Wind::import('APPS:native.controller.NativeBaseController');

class ManageController extends NativeBaseController {

    public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);
        $this->checkUserSessionValid();
	}

    public $manage;

    /**
     * 管理操作 
     * 
     * @access public
     * @return void
     * @example
     * <pre>
     * 1) /index.php?m=native&c=Manage <br>
     * post: action=dodelete&sendnotice&(tid=|tids=1,2,3)&_json=1
     * <br><br>
     * 2) /index.php?m=native&c=Manage <br>
     * post: action=doban&types[]&end_time&reason&ban_range&sendnotice&uids[]&post&pids[] <br>
     * //全部功能参数
     * <code>
     * uids[]:3
     * types[]:1
     * types[]:2
     * types[]:4
     * end_time:2015-01-08 20:34
     * reason:建议已收集，谢谢反馈！
     * sendnotice:1
     * delete[current]:1
     * delete[site]:1
     * tid:28
     * pids[]:0
     * </code>
     * </pre>
     */
    public function run(){
        $action = $this->getInput('action');
        if( in_array($action,array('dodelete','doban','dodelete_reply'))==false ){
            $this->showError('fail');
        }

        $this->manage = $this->_getManage($action);
        if (($result = $this->manage->check()) !== true) {
            if (false === $result) $this->showError(new PwError('BBS:manage.permission.deny'));
            $this->showError($result->getError());
        }

        $this->manage->execute();
        $sendnotice = $this->getInput('sendnotice');
        if ($sendnotice) {
            $this->_sendMessage($action, $this->manage->getData());
        }
        $this->showMessage('operate.success');
    }

    /**
     * 获得用户拥有的权限 //这个权限在读贴子详细内容时获取，此接口暂时不用
     * 
     * @access public
     * @return void
     * @example
     * <pre>
     * post: tid
     * </pre>
     */
    private function permissionAction(){
        $tid = $this->getInput('tid');

        $userBo = new PwUserBo($this->uid);

        Wind::import('SRV:forum.srv.PwThreadDisplay');
        $threadDisplay = new PwThreadDisplay($tid, $userBo);
        $this->runHook('c_read_run', $threadDisplay);

        if (($result = $threadDisplay->check()) !== true) {
            $this->showError($result->getError());

        }
        $_cache = Wekit::cache()->fetch(array('level', 'group_right'));

        $pwforum = $threadDisplay->getForum();
        $isBM = $pwforum->isBM($userBo->username);
        if ($threadPermission = $userBo->getPermission('operate_thread', $isBM, array())) {
            $operateThread = Pw::subArray($threadPermission, array('delete','ban') );
            $operateReply = Pw::subArray($threadPermission, array('delete', 'ban') );
        }
        /**
         * if ($hasFirstPart || $hasSecondPart || $hasThirdPart) //只要是版主都可以推荐
         * $operateThread['delete']
         * $operateReply['ban']
         */
//        print_r($operateThread);
//        print_r($operateReply);
    }

    protected function _getManage($action) {
        $tids = $this->getInput('tids');
        $tid = $this->getInput('tid');
        if ($tids && !is_array($tids)) {
            $tids = explode(',', $tids);
        } elseif (!$tids && $tid) {
            $tids = array($tid);
        }else{
            $tids = array();
        }
        $manage = new PwThreadManage(new PwFetchTopicByTid($tids), new PwUserBo($this->uid));
        switch ($action) {
        case 'dodelete':
            $do = $this->_getDeleteManage($manage);
            break;
        case 'doban':
            $do = $this->_getBanManage($manage);
            break;
        case 'dodelete_reply':
            Wind::import('SRV:forum.srv.dataSource.PwFetchReplyByTidAndPids');
            $pids = $this->getInput('pids');
            $manage = new PwThreadManage(new PwFetchReplyByTidAndPids($tid, $pids), new PwUserBo($this->uid));
            $do = $this->_getDeleteReplyManage($manage);
            break;
        }
        if (is_array($do)) {
            foreach ($do as $do1) {
                $manage->appendDo($do1);
            }
        } else {
            $manage->appendDo($do);
        }
        return $manage;
    }

    protected function _getDeleteManage($manage) {
        Wind::import('SRV:forum.srv.manage.PwThreadManageDoDeleteTopic');
        $do = new PwThreadManageDoDeleteTopic($manage);

        //是否扣分
        $deductCredit = 1;
        $reason = '任性';
        $do->setIsDeductCredit($deductCredit)
            ->setReason($reason);
        return $do;
    }

    /** 
     * ban manage
     *  
     * @return PwThreadManageDoBan
     */
    protected function _getBanManage($manage) {
        Wind::import('SRV:forum.srv.manage.PwThreadManageDoBan');
        $do = new PwThreadManageDoBan($manage, new PwUserBo($this->uid));

        $banInfo = new stdClass();
        $banInfo->types = array(1,2,4);
        $banInfo->reason = '任性';
        $banInfo->ban_range = 0;
        $banInfo->sendNotice = 1;
        $banInfo->end_time = $this->getInput('end_time');
        $do->setBanInfo($banInfo)->setBanUids($this->getInput('uids'));

        return $do;
    }

    /**
     * 删除回复
     *
     * @param mixed $manage
     * @access protected
     * @return void
     */
    protected function _getDeleteReplyManage($manage) {
		Wind::import('SRV:forum.srv.manage.PwThreadManageDoDeleteReply');
        $do = new PwThreadManageDoDeleteReply($manage);
        $deductCredit = $this->getInput('deductCredit');
        $reason = '任性';
        $do->setIsDeductCredit($deductCredit)->setReason($reason);
        return $do;
	}


    /**
     * send messages
     */
    protected function _sendMessage($action, $threads) {
        $userBo = new PwUserBo($this->uid);
        switch($action){
        case 'doban':
            foreach ($threads as $v) {
                PwLaiWangSerivce::sendNotification($v['created_userid'], array(
                    'type'    => 5,
                    'message' => '您被管理员 '.$userBo->username
                      .' 禁止发帖了，同时您的头像、签名将不可见，如要申诉，请联系管理员。--系统消息，回复无效。',
                ));
            }
            return;
            break;
        case 'dodelete_reply':
            foreach ($threads as $v) {
                PwLaiWangSerivce::sendNotification($v['created_userid'], array(
                    'type'    => 4,
                    'message' => "您有一个回帖被删除：\n".mb_substr($v['content'],0,30),
                ));
            }
            return;
            break;
        }
        if (!is_array($threads) || !$threads || !$action || $action == 'doban') return false;
        $noticeService = Wekit::load('message.srv.PwNoticeService');
        $reason = $this->getInput('reason');
        foreach ($threads as $thread) {
            $params = array();
            $params['manageUsername'] = $this->manage->user->username;
            $params['manageUserid'] = $this->manage->user->uid;
            $params['manageThreadTitle'] = $thread['subject'];
            $params['manageThreadId'] = $thread['tid'];
            //$this->params['_other']['reason'] && $params['manageReason'] = $this->params['_other']['reason'];
            $reason && $params['manageReason'] = $reason;
            if ($action == 'docombined') {
                $actions = $this->getInput('actions');
                $tmp = array();
                foreach ($actions as $v){
                    $tmp[] = $this->_getManageActionName('do' . $v);
                }
                $tmp && $params['manageTypeString'] = implode(',', $tmp);
            } else {
                $params['manageTypeString'] = $this->_getManageActionName($action);
            }
            // laiwang
            PwLaiWangSerivce::sendNotification($thread['created_userid'], array(
                'type'    => 4,
                'message' => '您的帖子《'.$params['manageThreadTitle'].'》被管理员 '
                             .$userBo->username.' 执行了删除操作。--系统消息，回复无效',
            ));
            //
            $noticeService->sendNotice($thread['created_userid'], 'threadmanage', $thread['tid'], $params);
        }
    }

    protected function _getManageActionName($action) {
        $resource = Wind::getComponent('i18n');
        $message = $resource->getMessage("BBS:manage.operate.name.$action");
        if (in_array($action, $this->doCancel)) {
            $message = $resource->getMessage("BBS:manage.operate.action.cancel") . $message;
        }
        return $message;
    }
}
