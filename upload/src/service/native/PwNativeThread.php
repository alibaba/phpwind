<?php
/**
 * 移动版贴子列表
 *
 * @fileName: PwNativeThread.php
 * @author: dongyong<dongyong.ydy@alibaba-inc.com>
 * @license: http://www.phpwind.com
 * @version: $Id
 * @lastchange: 2015-01-07 14:50:08
 * @desc: 
 **/

Wind::import('SRV:forum.PwThread');
Wind::import('SRV:forum.srv.PwThreadList');
Wind::import('SRV:native.srv.PwNativeThreadDataSource');

class PwNativeThread {

    private $threadCount = 0;

    /**
     * 关于某一个用户发布的贴子 (审核通过的)
     *
     * @param int $uid 
     * @param int $page 
     * @param string $type 
     * @access public
     * @return void
     */
    public function getThreadListByUid($uid, $page, $type){
        $page = $page ? $page : 1;
        $perpage = 20; 
        //
        $dataSource = new PwNativeThreadDataSource($uid, $this->_getForumService()->fids, $type);
        
        $threadList = new PwThreadList();
        $threadList->setPage($page)->setPerpage($perpage);

        $threadList->execute($dataSource);
        $threads = $threadList->getList();
        $this->threadPageCount = $threadList->maxPage;

        //
        $tids = array();
        if( $threads ){
            foreach ($threads as $thread) {
                $tids[] = $thread['tid'];
            }
        }
        return $tids;
    }

    /**
     * 关于某一个用户发布的贴子 (审核通过的); 总页数 
     * 
     * @access public
     * @return void
     */
    public function getThreadPageCount(){
       return $this->threadPageCount; 
    }


    /**
     * 整理合并贴子内容 
     * 
     * @param mixed $tids 
     * @param mixed $pids 
     * @access public
     * @return void
     */
    public function gather($threadList, $attList){
        if( !is_array($threadList) || !is_array($attList) ) return array();
        foreach($threadList as $key=>$thread){
            $pic_key = $thread['tid'].'_0';
            $threadList[$key]['pic'] = isset($attList[$pic_key])?$attList[$pic_key]:array();
            //列表数据，过滤掉图片及附件url等标签
            $_shareData = Pw::formatContent( $threadList[$key]['content'] );
            $threadList[$key]['share'] = $_shareData['share'];
            $threadList[$key]['content'] = preg_replace('/(\[)[^\]]*$/i','',mb_substr($_shareData['content'],0,90));//截字并修正表情标签
            //
            $threadList[$key]['created_user_avatar'] = Pw::getAvatar($threadList[$key]['created_userid'],'');
            //
            $threadList[$key]['lastpost_time'] = Pw::time2str($threadList[$key]['lastpost_time'], 'auto');
        }
        krsort($threadList, SORT_NUMERIC);
        return $threadList;
    }

    /**
     * 贴子的全部内容 
     * 
     * @param mixed $tids 
     * @access private
     * @return void
     */
    public function getThreadContent($tids){
        return $this->_getThreadDs()->fetchThread($tids, PwThread::FETCH_ALL); 
    }

    /**
     * 取贴子的附件 
     * 
     * @param array $tids 
     * @param array $pids 附件的个数
     * @access private
     * @return void
     */
    public function getThreadAttach($tids, $pids){
        $result = array();
        $array = $this->_getThreadAttachDs()->fetchAttachByTidAndPid($tids, $pids);
        $i=0;
        foreach ($array as $key => $value) {
            //只取9个
            if( $i>9 ){break;}
            $i++;

            //只取图片
            if ($value['type'] != 'img' || ($value['special'] > 0 && $value['cost'] > 0)) continue;
            $_key = $value['tid'] . '_' . $value['pid'];
            $value['path'] = Pw::getPath($value['path'], $value['ifthumb']);
            //$result[$_key][$value['aid']] = $value;
            //只用图片地址
            //$result[$_key][$value['aid']]['path'] = $value['path'];
            $result[$_key][]['path'] = $value['path'];
        }
        return $result;
    }


    private function _getForumService(){
        return Wekit::load('native.srv.PwForumService');
    }  

    private function _getThreadDs(){
        return Wekit::load('forum.PwThread');
    }

    private function _getThreadAttachDs(){
        return Wekit::load('attach.PwThreadAttach');
    }

}
