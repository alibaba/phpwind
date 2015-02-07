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
Wind::import('SRV:native.PwBanner');

class BannerController extends AdminBaseController {

	/* (non-PHPdoc)
	 * @see WindController::run()
	 */
	public function run() {
        $config = $this->_getDao()->getBanner(PwBanner::BANNER_TYPE_NATIVE_INDEX);
        $this->setOutput($config, 'configData');
    }

    public function addAction(){

        $bid = $this->getInput('bid','get');
        $banner = $this->_getDao()->getOneBanner($bid);

        //
        $this->setOutput( $bid, 'bid');
        $this->setOutput( $banner, 'banner');
    }

    public function doAddAction(){
        list($bid,$title,$clickType,$href,$vieworder,$img) = $this->getInput(array('bid','title','clickType','href','vieworder','img'));
        $title  = trim($title);
        $href   = trim($href);
        $vieworder  = (int)$vieworder;

        if( count($this->_getDao()->getBanner(PwBanner::BANNER_TYPE_NATIVE_INDEX))>=4 && !$bid ){
            $this->showError("NATIVE:banner.num.out");
        }
        if( empty($title) ){
            $this->showError("NATIVE:banner.title.empty");
        }
        if( empty($clickType) ){
            $this->showError("NATIVE:banner.clickType.empty");
        }
        if( empty($href) ){
            $this->showError("NATIVE:banner.link.empty");
        }
        if( $bid ){
            $fname = $bid;
        }else{
            $maxId = $this->_getDao()->getMaxId();
            $fname = count($maxId)?$maxId['banner_id']+1:1;
        }
        if( count($_FILES) ){
            Wind::import('SRV:upload.action.PwBannerUpload');                                                                                                
            Wind::import('LIB:upload.PwUpload');
            $bhv = new PwBannerUpload();
            $bhv->filename = $fname;

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
            $img = $data['path'].$data['filename'];
        }elseif(empty($bid)){
            $this->showError('upload.empty');
        }

        $data=array(
            'banner_type'=>PwBanner::BANNER_TYPE_NATIVE_INDEX,
            'type'=>$clickType,
            'title'=>$title,
            'href'=>$href,
            'img'=>$img,
            'vieworder'=>$order,
        );
        if( $bid ){
            $this->_getDao()->updateBanner($bid,$data);
        }else{
            $this->_getDao()->addBanner($data);
        }
        $this->showMessage('success');
    }

    /**
     * 删除 
     * 
     * @access public
     * @return void
     */
    public function delAction(){
        $bid = $this->getInput('bid');
        $banner = $this->_getDao()->getOneBanner($bid);
        if( $this->_getDao()->delete((int)$bid) ){
            Pw::deleteAttach($banner['img'], 0);
        }
        $this->showMessage('success', 'native/Banner/run', true);
    } 

    public function dosetveiworderAction(){
        $bids = $this->getInput('bid');
        foreach ($bids as $bid=>$vieworder) {
            $this->_getDao()->updateBanner($bid,array('vieworder'=>$vieworder));
        }
        $this->showMessage('success', 'native/Banner/run', true);
    }

    private function _getDao(){
        return Wekit::loadDao('native.dao.PwBannerDao'); 
    }

}
