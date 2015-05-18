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

// 用户已经存在
define('LAIWANG_ERROR_USER_DUPLICATE', '110004');

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
    const WK_API_CREATE_TALK    = 'https://wkapi.laiwang.com/v1/im/conversation/create';
    const WK_API_SEND_MESSAGE   = 'https://wkapi.laiwang.com/v1/im/message/send';

    public static $wk_setting      = array(
        'org'       =>'',
        'domain'    =>'',
        'appKey'    =>'',
        'openid'    =>'',   //openid
        'secretToken'=>'',  //用户需要这个登录来往
    );
    public static $wk_appToken = '';
    public static $wk_appSecret= '';

    /**
     * 需要从GET或者POST传入os，可以是android或者ios
     *
     */
    function __construct()
    {
        $_config = Wekit::C()->getValues('wukong');
        if (empty($_config) || !isset($_config['ios.appKey'])) {
            $_securityKey = Wekit::C()->getConfigByName('site', 'securityKey');
            $_config = self::saveAppekySetting($_securityKey['value']);
        }
        if (empty($_config)) {
            $info = Wekit::C()->getConfigByName('site', 'info.url');
            error_log('No laiwang info found for: '.$info['value']);
            return;
        }

        // NOTE: 增加了os判断
        $os = isset($_POST['os']) ? $_POST['os'] : (isset($_GET['os']) ? $_GET['os'] : '');
        $os = strtolower($os);
        if (empty($os) || !in_array($os, array('android', 'ios'))) {
            $os = 'android';
        }

        $_config['appKey']  = $_config[$os . '.appKey'];
        self::$wk_appToken  = $_config['appToken'];
        self::$wk_appSecret = $_config[$os . '.appSecret'];
        //
        unset($_config['android.appKey']);
        unset($_config['android.appSecret']);
        unset($_config['ios.appKey']);
        unset($_config['ios.appSecret']);
        unset($_config['appToken']);
        //
        self::$wk_setting = array_merge(self::$wk_setting, $_config);
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
        $unsecurityKey = WindidUtility::buildRequest($_uri,array(),true,self::WK_TIMEOUT,'get');
        $unsecurityKey = json_decode($unsecurityKey, true);
        if ($unsecurityKey && $unsecurityKey['status']==200) {
            $_unsecurityKey = Pw::decrypt($unsecurityKey['data']['info'],$key);
            $appSettingData = unserialize($_unsecurityKey);
            // 解密错了或者其他情况不保存空的数据到数据库
            if (!$appSettingData) {
                return array();
            }
            // 保存laiwang appkey等
            $config = new PwConfigSet('wukong');
            $config
                ->set('appToken',$appSettingData['appToken'])
                ->set('domain',$appSettingData['domain'])
                ->set('org',$appSettingData['org'])
                ->set('android.appKey',$appSettingData['Android']['appKey'])
                ->set('android.appSecret',$appSettingData['Android']['appSecret'])
                ->set('ios.appKey',$appSettingData['iOS']['appKey'])
                ->set('ios.appSecret',$appSettingData['iOS']['appSecret'])
                ->flush();
            return $appSettingData;
        }
        return array();
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

    const DEFAULT_PWD = 'b1ff423f07537e0dd5818f78c110b472';
    /**
     * 更新用户密码 
     * 
     * @param mixed $uid 
     * @param mixed $newpwd 
     * @access public
     * @return void
     */
    public static function updateSecret($uid, $newpwd){
        // 某些DX转过来的程序，pw_user表里的password字段会是空的
        // 绕过这个bug，不会有安全问题
        if (empty($newpwd)) {
            $newpwd = self::DEFAULT_PWD;
        }
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

    const CONVERSATION_SINGLE = 1;
    const CONVERSATION_MULTI  = 2;

    /**
     * createConversation
     *
     * @param $uids 参与会话的openids，其中第0项是发起者
     * @param $type 1是单聊，2是群聊
     *
     * @return array("data" => array("conversationId" => "1:19"),
     *               "success" => true)
     *
     * @access public
     * @return void
     */
    public static function createConversation(array $uids, $type, $title = '', $icon = '')
    {
        $content = array(
                   'openId' => $uids[0],
                   'type'   => $type,
                   'icon'   => $icon,
                   'title'  => $title,
                   'members'=> $uids,
                   );
        return self::request(self::WK_API_CREATE_TALK, json_encode($content),
                             array('Content-Type' => 'application/json'), true);
    }

    /**
     * sendMessage
     *
     * @param $content 参考来往服务端开发文档，比如文本类型的content是：
     *                 array('contentType' => 'TEXT', 'text' => '呵呵')
     * @param $extension key-value形式的array
     *
     * @return array("data" => array("createdAt" => "2015-04-03T07:11:17.948Z", "messageId" => 435430),
     *               "success" => true)
     *
     * @access public
     */
    public static function sendMessage($senderId, $conversationId, $content, $extension = array())
    {
        $data = array(
                'senderId'  => $senderId,
                'conversationId' => $conversationId,
                'content'   => $content,
                'extension' => (object)$extension,
                );
        return self::request(self::WK_API_SEND_MESSAGE, json_encode($data),
                             array('Content-Type' => 'application/json'), true);
    }

    // 也就是 admin 啊啊啊
    const DEFAULT_SENDER_UID = 1;

    const USERTYPE_NAME = 1;
    const USERTYPE_ID   = 2;

    public static $defaultNotifier = array(
        'usertype'  => self::USERTYPE_NAME,
        'userid'    => self::DEFAULT_SENDER_UID,
        'username'  => 'admin',
        'avatar'    => 'http://oss.aliyuncs.com/phpwind-image/b5828aae6b79286ec7cbbcea938f5290.png',
        'nickname'  => '小助手',
    );

    public static function getNotifier()
    {
        $config = Wekit::C()->getValues('notifier');
        if (empty($config)) {
            $config = self::$defaultNotifier;
        } else {
            $config['avatar'] = empty($config['avatar']) ? self::$defaultNotifier['avatar']
                                                         : Pw::getPath($config['avatar']);
        }
        return $config;
    }

    /**
     * sendNotification：以admin的形式给其他用户发通知消息
     *
     * @param $notification 发送的通知，消息格式见下面注释。
     *
     * @return true, false
     *
     * @access public
     *
     * @desc notification
     *
     * 1. 关注：xxxx关注了您。--系统消息，回复无效。
     *    {"type":1, "message":"xxxx关注了您。--系统消息，回复无效"}
     *
     * 2.回复主贴：xxxx评论了您的帖子《xxxx》。phpwind://openPostDetailActivity("read", ["35"])
     *   {"type":2, "message":"xxxx评论了您的帖子《xxxx》", "url":"read", "arg":["35"]}
     *
     * 3.回复回帖：xxxx评论了您的回帖《xxxx》。
     *   phpwind://openNewActivity("allReplyReply", ["tid", "pid","uid","username","avater", "lou","created_time","content"])
     *
     *   {"type":3, "message":"xxxx评论了您的回帖《xxxx》。",
     *    "url":"allReplyReply", "arg":["tid","pid","uid","username","avater","lou","created_time","content"]}
     *
     * 4. 被删帖：您的帖子《xxxx》被管理员xxxx执行了删除操作。--系统消息，回复无效。
     *    {"type":4, "message":"您的帖子《xxxx》被管理员xxxx执行了删除操作。--系统消息，回复无效"}
     *
     * 5. 禁止用户：您被管理员xxxx禁止发帖了，同时您的头像、签名将不可见，如要申诉，请联系管理员xxxx。--系统消息，回复无效。
     *    {"type":5, "message":
     *     "您被管理员xxxx禁止发帖了，同时您的头像、签名将不可见，如要申诉，请联系管理员xxxx。--系统消息，回复无效。"}
     */
    public static function sendNotification($touid, array $notification)
    {
        $notifier = self::getNotifier();

        // 按照来往的约定，两人会话的会话id，按照"小的uid:大的uid"这样组织
        $conversation = min($notifier['userid'], $touid) . ':' . max($notifier['userid'], $touid);
        $content = array('contentType' => 'TEXT', 'text' => json_encode($notification));

        $result = self::sendMessage($notifier['userid'], $conversation, $content);
        return $result['success'];
    }

    /**
     * 生成来往用户的SecretToken 
     * 
     * @access public
     * @return void
     */
    public static function getSecretToken($openId, $openSecret){
        // 某些DX转过来的程序，pw_user表里的password字段会是空的
        // 绕过这个bug，不会有安全问题
        if (empty($openSecret)) {
            $openSecret = self::DEFAULT_PWD;
        }
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
    private static function request($uri, $params, $headers = array(), $returndata = false){
        //必须有配置 
        foreach (self::$wk_setting as $k=>$v) {
            if( !$v && $k!="openid" && $k!="secretToken"){
                return false;
            }
        }

        //
        $request = Wind::getComponent('httptransfer', array($uri, self::WK_TIMEOUT));
        $headers = array_merge($headers, array('Authorization'=> self::_getAuthorization()));

        // 对于https，如果是OpenSSL，设置上这个选项
        $curl_version = curl_version();
        if (strstr($curl_version['ssl_version'], 'OpenSSL') !== false) {
            $request->request(CURLOPT_SSL_CIPHER_LIST, 'TLSv1');
        }

        $request->setData($params);
        $request->setHeader($headers);
        $result = $request->send('POST');
        if ($result){
            $result = json_decode($result, true);

            // 是否返回json_decode之后的原始数据
            if ($returndata) {
                return $result;
            }

            if ($result['success']==true) {
                return true; 
            }
            // 来往用户注册提示重复时，我们认为是正确的结果
            if ($uri == self::WK_API_REGISTER && $result['errorCode'] == LAIWANG_ERROR_USER_DUPLICATE) {
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
