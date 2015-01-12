<?php
/**
 * 用户贴子收藏
 *
 * @fileName: IndexController.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2015-01-09 16:21:04
 * @desc: 
 **/
class IndexController extends PwBaseController {

    public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);
        if ($this->loginUser->uid < 1) $this->forwardRedirect(WindUrlHelper::createUrl('u/login/run/'));
    }

    /**
     * 收藏 
     * 
     * @access public
     * @return void
     */
    public function doaddAction(){
        $data = array(
            'created_userid'=>$this->loginUser->uid,
            'fid'=>intval($this->getInput('fid')),
            'tid'=>intval($this->getInput('tid')),
            'created_time'=>time(),
        );
        if( $this->_getCollectService()->addCollect($data)!==false ){
            $this->showMessage('success');
        }
        $this->showError('fail');
    }

    /**
     * 取消收藏 
     * 
     * @access public
     * @return void
     */
    public function dodelAction(){
        $tid = intval($this->getInput('tid'));
        if( $this->_getCollectService()->delCollect($this->loginUser->uid, $tid)!==false ){
            $this->showMessage('success');
        }
        $this->showError('fail');
    }

    private function _getCollectService(){
        return Wekit::load('collect.srv.PwCollectService');
    }
}
