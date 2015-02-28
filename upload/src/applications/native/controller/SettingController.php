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
     * post: securityKey
     * </pre>
     */
    public function checkSecurityKeyAction(){
        $securityKey = $this->getInput('securityKey','post');
        $config = Wekit::C()->getConfigByName('site','securityKey');
        if( $securityKey==$config['value'] ){
            $this->setOutput(true, 'data');
            $this->showMessage("NATIVE:app.check.securityKey.success");
        }
        $this->showError("NATIVE:app.check.securityKey.failed");
    }

    /**
     * 移动端api的版本号 
     * 
     * @access public
     * @return void
     */
    public function apiVersionAction(){
        $this->setOutput(NATIVE_VERSION,'data');
        $this->showMessage("success");
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
     * @example
     * <pre>
     * /index.php?m=native&c=Setting&a=startup <br>
     * post: imgmd5 <br>
     * reponse: img&imgmd5
     * </pre>
     */
    public function startupAction(){
        $imgmd5 = $this->getInput('imgmd5');
        //
        $data = array(
            'img'=>'',
            'imgmd5'=>'',
        );
        if(isset($this->config['startup.status']) && $this->config['startup.status'] ){
            $_imgmd5 = Pw::getPath($this->config['startup.img']);
            if( $imgmd5!=$_imgmd5 ){
               $data['img'] = Pw::getPath($this->config['startup.img']);
               $data['imgmd5'] = $_imgmd5;
           }
        }
        $this->setOutput($data, 'data');
        $this->showMessage("success");
    }

    /**
     * 自定义发现数据 
     * 
     * @access public
     * @return void
     * <pre>
     * /index.php?m=native&c=Setting&a=freshList <br>
     *
     * </pre>
     */
    public function freshListAction(){
        $freshData = Wekit::loadDao('native.dao.PwFreshDao')->getFresh();
        foreach ($freshData as &$v) {
            $v['img'] = $v['img']? Pw::getPath($v['img']) : "";
        }
        $this->setOutput($freshData, 'data');
        $this->showMessage("success");
    }


    /**
     * 注册协议
     *
     * @access public
     * @return void
     * @example
     * </pre>
     * /index.php?m=native&c=Setting&a=protocol
     * </pre>
     */
    public function protocolAction(){
        $config = Wekit::C('register');
        $data = $config['protocol'];
        $this->setOutput($data, 'data');
        $this->showMessage("success");
    }


}
