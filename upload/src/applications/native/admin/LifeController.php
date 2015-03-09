<?php
Wind::import('ADMIN:library.AdminBaseController');
/**
 * 生活服务后台操作
 *
 * @fileName: LifeController.php
 * @author: yuliang<yuliang.lyl@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-16 19:08:17
 * @desc: 
 * */
class LifeController extends AdminBaseController {

	private $perpage = 20;
        
        public function beforeAction($handlerAdapter) {
            parent::beforeAction($handlerAdapter);
	}
	
	/**
	 * 菜单管理主入口
	 * lyl
	 * @return void
	 */
	public function run() {
		$forumLifeDao = Wekit::loadDao('native.dao.PwForumLifeDao');
                $res = $forumLifeDao->fetchForumLifeList();
                foreach($res as $k=>$v){
                    $res[$k]['name'] = strip_tags($v['name']);
                }
                $this->setOutput($res, 'forums');
//                var_dump($res);
//                exit;
                /*
		$forumService = $this->_getFroumService();
		$map = $forumService->getForumMap();
		$catedb = $map[0];

		foreach ($catedb as $key => $value) {
			$forumList[$value['fid']] = $forumService->getForumsByLevel($value['fid'], $map);
		}

		$this->setOutput($catedb, 'catedb');
		$this->setOutput($forumList, 'forumList');
		$this->setOutput($forumService->getForumOption(), 'option_html');
                 * */
	}
	
	/**
	 * 添加版块、修改版块排序、修改版主等操作
	 * lyl
	 * @return void
	 */
	public function dorunAction() {
		$this->getRequest()->isPost() || $this->showError('operate.fail');

		/**
		 * 修改版块资料
		 */
		list($vieworder, $manager) = $this->getInput(array('vieworder', 'manager'), 'post');
		//TODO 添加：先判断这些会员里是否含有身份不符合的用户，用户组1（游客）,2（禁止发言）,6（未验证用户）
		$_tmpManager = explode(',', implode(',', array_unique($manager)));
		$result = Wekit::load('SRV:user.srv.PwUserMiscService')->filterForumManger($_tmpManager);
		if ($result instanceof PwError) {
			$this->showError($result->getError());
		}
		
		$editArray = array();
		Wind::import('SRV:forum.dm.PwForumDm');
		foreach ($vieworder as $key => $value) {
			$dm = new PwForumDm($key);
			$dm->setVieworder($value)->setManager($manager[$key]);
			if (($result = $dm->beforeUpdate()) instanceof PwError) {
				$this->showError($result->getError(), 'bbs/setforum/run/');
			}
			$editArray[] = $dm;
		}
		$pwForum = Wekit::load('forum.PwForum');
		foreach ($editArray as $dm) {
			$pwForum->updateForum($dm, PwForum::FETCH_MAIN);
		}

		$forumset = array(
			'allowtype' => array('default'),
			'typeorder' => array('default' => 0)
		);
	
		/**
		 * 在真实版块下，添加子版
		 */
		list($new_vieworder, $new_forumname, $new_manager, $tempid) = $this->getInput(array('new_vieworder', 'new_forumname', 'new_manager', 'tempid'), 'post');
		$newArray = array();
		is_array($new_vieworder) || $new_vieworder = array();
		foreach ($new_vieworder as $parentid => $value) {
			foreach ($value as $key => $v) {
				if ($tempid[$parentid][$key] && $new_forumname[$parentid][$key]) {
					$dm = new PwForumDm();
					$dm->setParentid($parentid)
						->setName($new_forumname[$parentid][$key])
						->setVieworder($v)
						->setManager($new_manager[$parentid][$key])
						->setBasicSetting($forumset);
					if (($result = $pwForum->addForum($dm)) instanceof PwError) {
						$this->showError($result->getError(), 'bbs/setforum/run/');
					}
					$newArray[$tempid[$parentid][$key]] = $result;
				}
			}
		}
		
		/**
		 * 在虚拟版块下，添加子版
		 */
		list($temp_vieworder, $temp_forumname, $temp_manager) = $this->getInput(array('temp_vieworder', 'temp_forumname', 'temp_manager'), 'post');
		is_array($temp_vieworder) || $temp_vieworder = array();
		ksort($temp_vieworder);
		foreach ($temp_vieworder as $key => $value) {
			if (!isset($newArray[$key])) continue;
			foreach ($value as $k => $v) {
				if ($tempid[$key][$k] && $temp_forumname[$key][$k]) {
					$dm = new PwForumDm();
					$dm->setParentid($newArray[$key])
						->setName($temp_forumname[$key][$k])
						->setVieworder($v)
						->setManager($temp_manager[$key][$k])
						->setBasicSetting($forumset);
					if (($result = $pwForum->addForum($dm)) instanceof PwError) {
						$this->showError($result->getError(), 'bbs/setforum/run/');
					}
					$newArray[$tempid[$key][$k]] = $result;
				}
			}
		}
		Wekit::load('forum.srv.PwForumMiscService')->correctData();

		$this->showMessage('success', 'native/life/run/', true);
	}

	/**
	 * 编辑版块信息form
         * lyl
	 */
	public function editAction() {
		$fid = $this->getInput('fid');
		Wind::import('SRV:forum.bo.PwForumBo');
		$forum = new PwForumBo($fid, true);
		if (!$forum->isForum(true)) {
			$this->showMessage('版块不存在', 'native/life/run', true);
		}
		$this->setOutput($forum->foruminfo, 'foruminfo');
                $forumLifeDao = Wekit::loadDao('native.dao.PwForumLifeDao');
                $forumLife = $forumLifeDao->getForumLife($fid);
                $this->setOutput($forumLife, 'forumlife');
	}
        
        /**
	 * 执行编辑版块信息
         * lyl
	 */
	public function doeditAction() {
		$fid = $this->getInput('fid', 'post');
		if (!$fid) {
			$this->showError('operate.fail');
		}
                if($_FILES['logo']['size']>300000){
                    $this->showError("图片大小不能超过300k", 'native/life/run/',true);
                }
                Wind::import('SRV:forum.dm.PwForumDm');
                $pwForum = Wekit::load('forum.PwForum');
                //修改公共服务版面
                list($forumname, $manager, $vieworder, $descrip,$isshow,$url,$address) = $this->getInput(array('forumname', 'manager', 'vieworder', 'descrip','isshow','url','address'), 'post');
                if(!$forumname) $this->showError("商家名称不能为空", 'native/life/run/',true);
                if(Pw::strlen($address)>100) $this->showError("商家地址不能超过100个汉字", 'native/life/run/',true);
                $dm = new PwForumDm($fid);
                //上传版块logo
                $logo = $this->_uploadImage('logo', $fid);
                if($logo) $dm->setlogo($logo['path']);
                $dm->setName($forumname)
                        ->setVieworder($vieworder)
                        ->setManager($manager)
                        ->setDescrip($descrip)
                        ->setIsshow($isshow);
                if (($result = $pwForum->updateForum($dm)) instanceof PwError) {
                    $this->showError($result->getError(), 'native/life/run/');
                }
                //修改扩展表
		$forumLifeDao = Wekit::loadDao('native.dao.PwForumLifeDao');
                if(($result = $forumLifeDao->updateForumLife($fid,array('url'=>$url,'address'=>$address))) instanceof PwError){
                    $this->showError($result->getError(), 'native/life/run/');
                }
		
		$this->showMessage('success', 'native/life/run/', true);
	}

	public function uniteAction() {
		$options = Wekit::load('forum.srv.PwForumService')->getForumOption();
		$this->setOutput($options, 'options');
	}

	public function douniteAction() {
		$this->getRequest()->isPost() || $this->showError('operate.fail');
		$fid = $this->getInput('fid', 'post');
		$tofid = $this->getInput('tofid', 'post');

		Wind::import('SRV:forum.srv.operation.PwUniteForum');
		$srv = new PwUniteForum($fid, $tofid);
		if (($result = $srv->execute()) instanceof PwError) {
			$this->showError($result->getError());
		}

		$this->showMessage('success', 'bbs/setforum/unite/', true);
	}

	private function _updateForums($forum, $copyFids = array(), $copyItems = array()) {
		$mainFid = $forum->fid;
		$fids = array($mainFid);
		$copyFids && $fids = array_merge($fids, $copyFids);

		list($forumname, $vieworder, $parentid, $descrip, $isshow, $isshowsub, $jumpurl, $seotitle, $seokeywords, $seodescription, $numofthreadtitle, $threadperpage, $readperpage, $newtime, $threadorderby, $minlengthofcontent, $locktime, $edittime, $allowtype, $typeorder, $contentcheck, $ifthumb, $thumbwidth, $thumbheight, $anticopy, $copycontent, $water, $waterimg, $allowhide, $allowsell, $anonymous, $manager, $creditset, $password, $allowvisit, $allowread, $allowpost, $allowreply, $allowupload, $allowdownload, $style) = $this->getInput(array('forumname', 'vieworder', 'parentid', 'descrip', 'isshow', 'isshowsub', 'jumpurl', 'seotitle', 'seokeywords', 'seodescription', 'numofthreadtitle', 'threadperpage', 'readperpage', 'newtime', 'threadorderby', 'minlengthofcontent', 'locktime', 'edittime', 'allowtype', 'typeorder', 'contentcheck', 'ifthumb', 'thumbwidth', 'thumbheight', 'anticopy', 'copycontent', 'water', 'waterimg', 'allowhide', 'allowsell', 'anonymous', 'manager', 'creditset', 'password', 'allowvisit', 'allowread', 'allowpost', 'allowreply', 'allowupload', 'allowdownload', 'style'));
		Wind::import('SRV:forum.bo.PwForumBo');
		Wind::import('SRV:forum.dm.PwForumDm');
		$pwforum = Wekit::load('forum.PwForum');
		$copyItems = $copyItems ? array_flip($copyItems) : array();
		array_walk($copyItems, array($this,'_setCopyItems'));
		!$creditset && $creditset = array();
		foreach ($creditset as $key => $value) {
			!is_numeric($value['limit']) && $creditset[$key]['limit'] = '';
			foreach ($value['credit'] as $k => $v) {
				if (!is_numeric($v)) $creditset[$key]['credit'][$k] = '';
			}
		}
		
		$misc = false;
		foreach ($fids as $fid) {
			$flag = $fid == $mainFid;
			$tmpforum = $flag ? $forum : new PwForumBo($fid, true);
			if (!$tmpforum->isForum(true)) continue;

			$isCate = $tmpforum->foruminfo['type'] == 'category';
			$forumset = $tmpforum->forumset;
			($flag || $copyItems['jumpurl']) && $forumset['jumpurl'] = $jumpurl;
			($flag || $copyItems['numofthreadtitle']) && $forumset['numofthreadtitle'] = $numofthreadtitle ? intval($numofthreadtitle) : '';
			($flag || $copyItems['threadperpage']) && $forumset['threadperpage'] = $threadperpage ? intval($threadperpage) : '';
			($flag || $copyItems['readperpage']) && $forumset['readperpage'] = $readperpage ? intval($readperpage) : '';
			($flag || $copyItems['threadorderby']) && $forumset['threadorderby'] = $threadorderby;
			if ($isCate) {
				$tmpParentid = 0;
				$creditset = array();
			} else {
				$tmpParentid = $parentid;
				($flag || $copyItems['minlengthofcontent']) && $forumset['minlengthofcontent'] = $minlengthofcontent ? intval($minlengthofcontent) : '';
				($flag || $copyItems['locktime']) && $forumset['locktime'] = $locktime ? intval($locktime) : '';
				($flag || $copyItems['edittime']) && $forumset['edittime'] = $edittime ? intval($edittime) : '';
				if ($flag || $copyItems['allowtype']) {
					$forumset['allowtype'] = is_array($allowtype) ? $allowtype : array();
					$forumset['typeorder'] = array_map('intval', $typeorder);
				}
				if ($flag || $copyItems['allowhide']) {
					$forumset['allowhide'] = intval($allowhide);
					$forumset['allowsell'] = intval($allowsell);
					$forumset['anonymous'] = intval($anonymous);
				}
				($flag || $copyItems['contentcheck']) && $forumset['contentcheck'] = intval($contentcheck);
				if ($flag || $copyItems['ifthumb']) {
					$forumset['ifthumb'] = intval($ifthumb);
					$forumset['thumbwidth'] = $thumbwidth ? intval($thumbwidth) : '';
					$forumset['thumbheight'] = $thumbheight ? intval($thumbheight) : '';
				}
				($flag || $copyItems['water']) && $forumset['water'] = intval($water);
				($flag || $copyItems['waterimg']) && $forumset['waterimg'] = $waterimg;
				($flag || $copyItems['anticopy']) && $forumset['anticopy'] = intval($anticopy);
				($flag || $copyItems['copycontent']) && $forumset['copycontent'] = $copycontent;
	
				//主题分类设置
				list($topic_type, $force_topic_type, $topic_type_display) = $this->getInput(array('topic_type', 'force_topic_type', 'topic_type_display'));
				($flag || $copyItems['topic_type']) && $forumset['topic_type'] = intval($topic_type);
				($flag || $copyItems['force_topic_type']) && $forumset['force_topic_type'] = intval($force_topic_type);
				($flag || $copyItems['topic_type_display']) && $forumset['topic_type_display'] = intval($topic_type_display);
			}
			$dm = new PwForumDm($fid);
			if ($flag) {
				$dm->setName($forumname)
					->setVieworder($vieworder)
					->setParentid($tmpParentid);
					//上传版块图标 
					$icon = $this->_uploadImage('icon', $fid);
					//上传版块logo
					$logo = $this->_uploadImage('logo', $fid);
			}
			if ($icon && ($flag || $copyItems['icon'])) $dm->setIcon($icon['path']);
			if ($logo && ($flag || $copyItems['logo'])) $dm->setlogo($logo['path']);
			($flag || $copyItems['manager']) && $dm->setManager($manager);
			($flag || $copyItems['descrip']) && $dm->setDescrip($descrip);
			($flag || $copyItems['isshow']) && $dm->setIsshow($isshow);
			($flag || $copyItems['across']) && $dm->setAcross($this->getInput('across'));
			($flag || $copyItems['newtime']) && $dm->setNewtime($newtime);
			if ($flag || $copyItems['user_allows']) {
				$dm->setAllowVisit($allowvisit)
					->setAllowRead($allowread)
					->setAllowPost($allowpost)
					->setAllowReply($allowreply)
					->setAllowUpload($allowupload)
					->setAllowDownload($allowdownload);
			}
			
			($flag || $copyItems['creditset']) && $dm->setCreditSetting($creditset);
			($flag || $copyItems['style']) && $dm->setStyle($style);
			$dm->setBasicSetting($forumset);
			if ($password != '******' && ($flag || $copyItems['password'])) {
				$dm->setPassword($password);
			} elseif ($password == '******' && !$flag && $copyItems['password']) {
				$dm->setEncryptPassword($forum->foruminfo['password']);
			}
			$result = $pwforum->updateForum($dm);
			if ($result instanceof PwError) {
				$this->showError($result->getError());
			}
			//($flag || $copyItems['topictype']) && $this->doeditTopicType($fid);
			if ($flag) {
				$this->doeditTopicType($fid);
			} else if ($copyItems['topictype'] && !$flag){
				Wekit::load('SRV:forum.srv.PwTopicTypeService')->copyTopicType($mainFid, $fid);
			}
			
			//seo
			($flag || $copyItems['seo']) && $this->_updateForumSeo($fid);
			//domain
			($flag || $copyItems['forumdomain']) && $this->_updateForumDomain($tmpforum);
			
			if ($flag && $tmpforum->foruminfo['parentid'] != $tmpParentid) {
				Wekit::load('forum.srv.PwForumService')->updateForumStatistics($tmpParentid);
				Wekit::load('forum.srv.PwForumService')->updateForumStatistics($tmpforum->foruminfo['parentid']);
				$misc = true;
			}
			if ($flag && $forumname != $tmpforum->foruminfo['name']) {
				$misc = true;
			}
			if (($flag || $copyItems['manager']) && $manager != trim($tmpforum->foruminfo['manager'], ',')) {
				$misc = true;
			}
		}
		if ($misc) {
			Wekit::load('forum.srv.PwForumMiscService')->correctData();
		}
	}
	
	private function _setCopyItems(&$item,$key){
		$item = 1;
	}
	
	private function _updateForumSeo($fid){
		//seo
		$seo = $this->getInput('seo');
		Wind::import('SRV:seo.dm.PwSeoDm');
		$dm = new PwSeoDm();
		$dm->setMod('bbs')
		   ->setPage('thread')
		   ->setParam($fid)
		   ->setTitle($seo['title'])
		   ->setKeywords($seo['keywords'])
		   ->setDescription($seo['description']);
		Wekit::load('seo.srv.PwSeoService')->batchReplaceSeoWithCache($dm);
	}
	
	private function _updateForumDomain($forum){
		//版块域名
		$fid = $forum->fid;
		list($forumdomain, $forumroot) = $this->getInput(array('forumdomain', 'forumroot'));
		$domainKey = $forum->foruminfo['type'] == 'category' ? "bbs/cate/run?fid=$fid" : "bbs/thread/run?fid=$fid"; 
		$oldDomain = Wekit::load('domain.PwDomain')->getByDomainKey($domainKey);
		/* @var $srv PwDomainService */
		$srv = Wekit::load('domain.srv.PwDomainService');
		if (!$forumdomain) {
			Wekit::load('domain.PwDomain')->deleteByDomainKey($domainKey);
			if ($oldDomain) $srv->flushAll();
		}
		else {
			if ($forumroot) {
				$r = $srv->isDomainValid($forumdomain, $forumroot, $domainKey);
			}else {
				$r = $srv->isNameValid($forumdomain, $domainKey);
			}
			if ($r instanceof PwError) $this->showError($r->getError());
			Wind::import('SRV:domain.dm.PwDomainDm');
			$dm = new PwDomainDm();
			$dm->setDomain($forumdomain)
			->setDomainKey($domainKey)
			->setDomainType('forum')
			->setRoot($forumroot)
			->setFirst($forumdomain[0])
			->setId($fid);
			Wekit::load('domain.PwDomain')->replaceDomain($dm);
			if (!$oldDomain || $oldDomain['domain'] != $forumdomain) $srv->flushAll();
		}
	}
	
	public function editnameAction() {
		list($fid, $name) = $this->getInput(array('fid', 'name'));

		Wind::import('SRV:forum.dm.PwForumDm');
		$pwforum = Wekit::load('forum.PwForum');
		$dm = new PwForumDm($fid);
		$dm->setName($name);
		$result = $pwforum->updateForum($dm);

		$this->showMessage('success', 'bbs/setforum/edit/?fid=' . $fid, true);
	}

	/**
	 * 搜索版块名称 for ajax
	 */
	public function searchforumAction(){
		list($keyword) = $this->getInput(array('keyword'));
		$pwforum = Wekit::load('forum.PwForum');
		$data = $pwforum->searchForum($keyword);
		if (!$data || !is_array($data)){
			$this->showError('FORUM:searchforum.notfound');
		} else {
			$this->setOutput($data, 'data');
			$this->showMessage('FORUM:searchforum.success');
		}
	}
	
	public function deletetopictypeAction(){
		list($id) = $this->getInput(array('id'), 'get');
		$topicTypeService = Wekit::load('forum.PwTopicType'); /* @var $topicTypeService PwTopicType */
		$topicTypeService->deleteTopicType($id);
		$this->showMessage('FORUM:topictype.delete.success');
	}
	
	/**
	 * 删除一个版块
         * lyl
	 */
	public function deleteforumAction() {
//		var_dump($_POST);exit;
		$fid = $this->getInput('fid');

		Wind::import('SRV:forum.srv.operation.PwDeleteForum');
		$srv = new PwDeleteForum($fid, new PwUserBo($this->loginUser->uid));
		if (($result = $srv->execute()) instanceof PwError) {
			$this->showError($result->getError());
		}
		$foruminfo = $srv->forum->foruminfo;
		$foruminfo['logo'] && Pw::deleteAttach($foruminfo['logo']);
		$foruminfo['icon'] && Pw::deleteAttach($foruminfo['icon']);
                
                /* 增加删除扩展表 */
                $forumLifeDao = Wekit::loadDao('native.dao.PwForumLifeDao');
                if(($result = $forumLifeDao->deleteForumLife($fid)) instanceof PwError){
                    $this->showError($result->getError(), 'native/life/run/');
                }
                
		$this->showMessage('success', 'native/life/run/', true);
	}
	
	/**
	 * 删除板块logo
         * lyl
	 */
	public function deletelogoAction() {

		$fid = $this->getInput('fid');

		Wind::import('SRV:forum.bo.PwForumBo');
		$forum = new PwForumBo($fid, true);
		if (!$forum->isForum(true)) {
			$this->showMessage('版块不存在', 'native/life/run', true);
		}

		Wind::import('SRV:forum.dm.PwForumDm');
		$dm = new PwForumDm($fid);
		$dm->setLogo('');
		$pwforum = Wekit::load('forum.PwForum');
		$pwforum->updateForum($dm);

		Pw::deleteAttach($forum->foruminfo['logo']);

		$this->showMessage('success');
	}
	
	/**
	 * 删除板块icon
	 */
	public function deleteiconAction() {

		$fid = $this->getInput('fid');

		Wind::import('SRV:forum.bo.PwForumBo');
		$forum = new PwForumBo($fid, true);
		if (!$forum->isForum(true)) {
			$this->showMessage('版块不存在', 'bbs/setforum/run', true);
		}

		Wind::import('SRV:forum.dm.PwForumDm');
		$dm = new PwForumDm($fid);
		$dm->setIcon('');
		$pwforum = Wekit::load('forum.PwForum');
		$pwforum->updateForum($dm);

		Pw::deleteAttach($forum->foruminfo['icon']);

		$this->showMessage('success');
	}
	
	/**
	 * 保存主题分类
	 * 
	 * @param $fid
	 */
	protected function doeditTopicType($fid){
		//主题分类
		list($t_vieworder, $t_name, $t_logo, $t_issys) = $this->getInput(array('t_vieworder', 't_name', 't_logo', 't_issys'), 'post');
		list($t_new_vieworder, $t_new_name, $t_new_logo, $t_new_issys) = $this->getInput(array('t_new_vieworder', 't_new_name', 't_new_logo', 't_new_issys'), 'post');
		list($t_new_sub_vieworder, $t_new_sub_name, $t_new_sub_logo, $t_new_sub_issys) = $this->getInput(array('t_new_sub_vieworder', 't_new_sub_name', 't_new_sub_logo', 't_new_sub_issys'),'post');
		
		is_array($t_name) || $t_name = array();
		is_array($t_new_name) || $t_new_name = array();
		is_array($t_new_sub_name) || $t_new_sub_name = array();

		Wind::import('SRV:forum.dm.PwTopicTypeDm');
		$topicTypeService = Wekit::load('forum.PwTopicType'); /* @var $topicTypeService PwTopicType */
		
		//$logos = $this->_uploadTopicTypeIcon();
		$logos = array(); //TODO图标功能暂取消
		/* 更新原有 */
		$updateTopicTypes = array(); //待更新topicType Dm
		foreach ($t_name as $k=>$v) {
			$dm = new PwTopicTypeDm($k);
			$dm->setFid($fid)
				->setVieworder($t_vieworder[$k])
				->setName($t_name[$k])
				->setIsSystem($t_issys[$k]);
			$logos['t_logo'][$k] && $dm->setLogo($logos['t_logo'][$k]['filename']);
			$result = $dm->beforeUpdate();
			if ($result instanceof PwError) {
				$this->showError($result->getError());
			}
			$updateTopicTypes[] = $dm;
		}
		
		/* 新增主题分类 */
		$newTopicTypes = array();
		if (!$t_new_name) $t_new_name = array();
		foreach ($t_new_name as $k=>$v) {
			if (!$v) continue;
			$dm = new PwTopicTypeDm();
			$dm->setFid($fid)
				->setVieworder($t_new_vieworder[$k])
				->setName($t_new_name[$k])
				->setIsSystem($t_new_issys[$k]);
			$logos['t_new_logo'][$k] && $dm->setLogo($logos['t_new_logo'][$k]['filename']);
			$result = $dm->beforeAdd();
			if ($result instanceof PwError) {
				$this->showError($result->getError());
			}
			$newTopicTypes[$k] = $dm;
		}
		
		/* 新增二级主题分类 */
		$newSubTopicTypes = array();
		if (!$t_new_sub_name) $t_new_sub_name = array();
		foreach ($t_new_sub_name as $parentId=>$newSubs) {
			if (!is_array($newSubs)) continue;
			foreach ($newSubs as $k=>$v){
				$dm = new PwTopicTypeDm();
				$dm->setFid($fid)
					//->setParentId($parentid)
					->setVieworder($t_new_sub_vieworder[$parentId][$k])
					->setName($t_new_sub_name[$parentId][$k])
					->setIsSystem($t_new_sub_issys[$parentId][$k]);
				$logos['t_new_sub_logo'][$k] && $dm->setLogo($logos['t_new_sub_logo'][$k]['filename']);
				$result = $dm->beforeAdd();
				if ($result instanceof PwError) {
					$this->showError($result->getError());
				}
				$newSubTopicTypes[$parentId][] = $dm;
			}
		}
			
		/* 执行更新 */
		foreach ($updateTopicTypes as $v) {
			$topicTypeService->updateTopicType($v);
		}
		
		/* 执行新增 */
		$newTopicIds = array();
		foreach ($newTopicTypes as $k=>$v) {
			$topicId = $topicTypeService->addTopicType($v);
			is_numeric($topicId) && $newTopicIds[$k] = $topicId;
		}
		
		foreach ($newSubTopicTypes as $k=>$v) {
			if (!$k) continue;
			foreach ($v as $k2=>$v2) {
				$parentId = is_numeric($k) ? $k : $newTopicIds[$k];
				if (!$parentId) continue;
				$v2->setParentId($parentId);
				$topicTypeService->addTopicType($v2);
			}
		}
		//end 主题分类
	}
        
        /**
         * 添加公共账号表单
         * lyl
         */
        public function addAction(){
            $res = Wekit::loadDao('native.dao.PwNativeForumDao')->getMaxVieworder();
            $max_vieworder = isset($res['vieworder']) && $res['vieworder'] ? $res['vieworder']+1 : 0;
            $this->setOutput($max_vieworder, 'max_vieworder');
        }
        
        /**
         * 执行添加公共账号
         * lyl
         */
        public function doAddAction() {
            //判断公共账号所属的主分类是否存在
            $configs = Wekit::C()->getValues('native');
            $life_fid = isset($configs['forum.life_fid']) && $configs['forum.life_fid'] ? $configs['forum.life_fid'] : 0;
            Wind::import('SRV:forum.dm.PwForumDm');
            $pwForum = Wekit::load('forum.PwForum');
            $forumset = array(
                'allowtype' => array('default'),
                'typeorder' => array('default' => 0)
            );
            
            if (!$life_fid) {//尚未创建公共账号所属分类，自动创建
                $dm = new PwForumDm();
                $dm->setParentid(0)
                        ->setName('生活服务')
                        ->setVieworder(0)
                        ->setManager('')
                        ->setBasicSetting($forumset);
                if (($result = $pwForum->addForum($dm)) instanceof PwError) {
                    $this->showError($result->getError(), 'native/life/run/');
                }else{
//                    var_dump($result);
                    $life_fid = $result;
                    $config = new PwConfigSet('native');
                    $config->set('forum.life_fid',$life_fid)->flush();
//                    var_dump($life_fid);
                }
            }
            
            if($_FILES['logo']['size']>300000){
                $this->showError("图片大小不能超过300k", 'native/life/run/',true);
            }
//            exit;
            //添加公共服务版面
            $dm_life = new PwForumDm();
            list($forumname, $manager, $vieworder, $descrip,$isshow,$url,$address) = $this->getInput(array('forumname', 'manager', 'vieworder', 'descrip','isshow','url','address'), 'post');
            if(!$forumname) $this->showError("商家名称不能为空", 'native/life/run/',true);
            if(Pw::strlen($address)>100) $this->showError("商家地址不能超过100个汉字", 'native/life/run/',true);
            $dm_life->setParentid($life_fid)
                    ->setName($forumname)
                    ->setVieworder($vieworder)
                    ->setManager($manager)
                    ->setDescrip($descrip)
                    ->setIsshow($isshow)
                    ->setBasicSetting($forumset);
            if (($result = $pwForum->addForum($dm_life)) instanceof PwError) {
                $this->showError($result->getError(), 'native/life/run/');
            }
            $fid = $result;

            //上传版块logo
            $dm_life = new PwForumDm($fid);
            $logo = $this->_uploadImage('logo', $fid);
            $dm_life->setlogo($logo['path']);	
            if (($result = $pwForum->updateForum($dm_life)) instanceof PwError) {
                $this->showError($result->getError(), 'native/life/run/');
            }
            
//            Wekit::load('forum.srv.PwForumMiscService')->correctData();
            $forumLifeDao = Wekit::loadDao('native.dao.PwForumLifeDao');
            if(($result = $forumLifeDao->addForumLife(array('fid'=>$fid,'url'=>$url,'address'=>$address))) instanceof PwError){
                $this->showError($result->getError(), 'native/life/run/');
            }
            $this->showMessage('success', 'native/life/run', true);
        }

        private function _uploadTopicTypeIcon(){
 		Wind::import('SRV:upload.action.PwTopictypeUpload');
		Wind::import('LIB:upload.PwUpload');
		$bhv = new PwTopictypeUpload(16, 16);
		$upload = new PwUpload($bhv);
		if (($result = $upload->check()) === true) {
			$result = $upload->execute();
		}
		if ($result !== true) {
			$this->showError($result->getError());
		}
		return $bhv->getAttachInfo();
	}
	
	private function _uploadImage($type, $fid) {
		Wind::import('SRV:upload.action.PwForumUpload');
		Wind::import('LIB:upload.PwUpload');
		$bhv = new PwForumUpload($type, $fid);
		$upload = new PwUpload($bhv);
		
		if (($result = $upload->check()) === true) {
			$result = $upload->execute();
		}
		if ($result !== true) {
			$this->showError($result->getError());
		}
		$attachInfo = $bhv->getAttachInfo();
	
		return $attachInfo;
	}
	
	protected function _getFroumService() {
		return Wekit::load('forum.srv.PwForumService');
	}
}
?>

