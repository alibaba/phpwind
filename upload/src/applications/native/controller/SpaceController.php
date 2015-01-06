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

}
