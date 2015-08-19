<?php

/**
 * 版块列表相关
 *
 * @fileName: ForumListController.php
 * @author: yuliang<yuliang.lyl@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2014-12-16 19:08:17
 * @desc: 
 * */
Wind::import('APPS:native.controller.NativeBaseController');

class ForumListController extends NativeBaseController {
    
    protected $forums_version = array();
            
    public function beforeAction($handlerAdapter) {
        parent::beforeAction($handlerAdapter);
        //
        $this->loginUser = new PwUserBo($this->uid);
        $this->loginUser->resetGid($this->loginUser->gid);
        //获取版块的logo图片版本号
        $configs = Wekit::C()->getValues('native');
        $this->forums_version = isset($configs['forums.version']) && $configs['forums.version'] ? $configs['forums.version'] : array();
    }


    /**
     * 所有一级版块的列表数据 
     * 
     * @access public
     * @return void
     * <pre>
     * /index.php?m=native&c=forumlist&a=run
     * </pre>
     */
    public function run(){
        $forumList = $this->_getForumService()->getForumList();
        $default_forumid = Wekit::C()->getConfigByName('native','forum.fid.default');
        $data = array(
            'forum_list'=>array_values($forumList),
            'fid_default'=>$default_forumid['value'],
        );
        $this->setOutput($data,'data');
        $this->showMessage('success');
    }

    /**
     * 频道首页(分类+一级版块)
     * 
     * @access public
     * @return void
     * @example
     * <pre>
     * response: /index.php?m=native&c=forumlist&a=categorylist&_json=1 <br>
     * post: securityKey
     * </pre>
     */
    public function categoryListAction(){
        //
        $_fids = array();
        $join_forum = $this->loginUser->info['join_forum'];
        $join_forum && $_fids = self::splitStringToArray($join_forum);
        $default_forumid = Wekit::C()->getConfigByName('native','forum.fid.default');
        $forum_open = Wekit::C()->getConfigByName('native','forum.open');
        //
        $myFllowForumList = $this->_getForumService()->fetchForum( array_intersect($_fids,$this->_getForumService()->fids) );
        $categoryList = $this->_getForumService()->getCategoryList();
        //
        foreach($categoryList as $k=>$v){
            $categoryList[$k]['forums'] = $this->forumsForClass($v['fid']);
            $categoryList[$k]['logo_version'] = isset($this->forums_version[$v['fid']]) ? intval($this->forums_version[$v['fid']]) : 0;
        }

        //
        $data = array(
            'fid_default'=>$default_forumid['value'],
            'myFllowForumList'=>$myFllowForumList,
            'categoryList'=>$categoryList,
            'forum_open'=>$forum_open['value'],
        );

        $this->setOutput($data,'data');
        $this->showMessage('success');
    }

    /**
     * 根据分类获得频道信息 
     * 
     * @access public
     * @return void
     * @example
     * <pre>
     * request: /index.php?m=native&c=forumlist&a=forumsForClass&fup=分类id
     * </pre>
     */
    public function forumsForClassAction(){
        $fup = (int)$this->getInput('fup','get');
        $forumList = $this->forumsForClass($fup) ;

        $this->setOutput($forumList,'data');
        $this->showMessage('success');
    }

    private function forumsForClass($fup){
        $_fids = array();
        $join_forum = $this->loginUser->info['join_forum'];
        $join_forum && $_fids = self::splitStringToArray($join_forum);
        //
        $forumList = $this->_getForumService()->getForumList();
        $forumListTemp = array();
        foreach($forumList as $k=>$v){
            if( (int)$v['fup']!=$fup ){
                unset($forumList[$k]);
            }else{
                $forumList[$k]['isjoin'] = in_array( $v['fid'],$_fids )!==false?true:false;
                $forumList[$k]['logo_version'] = isset($this->forums_version[$v['fid']]) ? intval($this->forums_version[$v['fid']]) : 0;
                $forumListTemp[] = $forumList[$k];
            }
        }
        return $forumListTemp;
    }


    private function _getForumService(){                                                                                           
        return Wekit::load('native.srv.PwForumService');
    }
   
    /**
     * 格式化数据  把字符串"1,版块1,2,版块2"格式化为数组
     *
     * @param string $string
     * @return array
     */
    protected static function splitStringToArray($string) {                                                                                                     
        $a = explode(',', $string);
        $l = count($a);
        $l % 2 == 1 && $l--;
        $r = array();
        for ($i = 0; $i < $l; $i+=2) {
            $r[$a[$i]] = $a[$i];
        }
        return $r;
    }


}
