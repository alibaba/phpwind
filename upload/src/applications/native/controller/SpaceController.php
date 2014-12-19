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

class SpaceController extends PwBaseController {

	public function beforeAction($handlerAdapter) {
		parent::beforeAction($handlerAdapter);
//		if (!$this->loginUser->isExists()) $this->showError('VOTE:user.not.login');
	}

    /**
     * 空间首页 
     * @access public
     * @return void
     */
	public function run() {

    }

    /**
     * 用户资料
     * @access public
     * @return void
     */
    public function profileAction(){

    }

    /**
     * 我关注的频道 
     * @access public
     * @return void
     */
    public function forumAction(){
        
    }

    /**
     * 我关注的人 
     * @access public
     * @return void
     */
    public function followAction(){

    }

    /**
     * 我关注的话题
     * @access public
     * @return void
     */
    public function tagAction(){

    }

    /**
     * 我收藏的贴子||喜欢的贴子 
     * @access public
     * @return void
     */
    public function likeAction(){

    }

    /**
     * 我发布的帖子 
     * @access public
     * @return void
     */
    public function issuedByIAction(){
        
    }
    
    /**
     * 我回复的帖子 
     * @access public
     * @return void
     */
    public function replyByIAction(){

    }
}
