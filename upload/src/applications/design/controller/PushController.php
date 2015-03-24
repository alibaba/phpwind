<?php
Wind::import('LIB:base.PwBaseController');
/**
 * the last known user to change this file in the repository  <$LastChangedBy: gao.wanggao $>
 * @author $Author: gao.wanggao $ Foxsee@aliyun.com
 * @copyright ©2003-2103 phpwind.com
 * @license http://www.phpwind.com
 * @version $Id: PushController.php 28899 2013-05-29 07:23:48Z gao.wanggao $ 
 * @package 
 */
class PushController extends PwBaseController{
	
	public function beforeAction($handlerAdapter) {
		parent::beforeAction($handlerAdapter);
		Wekit::load('design.PwDesignPermissions');
		$permissions = $this->_getPermissionsService()->getPermissionsForUserGroup($this->loginUser->uid);
		if ($permissions < PwDesignPermissions::NEED_CHECK ) $this->showError("DESIGN:permissions.fail");
	}
	
	public function addAction() {
		$fromid = (int)$this->getInput('fromid', 'get');
		$fromtype = $this->getInput('fromtype', 'get');
		if (!$fromtype)  $this->showError("operate.fail");
		$data = $this->_getPushService()->getDataByFromid($fromtype, $fromid);
		if (!$data) $this->showError("operate.fail");
		$pageList = $this->_getPermissionsService()->getPermissionsAllPage($this->loginUser->uid);
		if (!$pageList) $this->showError("push.page.empty");
                //增加推送帖子到移动版
                $pageList[0] = Array(
                                    'page_id' => 0,
                                    'page_type' => 0,
                                    'page_name' => "移动端首页",
                                    'page_router' =>"",
                                    'page_unique' => 0,
                                    'is_unique' => 0,
                                    'module_ids' =>"",
                                    'struct_names' => "",
                                    'segments' => "",
                                    'design_lock' =>""
                                );
		$this->setOutput($pageList, 'pageList');
		
		$first = array_shift($pageList);
		$moduleList = $this->_getModuleDs()->fetchModule(explode(',', $first['module_ids']));
		foreach ($moduleList AS $k=>$module) {
			if ($module['model_flag'] != $fromtype) {
				unset($moduleList[$k]);
			}
		}
		$this->setOutput($moduleList, 'moduleList');
		$this->setOutput($fromtype, 'fromtype');
		$this->setOutput($fromid, 'fromid');
	}
	
	public function getmoduleAction() {
		$option = '';
		$pageid = (int)$this->getInput('pageid', 'post');
		$fromtype = $this->getInput('fromtype', 'post');
		$permissions = $this->_getPermissionsService()->getPermissionsForPage($this->loginUser->uid, $pageid);
		if ($permissions < PwDesignPermissions::NEED_CHECK ) {
			$option = '<option value="">无可用模块</option>';
			$this->setOutput($option, 'data');
			$this->showMessage("operate.success");
		}
		$moduleList = $this->_getModuleDs()->getByPageid($pageid);
		foreach ($moduleList AS $v) {
			if ($v['model_flag'] != $fromtype) continue;
			$option .= '<option value="'.$v['module_id'].'">'.$v['module_name'].'</option>';
		}
                $pageid === 0 && $option = '<option value="0">最热</option>';//增加推送帖子到移动版
		if (!$option) $option = '<option value="">无可用模块</option>';
		$this->setOutput($option, 'html');
		$this->showMessage("operate.success");
	}
	
	
	public function doaddAction() {
		$pageid = (int)$this->getInput('pageid', 'post');
		$moduleid = (int)$this->getInput('moduleid', 'post');
		$isnotice = (int)$this->getInput('isnotice', 'post');
		$fromid = (int)$this->getInput('fromid', 'post');
		$fromtype = $this->getInput('fromtype', 'post');
		$start = $this->getInput('start_time', 'post');
		$end = $this->getInput('end_time', 'post');
                /* 增加推送帖子到移动版start */
                if($pageid===0 && $moduleid===0){//推送移动端热帖
                    $tid = (int)$this->getInput('tid');
                    $tid || $this->showError("operate.fail");
//                    $this->forwardRedirect(WindUrlHelper::createUrl('native/dynamic/sethot'));
                    $threadsWeightDao = Wekit::loadDao('native.dao.PwThreadsWeightDao');

                    //获取帖子最高权重，将其作为管理员推送帖子的初始权重置顶
                    $weightData = $threadsWeightDao->getMaxWeight();
                    isset($weightData['weight']) ? $max_weight = intval($weightData['weight'])+1:1;
                    //
                    $data = array(
                        'create_time'   =>time(),
                        'weight'        =>$max_weight,
                        'create_userid' =>$this->loginUser->uid,
                        'create_username'=>$this->loginUser->username,
                        'tid'           =>$tid,
                    );
                    $threadWeight = $threadsWeightDao->getByTid($tid);
                    
                    if($threadWeight){//更新数据
                        $res = $threadsWeightDao->updateValue($data);
                    }else{//新增数据
                        $res = $threadsWeightDao->insertValue($data);
                    }
                    if($res){
                        $thread = Wekit::load('forum.PwThread')->getThread($tid);
                        $push_msg = '《'.$thread['subject'].'》已被推荐热贴'; 
                        Wekit::load("APPS:native.service.PwLaiWangSerivce");
                        PwLaiWangSerivce::pushMessage($thread['created_userid'], $push_msg, $push_msg); 
                        //
                        $this->showMessage('NATIVE:sethot.success');
                    }else{
                        $this->showMessage('NATIVE:sethot.failed');
                    }                                  
                }
                /* 增加推送帖子到移动版end */
		if ($moduleid < 1) $this->showError("operate.fail");
		$permiss = $this->_getPermissionsService()->getPermissionsForModule($this->loginUser->uid, $moduleid, $pageid);
		$pushService = $this->_getPushService();
		$data = $pushService->getDataByFromid($fromtype, $fromid);
		
		Wind::import('SRV:design.bo.PwDesignModuleBo');
		$bo = new PwDesignModuleBo($moduleid);
		$time = Pw::getTime();
		$startTime = $start ? Pw::str2time($start) : $time;
		$endTime = $end ? Pw::str2time($end) : $end;
		if ($end && $endTime < $time) $this->showError("DESIGN:endtimd.error");
		$pushDs = $this->_getPushDs();
		Wind::import('SRV:design.dm.PwDesignPushDm');
 		$dm = new PwDesignPushDm();
 		$dm->setFromid($fromid)
 			->setModuleId($moduleid)
 			->setCreatedUserid($this->loginUser->uid)
 			->setCreatedTime($time)
 			->setStartTime($startTime)
 			->setEndTime($endTime)
 			->setAuthorUid($data['uid']);
 		if ($isnotice) $dm->setNeedNotice(1);
 		if ($permiss <= PwDesignPermissions::NEED_CHECK) {
 			$dm->setStatus(PwDesignPush::NEEDCHECK);
 			$isdata = false;
 		} else {
 			$isdata = true;
 		}
		$resource = $pushService->addPushData($dm);
		if ($resource instanceof PwError) $this->showError($resource->getError());
		
		if ($isdata) {
			$pushService->pushToData((int)$resource);
			$pushService->afterPush((int)$resource);
		}
		$this->showMessage("operate.success");
	}
	
	private function _getDesignService() {
		return Wekit::load('design.srv.PwDesignService');
	}

	private function _getPushService() {
		return Wekit::load('design.srv.PwPushService');
	}
	
	protected function _getPermissionsService() {
		return Wekit::load('design.srv.PwDesignPermissionsService');
	}
	
	private function _getModuleDs() {
		return Wekit::load('design.PwDesignModule');
	}
	
	private function _getPageDs() {
		return Wekit::load('design.PwDesignPage');
	}
	
	private function _getPushDs() {
		return Wekit::load('design.PwDesignPush');
	}
}
?>