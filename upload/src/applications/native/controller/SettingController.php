<?php
/**
 * 移动版的设置调用
 *
 * @fileName: SettingController.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2015-01-04 11:54:43
 * @desc: 
 **/

defined('WEKIT_VERSION') || exit('Forbidden');

class SettingController extends PwBaseController {

    private $config = array();

	public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);
        //

        $this->config = Wekit::C()->getValues('native');
    }

    /**
     * 检查客户端与server通信加密用的key是否正确
     * apk生成支持相关接口
     * 
     * @access public
     * @return void
     * @example
     * <pre>
     * post: securityKey&apiversion
     * </pre>
     */
    public function checkSecurityKeyAction(){
        $securityKey = $this->getInput('securityKey','post');
        $apiversion  = $this->getInput('apiversion','post');
        $config = Wekit::C()->getConfigByName('site','securityKey');
        if( $securityKey==$config['value'] && $apiversion==NATIVE_VERSION ){
            $this->setOutput(true, 'data');
            $this->showMessage("NATIVE:app.check.securityKey.success");
        }
        $this->showError("NATIVE:app.check.securityKey.failed");
    }


    /**
     * 阿里妈妈广告 
     * 
     * @access public
     * @return void
     */
    public function adAction(){
        $data = array();
        if( isset($this->config['ad.status']) && $this->config['ad.status']){
           $data = $this->config['ad.code'];
        }
        $this->setOutput($data, 'data');
        $this->showMessage("success");
    }

    /**
     * 启动时图片 
     * 
     * @access public
     * @return void
     */
    public function startupAction(){
        $data = array();
        if( isset($this->config['startup.status']) && $this->config['startup.status']){
           $data['img'] = Pw::getPath($this->config['startup.img']);
           $data['imgmd5'] = $this->config['startup.imgmd5'];
           $this->setOutput($data, 'data');
           $this->showMessage("success");
        }
        $this->showError('fail');
    }

}
