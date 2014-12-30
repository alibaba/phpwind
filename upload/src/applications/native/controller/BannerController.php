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

Wind::import('SRV:native.PwBanner');

class BannerController extends PwBaseController {

	public function beforeAction($handlerAdapter) {
		parent::beforeAction($handlerAdapter);
    }

    /** 
     * native首页banner数据                                                                                                                                     
     * @access public
     * @return void
     * @example
     * <pre>
     * /index.php?m=native&c=Banner&a=bannerData <br>
     * response: type=(forum|tag|topic|link) 四种类型，四种跳转方式
     * </pre>
     */
    public function bannerDataAction(){
        $bannerData = $this->_getDao()->getBanner(PwBanner::BANNER_TYPE_NATIVE_INDEX);
        foreach ($bannerData as &$banner) {
            /* cursor */
        }

        $this->setOutput($bannerData, 'data');
        $this->showMessage('success');
    }  

    private function _getDao(){
        return Wekit::loadDao('native.dao.PwBannerDao'); 
    }

}
