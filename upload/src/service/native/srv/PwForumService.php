<?php
/**
 * 移动端版块相关
 * @fileName: PwForumService.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2015-01-05 17:10:20
 * @desc: 
 **/
class PwForumService {

    /**
     * 从配置中获得移动端显示版块fids
     */ 
    public $fids=array();
    public $life_fid = 0;

    public function __construct(){
        $config = Wekit::C()->getConfigByName('native','forum.life_fid');
        $this->life_fid = isset($config['value']) ? $config['value'] : 0;
        $this->fids = $this->_getForumFids();
    }

    /**
     * 获得分类列表 
     * 
     * @access public
     * @return void
     */
    public function getCategoryList(){
        $categoryList = $this->_getForumDs()->getCommonForumList(PwForum::FETCH_MAIN | PwForum::FETCH_STATISTICS);
        foreach($categoryList as $k=>$v){
            if($v['type']!='category' || $v['isshow']==0){
                unset($categoryList[$k]);
                continue;
            }
            if($this->life_fid && ($v['fid']==$this->life_fid || $v['parentid']==$this->life_fid)){
                unset($categoryList[$k]);
                continue;
            }
            $categoryList[$k]['name'] = strip_tags($v['name']);
        }
        return $this->_filterForumData($categoryList);
    }
    
    /**
     * 获所有一级版块 
     * 
     * @access public
     * @return void
     */
    public function getForumList(){
        $forumList = $this->_getForumDs()->getCommonForumList(PwForum::FETCH_MAIN | PwForum::FETCH_STATISTICS);
        foreach($forumList as $k=>$v){
            if($v['type']!='forum' || $v['isshow']==0){
                unset($forumList[$k]);
                continue;
            }
            if($this->life_fid && $v['parentid']==$this->life_fid){
                unset($forumList[$k]);
                continue;
            }
            $forumList[$k]['name'] = strip_tags($v['name']);
        }
        return $this->_filterForumData($forumList);
    }


    /**
     * 获得移动端显示版块具体信息
     * 
     * @access public
     * @return void
     */
    public function fetchForum($fids){
        $forumList = $this->_getForumDs()->fetchForum($fids,PwForum::FETCH_MAIN | PwForum::FETCH_STATISTICS);
        foreach($forumList as $key=>$value){
            $forumList[$key]['name'] = strip_tags($value['name']);
        }
        return $this->_filterForumData($forumList);
    }

    /**
     * 移动端显示的模块fids 
     * 
     * @access public
     * @return void
     */
    private function _getForumFids(){
        //所有的版块都打开
        $_firstFormList = $this->getForumList();
        //
        $result = array();
        foreach($_firstFormList as $v){
            $result[] = $v['fid'];
        }
        return $result;

        /*
        $config = Wekit::C()->getConfigByName('native','forum.fids');
        $fids_array = unserialize($config['value']);
        return is_array($fids_array) ? array_keys($fids_array) : array();
         */
    }

    /**
     * 过滤版块数据，不需要的字段过滤掉 
     * 
     * @param mixed $data 
     * @access private
     * @return void
     */
    private function _filterForumData($forumList){
        if( $forumList  ){
            $vieworder = array();
            foreach ($forumList as $key=>$forum) {
                $forumList[$key] = array(
                    'fid'   =>$forum['fid'],
                    'name'  =>$forum['name'],
                    'threads'=>$forum['threads'],
                    'todayposts'=>$forum['todayposts'],
                    'article'=>$forum['article'],
                    'posts' =>$forum['posts'],
                    'icon'  =>$forum['icon']?Pw::getPath($forum['icon']):"",
                    'logo'  =>$forum['logo']?Pw::getPath($forum['logo']):"",
                    'fup'   =>$forum['fup'],
                    'fupname'=>$forum['fupname'],
                    'vieworder'=>intval($forum['vieworder']),
                    'lastpost_time'=>Pw::time2str($forum['lastpost_time'], 'auto'),
                );  
                $vieworder[] = intval($forum['vieworder']);
            }   
        }
        array_multisort($vieworder,SORT_ASC,$forumList);
        return $forumList; 
    }

    private function _getForumDs(){
        return Wekit::load('forum.PwForum'); 
    }

}
