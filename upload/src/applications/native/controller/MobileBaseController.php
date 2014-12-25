<?php
/**
 * mobile应用基础类 
 *
 * @fileName: MobileBaseController.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-17 15:53:58
 * @desc: 
 **/
defined('WEKIT_VERSION') || exit('Forbidden');

class MobileBaseController extends PwBaseController {

    protected $_securityKey = null;

	public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);

        $_config_securityKey = Wekit::C()->getConfigByName('site', 'securityKey');
        $this->_securityKey = $_config_securityKey['value'];

        $_POST['_json'] = 1;
	}

    /**
     *
     * 测试接口，从第三方获得用户信息
     */
    public function testAction(){
        $userinfo = $this->authThirdPlatform();
        if( empty($userinfo) ){
            $this->showMessage('operate.fail');
        }else{
            $this->setOutput($userSrv, 'data');
            $this->showMessage('USER:login.success');
        }
    }

    /**
     * 校验用户是否登录; 返回appid接口数据
     * 
     * @access public
     * @return void
     * @example
     <pre>
     /index.php?m=native&c=user&a=checkLoginStatus
     <br>
     post: securityKey <br>
     response: {"referer":"","refresh":false,"state":"success","data":{"thirdPlatformAppid":{"taobao":{"order":"0","appId":"a123456"}},"userinfo":{"username":"qiwen","avatar":"http:\/\/img1.phpwind.net\/attachout\/avatar\/002\/37\/41\/2374101_small.jpg","gender":0}},"html":"","message":["\u6b22\u8fce\u56de\u6765..."],"__error":""}
     </pre>
     */
    public function checkLoginStatusAction(){
        if( $this->_checkUserSessionValid() ){

            $data['thirdPlatformAppid'] = $this->_thirdPlatformAppid();
            $data['userinfo'] = array(
                'username'=>'qiwen',
                'avatar'=>'http://img1.phpwind.net/attachout/avatar/002/37/41/2374101_small.jpg',
                'gender'=>0,
            );

            $this->setOutput($data, 'data');
            $this->showMessage('USER:login.success');
        }
    } 
 
    /**
     * 获得第三方平台的appid，用来app生成使用
     * 
     * @access public
     * @return void
     */
    protected function _thirdPlatformAppid(){
        $config = Wekit::C()->getValues('thirdPlatform');                                                                                                 
        //
        $data = array();
        if( count($config) ){
            foreach($config as $k=>$v){
                $_keys = explode('.',$k);
                $data[$_keys[0]][$_keys[1]] = $v;
            }
            foreach($data as $k=>$v){
                if( $v['status']==1 ){
                    $data[$k] = $v['displayOrder'].'-'.$v['appId'];
                }else{
                    unset($data[$k]);
                }
            }
            asort($data);
            foreach($data as $k=>$v){
                list($order,$appId) = explode('-',$v);
                $data[] = array(
                    'platformname'=>$k,
                    'order'=>$order,
                    'appId'=>$appId,
                );
            }
        }
        return $data;
        //$this->setOutput($data, 'data');
        //$this->showMessage("USER:message.success");
    }


    /**
     * Enter description here ...
     *
     * @return PwCheckVerifyService
     */
    protected function _getVerifyService() {                                                                                                                
        return Wekit::load("verify.srv.PwCheckVerifyService");
    }

    /**
     * 
     * 校验登录用户的sessionid是否合法
     *
     * @access protected
     * @return void
     * @example
     <pre>
        post: session
     </pre>
     */
    protected function _checkUserSessionValid(){
        $unsecurityKey = $this->getInput('securityKey');
        $unsecurityKey ='nvKjBzbL9mn7pAjNsH1Y8DIs6Lno1j2jlUjowLt80swW31AOI/tCAb5LRflITYZ7ZQR4eqMtyM8ZEQ0E/ezW1h1InMvIO2iY';
        //
        $unsecurityKey = Pw::decrypt($unsecurityKey,$this->_securityKey);
        $securityKey   = Pw::jsonDecode($unsecurityKey);
        
        //
        if( is_array($securityKey) && isset($securityKey['username']) && isset($securityKey['password']) ){
            $_userInfo = Wekit::load('user.PwUser')->getUserByName($securityKey['username'], PwUser::FETCH_MAIN);
            if( $_userInfo['username']==$securityKey['username'] && $securityKey['password']==$securityKey['password'] ){
                $this->showError('USER:login.error.pwd');
            }
            return $_userInfo['uid'];
        }else{
            $this->showError("NATIVE:error.sessionkey.error");
        }
    }

    /**
     * 第三方平台用户登录校验; 返回用户信息
     * 
     * @access protected
     * @return array()
     * @example
     * <pre>
     * post: auth_code&platformname&native_name
     * </pre>
     */
    protected function authThirdPlatform(){
        $_oauth = Wekit::load("APPS:native.service.PwThirdOpenPlatformService");
        $_oauth->auth_code = $this->getInput('auth_code');
        $_oauth->third_platform_name = $this->getInput('platformname');
        $_oauth->native_name = $this->getInput('native_name');
        //
        $_oauth->auth_code='6AF5CBB5925BC219956DD3F50A5BC684';
        $_oauth->third_platform_name = 'qq';
        $_oauth->native_name = 'http%3A%2F%2Fwww.iiwoo.com';
        //
        $info = array();
        $_method_name = $_oauth->third_platform_name.'AuthInfo';
        if( method_exists($_oauth,$_method_name) ){
            $info = $_oauth->$_method_name();
        }
        if( empty($info) ){
            $this->showError('NATIVE:error.openaccount.noauth'); 
        }
        $info['type'] = $_oauth->third_platform_name;
        return $info; 
    }

}
