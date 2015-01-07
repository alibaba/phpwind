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

    /**
     * global post: securityKey
     */
    public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);

    }

    /**
     * 空间首页列出审核过的贴子
     * 
     * @access public
     * @return void
     */
    public function run(){
        $spaceUid = $this->getInput('uid','get');
        $page = $this->getInput('page','get');
        //
        $array = $this->_getPwNativeThreadDs()->getThreadListByUid($spaceUid, $page, 'space');
        $myThreadList = $this->_getPwNativeThreadDs()->getThreadContent($array['tids']);
        $attList = $this->_getPwNativeThreadDs()->getThreadAttach($array['tids'], $array['pids']);
        //
        $threadList = $this->_getPwNativeThreadDs()->gather($myThreadList, $attList);
        //
        $this->setOutput($threadList, 'data');
        $this->showMessage('success');
    }


    private function _getPwNativeThreadDs(){
        return Wekit::load('native.PwNativeThread');
    }


}
