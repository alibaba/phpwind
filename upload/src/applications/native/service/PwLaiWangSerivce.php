<?php
/**
 * 来往接入的通讯服务
 *
 * @fileName: PwLaiWangSerivce.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2015-02-06 15:05:02
 * @desc: 
 **/

Wind::import('WSRV:base.WindidUtility');
Wind::import('WIND:http.session.WindSession');

class PwLaiWangSerivce {

    //const PW_CREATER_URI = 'http://10.101.81.197:8030/api/getlaiwanginfo?siteurl=http%3A%2F%2F10.101.81.197%3A8001%2Fphpwind%2Fupload';
    const PW_CREATER_URI = 'http://10.101.81.197:8030';

    //
    function __construct(){
        $this->_third_platform_conf = Wekit::C()->getValues('thirdPlatform');
    }

    public function saveAppekySetting($key){
        $config = Wekit::C()->getConfigByName('site', 'info.url');
        $_uri = self::PW_CREATER_URI.'/api/getlaiwanginfo?siteurl='.urlencode($config['value']);
        $unsecurityKey = WindidUtility::buildRequest($_uri,array(),true,2,'get');
        $unsecurityKey = json_decode($unsecurityKey, true);
        if( $unsecurityKey && $unsecurityKey['status']==200 ){
            $_unsecurityKey = Pw::decrypt($unsecurityKey['data']['info'],$key);
            $appSettingData = unserialize($_unsecurityKey);
            //
            $config = new PwConfigSet('wukong');
            $config
                ->set('appToken',$appSettingData['appToken'])
                ->set('domain',$appSettingData['domain'])
                ->set('org',$appSettingData['org'])
                ->set('android.appKey',$appSettingData['Android']['appKey'])
                ->set('android.appSecret',$appSettingData['Android']['appSecret'])
                ->flush();
        }
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
        if( $this->access_token ){
            $_uri = sprintf($this->_third_platform_uri_conf[$this->third_platform_name]['openid'],$this->access_token);
            $_openid_result = WindidUtility::buildRequest($_uri,array(),true,2,'get');
            if( !empty($_openid_result) ){
                $_openid_result = substr($_openid_result, 9, count($_openid_result)-4);
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
}
