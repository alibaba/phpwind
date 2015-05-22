<?php
/**
 * mobile应用基础类 
 *
 * @fileName: NativeBaseController.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com><34214399@qq.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-17 15:53:58
 * @desc: 
 **/
defined('WEKIT_VERSION') || exit('Forbidden');

abstract class NativeBaseController extends PwBaseController {

    protected $uid = 0;

    protected $_securityKey = null;

	public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);
        $_config_securityKey = Wekit::C()->getConfigByName('site', 'securityKey');
        $this->_securityKey = $_config_securityKey['value'];
        //
        $this->authSessionKey();
        //
        //更新来往appKey&appSecret
        $this->_getLaiWangSerivce();
	}

    /**
     * 获得第三方平台的appid，用来app生成使用
     * 
     * @access public
     * @return void
     */
    protected function thirdPlatformAppid(){
        $config = Wekit::C()->getValues('thirdPlatform');
        //
        $apidata = array();
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
                $apidata[] = array(
                    'platformname'=>$k,
                    'order'=>$order,
                    'appId'=>$appId,
                );
            }
        }
        return $apidata;
    }

    /**
     * 获得消息助手的通知设置
     * 
     * @access public
     * @return void
     */
    protected function notifierSetting()
    {
        Wind::import('APPS:native.service.PwLaiWangSerivce');
        $config = PwLaiWangSerivce::getNotifier();

        // 返回uid，nickname，avatar
        return array(
               'uid'      => $config['userid'],
               'nickname' => $config['nickname'],
               'avatar'   => $config['avatar'],
               );
    }

    /**
     * 获得基本用户信息 
     * 
     * @param mixed $uid 
     * @access private
     * @return void
     */
    protected function _getUserInfo($laiwangOK = true){
        //
        $_userInfo = $this->_getUserAllInfo(PwUser::FETCH_MAIN+PwUser::FETCH_INFO);

        //登录成功后，加密身份key
        $_idInfo = array(
            'username'=>$_userInfo['username'],
            'password'=>$_userInfo['password'],
        );
        $securityKey = Pw::encrypt( serialize($_idInfo), $this->_securityKey);

        //laiwang
        $wk_setting = PwLaiWangSerivce::$wk_setting;
        $wk_setting['openid']       = $_userInfo['uid'];
        $wk_setting['secretToken']  = PwLaiWangSerivce::getSecretToken($_userInfo['uid'], $_userInfo['password']);
        // 是否已经成功同步用户到来往
        $wk_setting['laiwangOK']    = $laiwangOK;

        //返回数据
        $_data = array(
            'securityKey'=>$securityKey,
            'userinfo'   =>array(
                'uid'=>$_userInfo['uid'],
                'username'=>$_userInfo['username'],
                'avatar'=>Pw::getAvatar($_userInfo['uid'],'big'),
                'gender'=>$_userInfo['gender'],
            ),
            'laiwangSetting'=>$wk_setting,
        ); 
        return $_data;
    }

    /**
     * 根据需求获得用户信息 
     * 
     * @access protected
     * @return void
     * @example
     * <pre>
     * args: PwUser::FETCH_MAIN | PwUser::FETCH_INFO | PwUser::FETCH_DATA | PwUser::FETCH_ALL
     * </pre>
     */
    protected function _getUserAllInfo($range=PwUser::FETCH_MAIN){
        return $this->uid?$this->_getUserDs()->getUserByUid($this->uid, $range):array();
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
    protected function checkUserSessionValid(){
        if( $this->isLogin() ){
            return $this->uid;
        }else{
            $this->showError("NATIVE:error.sessionkey.error");
        }
    }

    /**
     * 认证sessionKey是否合法 
     * 
     * @access protected
     * @return integer
     */
    protected function authSessionKey(){
        $unsecurityKey = $this->getInput('securityKey');
        //
        //        if(isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'],'multipart/form-data')!==false){
        //            //$unsecurityKey = urldecode($unsecurityKey);
        //        }
        if( $unsecurityKey ){
            $securityKey = unserialize(Pw::decrypt($unsecurityKey,$this->_securityKey));
            if( is_array($securityKey) && isset($securityKey['username']) && isset($securityKey['password']) ){
                $_userInfo = $this->_getUserDs()->getUserByName($securityKey['username'], PwUser::FETCH_MAIN);
                if( $_userInfo['username']==$securityKey['username'] && $_userInfo['password']==$securityKey['password'] ){
                    $this->uid = $_userInfo['uid'];
                }
            }
        }
        return $this->uid;
    }

    /**
     * 判断是否登录 
     * 
     * @access protected
     * @return void
     */
    protected function isLogin(){
        return $this->uid;
    }

    /**
     * 关联帐号后，下载第三方平台的头像 
     * 
     * @access protected
     * @return void
     */
    protected function downloadThirdPlatformAvatar($uid,$avatar_url){
        Wind::import('WSRV:base.WindidUtility');
        $image_content = WindidUtility::buildRequest($avatar_url,array(),true,2,'get');

        if( $image_content ){
//            $temp_file = tempnam(sys_get_temp_dir(),'tmp_');
            $temp_file = tempnam(PUBLIC_PATH."data/tmp/",'tmp_');
            $handle = fopen($temp_file, "w");
//            $tmpdir = sys_get_temp_dir();
//            var_dump($temp_file,$handle);
            if( $handle ){
                $res = fwrite($handle, $image_content);
                fclose($handle);

                //
                Wind::import('WSRV:upload.action.WindidAvatarUpload');
                Wind::import('LIB:upload.PwUpload');
                $bhv = new WindidAvatarUpload($uid);
                $upload = new PwUpload($bhv);

                $value= array('name'=>'avatar.jpg','size'=>1024*1024*1,'tmp_name'=>$temp_file);
                $file = new PwUploadFile('_0', $value);
                $file->filename = $upload->filterFileName($bhv->getSaveName($file));
                $file->savedir = $bhv->getSaveDir($file);
                $file->store = Wind::getComponent($bhv->isLocal ? 'localStorage' : 'storage');
                $file->source = str_replace('attachment','windid/attachment',$file->store->getAbsolutePath($file->filename, $file->savedir) );

                if (PwUpload::moveUploadedFile($value['tmp_name'], $file->source)) {
                    $image = new PwImage($file->source);
                    if ($bhv->allowThumb()) {
                        $thumbInfo = $bhv->getThumbInfo($file->filename, $file->savedir);
                        foreach ($thumbInfo as $key => $value) {
                            $thumburl = $file->store->getAbsolutePath($value[0], $value[1]);
                            $thumburl = str_replace('attachment','windid/attachment',$thumburl);

                            $result = $image->makeThumb($thumburl, $value[2], $value[3], $quality, $value[4], $value[5]);
                            if ($result === true && $image->filename != $thumburl) {
                                $ts = $image->getThumb();
                            }   
                        }   
                    }
                }
                @unlink($temp_file);
            }
        }
    }


    /**
     * 第三方平台用户登录校验; 返回用户信息
     * 
     * @access protected
     * @return array()
     * @example
     * <pre>
     * //post: access_token&platformname&native_name&oauth_uid(sina use) <br>
     * post: access_token&platformname&oauth_uid(sina use)(如果直接传递token就只需要二个参数)
     * </pre>
     */
    protected function authThirdPlatform(){
        $_oauth = Wekit::load("APPS:native.service.PwThirdOpenPlatformService");
        $_oauth->access_token = $this->getInput('access_token');
        $_oauth->third_platform_name = $this->getInput('platformname');
        $_oauth->oauth_uid = $this->getInput('oauth_uid');
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

    protected function _getUserDs(){
        return Wekit::load('user.PwUser');
    }

    protected function _getLaiWangSerivce(){
        return Wekit::load("APPS:native.service.PwLaiWangSerivce");
    }


}
