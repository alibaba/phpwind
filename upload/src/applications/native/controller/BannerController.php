<?php
/**
 * banner 接口集合
 *
 * @fileName: BannerController.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-15 19:09:15
 * @desc: 
 **/
defined('WEKIT_VERSION') || exit('Forbidden');

class BannerController extends PwBaseController {

	public function beforeAction($handlerAdapter) {
		parent::beforeAction($handlerAdapter);
//		if (!$this->loginUser->isExists()) $this->showError('VOTE:user.not.login');
	}
    /** 
     * banner列表数据                                                                                                                                     
     * @access public
     * @return void
     */
    public function bannerDataAction(){


    }  

}
