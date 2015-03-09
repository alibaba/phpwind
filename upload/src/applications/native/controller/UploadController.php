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
Wind::import('APPS:native.controller.NativeBaseController');

class UploadController extends NativeBaseController  {

    public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);
        $this->checkUserSessionValid();
        $this->loginUser = new PwUserBo($this->uid);
        $this->checkUserSessionValid();//统一校验用户是否登录，未登录做跳转
    }


    /**
     * 图片上传接口
     * @access public
     * @return string
     * @example
     <pre>
     /index.php?m=native&c=upload&a=dorun&_json=1
     //post: fid=帖子分类id&Filename=图片名字&Upload=Submit Query&attach=图片file
     post(精简版): fid=帖子分类id&Filename=附件二进制
     cookie:usersession
     response: {err:"",data:""}  
     </pre>
     */
    public function dorunAction() {

        $user = new PwUserBo($this->uid);

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
    
    /**
     * 暂时不用
     */
    public function replaceAction() {

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

}
