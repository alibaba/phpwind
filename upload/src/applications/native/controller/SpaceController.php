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

Wind::import('APPS:native.controller.NativeBaseController');

class SpaceController extends NativeBaseController {

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
        $tids           = $this->_getPwNativeThreadDs()->getThreadListByUid($spaceUid, $page, 'space');
        $myThreadList   = $this->_getPwNativeThreadDs()->getThreadContent($tids);
        //pids 默认是0； 
        $attList        = $this->_getPwNativeThreadDs()->getThreadAttach($tids, array(0) );
        $threadList     = $this->_getPwNativeThreadDs()->gather($myThreadList, $attList);
        //
        $data = array(
            'pageCount'=>$this->_getPwNativeThreadDs()->getThreadPageCount(),
            'threadList'=>$threadList,
        );
        $this->setOutput($data, 'data');
        $this->showMessage('success');

    }


    private function _getPwNativeThreadDs(){
        return Wekit::load('native.PwNativeThread');
    }


}
