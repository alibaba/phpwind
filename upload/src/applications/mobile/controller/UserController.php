<?php
/**
 * 用户登录,注册等接口
 *
 * @fileName: UserController.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-15 19:10:43
 * @desc: 
 **/

defined('WEKIT_VERSION') || exit('Forbidden');

class UserController extends PwBaseController {

	public function beforeAction($handlerAdapter) {
		parent::beforeAction($handlerAdapter);
//		if (!$this->loginUser->isExists()) $this->showError('VOTE:user.not.login');
	}
	
	public function run() {


    }

    /**
     * 登录 
     * @access public
     * @return void
     */
    public function doLoginAction(){

        echo 111;exit;
    }

    /**
     * 注册帐号 
     * @access public
     * @return void
     */
    public function doRegisterAction(){

    }

    /**
     * 修改头像 
     * @access public
     * @return void
     */
    public function doAvatarAction(){
        
    }

    /**
     * 修改性别 
     * @access public
     * @return void
     */
    public function doSexAction(){
        
    }

    /**
     * 保存修改密码 
     * @access public
     * @return void
     */
    public function doPassWordAction(){

    }

    /**
     * 退出登录 
     * @access public
     * @return void
     */
    public function doLoginOutAction(){

    }

    /**
     * 显示验证码 
     * 
     * @access public
     * @return void
     */
    public function showVerifycodeAction(){
    
    }

    /**
     * 验证验证码接口 
     * 
     * @access public
     * @return void
     */
    public function checkVerifycodeAction(){
        
    }
}
