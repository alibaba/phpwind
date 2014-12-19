<?php
/**
 * 热门话题接口集合
 *
 * @fileName: TagController.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-15 19:10:17
 * @desc: 
 **/
defined('WEKIT_VERSION') || exit('Forbidden');

class TagController extends PwBaseController {

	public function beforeAction($handlerAdapter) {
		parent::beforeAction($handlerAdapter);
//		if (!$this->loginUser->isExists()) $this->showError('VOTE:user.not.login');
	}
	
	public function run() {


    }

    
    /**
     * 热门话题 
     * @access public
     * @return void
     */
    public function hotAction(){

    }

    /**
     * 话题相关的帖子列表 
     * @access public
     * @return void
     */
    public function threadAction(){
        
    }

    /**
     * 关注一个话题 
     * @access public
     * @return void
     */
    public function followAction(){
        
    }



}
