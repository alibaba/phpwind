<?php
/**
 * 启动画面设置
 *
 * @fileName: StartUpController.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-31 19:53:06
 * @desc: 
 **/
Wind::import('ADMIN:library.AdminBaseController');

class StartUpController extends AdminBaseController {

	/* (non-PHPdoc)
	 * @see WindController::run()
	 */
	public function run() {
        $config = Wekit::C()->getValues('native');
        $this->setOutput($config, 'config');
    }

    public function addAction(){
        $status = $this->getInput('startup_status');
        //
        $config = new PwConfigSet('native');
        if( count($_FILES) ){
            Wind::import('SRV:upload.action.PwStartUpUpload');
            Wind::import('LIB:upload.PwUpload');
            $bhv = new PwStartUpUpload();
            $bhv->filename = 'startup';
            //
            $upload = new PwUpload($bhv);
            if ( $upload->check() === true) {
                $result = $upload->execute();
            }   
            if ($result !== true) {
                $this->showError($result->getError());
            }   
            if (!$data = $bhv->getAttachInfo() ) {
                $this->showError('upload.fail');
            }
            //
            $filepath = $upload->getStore()->getAbsolutePath($data['filename'],$data['path']);
            $filecontent = file_get_contents($filepath);
            //
            $config->set('startup.imgmd5', md5($filecontent) );
            $config->set('startup.img', $data['path'].$data['filename'] );
        }
        $config
            ->set('startup.status',$status)
            ->flush();
        $this->showMessage('ADMIN:success');
    }


}
