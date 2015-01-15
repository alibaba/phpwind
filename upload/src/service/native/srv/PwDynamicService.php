<?php


class PwDynamicService {
    /**
     * 获取列表页展示的帖子数据
     */
    public function fetchThreadsList($tids,$result_type='ASSOC'){
        $threads = Wekit::loadDao('native.dao.PwNativeThreadsDao')->fetchThreads($tids);
        $threads_place = Wekit::loadDao('native.dao.PwThreadsPlaceDao')->fetchByTids($tids);
        $threads_content = Wekit::loadDao('forum.dao.PwThreadsContentDao')->fetchThread($tids);
        $tag_names_str = '';
        foreach($threads as $k=>$v){
            $threads[$k]['content'] = isset($threads_content[$k]['content']) ? $threads_content[$k]['content'] : '';
            $threads[$k]['tags'] = isset($threads_content[$k]['tags']) ? $threads_content[$k]['tags'] : '';
            $threads[$k]['from_type'] = isset($threads_place[$k]['from_type']) ? $threads_place[$k]['from_type'] : 0;
            $threads[$k]['created_address'] = isset($threads_place[$k]['created_address']) ? $threads_place[$k]['created_address'] : '';
            $threads[$k]['tags'] && $tag_names_str.=','.$threads[$k]['tags'];
        }
        $tag_names_arr = array_unique(explode(',', trim($tag_names_str,',')));
        $tag_names = Wekit::loadDao('tag.dao.PwTagDao')->getTagsByNames($tag_names_arr);
//        var_dump($tag_names);exit;
        foreach($threads as $k=>$v){
            if($v['tags']){
                $tag_arr = explode(',', $v['tags']);
                $tag_tmp = array();
                foreach($tag_arr as $name){
                    array_key_exists($name, $tag_names) && $tag_tmp[] = array('tag_id'=>$tag_names[$name]['tag_id'],'tag_name'=>$name);
                }
                $threads[$k]['tags'] = $tag_tmp;
            }
        }
        $threads_tmp = array();
        if($result_type=='ASSOC'){//按照tids的顺序重新排序结果集
            foreach($tids as $v){
                isset($threads[$v]) && $threads_tmp[$v] = $threads[$v];
            }
        }else{
            foreach($tids as $v){
                isset($threads[$v]) && $threads_tmp[] = $threads[$v];
            }
        }
        
        return $threads_tmp;
    }
}