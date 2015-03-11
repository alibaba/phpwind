<?php
/**
 * 来往(悟空)接入的通讯服务
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
Wind::import('WIND:security.WindMcryptDes');

class PwLaiWangSerivce {

    //debug
    //const PW_CREATER_URI        = 'http://10.101.81.197:8030';
    //const WK_TIMEOUT            = 2;
    //const WK_API_REGISTER       = 'https://sandbox-wkapi.laiwang.com/v1/user/register';
    //const WK_API_UPDATE_SECRET  = 'https://sandbox-wkapi.laiwang.com/v1/user/update/secret';
    //const WK_API_UPDATE_PROFILE = 'https://sandbox-wkapi.laiwang.com/v1/user/profile/update';
    //const WK_API_SELECT_PROFILE = 'https://sandbox-wkapi.laiwang.com/v1/user/profile';
    //const WK_API_PUSH_MESSAGE   = 'https://sandbox-wkapi.laiwang.com/v1/notification/user';
    //public static $wk_setting      = array(
    //    'org'       =>'demo',
    //    'domain'    =>'demo',
    //    'appKey'    =>'815678BC16A624B292E4FA6C79A818D7',
    //    'openid'    =>0,   //openid
    //    'secretToken'=>'',  //用户需要这个登录来往
    //);
    //public static $wk_appToken = 'demo';
    //public static $wk_appSecret= 'B1CC50C442D96B3ACA920616D95C64B2';

    //online
    const PW_CREATER_URI        = 'http://phpwind.aliyun.com';
    const WK_TIMEOUT = 2;
    const WK_API_REGISTER       = 'https://wkapi.laiwang.com/v1/user/register';
    const WK_API_UPDATE_SECRET  = 'https://wkapi.laiwang.com/v1/user/update/secret';
    const WK_API_UPDATE_PROFILE = 'https://wkapi.laiwang.com/v1/user/profile/update';
    const WK_API_SELECT_PROFILE = 'https://wkapi.laiwang.com/v1/user/profile';
    const WK_API_PUSH_MESSAGE   = 'https://wkapi.laiwang.com/v1/notification/user';
    public static $wk_setting      = array(
        'org'       =>'',
        'domain'    =>'',
        'appKey'    =>'',
        'openid'    =>0,   //openid
        'secretToken'=>'',  //用户需要这个登录来往
    );
    public static $wk_appToken = '';
    public static $wk_appSecret= '';

    function __construct(){
        $_config = Wekit::C()->getValues('wukong');
        if( empty($_config) ){
            $_securityKey = Wekit::C()->getConfigByName('site', 'securityKey');
            self::saveAppekySetting($_securityKey['value']);
        }else{
            $_config['appKey']  = $_config['android.appKey'];
            self::$wk_appToken  = $_config['appToken'];
            self::$wk_appSecret = $_config['android.appSecret'];
            //
            unset($_config['android.appKey']);
            unset($_config['android.appSecret']);
            unset($_config['appToken']);
            //
            self::$wk_setting = $_config;
        }
    }

    /**
     * 保存phpwind.aliyun.com帮站长申请关于悟空的im应用密匙 
     * 
     * @param mixed $key 
     * @static
     * @access public
     * @return void
     */
    public static function saveAppekySetting($key){
        $config = Wekit::C()->getConfigByName('site', 'info.url');
        $_uri = self::PW_CREATER_URI.'/api/getlaiwanginfo?siteurl='.urlencode($config['value']);
        $unsecurityKey = WindidUtility::buildRequest($_uri,array(),true,WK_TIMEOUT,'get');
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
     * 来往用户注册 
     * 
     * @param mixed $uid 
     * @param mixed $pwd 
     * @param mixed $username 
     * @param mixed $avatar 
     * @param mixed $gender 
     * @access public
     * @return void
     */
    public static function registerUser($uid, $pwd, $username, $avatar, $gender){
        $params = array(
            'openid'    =>$uid,
            'opensecret'=>$pwd,
            'profile'   =>array(
                'nick'=>$username,
                'avatar'=>$avatar,
                'gender'=>$gender,
            ),
        );
        $params['profile'] = json_encode($params['profile']);
        return self::request(self::WK_API_REGISTER, $params);
    }

    /**
     * 更新用户密码 
     * 
     * @param mixed $uid 
     * @param mixed $newpwd 
     * @access public
     * @return void
     */
    public static function updateSecret($uid, $newpwd){
        $params = array(
            'openid'   =>$uid,
            'newsecret'=>$newpwd,
        );
        return self::request(self::WK_API_UPDATE_SECRET, $params);
    }

    /**
     * 更新用户资料 
     * 
     * @param mixed $uid 
     * @param mixed $username 
     * @param mixed $avatar 
     * @param mixed $gender 
     * @access public
     * @return void
     */
    public static function updateProfile($uid, $username, $avatar, $gender){
        $params = array(
            'openid'=>$uid,
            'nick'  =>$username,
            'avatar'=>$avatar,
            'gender'=>$gender,
            'extension'=>'',
        );
        return self::request(self::WK_API_UPDATE_PROFILE, 'profile='.json_encode($params) );
    }

    /**
     * 查询一个用户是否存在 
     * 
     * @param mixed $uid 
     * @access public
     * @return void
     */
    public static function selectProfile($uid){
        $params = array(
            'openid'=>$uid,
        );
        return self::request(self::WK_API_SELECT_PROFILE, $params);
    }

    /**
     * pushMessage 
     * 
     * @access public
     * @return void
     */
    public static function pushMessage($toUid, $title, $text){
        $content = array(
            'title' =>$title,
            'msgid' =>(string)$toUid,
            'description'=>$text,
        );
        $params = array(
            'alert'     =>$title,
            'receiverid'=>$toUid,
            'content'   =>json_encode($content),
            'persist'   =>true,
            'binary'    =>false,
            'type'      =>2,
            'badge'     =>1,
            'sound'     =>'cat.wav',
            'timeToLive'=>10,
            'param'     =>array('key'=>''),
        );
        $params = json_encode($params);
        return self::request(self::WK_API_PUSH_MESSAGE, $params);
    }

    /**
     * 生成来往用户的SecretToken 
     * 
     * @access public
     * @return void
     */
    public static function getSecretToken($openId, $openSecret){
        $params = array(
            'org'   =>self::$wk_setting['org'],
            'domain'=>self::$wk_setting['domain'],
            'appKey'=>self::$wk_setting['appKey'],
            'openId'    =>$openId,
            'openSecret'=>$openSecret,
        );
        $query = http_build_query( $params );
        $desLib = new WindMcryptDes();
        return $desLib->encrypt($query, self::$wk_appSecret);
    }

    /**
     * 向来往发起请求 
     * 
     * @param mixed $params 
     * @access private
     * @return void
     */
    private static function request($uri, $params){
        $request = Wind::getComponent('httptransfer', array($uri, self::WK_TIMEOUT));
        $headers = array('Authorization'=> self::_getAuthorization());
        $request->setData($params);
        $request->setHeader($headers);
        $result = $request->send('POST');
        if( $result ){
            $result = json_decode($result, true);
            if( $result['success']==true ){
                return true; 
            }
        }
        return false; 
    }

    /**
     * 生成认证信息 
     * 
     * @access private
     * @return void
     */
    private static function _getAuthorization(){
        $nonce = mt_rand(100000,200000);
        $timestamp = time();
        //
        $signature_array=array(
            self::$wk_appToken,
            (string)$nonce,
            (string)$timestamp,
        );
        sort($signature_array, SORT_STRING);
        $signature= sha1(implode($signature_array));
        return "Wukong nonce=\"{$nonce}\", domain=\"".self::$wk_setting['domain']."\", timestamp=\"{$timestamp}\", signature_method=\"sha1\", version=\"1.0\", signature=\"{$signature}\"";
    }

}
