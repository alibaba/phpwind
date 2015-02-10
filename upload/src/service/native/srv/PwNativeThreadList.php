<?php
defined('WEKIT_VERSION') || exit('Forbidden');

/**
 * 移动端版块显示帖子列表
 * @author yuliang.lyl 
 */

class PwNativeThreadList extends PwBaseHookService {

	public $page = 1;
	public $perpage = 20;
	public $total = 0;
	public $maxPage;

	public $icon;
	public $uploadIcon;
	public $specialIcon;

	protected $_iconNew;
	protected $_iconHot;
	protected $_maxPage;
	protected $_ds;

	public function __construct() {
		parent::__construct();
		$this->icon = array(
			'headtopic_3' => '置顶3',
			'headtopic_2' => '置顶2',
			'headtopic_1' => '置顶1',
			'digest' => '精华',
			'lock' => '锁定',

			'vote' => '投票',
			'reward' => '悬赏',
			'shop' => '商品',
			'debate' => '辩论',
			'active' => '活动',

			'topichot' => '热门帖',
			'topicnew' => '新帖',
			'topic' => '普通帖',

			'img' => '图片帖',
			'file' => '附件',
			'music' => '音乐',
			'like' => '喜欢帖',
		);
		$this->uploadIcon = array(
			1 => 'img', 2 => 'txt', 3 => 'img', 4 => 'file', 5 => 'img', 6 => 'txt', 7 => 'img'
		);
		$this->specialIcon = array(
			'poll' => 'vote'
		);
		$this->_iconNew = Wekit::C('bbs', 'thread.new_thread_minutes') * 60;
		$this->_iconHot = Wekit::C('bbs', 'thread.hotthread_replies');
		$this->_maxPage = Wekit::C('bbs', 'thread.max_pages');
	}

	public function setPage($page) {
		$this->page = intval($page);
		return $this;
	}

	public function setPerpage($perpage) {
		$perpage = intval($perpage);
		$perpage > 0 && $this->perpage = $perpage;
		return $this;
	}

	public function setIconNew($time) {
		$time && $this->_iconNew = $time * 60;
		return $this;
	}

	public function execute(PwThreadDataSource $ds) {
//            var_dump($ds);exit;//PwNativeCommonThread
		$this->_ds = $ds;
		$this->total = $ds->getTotal();
		$this->maxPage = ceil($this->total / $this->perpage);
		$this->_maxPage > 0 && $this->maxPage = min($this->maxPage, $this->_maxPage);
		$this->page < 1 && $this->page = 1;
		$this->page > $this->maxPage && $this->page = $this->maxPage;
		list($start, $limit) = Pw::page2limit($this->page, $this->perpage);

		$threaddb = $ds->getData($limit, $start);
//                var_dump($threaddb);exit;
		$this->runDo('initData', $threaddb);
//var_dump($threaddb);exit;
		foreach ($threaddb as $key => $value) {
			$threaddb[$key] = $this->bulidThread($value);
		}
		$this->threaddb = $threaddb;
	}
	
	public function bulidThread($thread) {
		if ($thread['issort'] && $thread['topped']) {
			$thread['icon'] = 'headtopic_' . $thread['topped'];
		} elseif ($thread['digest']) {
			$thread['icon'] = 'digest';
		} elseif (Pw::getstatus($thread['tpcstatus'], PwThread::STATUS_LOCKED)) {
			$thread['icon'] = 'lock';
		} elseif ($thread['special'] && isset($this->specialIcon[$thread['special']])) {
			$thread['icon'] = $this->specialIcon[$thread['special']];
		} elseif ($thread['replies'] > $this->_iconHot) {
			$thread['icon'] = 'topichot';
		} elseif (Pw::getTime() - $thread['created_time'] < $this->_iconNew) {
			$thread['icon'] = 'topicnew';
		} else {
			$thread['icon'] = 'topic';
		}
		if ($thread['overtime'] && $thread['overtime'] < Pw::getTime()) {
			$overtimeService = Wekit::load("SRV:forum.srv.PwOvertimeService");
			$overtimeService->updateOvertime($thread['tid']);
		}
		if ($thread['highlight']) {
			$highlight = Wekit::load("Lib:utility.PwHighlight");
			$thread['highlight_style'] = $highlight->getStyle($thread['highlight']);
		}
		if ($thread['inspect']) {
			$thread['inspect'] = explode("\t", $thread['inspect']);
		}
		if ($thread['ifshield']) {
			$thread['highlight_style'] = 'text-decoration: line-through';
			$thread['subject'] = '此帖已被屏蔽';
		}
		return $this->runWithFilters('bulidThread', $thread);
	}

	public function getList() {
		return $this->threaddb;
	}

	public function getUrlArgs() {
		return $this->_ds->getUrlArgs();
	}

	protected function _getInterfaceName() {
		return 'PwThreadListDoBase';
	}
}
