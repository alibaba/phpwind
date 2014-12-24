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

    public function testAction(){

        $this->_authThirdPlatform();
        
        exit;
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
        $unsecurityKey ='upE8/bmzngysvSECXLkY7s5xsq+e1vyk2WzIhAyewKVgW9AenvFKP+lu7PM=';
        //
        $unsecurityKey = Pw::decrypt($unsecurityKey,$this->_securityKey);
        $securityKey   = Pw::jsonDecode($unsecurityKey);
//        exit;
        //
        if( is_array($securityKey) && isset($securityKey['username']) && isset($securityKey['password']) ){
            $userSrv = Wekit::load('user.srv.PwUserService');
            if (($r = $userSrv->verifyUser($securityKey['username'], $securityKey['password'], 2)) instanceof PwError) {
                $this->showError('USER:login.error.pwd');
            }
            $localUser = Wekit::load('user.PwUser')->getUserByName($securityKey['username'], PwUser::FETCH_MAIN); 
            return $localUser['uid'];
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
    protected function _authThirdPlatform(){
        $_oauth = Wekit::load("APPS:native.service.PwThirdOpenPlatformService");
        $_oauth->auth_code = $this->getInput('auth_code');
        $_oauth->third_platform_name = $this->getInput('platformname');
        $_oauth->native_name = $this->getInput('native_name');
        //
        $_oauth->auth_code='96246b6f3a35a4ce0899c2099be11900';
        $_oauth->third_platform_name = 'taobao';
        $_oauth->native_name = 'gamecenter://oauth.m.taobao.com/callback';
//        $_oauth->native_name = 'http%3A%2F%2Fwww.iiwoo.com';
        //
        $info = array();
        $_method_name = $_oauth->third_platform_name.'AuthInfo';
        if( method_exists($_oauth,$_method_name) ){
            $info = $_oauth->$_method_name();
        }
        print_r($info);
        return $info; 
    }

}
