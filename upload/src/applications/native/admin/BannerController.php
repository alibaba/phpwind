<?php
/**
 * 移动端banner管理
 *
 * @fileName: BannerController.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-15 19:15:21
 * @desc: 
 **/
Wind::import('ADMIN:library.AdminBaseController');

class BannerController extends AdminBaseController {

	/* (non-PHPdoc)
	 * @see WindController::run()
	 */
	public function run() {

        $config = $this->_getDs()->getData();
        $this->setOutput($config, 'configData');
    }

    private function getBannerData(){
        $config = Wekit::C()->getValues('nativeBanner');
        return $config;
    }

    private function getNewPos(){
        $config = $this->getBannerData();
        $pos = array(0,1,2,3);
        foreach($config as $v){
            unset($pos[$v['pos']]);
        }
        $key = array_rand($pos);
        return $key===null ? false : $pos[$key];
    }


    public function addAction(){
        $pos = $this->getInput('pos','get');
        $config = $this->_getDs()->getData();

        //
        $banner = array();
        isset($config[$pos]) && $banner = $config[$pos];
        $this->setOutput( $pos, 'pos');
        $this->setOutput( $banner, 'banner');
    }

    public function doAddAction(){
        list($title,$clickType,$link,$pos,$order,$path) = $this->getInput(array('title','clickType','link','pos','order','path'));
        $title  = trim($title);
        $link   = trim($link);
        $isedit = false;
        if( $pos==="" ){
            $pos = $this->getNewPos();
            $order = $pos;
        }else{
            $isedit = true; 
        }
        if( $pos===false ){
            $this->showError("NATIVE:banner.num.out");
        }
        if( empty($title) ){
            $this->showError("NATIVE:banner.title.empty");
        }
        if( empty($clickType) ){
            $this->showError("NATIVE:banner.clickType.empty");
        }
        if( empty($link) ){
            $this->showError("NATIVE:banner.link.empty");
        }

        if( count($_FILES) ){
            Wind::import('SRV:upload.action.PwBannerUpload');                                                                                                
            Wind::import('LIB:upload.PwUpload');
            $bhv = new PwBannerUpload();
            $bhv->filename = $pos;

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
            $path = $data['path'].$data['filename'];
        }elseif($isedit===false){
            $this->showError('upload.empty');
        }


        //删除原图
        //Pw::deleteAttach($data['path'].$data['filename'], 0);

        //
        $config = new PwConfigSet('nativeBanner');
        $config
            ->set('title.'.$pos, $title)
            ->set('clickType.'.$pos, $clickType)
            ->set('path.'.$pos, $path)
            ->set('link.'.$pos, $link)
            ->set('order.'.$pos, $order)
            ->flush();

        $this->setOutput($data, 'data');
        $this->showMessage('success');
    }


    /**
     * 删除 
     * 
     * @access public
     * @return void
     */
    public function delAction(){
        $pos = $this->getInput('pos');
        foreach( $this->_getDs()->fileds as $v){
            Wekit::C()->deleteConfigByName('nativeBanner', $v.'.'.$pos );
        }
        $this->showMessage('success', 'native/Banner/run', true);
    } 

    /**
     * 显示添加banner布局 
     * @access public
     * @return void
     */
    public function addBannerAction(){

    }
    
    /**
     * 保存添加,删除,排序 
     * @access public
     * @return void
     */
    public function doBannerAction(){

    } 

    private function _getDs(){
       return Wekit::load('native.PwBanner'); 
    }


}
