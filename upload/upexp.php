<?php
if(!isset($_GET['action'])){//说明页面
?>
<!doctype html>
<html>
<head>
<title>9.0.1移动版表情包升级</title>
<meta charset="utf8" />
<link rel="stylesheet" href="res/css/install.css" />
</head>
<body>
<div class="wrap">
    <div class="header">
        <h1 class="logo">logo</h1>
        <div class="icon_update">升级向导</div>
        <div class="version">9.0.1移动版表情包升级</div>
    </div>
    
    <div class="main cc">
        <pre class="pact" readonly="readonly">
        升级程序说明：
        1)	运行环境需求：php版本 >php 5.3.x  Mysql版本>5 
        2)	支持升级版本：已经升级过9.0.1移动版的用户（如果这是您第一次升级移动版请不要执行此脚本，
                直接执行up9xto91.php升级脚本即可）；
        3)	升级后会增加一套表情包同时支持PC端与APP、对部分功能进行完善及bug的修复；
        4)      新增加的表情包文件在根目录\res\images\emotion\wangwang中。
                如果您之前有添加过自定义表情包，并且跟wangwang目录同名，那么请不要直接
                将升级包进行覆盖并执行升级脚本。请先将升级包中的对应的表情包文件夹wangwang重命名再覆盖，
                然后登陆到管理后台，在【全局】->【表情管理】中进行手动安装，并参考配置文件,
                根目录\face_configure.txt对表情进行命名。
        升级步骤：
        1)	请提前备份您的站点文件，网站根目录下所有文件；
        2)	请提前备份您网站所有的数据库文件；
        3)	下载移动版插件，解压，并上传至站点根目录；
        4)	在上传移动版插件后请不要修改”根目录\src\applications\native”这个目录的名字；
        5)	请确保“根目录/conf/Database.php”文件存在且数据库连接配置信息正确；
        7)	将upexp.php文件拷贝到根目录下；
        8)	运行“http://yourwebsite/upexp.php”执行表情包升级。
        风险说明：
        因phpwind为开源程序，升级程序是在基础程序上做的更新优化，对基础程序作过二次开发的，可能部分文件、数据库会出现冲突覆盖，可能会导致程序无法正常运行，强烈建议自行手动升级。
        </pre>
    </div>
    
    <div class="bottom tac">
        <a href="upexp.php?action=dorun" class="btn">同 意</a>
    </div>
</body>
</html>


<?php
}else if($_GET['action']=="dorun"){//用户提交基本信息，执行升级过程
//    header("Content-type: text/html; charset=utf-8");
//    echo "脚本开始执行...<br>";
    ignore_user_abort(true);
    set_time_limit(0);
    error_reporting(0);
    ini_set( 'display_errors', 'Off' );
//    error_reporting(E_ALL);
//    ini_set( 'display_errors', 'On' );
    //检查lock文件是否存在
//    echo "检查lock文件是否存在...<br>";
    if (file_exists("./data/upexp.lock")) {
	showError('升级程序已被锁定, 如需重新运行，请先删除./data/upexp.lock');
    }
    


    $err_msg = '';
//    echo "加载本地数据库配置...<br>";
    $db_conf = include_once './conf/database.php';
    $username = $db_conf['user'];
    $password = $db_conf['pwd'];
    $charset = $db_conf['charset'];
    $dbpre = $db_conf['tableprefix'];
    $engine = $db_conf['engine'];
    $dsn = explode(";", $db_conf['dsn']);
//    var_dump($dsn);
    $host = trim(substr($dsn[0],strpos($dsn[0], "=")+1));
    $dbname = trim(substr($dsn[1],strpos($dsn[1], "=")+1));
    $port = trim(substr($dsn[2],strpos($dsn[2], "=")+1));
//    var_dump($host,$dbname,$port);exit;
    
//    echo "连接数据库...<br>";
    $con = mysql_connect($host.":".$port,$username,$password) or showError('Could not connect: ' . mysql_error());
    mysql_select_db($dbname, $con) or showError('Can\'t use foo : ' . mysql_error());
//    echo "设置查询字符集...<br>";
    $result = mysql_query("SET character_set_connection= 'utf8', character_set_results= 'utf8', character_set_client=BINARY, sql_mode=''") or die("Invalid query: " . mysql_error());
    $result = mysql_query("SET NAMES 'utf8'") or die("Invalid query: " . mysql_error());
    //如果是二次运行，本次导入表情包前先删除旧数据
    $sql = "DELETE FROM `{$dbpre}common_emotion_category` WHERE `emotion_folder`='wangwang'";
    mysql_query($sql) or showError("Invalid query: " . mysql_error());
    $sql = "DELETE FROM `{$dbpre}common_emotion` WHERE `emotion_folder`='wangwang'";
    mysql_query($sql) or showError("Invalid query: " . mysql_error());
    //添加旺旺表情包分类
    $sql = "INSERT INTO {$dbpre}common_emotion_category (`category_name`, `emotion_folder`, `emotion_apps`, `orderid`, `isopen`) VALUES ('旺旺', 'wangwang', 'bbs', 0, 1);";
    mysql_query($sql) or showError("Invalid query: " . mysql_error());
    //获取旺旺表情包分类id
    $sql = "SELECT `category_id` FROM `".$dbpre."common_emotion_category` WHERE `emotion_folder`='wangwang'";
    $result = mysql_query($sql) or showError("Invalid query: " . mysql_error());
    $row = mysql_fetch_array($result,MYSQL_ASSOC);
    $category_id = $row['category_id'];
    $wangwang = array(
        array('name'=>'闭嘴','file'=>'face_01.gif'),
        array('name'=>'握手','file'=>'face_02.gif'),
        array('name'=>'晕死','file'=>'face_03.gif'),
        array('name'=>'口水','file'=>'face_04.gif'),
        array('name'=>'神马','file'=>'face_05.gif'),
        array('name'=>'猪头','file'=>'face_06.gif'),
        array('name'=>'明白','file'=>'face_07.gif'),
        array('name'=>'心动','file'=>'face_08.gif'),
        array('name'=>'汗死','file'=>'face_09.gif'),
        array('name'=>'真弱','file'=>'face_10.gif'),
        array('name'=>'调皮','file'=>'face_11.gif'),
        array('name'=>'战斗','file'=>'face_12.gif'),
        array('name'=>'呕吐','file'=>'face_13.gif'),
        array('name'=>'流泪','file'=>'face_14.gif'),
        array('name'=>'亲亲','file'=>'face_15.gif'),
        array('name'=>'伤心','file'=>'face_16.gif'),
        array('name'=>'酷毙','file'=>'face_17.gif'),
        array('name'=>'不屑','file'=>'face_18.gif'),
        array('name'=>'得意','file'=>'face_19.gif'),
        array('name'=>'怕怕','file'=>'face_20.gif'),
        array('name'=>'扁你','file'=>'face_21.gif'),
        array('name'=>'鼓掌','file'=>'face_22.gif'),
        array('name'=>'嘿嘿','file'=>'face_23.gif'),
        array('name'=>'发怒','file'=>'face_24.gif'),
        array('name'=>'心碎','file'=>'face_25.gif'),
        array('name'=>'好强','file'=>'face_26.gif'),
    );
    $values = array();
    foreach($wangwang as $v){//插入表情
        $values[] = "({$category_id},'{$v['name']}','wangwang','{$v['file']}',0,1)";
    }
    $values = implode(",", $values);
    $sql = "INSERT INTO `{$dbpre}common_emotion` (`category_id`,`emotion_name`,`emotion_folder`,`emotion_icon`,`vieworder`,`isused`) VALUES {$values};";
    mysql_query($sql) or showError("Invalid query: " . mysql_error());
    //删除表情包缓存数据
    $sql = "DELETE FROM `{$dbpre}cache` WHERE `cache_key`='all_emotions'";
    mysql_query($sql) or showError("Invalid query: " . mysql_error());
    mysql_close($con);
    //生成lock文件
//    echo "生成lock文件...<br>";
    file_put_contents("./data/upexp.lock", "pw9.0.1移动版");
    $success_text = "恭喜！您的站点表情包升级完毕！
                    感谢您使用phpwind，在使用或者升级过程中有任何问题，请反馈至phpwind官方论坛<a href='http://www.phpwind.net' target='_blank'>（http://www.phpwind.net）</a> 
                    <a href='$http_server' target='_blank'>返回站点首页</a>";
//    echo "升级结束...<br>";
    showMsg($success_text);
}else{
    echo "args error";
}


/*错误信息页面*/
function showError($msg, $url = false) {
	global $action,$token;
	if (!$url) {
		if ($action) {
			$url = '<a href="' . $_SERVER['SCRIPT_NAME']. '">返回重新开始</a>';
		} else {
			$url = '<a href="javascript:window.history.go(-1);">返回重新开始</a>';
		}
	} else {
		$url = '';
	}
	echo <<<EOT
<!doctype html>
<html>
<head>
<title>phpwind 9.0.1移动版 表情包升级程序</title>
<meta charset="utf8" />
<link rel="stylesheet" href="res/css/install.css" />
</head>
<body>
	<div class="wrap">
		<div class="header">
			<h1 class="logo">logo</h1>
			<div class="icon_update">升级向导</div>
			<div class="version">phpwind 9.0.1移动版 表情包升级程序</div>
		</div>

		<div class="success_tip cc error_tip">
			<div class="mb10 f14">$msg</div>
			<div class="error_return">{$url}</div>
		</div>
	</div>
	<div class="footer">
		&copy; 2003-2103 <a href="http://www.phpwind.com" target="_blank">phpwind.com</a>（阿里巴巴集团旗下品牌）
	</div>
</body>
</html>
EOT;
	exit;
}


/*信息页面*/
function showMsg($msg) {
	global $action,$token;
	
	echo <<<EOT
<!doctype html>
<html>
<head>
<title>phpwind 9.0.1移动版 表情包升级程序</title>
<meta charset="utf8" />
<link rel="stylesheet" href="res/css/install.css" />
</head>
<body>
	<div class="wrap">
		<div class="header">
			<h1 class="logo">logo</h1>
			<div class="icon_update">升级向导</div>
			<div class="version">phpwind 9.0.1移动版 表情包升级程序</div>
		</div>

		<div class="success_tip cc error_tip">
			<div class="mb10 f14">$msg</div>
		</div>
	</div>
	<div class="footer">
		&copy; 2003-2103 <a href="http://www.phpwind.com" target="_blank">phpwind.com</a>（阿里巴巴集团旗下品牌）
	</div>
</body>
</html>
EOT;
	exit;
}

?>

