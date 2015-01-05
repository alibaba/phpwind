<?php

/**
 * 附件上传接口
 *
 * @fileName: UploadController.php
 * @author: yuliang<yuliang.lyl@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-16 19:08:17
 * @desc: 
 * */
class UploadController extends PwBaseController {
        public function attachFormAction(){
            
        }
        
	public function run() {

		header("Content-type: text/html; charset=" . Wekit::V('charset'));
		//$pwServer['HTTP_USER_AGENT'] = 'Shockwave Flash';
		$swfhash = 1/*GetVerify($winduid)*/;
		Pw::echoJson(array('uid' => $this->loginUser->uid, 'a' => 'dorun', 'verify' => $swfhash));
		
		$this->setTemplate('');

	}
        
        /**
        * 图片上传接口
        * @access public
        * @return string
        * @example
        <pre>
        /index.php?m=native&c=upload&a=dorun&_json=1
        post: fid=帖子分类id&Filename=图片名字&Upload=Submit Query&attach=图片file
        cookie:usersession
        response: {err:"",data:""}  
        </pre>
        */
	public function dorunAction() {
//            var_dump($this);exit;
                isset($_COOKIE['WN0_winduser']) ? $_POST['WN0_winduser'] = urlencode($_COOKIE['WN0_winduser']) : '';
                isset($_COOKIE['WN0_AdminUser']) ? $_POST['WN0_AdminUser'] = $_COOKIE['WN0_AdminUser'] : '';
                isset($_COOKIE['WN0_visitor']) ? $_POST['WN0_visitor'] = $_COOKIE['WN0_visitor'] : '';
                isset($_COOKIE['WN0_lastvisit']) ? $_POST['WN0_lastvisit'] = $_COOKIE['WN0_lastvisit'] : '';
		if (!$user = $this->_getUser()) {
			$this->showError('login.not');
		}
//                var_dump($user);exit;
		$fid = $this->getInput('fid', 'post');

		Wind::import('SRV:upload.action.PwAttMultiUpload');
		Wind::import('LIB:upload.PwUpload');
		$bhv = new PwAttMultiUpload($user, $fid);

		$upload = new PwUpload($bhv);
		if (($result = $upload->check()) === true) {
			$result = $upload->execute();
		}
		if ($result !== true) {
			$this->showError($result->getError());
		}
		if (!$data = $bhv->getAttachInfo()) {
			$this->showError('upload.fail');
		}
		$this->setOutput($data, 'data');
		$this->showMessage('upload.success');
	}

	public function replaceAction() {
		
		if (!$this->loginUser->isExists()) {
			$this->showError('login.not');
		}
		$aid = $this->getInput('aid');
		
		Wind::import('SRV:upload.action.PwAttReplaceUpload');
		Wind::import('LIB:upload.PwUpload');
		$bhv = new PwAttReplaceUpload($this->loginUser, $aid);

		$upload = new PwUpload($bhv);
		if (($result = $upload->check()) === true) {
			$result = $upload->execute();
		}
		if ($result !== true) {
			$this->showError($result->getError());
		}
		$this->setOutput($bhv->getAttachInfo(), 'data');
		$this->showMessage('upload.success');
	}

	protected function _getUser() {
		$authkey = 'winduser';
		$pre = Wekit::C('site', 'cookie.pre');
		$pre && $authkey = $pre . '_' . $authkey;
		$winduser = $this->getInput($authkey, 'post');
//                var_dump($_POST);exit;
//                var_dump($pre,$authkey,$winduser);exit;
//                print_r($pre);//WN0
//                echo "<br>";
//                print_r($authkey);//WN0_winduser
//                echo "<br>";
//                print_r($winduser);//VOVw1Dsbc%2FSfX5HQG59FRR%2Bm0SDywFxjI9t88lqSZNlSds76FtSQvw%3D%3D
//                exit;

		list($uid, $password) = explode("\t", Pw::decrypt(urldecode($winduser)));
//                var_dump($uid,$password);exit;
		$user = new PwUserBo($uid);
		if (!$user->isExists() || Pw::getPwdCode($user->info['password']) != $password) {
			return null;
		}
		unset($user->info['password']);
		return $user;
	}
}