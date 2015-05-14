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

function writelog()
{
    $file = dirname(__FILE__) . '/../../../../data/log/third.log';
    foreach(func_get_args() as $arg) {
        file_put_contents($file,
            (is_string($arg) ? $arg : var_export($arg, true))."\r\n", FILE_APPEND);
    }
}

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
    
    public $access_token;

    public $native_name = 'www.phpwind.net';

    public $third_platform_name = 'qq';

    public $oauth_uid = 0;

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
        /*
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
                $this->_session->set($this->third_platform_name.'_access_token', $_access_token);
            }
        }
        //step 2
        if( $_access_token ){
         */
        if ($this->access_token) {
            $_uri = sprintf($this->_third_platform_uri_conf[$this->third_platform_name]['openid'],$this->access_token);
            $_openid_result = WindidUtility::buildRequest($_uri, array(), true, 2, 'get');
            if (!empty($_openid_result)) {
                $_openid_result = substr($_openid_result, 9, -3);
                $_openid_result = json_decode($_openid_result,true);
                if( isset($_openid_result['openid']) ){
                    //step 3
                    $_uri = sprintf($this->_third_platform_uri_conf[$this->third_platform_name]['userinfo_uri'],$this->access_token, $_openid_result['client_id'],$_openid_result['openid']);
                    $_userinfo_result = WindidUtility::buildRequest($_uri,array(),true,2,'get');
                    $_userinfo_result = json_decode($_userinfo_result,true);
                }else{
                    $_userinfo_result = null;
                }
                //
                if( isset($_userinfo_result['ret']) && $_userinfo_result['ret']==0  ){
                    $info = array(
                        'uid'       =>md5($_userinfo_result['figureurl_qq_2']),
                        'username'  =>$_userinfo_result['nickname'],
                        'gender'    =>$_userinfo_result['gender']=='男'?0:1,
                        'avatar'    =>$_userinfo_result['figureurl_qq_2'],
                    );
                    unset($_userinfo_result);
                }
            }
        }
        /*
        if( empty($info) || !isset($info) ){
            $this->_session->delete($this->third_platform_name.'_access_token');
        }
         */
        return $info;
    }

    /**
     * weixinAuthInfo 
     * 
     * @access public
     * @return void
     */
    public function weixinAuthInfo(){
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
                    'uid'       =>'',
                    'username'  =>$_userinfo_result['nickname'],
                    'gender'    =>$_userinfo_result['sex']=='1'?0:1,
                    'avatar'    =>$_userinfo_result['headimgurl'],
                );
            }
            unset($_userinfo_result);
        }
        if( empty($info) || !isset($info) ){
            $this->_session->delete($this->third_platform_name.'_access_token');
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
        /*
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
         */
        //step 2
        $info = array();
        if( $this->access_token ){
            $_uri = sprintf($this->_third_platform_uri_conf[$this->third_platform_name]['userinfo_uri'],$this->access_token,$this->oauth_uid,$this->_appkey);
            $_userinfo_result = WindidUtility::buildRequest($_uri,array(),true,2,'get');
            $_userinfo_result = json_decode($_userinfo_result,true);
            //
            if( isset($_userinfo_result['name']) ){
                $info = array(
                    'uid'       =>$_userinfo_result['id'],
                    'username'  =>$_userinfo_result['name'],
                    'gender'    =>$_userinfo_result['gender']=='m'?0:1,
                    'avatar'    =>$_userinfo_result['avatar_large'],
                );
            }
            unset($_userinfo_result);
        }
        /*
        if( empty($info) || !isset($info) ){
            $this->_session->delete($this->third_platform_name.'_access_token');
        }
         */
        return $info;
    }


    /**
     * 淘宝; 暂时不支持 
     * 
     * @access public
     * @return void
     */
    public function taobaoAuthInfo(){
        //
        $this->_appid   = $this->_third_platform_conf[$this->third_platform_name.'.appId'];
        $this->_appkey  = $this->_third_platform_conf[$this->third_platform_name.'.appKey'];

        //step 1
        $_access_token = $this->_session->get($this->third_platform_name.'_access_token');
        if( empty($_access_token) ){
            $_uri = sprintf($this->_third_platform_uri_conf[$this->third_platform_name]['access_token_uri'],$this->_appid,$this->_appkey,$this->auth_code,$this->native_name);
            $_uri_data = parse_url($_uri);
            $_uri = sprintf('%s://%s%s',$_uri_data['scheme'],$_uri_data['host'],$_uri_data['path']);
            parse_str($_uri_data['query'], $postdata);
            $_access_token_result = WindidUtility::buildRequest($_uri,$postdata,true,2);
            $_args = json_decode($_access_token_result, true);
            if( isset($_args['access_token']) ){
                $_access_token = $_args['access_token'];
                $this->_session->set($this->third_platform_name.'_access_token', $_access_token);
            }
        }

        exit;
        //step 2
        if( $_access_token ){
            $_uri = sprintf($this->_third_platform_uri_conf[$this->third_platform_name]['userinfo_uri'],$_access_token['access_token'],$_access_token['uid'],$this->_appkey);
            $_userinfo_result = WindidUtility::buildRequest($_uri,array(),true,2,'get');
            $_userinfo_result = json_decode($_userinfo_result,true);
            //
            if( isset($_userinfo_result['name']) ){
                $info = array(
                    'uid'       =>'',
                    'username'  =>$_userinfo_result['name'],
                    'gender'    =>$_userinfo_result['gender']=='m'?0:1,
                    'avatar'    =>$_userinfo_result['avatar_large'],
                );
            }
            unset($_userinfo_result);
        }
        if( empty($info) || !isset($info) ){
            $this->_session->delete($this->third_platform_name.'_access_token');
        }
        return $info;
    }

}
