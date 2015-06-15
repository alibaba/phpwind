<?php
/**
 * 用户登录,注册等接口
 * 注意：客户端在请求时需要携带cookie <br>
 *
 * @fileName: UserController.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com><34214399@qq.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-15 19:10:43
 * @desc:
 **/
defined('WEKIT_VERSION') || exit('Forbidden');

Wind::import('SRV:user.srv.PwRegisterService');
Wind::import('SRV:user.srv.PwLoginService');
Wind::import('APPS:native.controller.NativeBaseController');

class UserController extends NativeBaseController {

	public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);
	}

    /**
     * 校验用户是否登录; 返回appid接口数据
     *
     * @access public
     * @return void
     * @example
     <pre>
     /index.php?m=native&c=user&a=checkLoginStatus&os=Android
     <br>
     os可以是android或者ios <br>
     post: securityKey <br>
     response: {"referer":"","refresh":false,"state":"success","data":{"thirdPlatformAppid":{"taobao":{"order":"0","appId":"a123456"}},"userinfo":{"username":"qiwen","avatar":"http:\/\/img1.phpwind.net\/attachout\/avatar\/002\/37\/41\/2374101_small.jpg","gender":0}},"html":"","message":["\u6b22\u8fce\u56de\u6765..."],"__error":""}
     </pre>
     */
    public function checkLoginStatusAction(){

        $data['notifier'] = $this->notifierSetting();
        $data['thirdPlatformAppid'] = $this->thirdPlatformAppid();
        if ($this->isLogin()) {
            // TODO: 先将laiwangOK设置成false
            $data = array_merge($this->_getUserInfo(false),$data) ;
            //
            $this->setOutput($data, 'data');
            $this->showMessage('USER:login.success');
        }
        $this->setOutput($data, 'data');
        $this->showMessage('USER:login.success');
    }

    /**
     * 检查是否已经成功同步用户到来往。
     *
     * 如果指定了openid，则检查openid；如果未指定，则检查当前的登录用户。
     *
     * @access public
     * @return void
     * @example
     <pre>
     /index.php?m=native&c=user&a=checkLaiwang&openid=1
     <br>
     post: securityKey <br>
     response: {"referer":"","refresh":false,"state":"success","data":{"laiwangOK":true},
                "html":"","message":["\u6b22\u8fce\u56de\u6765..."],"__error":""}
     </pre>
     */
    public function checkLaiwangAction() {
        $openid = intval($this->getInput('openid'));

        if (empty($openid)) {
            if (!$this->isLogin()) {
                $this->showError('USER:user.not.login');
            }
        }
        // trick, this is bad

        if (!empty($openid)) {
            $olduid    = $this->uid;
            $this->uid = $openid;
        }

        //
        $userDs = Wekit::load('user.PwUser');
        $_userInfo = $this->_getUserAllInfo(PwUser::FETCH_MAIN+PwUser::FETCH_INFO);
        $laiwangOK = PwLaiWangSerivce::registerUser($this->uid,
                                                    $_userInfo['password'],
                                                    $_userInfo['username'],
                                                    Pw::getAvatar($this->uid, 'big'),
                                                    $_userInfo['gender']);
        PwLaiWangSerivce::updateSecret($this->uid, $_userInfo['password']);
        PwLaiWangSerivce::updateProfile($this->uid, $_userInfo['username'],
                                        Pw::getAvatar($this->uid, 'big'),
                                        $_userInfo['gender']);

        if (!empty($openid)) {
            $this->uid = $olduid;
        }

        $this->setOutput(array('laiwangOK' => $laiwangOK), 'data');
        $this->showMessage('success');
    }

    /**
     * 登录;并校验验证码
     * @access public
     * @return string
     * @example
     <pre>
     /index.php?m=native&c=user&a=doLogin&os=android <br>
     os可以是android或者ios <br>
     post: username&password&csrf_token&code&_json=1 <br>
     response:
    {
        "referer": "",
            "refresh": false,
            "state": "fail",
            "message": [
                "帐号不存在"

            ],
        "__error": ""
    }
     </pre>
     */
    public function doLoginAction(){

        list($username, $password, $code) = $this->getInput(array('username', 'password', 'code'));

        if (empty($username) || empty($password)) $this->showError('USER:login.user.require');

        //
        if( $this->_showVerify() ){
            $veryfy = $this->_getVerifyService();
            if ($veryfy->checkVerify($code) !== true) {
                $this->showError('USER:verifycode.error');
            }
        }

        /* [验证用户名和密码是否正确] */
        $login = new PwLoginService();
        $this->runHook('c_login_dorun', $login);

        $isSuccess = $login->login($username, $password, $this->getRequest()->getClientIp());
        if ($isSuccess instanceof PwError) {
            $this->showError($isSuccess->getError());
        }

        //
        Wind::import('SRV:user.srv.PwRegisterService');
        $registerService = new PwRegisterService();
        $info = $registerService->sysUser($isSuccess['uid']);
        if (!$info)  $this->showError('USER:user.syn.error');

        //
        $this->uid=$isSuccess['uid'];

        //
        $_userInfo = $this->_getUserAllInfo(PwUser::FETCH_MAIN+PwUser::FETCH_INFO);
        $laiwangOK = PwLaiWangSerivce::registerUser($this->uid,
                                                    $_userInfo['password'],
                                                    $_userInfo['username'],
                                                    Pw::getAvatar($this->uid,'big'),
                                                    $_userInfo['gender']);
        PwLaiWangSerivce::updateSecret($this->uid, $_userInfo['password']);
        PwLaiWangSerivce::updateProfile($this->uid, $_userInfo['username'],
                                        Pw::getAvatar($this->uid, 'big'),
                                        $_userInfo['gender']);

        //
        $this->uid=$isSuccess['uid'];
        $this->setOutput($this->_getUserInfo($laiwangOK), 'data');
        $this->showMessage('USER:login.success');
    }

    /**
     * 注册帐号
     * @access public
     * @return void
     * @example
     <pre>
     /index.php?m=native&c=user&a=doRegister&os=android <br>
     os可以是android或者ios <br>
     post: username&password&repassword&email&code
     response: {err:"",data:""}
     </pre>
     */
    public function doRegisterAction(){
        list($username,$password,$email,$code) = $this->getInput(array('username','password','email','code'));

        //  验证输入
        Wind::import('Wind:utility.WindValidator');
        $config = $this->_getRegistConfig();
        if (!$username) $this->showError('USER:user.error.-1');
        if (!$password) $this->showError('USER:pwd.require');
        if (!$email) $this->showError('USER:user.error.-6');
        if (!WindValidator::isEmail($email)) $this->showError('USER:user.error.-7');
	
		foreach ($config['active.field'] as $field) {
			if (!$this->getInput($field, 'post')) $this->showError('USER:register.error.require.needField.' . $field);
		}
		if ($config['active.check'] && !$regreason) {
			$this->showError('USER:register.error.require.regreason');
		}

        if( $this->_showVerify() ){
            $veryfy = $this->_getVerifyService();
            if ($veryfy->checkVerify($code) !== true) {
                $this->showError('USER:verifycode.error');
            }
        }

        Wind::import('SRC:service.user.dm.PwUserInfoDm');
        $userDm = new PwUserInfoDm();
        $userDm->setUsername($username);
        $userDm->setPassword($password);
        $userDm->setEmail($email);
        $userDm->setRegdate(Pw::getTime());
        $userDm->setLastvisit(Pw::getTime());
        $userDm->setRegip(Wind::getComponent('request')->getClientIp());

        $userDm->setAliww($aliww);
        $userDm->setQq($qq);
        $userDm->setMsn($msn);
        $userDm->setMobile($mobile);
        $userDm->setMobileCode($mobileCode);
        $userDm->setQuestion($question, $answer);
        $userDm->setRegreason($regreason);

        $areaids = array($hometown, $location);
        if ($areaids) {
            $srv = WindidApi::api('area');
            $areas = $srv->fetchAreaInfo($areaids);
            $userDm->setHometown($hometown, isset($areas[$hometown]) ? $areas[$hometown] : '');
            $userDm->setLocation($location, isset($areas[$location]) ? $areas[$location] : '');
        }

        //
		$registerService = new PwRegisterService();
		$registerService->setUserDm( $userDm );
		/*[u_regsiter]:插件扩展*/
		$this->runHook('c_register', $registerService);
		if (($info = $registerService->register()) instanceof PwError) {
			$this->showError($info->getError());
        } else {
            $laiwangOK = PwLaiWangSerivce::registerUser($info['uid'], $info['password'], $info['username'], '', 1);
            //
            if (1 == Wekit::C('register', 'active.mail')) {
                $this->showMessage('USER:active.sendemail.success');
            } else {
                $this->uid = $info['uid'];
                $this->setOutput($this->_getUserInfo($laiwangOK), 'data');
                $this->showMessage('USER:register.success');
			}
		}
    }

    /**
     * 找回密码
     *
     * @access public
     * @return void
     */
    public function findPwdAction(){

        $step = $this->getInput('step');
        $username = $this->getInput('username');

        //
        Wind::import('SRV:user.srv.PwFindPassword');
        $findPasswordBp = new PwFindPassword($username);

        //
        switch($step){
        case 1:
            //返回混淆的email
            $email = $findPasswordBp->getFuzzyEmail();
            $this->setOutput($email, 'data');
            $this->showMessage('success');
            break;
        case 2:
            //通过username&email发送邮件
            $email = $this->getInput('email');
            /*检查邮箱是否正确*/
            if (true !== ($result = $findPasswordBp->checkEmail($email))) {
                $this->showError($result->getError());
            }
            /*发送重置邮件*/
            if (!$findPasswordBp->sendResetEmail(PwFindPassword::createFindPwdIdentify($username, PwFindPassword::WAY_EMAIL, $email))){
                $this->showError('USER:findpwd.error.sendemail');
            }
            $this->showMessage('USER:active.sendemail.success');
            break;
        case 3:
            //_statu 找回密码
            $statu = $this->getInput('_statu', 'get');
            !$statu && $statu = $this->getInput('statu', 'post');
            if (!$statu) $this->showError('USER:illegal.request');
            list($username, $way, $value) = PwFindPassword::parserFindPwdIdentify($statu);
            $userInfo = $this->_getUserDs()->getUserByName($username, PwUser::FETCH_INFO | PwUser::FETCH_MAIN);
            if ($userInfo[PwFindPassword::getField($way)] != $value) {
                $this->showError('fail');
            }

            //
            $code = $this->getInput('code', 'get');
            $findPasswordBp = new PwFindPassword($userinfo['username']);
            if ($way == PwFindPassword::WAY_EMAIL) {
                if ($findPasswordBp->isOverByMail()) {
                    $this->showError('USER:findpwd.over.limit.email');
                }
                if (($result = $findPasswordBp->checkResetEmail($value, $code)) instanceof PwError) {
                    $this->showError($result->getError());
                }
            }
            $this->showMessage("USER:findpwd.over.validate.success");
            break;
        case 4:
            $statu = $this->getInput('_statu', 'get');
            !$statu && $statu = $this->getInput('statu', 'post');
            if (!$statu) $this->showError('USER:illegal.request');
            list($username, $way, $value) = PwFindPassword::parserFindPwdIdentify($statu);
            $userInfo = $this->_getUserDs()->getUserByName($username, PwUser::FETCH_INFO | PwUser::FETCH_MAIN);
            if ($userInfo[PwFindPassword::getField($way)] != $value) {
                $this->showError('fail');
                $this->forwardAction('u/findPwd/run', array(), true);
            }

            //
            list($password, $repassword) = $this->getInput(array('password', 'repassword'), 'post');
            if ($password != $repassword) $this->showError('USER:user.error.-20');
            $userDm = new PwUserInfoDm($userInfo['uid']);
            $userDm->setUsername($userInfo['username']);
            $userDm->setPassword($password);
            $userDm->setQuestion('', '');
            /* @var $userDs PwUser */
            $userDs = Wekit::load('user.PwUser');
            $result = $this->_getUserDs()->editUser($userDm, PwUser::FETCH_MAIN);
            if ($result instanceof PwError) {
                $this->showError($result->getError());
            } else {
                //检查找回密码次数及更新
                $findPasswordBp = new PwFindPassword($userInfo['username']);
                $findPasswordBp->success($type);
            }
            $this->showMessage('USER:findpwd.success');
            break;
        }
    }


    /**
     * 开放帐号登录：通过第三方开放平台认证通过后,获得的帐号id在本地查找是否存在,如果存在登录成功。
     * 如果没绑定第三方账号，那么结果不返回securityKey，而是返回第三方账号用户信息；否则返回securityKey以及论坛账号信息。
     * @access public
     * @return string sessionid
     * @example
     <pre>
     os可以是android或者ios <br>
     post: access_token&platformname(qq|weibo|weixin|taobao)&native_name(回调地址)
     </pre>
     */
    public function openAccountLoginAction(){
        $accountData=$this->authThirdPlatform();
        //
        $accountRelationData = $this->_getUserOpenAccountDs()->getUid($accountData['uid'], $accountData['type']);
        //还没有绑定帐号
        if (empty($accountRelationData)){
            $accountData['uid'] = 0;//qq not uid
            $userdata = array(
                //'securityKey'=>null, //这个键值对不存在,android走注册流程
                'userinfo'=>$accountData,
                'laiwangSetting' => array_merge(array('laiwangOK'=> false), PwLaiWangSerivce::$wk_setting),
            );

        } else {
            /* [验证用户名和密码是否正确] */
            $login = new PwLoginService();
            $this->runHook('c_login_dorun', $login);

            Wind::import('SRV:user.srv.PwRegisterService');
            $registerService = new PwRegisterService();
            $info = $registerService->sysUser($accountRelationData['uid']);
            if (!$info) {
                $this->showError('USER:user.syn.error');
            }
            $this->uid=$info['uid'];
            $_userInfo = $this->_getUserAllInfo(PwUser::FETCH_MAIN+PwUser::FETCH_INFO);
            $laiwangOK = PwLaiWangSerivce::registerUser($this->uid,
                                                        $_userInfo['password'],
                                                        $_userInfo['username'],
                                                        Pw::getAvatar($this->uid,'big'),
                                                        $_userInfo['gender']);
            PwLaiWangSerivce::updateSecret($this->uid, $_userInfo['password']);
            PwLaiWangSerivce::updateProfile($this->uid, $_userInfo['username'],
                                            Pw::getAvatar($this->uid, 'big'), $_userInfo['gender']);
            $userdata = $this->_getUserInfo($laiwangOK);
        }
        //success
        $this->setOutput($userdata,'data');
        $this->showMessage('USER:login.success');
    }

    /**
     * 开放帐号注册到本系统内
     *
     * @access public
     * @return void
     * @example
     <pre>
     os可以是android或者ios <br>
     post: access_token&platformname&native_name&username&email&sex
     </pre>
     */
    public function openAccountRegisterAction() {
//        if($this->_getUserOpenAccountDs()->addUser(56,56,56)==false){
//        if(true){
//            echo "true";
//            $this->downloadThirdPlatformAvatar(59,"http://q.qlogo.cn/qqapp/1104230675/A773C383D93AAF2986157BA34965A5CD/100");
//        }else{
//            echo "false";
//        }
//        exit;
        $accountData=$this->authThirdPlatform();
        //
        list($username,$email,$sex) = $this->getInput(array('username','email','sex'));
        //随机密码
        $password = substr(str_shuffle('abcdefghigklmnopqrstuvwxyz1234567890~!@#$%^&*()'),0,7);
        //
        Wind::import('SRC:service.user.dm.PwUserInfoDm');
        $userDm = new PwUserInfoDm();
        $userDm->setUsername($username);
        $userDm->setPassword($password);
        $userDm->setEmail($email);
        $userDm->setGender($sex);
        $userDm->setRegdate(Pw::getTime());
        $userDm->setLastvisit(Pw::getTime());
        $userDm->setRegip(Wind::getComponent('request')->getClientIp());

        //
        $registerService = new PwRegisterService();
        $registerService->setUserDm( $userDm );
        /*[u_regsiter]:插件扩展*/
        $this->runHook('c_register', $registerService);
        if (($info = $registerService->register()) instanceof PwError) {
            $this->showError($info->getError());
        } else {
            // 这里注册成功，要把第三方帐号的头像下载下来并处理
            // 这里取了lastInsertId，但已经指定了主键的值，所以返回false表示成功
            if ($this->_getUserOpenAccountDs()->addUser($info['uid'],$accountData['uid'],$accountData['type']) == false) {
                $this->downloadThirdPlatformAvatar($info['uid'],$accountData['avatar']);
                $laiwangOK = PwLaiWangSerivce::registerUser($info['uid'],
                                                            $info['username'],
                                                            $info['password'],
                                                            Pw::getAvatar($info['uid'],'big'),
                                                            $accountData['gender']);
                // 重置uid
                $this->uid = $info['uid'];
                $userdata  = $this->_getUserInfo($laiwangOK);
                $this->setOutput($userdata,'data');
                $this->showMessage('USER:register.success');
            }
        }
    }

    /**
     * 修改头像
     * @access public
     * @return void
     * @example
     <pre>
     /index.php?m=native&c=user&a=doAvatar <br>
     post: securityKey <br>
     postdata: Filename <br>
     curl -X POST -F 'Filename=@icon1.jpg' -F 'csrf_token=aaa' -F '_json=1' -F 'securityKey=xx' -b 'csrf_token=aaa' '/index.php?m=native&c=user&a=doAvatar'
     </pre>
     */
    public function doAvatarAction(){
        $this->checkUserSessionValid();
        //
        Wind::import('WSRV:upload.action.WindidAvatarUpload');
        Wind::import('LIB:upload.PwUpload');
        $bhv = new WindidAvatarUpload($this->uid);

        $upload = new PwUpload($bhv);
        if (($result = $upload->check()) === true) {
            foreach ($_FILES as $key => $value) {
                if (!PwUpload::isUploadedFile($value['tmp_name']) || !$bhv->allowType($key)) {
                    continue;
                }
            }
            $file = new PwUploadFile($key, $value);
            if (($result = $upload->checkFile($file)) !== true) {
                $this->showError($result->getError());
            }
            $file->filename = $upload->filterFileName($bhv->getSaveName($file));
            $file->savedir = $bhv->getSaveDir($file);
            $file->store = Wind::getComponent($bhv->isLocal ? 'localStorage' : 'storage');
            $file->source = str_replace('attachment','windid/attachment',$file->store->getAbsolutePath($file->filename, $file->savedir) );
            //
            if (!PwUpload::moveUploadedFile($value['tmp_name'], $file->source)) {
                $this->showError('upload.fail');
            }

            $image = new PwImage($file->source);
            if ($bhv->allowThumb()) {
                $thumbInfo = $bhv->getThumbInfo($file->filename, $file->savedir);
                foreach ($thumbInfo as $key => $value) {
                    $thumburl = $file->store->getAbsolutePath($value[0], $value[1]);
                    $thumburl = str_replace('attachment','windid/attachment',$thumburl);
                    //
                    $result = $image->makeThumb($thumburl, $value[2], $value[3], $quality, $value[4], $value[5]);
                    if ($result === true && $image->filename != $thumburl) {
                        $ts = $image->getThumb();
                    }
                }
            }
            $this->showMessage('success');
        }
        $this->showMessage('operate.fail');
    }

    /**
     * 修改性别
     * @access public
     * @return void
     * @example
     <pre>
     /index.php?m=native&c=user&a=doSex <br>
     post: securityKey&gender
     </pre>
     */
    public function doSexAction(){
        $this->checkUserSessionValid();
        //
        $userDm = new PwUserInfoDm($this->uid);
        $userDm->setGender($this->getInput('gender', 'post'));

        /* @var $userDs PwUser */
        $result = $this->_getUserDs()->editUser($userDm, PwUser::FETCH_MAIN + PwUser::FETCH_INFO);

        if ($result instanceof PwError) {
            $this->showError($result->getError());
        }else{
            PwSimpleHook::getInstance('profile_editUser')->runDo($dm);
            $this->showMessage('USER:user.edit.profile.success');
        }
    }

    /**
     * 保存修改密码
     * @access public
     * @return void
     * @example
     <pre>
     /index.php?m=native&c=user&a=doPassWord <br>
     post: securityKey&oldPwd&newPwd&rePwd
     </pre>
     */
    public function doPasswordAction(){
        $this->checkUserSessionValid();
        //
        list($newPwd, $oldPwd, $rePwd) = $this->getInput(array('newPwd', 'oldPwd', 'rePwd'), 'post');
        if (!$oldPwd) {
            $this->showError('USER:pwd.change.oldpwd.require');
        }
        if (!$newPwd) {
            $this->showError('USER:pwd.change.newpwd.require');
        }
        if ($rePwd != $newPwd) {
            $this->showError('USER:user.error.-20');
        }
        $this->checkOldPwd($this->uid, $oldPwd);

        Wind::import('SRC:service.user.dm.PwUserInfoDm');
        $userDm = new PwUserInfoDm($this->uid);
        $userDm->setPassword($newPwd);
        $userDm->setOldPwd($oldPwd);
        /* @var $userDs PwUser */
        $userDs = Wekit::load('user.PwUser');
        if (($result = $userDs->editUser($userDm, PwUser::FETCH_MAIN)) instanceof PwError) {
            $this->showError($result->getError());
        }
        $this->showMessage('USER:pwd.change.success');
    }


    /**
     * 检测一个openid在悟空是否注册
     *
     * @access public
     * @return void
     * @example
     * <pre>
     * /index.php?m=native&c=user&a=checkLaiwangUser&uid=1
     * </pre>
     */
    public function checkLaiwangUserAction(){
        $openid = $this->getInput('uid');
        $result = PwLaiWangSerivce::selectProfile($openid);
        //
        $this->setOutput($result?'1':'0', 'data');
        $this->showMessage('success');
    }

    /**
     * 是否需要显示验证码 <br>
     * 需要cookie携带 PHPSESSID <br>
     * /index.php?m=verify&a=get&rand=rand()
     *
     * @access public
     * @return boolean
     * @example
     * <pre>
     * /index.php?m=native&c=user&a=ifshowVerifycode <br>
     * /index.php?m=verify&a=get&rand=rand()
     * </pre>
     */
    public function ifShowVerifycodeAction(){
        $this->setOutput($this->_showVerify(), 'data');
        $this->showMessage('success');
    }

    /**
     * 显示验证码。
     * 为了和8.7 API兼容而增加。
     *
     */
    public function showVerifycodeAction()
    {
        $this->forwardAction('verify/index/get?rand='.Pw::getTime());
    }

    /**
     * 判断是否需要展示验证码
     *
     * @return boolean
     */
    private function _showVerify() {
        $config = Wekit::C('verify', 'showverify');
        !$config && $config = array();
        if(in_array('userlogin', $config)==true){
            $result = true;
        }else{
            //ip限制,防止撞库; 错误三次,自动显示验证码
            $ipDs = Wekit::load('user.PwUserLoginIpRecode');
            $info = $ipDs->getRecode($this->getRequest()->getClientIp());
            $result = is_array($info) && $info['error_count']>3 ? true : false;
        }
        return $result;
    }

    /**
     * 开放平台帐号关联ds
     *
     * @access private
     * @return void
     */
    private function _getUserOpenAccountDs() {
        return Wekit::load('native.PwOpenAccount');
    }


}
