<?php
/**
 * 关于我,空间的所有接口集合
 *
 * @fileName: SpaceController.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-15 19:09:45
 * @desc: 
 **/
defined('WEKIT_VERSION') || exit('Forbidden');

Wind::import('APPS:native.controller.NativeBaseController');
Wind::import('SRV:space.bo.PwSpaceModel');
Wind::import('SRV:like.PwLikeContent');

class SpaceController extends NativeBaseController {

    /**
     * global post: securityKey
     */
    public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);

    }

    /**
     * 空间首页列出审核过的贴子
     * 
     * @access public
     * @return void
     */
    public function run(){
        $spaceUid = $this->getInput('uid','get');
        $page = $this->getInput('page','get');
        
        //
        $space = new PwSpaceModel($spaceUid);
        $space->setTome($spaceUid, $this->uid);
        //
        $tids           = $this->_getPwNativeThreadDs()->getThreadListByUid($spaceUid, $page, $this->uid==$spaceUid?'my':'space');
        $myThreadList   = $this->_getPwNativeThreadDs()->getThreadContent($tids);
        //pids 默认是0； 
        $attList        = $this->_getPwNativeThreadDs()->getThreadAttach($tids, array(0) );
        $threadList     = $this->_getPwNativeThreadDs()->gather($myThreadList, $attList);


        //
        $prev_val = '';
        $_tids = $_threadList = array();
        foreach($threadList as $k=>$v){
            $_created_time = Pw::time2str($v['created_time'],'auto');
            list($_key, $_time) = explode(' ',$_created_time);
            if( !preg_match('/-/',$_created_time) ){
                $_key = '今天';
            }
            if( $prev_val!=$_key  ){
                $threadList[$k]['barName'] = $_key;
                $prev_val = $_key;
            }else{
                $threadList[$k]['barName'] = '';
            }
            $_threadList[] = $threadList[$k];
            $_tids[] = $v['tid'];
        }

        //获得登陆用户是否喜欢过帖子|回复
        $threadLikeData = array();
        if( $this->uid && $_tids ){
            $_threadLikeData = $this->_getLikeReplyService()->getAllLikeUserids(PwLikeContent::THREAD, $_tids );
            foreach($_tids as $v){
                if( isset($_threadLikeData[$v]) ){
                    $threadLikeData[$v] = array_search($this->uid, $_threadLikeData[$v])===false?0:1;
                }
            }
        }
        
        //
        $data = array(
            'userInfo'  =>isset($space->spaceUser)
            ?array(
                'username'  =>$space->spaceUser['username'],
                'gender'    =>$space->spaceUser['gender'],
                'location_text'=>$space->spaceUser['location_text'],
                'avatar'    =>Pw::getAvatar($spaceUid)
            )
            :array('username'=>'','gender'=>0,'location_text'=>'','avatar'=>''),
            'tome'      =>isset($space->tome)?$space->tome:0,
            'pageCount' =>$this->_getPwNativeThreadDs()->getThreadPageCount(),
            'threadList'=>$_threadList,
        );
        $this->setOutput($data, 'data');
        $this->showMessage('success');

    }

    private function _getPwNativeThreadDs(){
        return Wekit::load('native.PwNativeThread');
    }

    private function _getLikeReplyService() {
        return Wekit::load('like.srv.reply.do.PwLikeDoReply');
    } 

}
