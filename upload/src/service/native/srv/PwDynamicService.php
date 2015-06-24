<?php


class PwDynamicService {
    /**
     * 获取列表页展示的帖子数据
     */
    public function fetchThreadsList($tids,$uid=0,$result_type='ASSOC'){
        if(!$tids) return array();
        Wind::import('SRV:like.PwLikeContent');
        $threads = Wekit::loadDao('native.dao.PwNativeThreadsDao')->fetchThreads($tids);
        $threads_place = Wekit::loadDao('native.dao.PwThreadsPlaceDao')->fetchByTids($tids);
        $threads_content = Wekit::loadDao('forum.dao.PwThreadsContentDao')->fetchThread($tids);
        $PwThreadService = Wekit::load('forum.srv.PwThreadService');
        $PwNativeThreadService = Wekit::load('native.PwNativeThread');
        $threadLikeData = Wekit::load('like.srv.reply.do.PwLikeDoReply')->getAllLikeUserids(PwLikeContent::THREAD, $tids );
        foreach($threadLikeData as $k => $v){
            if(!in_array($uid, $v)) unset($threadLikeData[$k]);
        }
        $tag_names_str = '';        
        foreach($threads as $k=>$v){
            $content = isset($threads_content[$k]['content']) ? $threads_content[$k]['content'] : '';
            $threads[$k]['tags'] = $threads[$k]['tags_origin'] = isset($threads_content[$k]['tags']) ? $threads_content[$k]['tags'] : '';
            $threads[$k]['from_type'] = isset($threads_place[$k]['from_type']) ? $threads_place[$k]['from_type'] : 0;
            $threads[$k]['created_address'] = isset($threads_place[$k]['created_address']) ? $threads_place[$k]['created_address'] : '';
            $threads[$k]['area_code'] = isset($threads_place[$k]['area_code']) ? $threads_place[$k]['area_code'] : '';
            $threads[$k]['tags'] && $tag_names_str.=','.$threads[$k]['tags'];
            $threads[$k]['avatar'] = Pw::getAvatar($v['created_userid'],'small');
            $threads[$k]['created_time'] = Pw::time2str($v['created_time'],'auto');
            $threads[$k]['lastpost_time'] = Pw::time2str($v['lastpost_time'],'auto');
            preg_match("/\[mp3.*?\].*?\[\/mp3\]/i",$content, $matches);
            $threads[$k]['have_mp3'] = $matches ? true : false;
            preg_match("/\[flash.*?\].*?\[\/flash\]/i",$content, $matches);
            $threads[$k]['have_flash'] = $matches ? true : false;
            $format_content = Pw::formatContent($content);//格式化移动端帖子内容去除ubb标签、分享链接内容、推广链接内容
            $threads[$k]['isliked'] = isset($threadLikeData[$k]) ? true :false;
            $imgs = array_shift($PwNativeThreadService->getThreadAttach(array($k),array(0)));
            ksort($imgs);
            $imgs = array_slice($imgs,0, 9);
            foreach($imgs as $imgs_k=>$imgs_v){
                $imgs[$imgs_k]['realpath'] = str_replace("/thumb/mini","",$imgs_v['path']);
            }
            $threads[$k]['content'] = array(
//                                            'text'=>  str_replace(array('[视频]','[音乐]','[附件]'),array('','',''),trim($PwThreadService->displayContent($content,1,array(),70),'.')),//帖子内容文本
//                                            'text'=> str_replace(array('[视频]','[音乐]','[附件]'),array('','',''),Pw::substrs($format_content['content'],70,0,false)),//帖子内容文本截字
                                            'text'=> preg_replace('/(\[)[^\]]*$/i','',Pw::substrs($format_content['content'],70,0,false)),//帖子内容文本截字，修正被截断的标签
                                            'imgs'=>$imgs,//获取内容图片
                                            'share'=>$format_content['share'],//帖子分享链接中的内容(待定)
                                            'origin_content'=>$content,
                                            'format_content'=>$format_content,
                                            );
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
        $sort_num = 0;
        if($result_type=='ASSOC'){//按照tids的顺序重新排序结果集，tid作为索引
            foreach($tids as $v){
                if(isset($threads[$v])){ 
                    $threads_tmp[$v] = $threads[$v];
                    $threads_tmp[$v]['sort'] = $sort_num++;
                }
            }
        }else{//tid会有重复的情况，置顶帖在列表中显示2次，数字顺序索引
            foreach($tids as $v){
                if(isset($threads[$v])){
                    $threads[$v]['sort'] = $sort_num++;
                    $threads_tmp[] = $threads[$v];   
                }
            }
        }
//        print_r($threads_tmp);exit;
        return $threads_tmp;
    }
    
    public function weightCron() {
        $threadsWeightDao = Wekit::loadDao('native.dao.PwThreadsWeightDao');
        //执行权重计算逻辑
        $current_time = time();
        $stop_time = $current_time - 604800; //获取7天前的数据进行计算
        $threadsWeightDao->deleteAutoData(); //删除自动生成热帖数据
        $threadsWeightDao->deleteUserData($stop_time); //删除推荐的过期热帖数据
//            $stop_time = $current_time-1604800;//获取更早前的数据
//            echo $stop_time;exit;
        $nativeThreadsDao = Wekit::loadDao('native.dao.PwNativeThreadsDao');
        //从论坛帖子列表获取指定时间内的帖子条数
        $res = $nativeThreadsDao->getCountByTime($stop_time);
        $threads_count = intval($res['count']);
        $threads_count = $threads_count > 1000 ? 1000 : $threads_count; //权重计算默认只取1000条
        $num = 50; //一次处理的记录数
        $pages = ceil($threads_count / $num);
        //计算热帖的自然权重值，并将结果插入权重表
        for ($i = 1; $i <= $pages; $i++) {
//                $starttime_test = time();
            $page = $i;
            $start = ($page - 1) * $num; //开始位置偏移
            $res = $nativeThreadsDao->fetchThreadsData($stop_time, $start, $num);
            $weight_values = array();
            if ($res) {
                foreach ($res as $k => $v) {
                    $weight = $v['like_count'] * 2 +
                            $v['replies'] * 4 +
                            $v['reply_like_count'] +
                            floor(($current_time - $v['lastpost_time']) / 86400) * -4 +
                            floor(($current_time - $v['created_time']) / 86400) * -20;
//                        $res[$k]['weight'] = $weight;
                    $weight_values[] = "({$v['tid']},$weight,$current_time,1)";
                }
                $weight_values = implode(',', $weight_values);
                //将权重计算结果插入权重表,表中已有数据不再重复插入
                $threadsWeightDao->insertValues($weight_values);
            }
        }
        //获取权重表中管理员设置的热帖数量
        $threads_count = $threadsWeightDao->getUserDataCount();
        $threads_count = isset($threads_count['count']) ? intval($threads_count['count']) : 0;
        $pages = ceil($threads_count / $num);
        //将管理员设置的热帖进行自然权重计算并更新数据
        for ($i = 1; $i <= $pages; $i++) {
//                $starttime_test = time();
            $page = $i;
            $start = ($page - 1) * $num; //开始位置偏移
            $res = $threadsWeightDao->fetchUserThreadsData($start, $num); //获取管理员设置的热帖数据计算权重
            $weight_values = array();
            if ($res) {
                foreach ($res as $k => $v) {
                    $weight = $v['like_count'] * 2 +
                            $v['replies'] * 4 +
                            $v['reply_like_count'] +
                            floor(($current_time - $v['lastpost_time']) / 86400) * -4 +
                            floor(($current_time - $v['create_time']) / 86400) * -20;
                    $weight_values[] = "({$v['tid']},$weight,{$v['create_time']},{$v['create_userid']},'{$v['create_username']}',{$v['isenable']})";
                }
                $weight_values = implode(',', $weight_values);
                //将权重计算结果覆盖插入权重表,表中已有数据将被覆盖
                $threadsWeightDao->replaceValues($weight_values);
            }
        }
        //对推荐不到2小时的数据继续置顶
        $max_weight = $threadsWeightDao->getMaxWeight();
        $max_weight = isset($max_weight['weight']) ? intval($max_weight['weight']) + 1 : 1;
        $threadsWeightDao->batchUpdateUserWeight($current_time - 7200, $max_weight);
        //只保留100条非用户推荐的自然计算数据
        $res = $threadsWeightDao->getWeightByPos(99);
        if ($res) {
            $weight = $res['weight'];
            $threadsWeightDao->deleteByWeight($weight);
        }
//        echo "SCRIPT EXCUTE FINISHED";
    }

}