<?php
/**
 * 增加喜欢、删除喜欢接口
 *
 * @fileName: MylikeController.php
 * @author: yuliang<yuliang.lyl@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-16 19:08:17
 * @desc: 
 **/
defined('WEKIT_VERSION') || exit('Forbidden'); 

Wind::import('APPS:native.controller.MobileBaseController');

class MylikeController extends MobileBaseController {

	public function beforeAction($handlerAdapter) {
		parent::beforeAction($handlerAdapter);
        //if ($this->uid < 1) $this->forwardRedirect(WindUrlHelper::createUrl('u/login/run/'));
        $this->uid=3;
	}
        
	public function run() {
		$page = (int) $this->getInput('page', 'get');
		$tagid = (int) $this->getInput('tag', 'get');
		$perpage = 10;
		$page = $page > 1 ? $page : 1;
		$service = $this->_getBuildLikeService();
		$tagLists = $service->getTagsByUid($this->uid);
		if ($tagid > 0) {
			$resource = $this->_getLikeService()->allowEditTag($this->uid, $tagid);
			if ($resource instanceof PwError) $this->showError($resource->getError());
			$count = $resource['number'];
			$logids = $service->getLogidsByTagid($tagid, $page, $perpage);
			$logLists = $service->getLogLists($logids);
		} else {
			list($start, $perpage) = Pw::page2limit($page, $perpage);
			$count = $this->_getLikeLogDs()->getLikeCount($this->uid);
			$logLists = $service->getLogList($this->uid, $start, $perpage);
		}
		
		// start
		$json = array();
		foreach ($logLists AS $_log) {
			$_log['tags'] = array_unique((array)$_log['tags']);
			if (!$_log['tags']) continue;
			$tagJson = array();
			foreach ((array)$_log['tags'] AS $_tagid) {
				if (!isset($tagLists[$_tagid]['tagname'])) continue;
				$tagJson[] = array(
					'id'=>$_tagid,
					'value'=>$tagLists[$_tagid]['tagname'],
				);
			}
			$json[] = array(
				'id'=>$_log['logid'],
				'items'=>$tagJson,
			);
		}
		//end
		$likeLists = $service->getLikeList();
		$likeInfos = $service->getLikeInfo();
		$hotBrand = $this->_getLikeService()->getLikeBrand('day1', 0, 10, true);
		$args = $tagid > 0 ? array("tag" => $tagid) : array();
		$this->setOutput($args, 'args');
		$this->setOutput($logLists, 'logLists');
		$this->setOutput($likeLists, 'likeLists');
		$this->setOutput($likeInfos, 'likeInfos');
		$this->setOutput($tagLists, 'tagLists');
		$this->setOutput($hotBrand, 'hotBrand');
		$this->setOutput($count, 'count');
		$this->setOutput($page, 'page');
		$this->setOutput($perpage, 'perpage');
		$this->setOutput($json, 'likeJson');
		
		// seo设置
		Wind::import('SRV:seo.bo.PwSeoBo');
		$seoBo = PwSeoBo::getInstance();
		$lang = Wind::getComponent('i18n');
		$seoBo->setCustomSeo($lang->getMessage('SEO:like.mylike.run.title'), '', '');
		Wekit::setV('seo', $seoBo);
	}

	public function taAction() {
		// seo设置
		Wind::import('SRV:seo.bo.PwSeoBo');
		$seoBo = PwSeoBo::getInstance();
		$lang = Wind::getComponent('i18n');
		$seoBo->setCustomSeo($lang->getMessage('SEO:like.mylike.ta.title'), '', '');
		Wekit::setV('seo', $seoBo);
	}
	private function _getLikeRelationsService() {
		return Wekit::load('like.PwLikeRelations');
	}

	private function _getLikeTagService() {
		return Wekit::load('like.PwLikeTag');
	}

	private function _getBuildLikeService() {
		return Wekit::load('like.srv.PwBuildLikeService');
	}

	private function _getLikeService() {
		return Wekit::load('like.srv.PwLikeService');
	}
	
	private function _getLikeLogDs() {
		return Wekit::load('like.PwLikeLog');
	}
}
?>
