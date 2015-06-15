<?php

/**
 * 开放平台接入设置
 *
 * @fileName: ThirdOpenPlatformController.php
 * @author: dongyong <dongyong.ydy@alibaba-inc.com>
 * @author: shangyuanchun <yuanchun.syc@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-22 14:46:35
 * @desc: 
 **/
Wind::import('ADMIN:library.AdminBaseController');

class ThirdOpenPlatformController extends AdminBaseController {

    public function run()
    {
        // type表示哪一种第三方平台
        $type = $this->getInput('type','get') ? $this->getInput('type','get') : 'qq';
        // 管理界面显示的名称
        $lab    = $this->_displayText($type);
        // 在数据库中存储的字段名称
        $status = $type.'.status';
        $appId  = $type.'.appid';
        $appKey = $type.'.appkey';
        $displayOrder = $type.'.displayOrder';

        //
        $config = Wekit::C()->getValues('webThirdLogin');
        $info   = array(
            'status' => $config[$status],
            'appId'  => $config[$appId],
            'appKey' => $config[$appKey],
            'displayOrder' => $config[$displayOrder],
        );

        // 回调地址
        $config = Wekit::C()->getConfigByName('site', 'info.url');
        if ($type == 'qq') {
            // QQ的回调地址填写比较诡异，提示和文档都是错的
            $redirecturl = $config['value'].'/index.php';
        } else {
            $redirecturl = $config['value'].'/index.php?m=u&c=login&a=thirdlogincallback&platform='.$type;
        }

        // 
        $this->setOutput($redirecturl, 'redirecturl'); 
        $this->setOutput($type, 'type'); 
        $this->setOutput($lab,  'lab'); 
        $this->setOutput($info, 'info');

        //
        $typeClasses[$type] = 'class="current"';
        $this->setOutput($typeClasses, 'typeClasses');
    }


    /**
     * 保存设置 
     * 
     * @access public
     * @return void
     */
    public function dosetAction()
    {
        $type   = $this->getInput('type');

        //
        $status = $type.'_status';
        $appId  = $type.'_appId';
        $appKey = $type.'_appKey';
        $displayOrder = $type.'_displayOrder';

        list($$status,$$appId,$$appKey,$$displayOrder) = $this->getInput(array($status, $appId, $appKey, $displayOrder));

        $config = new PwConfigSet('webThirdLogin');
        $config
            ->set($type.'.status', (int)$$status)
            ->set($type.'.appid',  $$appId)
            ->set($type.'.appkey', $$appKey)
            ->set($type.'.displayOrder', (int)$$displayOrder)
            ->flush();

        $this->showMessage('ADMIN:success');
    }

    /**
     * 显示在模板中的文本内容 
     * 
     * @access private
     * @return void
     */
    private function _displayText($type)
    {
        $_lab1 = array(
            'AppId' =>'AppKey',
            'AppKey'=>'AppSecret',
        );
        $_lab2 = array(
            'AppId' =>'AppId',
            'AppKey'=>'AppKey',
        );
        $_labs = array(
            'qq'    => $_lab2,
            'weibo' => $_lab1,
            'taobao'=> $_lab1,
            'weixin'=> $_lab2,
        );
        return $_labs[$type];
    }
}
