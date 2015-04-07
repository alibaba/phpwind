<?php

/**
 * 附件上传页面
 *
 * @author Jianmin Chen <sky_hold@163.com>
 * @license http://www.phpwind.com
 * @version $Id: UploadController.php 28799 2013-05-24 06:47:37Z yetianshi $
 * @package forum
 */

class UploadController extends PwBaseController {

	public function run() {

		header("Content-type: text/html; charset=" . Wekit::V('charset'));
		//$pwServer['HTTP_USER_AGENT'] = 'Shockwave Flash';
		$swfhash = 1/*GetVerify($winduid)*/;
		Pw::echoJson(array('uid' => $this->loginUser->uid, 'a' => 'dorun', 'verify' => $swfhash));
		
		$this->setTemplate('');

	}

	public function dorunAction() {
		
		if (!$user = $this->_getUser()) {
			$this->showError('login.not');
		}
        $fid = $this->getInput('fid', 'post');

        //
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

    /**
     * 淘宝推广图片 
     * 
     * @access public
     * @return void
     */
    public function dotaoAction(){
        if (!$user = $this->_getUser()) {
            $this->showError('login.not');
        }
        $fid = $this->getInput('fid', 'post');
        //
        $this->_accpetUploadForH5();
        //
        Wind::import('SRV:upload.action.PwTaoUpload');
        Wind::import('LIB:upload.PwUpload');
        $bhv = new PwTaoUpload();
        $bhv->filename = date('YmdHis');
        //
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
        $data['path'] = Pw::getPath($data['path'].$data['filename']);
        // 
        $this->setOutput($data, 'data');
        $this->showMessage('upload.success');
    }

    /**
     * 对h5上传图片支持 
     * 
     * @access protected
     * @return void
     */
    protected function _accpetUploadForH5(){
        if( isset($_POST['Filename']) && isset($_FILES['Filedata']) ){
            $Filedata = $this->getInput('Filename', 'post');
            preg_match('/image\/jpeg/i',$Filedata) && $image_content = base64_decode(substr($Filedata, 23));
            if( isset($image_content) && file_exists($_FILES['Filedata']['tmp_name']) ){
                file_put_contents($_FILES['Filedata']['tmp_name'], $image_content );
                $_FILES['Filedata']['size'] = strlen($image_content);
            }
        }
    }

    protected function _getUser() {
        $authkey = 'winduser';                                                                                                                            
        $pre = Wekit::C('site', 'cookie.pre');
        $pre && $authkey = $pre . '_' . $authkey;

        $winduser = $this->getInput($authkey, 'post');
        if( !$winduser ){
            $winduser = Pw::getCookie('winduser');
            $winduser = urlencode($winduser);
        }

        list($uid, $password) = explode("\t", Pw::decrypt(urldecode($winduser)));
        $user = new PwUserBo($uid);
        if (!$user->isExists() || Pw::getPwdCode($user->info['password']) != $password) {
            return null;
        }   
        unset($user->info['password']);
        return $user;
	}
}
