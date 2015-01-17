<?php
/**
 * 开放平台接入设置
 *
 * @fileName: ThirdOpenPlatformController.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-22 14:46:35
 * @desc: 
 **/
Wind::import('ADMIN:library.AdminBaseController');

class ThirdOpenPlatformController extends AdminBaseController {

    public function run() {

        $type = $this->getInput('type','get')?$this->getInput('type','get'):'taobao';
        //
        $lab    = $this->_displayText($type);
        //
        $status = $type.'.status';
        $appId  = $type.'.appId';
        $appKey = $type.'.appKey';
        $displayOrder = $type.'.displayOrder';

        //
        $config = Wekit::C()->getValues('thirdPlatform');
        $info = array(
            'status'=>$config[$status],
            'appId'=>$config[$appId],
            'appKey'=>$config[$appKey],
            'displayOrder'=>$config[$displayOrder],
        );

        // 
        $this->setOutput($type, 'type'); 
        $this->setOutput($this->_displayText($type), 'lab'); 
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
    public function dosetAction(){
        $type   = $this->getInput('type');
        //
        $lab    = $this->_displayText($type);
        //
        $status = $type.'_status';
        $appId  = $type.'_appId';
        $appKey = $type.'_appKey';

        list($$status,$$appId,$$appKey,$$displayOrder) = $this->getInput(array($status, $appId, $appKey, $displayOrder));

        $config = new PwConfigSet('thirdPlatform');
        $config
            ->set(str_replace('_','.',$status), (int)$$status)
            ->set(str_replace('_','.',$appId), $$appId)
            ->set(str_replace('_','.',$appKey), $$appKey)
            ->set(str_replace('_','.',$displayOrder), (int)$$displayOrder)
            ->flush();

        $this->showMessage('ADMIN:success');
    }

    /**
     * 显示在模板中的文本内容 
     * 
     * @access private
     * @return void
     */
    private function _displayText($type){
        $_lab1 = array(
            'AppId' =>'AppKey',
            'AppKey'=>'AppSecret',
        );
        $_lab2 = array(
            'AppId' =>'AppId',
            'AppKey'=>'AppKey',
        );
        $_labs = array(
            'taobao'=>$_lab1,
            'weibo' =>$_lab1,
            'weixin'=>$_lab2,
            'qq'    =>$_lab2,
        );
        return $_labs[$type];
    }


    /**
     * 悟空云im接入 
     * 
     * @access public
     * @return void
     */
    public function wukongAction(){
        $config = Wekit::C()->getValues('native');
        $this->setOutput($config, 'config');
    }

    /**
     * 保存悟空云im接入设置 
     * 
     * @access public
     * @return void
     */
    public function dosetwukongAction(){
        list($appKey,$appSecret,$serverToken) = $this->getInput(array('wukong_appKey','wukong_appSecret','wukong_serverToken'),'post');
        $config = new PwConfigSet('native');
        $config
            ->set('wukong.appKey',$appKey)
            ->set('wukong.appSecret',$appSecret)
            ->set('wukong.serverToken',$serverToken)
            ->flush();
        $this->showMessage('success');
    }


}
