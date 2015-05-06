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
     * 查询laiwang帐号信息
     * 注意为了兼容旧的phpwind代码，当设置项中不存在ios的appkey时，返回空的结果。
     * 
     * @access public
     * @return void
     */
    public function getLaiwangInfoAction() {
        $config = Wekit::C()->getValues('wukong');
        if (empty($config) || !isset($config['ios.appKey'])) {
            $data = array('info' => '');
        } else {
            $cont = array('appToken' => $config['appToken'],
                          'org'      => $config['org'],
                          'domain'   => $config['domain'],
                          'Android'  => array(
                                        'appKey'    => $config['android.appKey'],
                                        'appSecret' => $config['android.appSecret'],
                                        ),
                          'iOS'      => array(
                                        'appKey'    => $config['ios.appKey'],
                                        'appSecret' => $config['ios.appSecret'],
                                        ),
                    );
            $cont = serialize($cont);
            $_securityKey = Wekit::C()->getConfigByName('site', 'securityKey');
            $cont = Pw::encrypt($cont, $_securityKey['value']);
            $data = array('info' => $cont ? $cont : '');
        }

        $this->setOutput($data, 'data');
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
     * reponse: img&imgmd5&status
     * </pre>
     */
    public function startupAction(){
        $imgmd5 = $this->getInput('imgmd5');
        //
        $data = array(
            'img'=>'',
            'imgmd5'=>'',
            'status'=>'',
        );
        $data['status'] = isset($this->config['startup.status']) ? "{$this->config['startup.status']}" : "0";
        if (isset($this->config['startup.status']) && $this->config['startup.status'] ) {
            $_imgmd5 = md5_file(Pw::getPath($this->config['startup.img']));
            if ($imgmd5 != $_imgmd5){
               $data['img']    = Pw::getPath($this->config['startup.img']);
               $data['imgmd5'] = $_imgmd5;
           }
        }
        $this->setOutput($data, 'data');
        $this->showMessage("success");
    }


    /**
     * android发现页显示模块开关配置 
     * 
     * @access public
     * @return void
     * @example
     * <pre>
     * /index.php?m=native&c=Setting&a=freshSetting
     * </pre>
     */
    public function freshSettingAction(){
        $config = Wekit::C()->getValues('freshSetting');
        $this->setOutput($config, 'data');
        $this->showMessage("success");
    }

    /**
     * 自定义发现数据 
     * 
     * @access public
     * @return void
     * @example
     * <pre>
     * /index.php?m=native&c=Setting&a=freshList
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
