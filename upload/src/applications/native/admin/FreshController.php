<?php
/**
 * 移动端发现管理
 *
 * @fileName: FreshController.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2015-02-27 11:13:14
 * @desc: 
 **/

Wind::import('ADMIN:library.AdminBaseController');
Wind::import('SRV:native.PwBanner');

class FreshController extends AdminBaseController {

	/* (non-PHPdoc)
	 * @see WindController::run()
	 */
	public function run() {
        $freshData = $this->_getDao()->getFresh();
        $this->setOutput($freshData, 'freshData');
    }

    public function addAction(){
        $fid = $this->getInput('fid','get');
        $oneFresh = $this->_getDao()->getOneFresh($fid);

        //
        $this->setOutput( $fid, 'fid');
        $this->setOutput( $oneFresh, 'oneFresh');
    }

    /**
     * 发存修改一个发现数据 
     * 
     * @access public
     * @return void
     */
    public function doAddAction(){
        list($fid,$title,$href,$des,$vieworder) = $this->getInput(array('fid','title','href','des','vieworder'));
        $title  = trim($title);
        $href   = trim($href);
        $des    = trim($des);
        $vieworder  = (int)$vieworder;

        if( empty($title) ){
            $this->showError("NATIVE:fresh.title.empty");
        }
        if( empty($href) ){
            $this->showError("NATIVE:fresh.link.empty");
        }
        if(Pw::strlen($title)>5) $this->showError("名称不能超过5个字符");
        if(Pw::strlen($des)>9) $this->showError("备注不能超过9个字符");
        if( $fid ){
            $fname = $fid;
        }else{
            $maxId = $this->_getDao()->getMaxId();
            $fname = count($maxId)?$maxId['fresh_id']+1:1;
        }
        if( !$fid && !$_FILES ){
            $this->showError('NATIVE:upload.empty');
        }
        if( $_FILES ){
            Wind::import('SRV:upload.action.PwFreshUpload');                                                                                                
            Wind::import('LIB:upload.PwUpload');
            $bhv = new PwFreshUpload();
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
        }

        $data=array(
            'title'=>$title,
            'href'=>$href,
            'des'=>$des,
            'vieworder'=>$order,
        );
        if( $img && $_FILES ){
            $data['img'] = $img;
        }
        if( $fid ){
            $this->_getDao()->updateFresh($fid,$data);
        }else{
            $this->_getDao()->addFresh($data);
        }
        $this->showMessage('success');
    }

    /**
     * 删除一个发现 
     * 
     * @access public
     * @return void
     */
    public function delAction(){
        $fid = $this->getInput('fid');
        $banner = $this->_getDao()->getOneBanner($fid);
        if( $this->_getDao()->delete((int)$fid) ){
            Pw::deleteAttach($banner['img'], 0);
        }
        $this->showMessage('success', 'native/Fresh/run', true);
    } 

    public function dosetveiworderAction(){
        $fids = $this->getInput('fid');
        foreach ($fids as $fid=>$vieworder) {
            $this->_getDao()->updateFresh($fid,array('vieworder'=>$vieworder));
        }
        $this->showMessage('success', 'native/Fresh/run', true);
    }

    /**
     * 开关设置 
     * 
     * @access public
     * @return void
     */
    public function customAction(){
        $config = Wekit::C()->getValues('freshSetting');
        $this->setOutput($config, 'info');
    }

    /**
     * 保存自定义开关 
     * 
     * @access public
     * @return void
     */
    public function doCustomAction(){
        list($hotTopic, $lifeService, $sameCity, $goodRecommend) = $this->getInput( array('hotTopic','lifeService','sameCity','goodRecommend') );
        $config = new PwConfigSet('freshSetting');
        $config
            ->set('hotTopic',$hotTopic)
            ->set('lifeService',$lifeService)
            ->set('sameCity',$sameCity)
            ->set('goodRecommend',$goodRecommend)
            ->flush();
        $this->showMessage('success', 'native/Fresh/custom', true);
    }

    private function _getDao(){
        return Wekit::loadDao('native.dao.PwFreshDao'); 
    }

}
