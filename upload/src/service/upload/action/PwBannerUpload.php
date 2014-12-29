<?php
/**
 * @fileName: PwBannerUpload.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-26 20:20:43
 * @desc: 
 **/
defined('WEKIT_VERSION') || exit('Forbidden');

Wind::import('LIB:upload.PwUploadAction');
Wind::import('COM:utility.WindUtility');

class PwBannerUpload extends PwUploadAction {

    public $ftype = array();    
	public $mime = array();

	public function __construct() {
		$this->ftype = array('jpg' => 2000, 'jpeg' => 2000, 'png' => 2000, 'gif' => 2000);
        $this->mime = array('image/jpg', 'image/jpeg', 'image/png', 'image/gif');
	}
	
	/**
	 * @see PwUploadAction.check
	 */
	public function check() {
		return true;
	}
	
	/**
	 * @see PwUploadAction.allowType
	 */
	public function allowType($key) {
        return true;
    }
	
	/**
	 * @see PwUploadAction.getSaveName
	 */
	public function getSaveName(PwUploadFile $file) {
		$this->filename = $this->filename . '.' .$file->ext;
		return $this->filename;
	}
	
	/**
	 * @see PwUploadAction.getSaveDir
	 */
	public function getSaveDir(PwUploadFile $file) {
        return $this->dir = 'native/banner/';
	}
	
	/**
	 * @see PwUploadAction.allowThumb
	 */
	public function allowThumb() {
		return true;
	}
	
	/**
	 * @see PwUploadAction.getThumbInfo
	 */
    public function getThumbInfo($filename, $dir) {
        return array(
            array($filename, $dir, 350, 150, 2)
        );
	}
	
	/**
	 * @see PwUploadAction.allowWaterMark
	 */
	public function allowWaterMark() {
		return false;
	}
	
	public function transfer() {
		return false;
	}

	/**
	 * @see PwUploadAction.update
	 */
	public function update($uploaddb) {
		foreach ($uploaddb as $key => $value) {
			$this->attachs = array(
				'name'      => $value['name'],
				'type'      => $value['type'],
				'path'		=> $this->dir,
				'filename'	=> $this->filename,
				'size'      => $value['size'],
				'ext'		=> $value['ext'],
			);
		}
		return true;
	}

	public function getAttachInfo() {
		return $this->attachs;
	}
}
