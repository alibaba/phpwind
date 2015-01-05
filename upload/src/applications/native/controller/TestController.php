<?php
/**
 * 测试相关接口、主要用于生成测试表单
 *
 * @fileName: ListController.php
 * @author: yuliang<yuliang.lyl@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-16 19:08:17
 * @desc: 
 **/
defined('WEKIT_VERSION') || exit('Forbidden');

class TestController extends PwBaseController {

        /**
         * 测试用表单，上线删除
         */
        public function testFormAction(){}
        
	public function beforeAction($handlerAdapter) {
		parent::beforeAction($handlerAdapter);
//		if (!$this->loginUser->isExists()) $this->showError('VOTE:user.not.login');
	}


 
    public function testAction(){
        echo $this->getInput('arg')."<br>";
        echo "testAction<br>";exit;
    }
    
}
