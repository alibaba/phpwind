<?php
/**
 * 第三方平台帐号认证
 *
 * @fileName: PwThirdOpenPlatformService.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-24 09:44:34
 * @desc: 
 **/

Wind::import('WSRV:base.WindidUtility');
Wind::import('WIND:http.session.WindSession');

class PwThirdOpenPlatformService {

    private $_session;

    /**
     * appId & appKey 配置 
     */
    private $_third_platform_conf;

    /**
     * 平台的接口 uri
     */
    private $_third_platform_uri_conf;

    private $_appid;

    private $_appkey;

    public $auth_code;

    public $native_name = 'www.iiwoo.com';

    public $third_platform_name = 'qq';


    function __construct(){
        $this->_session = new WindSession(); 
        $this->_third_platform_conf = Wekit::C()->getValues('thirdPlatform');
        $this->_third_platform_uri_conf = include(Wind::getRealPath('APPS:native.conf.thirdplatformuri.php', true));
    }

    /**
     * qq平台认证，测试通过
     * 
     * @access public
     * @return void
     */
    public function qqAuthInfo(){
        $info = array();
        //
        $this->_appid   = $this->_third_platform_conf[$this->third_platform_name.'.appId'];
        $this->_appkey  = $this->_third_platform_conf[$this->third_platform_name.'.appKey'];

        //step 1
        $_access_token = $this->_session->get($this->third_platform_name.'_access_token');
        if( empty($_access_token) ){
            $_uri = sprintf($this->_third_platform_uri_conf[$this->third_platform_name]['access_token_uri'],$this->_appid,$this->_appkey,$this->auth_code,$this->native_name);
            $_access_token_result = WindidUtility::buildRequest($_uri,array(),true,2,'get');
            parse_str($_access_token_result, $_args);
            if( isset($_args['access_token']) ){
                $_access_token = $_args['access_token'];
                print_r($_access_token);
                $this->_session->set($this->third_platform_name.'_access_token', $_access_token);
            }
        }
        //step 2
        if( $_access_token ){
            $_uri = sprintf($this->_third_platform_uri_conf[$this->third_platform_name]['openid'],$_access_token);
            $_openid_result = WindidUtility::buildRequest($_uri,array(),true,2,'get');
            if( !empty($_openid_result) ){
                $_openid_result = substr($_openid_result, 9, count($_openid_result)-4);
                $_openid_result = json_decode($_openid_result,true);
                //step 3
                $_uri = sprintf($this->_third_platform_uri_conf[$this->third_platform_name]['userinfo_uri'],$_access_token, $_openid_result['client_id'],$_openid_result['openid']);
                $_userinfo_result = WindidUtility::buildRequest($_uri,array(),true,2,'get');
                $_userinfo_result = json_decode($_userinfo_result,true);
                //
                $info = array(
                    'username'  =>$_userinfo_result['nickname'],
                    'gender'    =>$_userinfo_result['gender']=='男'?0:1,
                    'avatar'    =>$_userinfo_result['figureurl_qq_2'],
                );
                unset($_userinfo_result);
            }
        }
        return $info;
    }

    /**
     * weixinAuthInfo 
     * 
     * @access public
     * @return void
     */
    public function weixinAuthInfo(){
        $info = array();
        //
        $this->_appid   = $this->_third_platform_conf[$this->third_platform_name.'.appId'];
        $this->_appkey  = $this->_third_platform_conf[$this->third_platform_name.'.appKey'];

        //step 1
        $_access_token = $this->_session->get($this->third_platform_name.'_access_token');
        if( empty($_access_token) ){
            $_uri = sprintf($this->_third_platform_uri_conf[$this->third_platform_name]['access_token_uri'],$this->_appid,$this->_appkey,$this->auth_code,$this->native_name);
            $_access_token_result = WindidUtility::buildRequest($_uri,array(),true,2,'get');
            parse_str($_access_token_result, $_args);
            if( isset($_args['access_token']) && isset($_args['openid']) ){
                $_access_token = serialize(array('access_token'=>$_args['access_token'],'openid'=>$_args['openid']));
                $this->_session->set($this->third_platform_name.'_access_token', $_access_token);
            }
        }
        $_access_token = unserialize($_access_token);
        //step 2
        if( $_access_token ){
            $_uri = sprintf($this->_third_platform_uri_conf[$this->third_platform_name]['userinfo_uri'],$_access_token['access_token'], $_access_token['openid']);
            $_userinfo_result = WindidUtility::buildRequest($_uri,array(),true,2,'get');
            $_userinfo_result = json_decode($_userinfo_result,true);
            //
            if( isset($_userinfo_result['nickname']) ){
                $info = array(
                    'username'  =>$_userinfo_result['nickname'],
                    'gender'    =>$_userinfo_result['sex']=='1'?0:1,
                    'avatar'    =>$_userinfo_result['headimgurl'],
                );
            }
            unset($_userinfo_result);
        }
        return $info;
    }

    /**
     * 微博验证，测试通过 
     * 
     * @access public
     * @return void
     */
    public function weiboAuthInfo(){
        $info = array();
        //
        $this->_appid   = $this->_third_platform_conf[$this->third_platform_name.'.appId'];
        $this->_appkey  = $this->_third_platform_conf[$this->third_platform_name.'.appKey'];

        //step 1
        $_access_token = $this->_session->get($this->third_platform_name.'_access_token');
        if( empty($_access_token) ){
            $_uri = sprintf($this->_third_platform_uri_conf[$this->third_platform_name]['access_token_uri'],$this->_appid,$this->_appkey,$this->auth_code,$this->native_name);
            $_uri_data = parse_url($_uri);
            parse_str($_uri_data['query'], $postdata);
            $_access_token_result = WindidUtility::buildRequest($_uri,$postdata,true,2);
            $_args = json_decode($_access_token_result, true);
            if( isset($_args['access_token']) && isset($_args['uid']) ){
                $_access_token = serialize(array('access_token'=>$_args['access_token'],'uid'=>$_args['uid']));
                $this->_session->set($this->third_platform_name.'_access_token', $_access_token);
            }
        }
        $_access_token = unserialize($_access_token);
        //step 2
        if( $_access_token ){
            $_uri = sprintf($this->_third_platform_uri_conf[$this->third_platform_name]['userinfo_uri'],$_access_token['access_token'],$_access_token['uid'],$this->_appkey);
            $_userinfo_result = WindidUtility::buildRequest($_uri,array(),true,2,'get');
            $_userinfo_result = json_decode($_userinfo_result,true);
            //
            if( isset($_userinfo_result['name']) ){
                $info = array(
                    'username'  =>$_userinfo_result['name'],
                    'gender'    =>$_userinfo_result['gender']=='m'?0:1,
                    'avatar'    =>$_userinfo_result['avatar_large'],
                );
            }
            unset($_userinfo_result);
        }
        return $info;
    }


}
