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
Wind::import('WIND:security.WindMcryptDes');

class PwLaiWangSerivce {

    //const PW_CREATER_URI = 'http://10.101.81.197:8030/api/getlaiwanginfo?siteurl=http%3A%2F%2F10.101.81.197%3A8001%2Fphpwind%2Fupload';
    const PW_CREATER_URI = 'http://10.101.81.197:8030';

    const WK_API_REGISTER= 'https://sandbox-wkapi.laiwang.com/v1/user/register';

    const WK_ORG = 'phpwind';
    const WK_APP_TOKEN = 'demo';
    const WK_DOMAIN = 'demo';


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
    public function registerUser($uid, $pwd, $username, $avatar, $gender){
        $nonce = mt_rand(100000,200000);
        $timestamp = time();
        //
        $signature_array=array(
            self::WK_APP_TOKEN,
            (string)$nonce,
            (string)$timestamp,
        );
        sort($signature_array, SORT_STRING);

        $signature= sha1(implode($signature_array));
        $params = array(
            'openid'   =>$uid,
            'opensecret'=>$pwd,
            'profile'=>json_encode(
                array(
                    'nick'=>$username,
                    'avatar'=>$avatar,
                    'gender'=>$gender,
                )
            ),
        );
        $request = Wind::getComponent('httptransfer', array(self::WK_API_REGISTER, 2));
        $headers = array('Authorization'=> "Wukong nonce=\"{$nonce}\", domain=\"".self::WK_DOMAIN."\", timestamp=\"{$timestamp}\", signature_method=\"sha1\", version=\"1.0\", signature=\"{$signature}\"");
        $result = $request->post($params, $headers);
        if( $result ){
            $result = json_decode($request, true);
            if( $result['success']==true ){
               return true; 
            }
        }
        return false; 
    }

    /**
     * 生成来往用户的SecretToken 
     * 
     * @access public
     * @return void
     */
    public function getSecretToken($openId, $openSecret){
        $appSecret = 'B1CC50C442D96B3ACA920616D95C64B2';
        $params = array(
            'org'   =>self::WK_ORG,
            'domain'=>self::WK_DOMAIN,
            'appKey'=>'815678BC16A624B292E4FA6C79A818D7',
            'openId'=>$openId,
            'openSecret'=>$openSecret,
        );
        
        $query = http_build_query( $params );


        $desLib = new WindMcryptDes();
        return $desLib->encrypt($query, $appSecret);
    }


}
