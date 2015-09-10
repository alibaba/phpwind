<?php
error_reporting(0);
ini_set( 'display_errors', 'Off' );
//pw移动版升级包版本列表
$pw_version = array(
    1=>array(//完整版升级（不包含表情包）
            'version_num'=>'1',
            'title'=>'phpwind 9.0.1 to 9.0.1移动版',
            'description'=>'升级程序说明：
        1)	运行环境需求：php版本 >php 5.3.x  Mysql版本>5 
        2)	支持升级版本：9.0.1（20141223版）；
        3)	升级后会增加对移动端的支持、对部分功能进行完善及bug的修复；
        4)	更新内容详见补丁包文件，数据变更内容详见数据库sql文件。
        升级步骤：
        1)	关闭站点，后台管理-全局-站点设置-站点状态设置页面进行设置；
        2)	请提前备份您的站点文件，网站根目录下所有文件；
        3)	请提前备份您网站所有的数据库文件；
        4)	下载移动版插件，解压，并上传至站点根目录；
        5)	在上传移动版插件后请不要修改”根目录\src\applications\native”这个目录的名字；
        6)	请确保“根目录/conf/Database.php”文件存在且数据库连接配置信息正确；
        7)	运行“http://yourwebsite/up9xto91.php”执行升级。
        风险说明：
        因phpwind为开源程序，升级程序是在基础程序上做的更新优化，对基础程序作过二次开发的，可能部分文件、数据库会出现冲突覆盖，可能会导致程序无法正常运行，强烈建议自行手动升级。',
            'fun'=>'update01',
            'lockfile'=>'./data/up9xto91.lock',
        ),
    2=>array(//完整版补充升级，增加一个库（不包含表情包）
            'version_num'=>'2',
            'title'=>'phpwind 9.0.1 to 9.0.1移动版',
            'description'=>'升级程序说明：
        1)	运行环境需求：php版本 >php 5.3.x  Mysql版本>5 
        2)	支持升级版本：9.0.1（20141223版）；
        3)	升级后会增加对移动端的支持、对部分功能进行完善及bug的修复；
        4)	更新内容详见补丁包文件，数据变更内容详见数据库sql文件。
        升级步骤：
        1)	关闭站点，后台管理-全局-站点设置-站点状态设置页面进行设置；
        2)	请提前备份您的站点文件，网站根目录下所有文件；
        3)	请提前备份您网站所有的数据库文件；
        4)	下载移动版插件，解压，并上传至站点根目录；
        5)	在上传移动版插件后请不要修改”根目录\src\applications\native”这个目录的名字；
        6)	请确保“根目录/conf/Database.php”文件存在且数据库连接配置信息正确；
        7)	运行“http://yourwebsite/up9xto91.php”执行升级。
        风险说明：
        因phpwind为开源程序，升级程序是在基础程序上做的更新优化，对基础程序作过二次开发的，可能部分文件、数据库会出现冲突覆盖，可能会导致程序无法正常运行，强烈建议自行手动升级。',
            'fun'=>'update02',
            'lockfile'=>'./data/up9xto91_2.lock',
        ),
    3=>array(//表情包升级
            'version_num'=>'3',
            'title'=>'phpwind 9.0.1 to 9.0.1移动版',
            'description'=>'升级程序说明：
        1)	运行环境需求：php版本 >php 5.3.x  Mysql版本>5 
        2)	支持升级版本：9.0.1（20141223版）；
        3)	升级后会增加对移动端的支持、对部分功能进行完善及bug的修复；
        4)	更新内容详见补丁包文件，数据变更内容详见数据库sql文件。
        升级步骤：
        1)	关闭站点，后台管理-全局-站点设置-站点状态设置页面进行设置；
        2)	请提前备份您的站点文件，网站根目录下所有文件；
        3)	请提前备份您网站所有的数据库文件；
        4)	下载移动版插件，解压，并上传至站点根目录；
        5)	在上传移动版插件后请不要修改”根目录\src\applications\native”这个目录的名字；
        6)	请确保“根目录/conf/Database.php”文件存在且数据库连接配置信息正确；
        7)	运行“http://yourwebsite/up9xto91.php”执行升级。
        风险说明：
        因phpwind为开源程序，升级程序是在基础程序上做的更新优化，对基础程序作过二次开发的，可能部分文件、数据库会出现冲突覆盖，可能会导致程序无法正常运行，强烈建议自行手动升级。',
            'fun'=>'update03',
            'lockfile'=>'./data/up9xto91_3.lock',
    ),

);

$http_server = "http://".((isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:''));

if(!isset($_GET['action'])){//升级前的版本检测
    /* 版本检测 */
    $update_version = choose_version();
    if($update_version){//需要执行脚本升级，展示说明页
?>        
<!doctype html>
<html>
<head>
<title>update <?php echo $pw_version[$update_version]['title']?></title>
<meta charset="utf8" />
<link rel="stylesheet" href="res/css/install.css" />
</head>
<body>
<div class="wrap">
    <div class="header">
        <h1 class="logo">logo</h1>
        <div class="icon_update">升级向导</div>
        <div class="version"><?php echo $pw_version[$update_version]['title']?></div>
    </div>
    
    <div class="main cc">
        <pre class="pact" readonly="readonly">
        <?php echo $pw_version[$update_version]['description']?>

        <!--
        phpwind9.x的环境准备，请确认：
        1、确定系统环境
        PHP版本 	> 5.3.x
        PDO_Mysql 安装扩展
        Mysql版本（client） 	>5.x.x
        附件上传 	>2M
        如果确认如上条件都成立，则可以准备开始升级，升级步骤如下：
        1、将phpwind9.x即Nextwind安装包解压，并将upload目录下的文件上传至安装目录。
        （注意，不能直接覆盖原来8.7的环境。如果是虚拟主机，建议先将原87环境除attachment目录外，移动到backup下，这样即使出现问题后可以通过移动目录恢复87的环境。） 
        2、文件转移： 
        2.1、头像图片转移：将原87目录下attachment/upload文件夹，拷贝到phpwind9.x的attachment目录下;（注意如果在第一步已经完成了attachment合并，则此步可忽略。）
        2.2、表情图片转移：将原87目录下images/post/smile/下的所有目录拷贝到phpwind9.x的res/images/emotion/下;
        2.3、勋章图片转移：将原87目录下images/medal/下的所有目录拷贝到phpwind9.x的res/images/medal/下;
        注：如果下载的phpwind9.x包是含有www目录的，则将attachment包括在内的以上目录移到www目录下的对应目录中，比如res/images/emotion/则为www/res/images/emotion/
        3、将升级包up87to90.php文件上传到网站根目录。（如果下载的nextwind包是含有www目录的，则需要放到www目录下）;
        4、确定以下目录的可写权限：
        <font color="red">
        attachment/
        conf/database.php
        conf/founder.php
        conf/windidconfig.php
        data/
        data/cache/
        data/compile/
        data/design/
        data/log/
        data/tmp/
        html/
        src/extensions/
        themes/
        themes/extres/
        themes/forum/
        themes/portal/
        themes/site/
        themes/space/
        </font>
        5、执行升级程序访问站点的升级程序xxx.com/up87to90.php
        6、填写完整需要的数据库信息，及创始人信息
        7、递交之后会执行一步基本配置信息的转换
        8、转换完基本配置信息之后，会正式进入主数据升级，主数据升级页面是允许多进程升级和一键升级选择的页面，在多进程升级中，您可以一次点开多个没有依赖（每步都有说明各自所需的依赖，如果没有说明则没有）的进程。
        注：如果是分进程执行，请确保每一步都执行到位。
        特别说明：如果原87站点开启了ftp服务，那么在分进程页面中会存在单独的一条“用户头像转移”的步骤，请仔细看该步骤说明，该步骤不被包含到一键升级和分进程中，无论选择多进程升级或是一键升级都需要运行，否则用户头像将采用默认头像。
        9、升级执行完之后将会自动进入nextwind9的首页。
        注：如果需要再次升级，请删除data/setup/setup.lock文件
        -->
        </pre>
    </div>
    
    <div class="bottom tac">
        <a href="up9xto91.php?action=dorun&version=<?php echo $update_version;?>" class="btn">同 意</a>
    </div>
</body>
</html>

<?php
    }else{//不需要脚本升级，直接提示升级成功
        $success_text = "恭喜！您的站点已成功升级至phpwind 9.0.1移动最新版本！
                    感谢您使用phpwind，在使用或者升级过程中有任何问题，请反馈至phpwind官方论坛<a href='http://www.phpwind.net' target='_blank'>（http://www.phpwind.net）</a> 
                    <a href='$http_server' target='_blank'>返回站点首页</a>";
        showMsg($success_text);
    }
    //关闭数据库连接
//    mysql_close($con);
}else if($_GET['action']=="dorun" && isset($_GET['version']) && isset($pw_version[$_GET['version']])){//执行脚本升级
//    header("Content-type: text/html; charset=utf-8");
//    echo "脚本开始循环执行...<br>";
    ignore_user_abort(true);
    set_time_limit(0);
//    error_reporting(E_ALL);
//    ini_set( 'display_errors', 'On' );
    /*
    print str_pad("", 10000);
    @ob_flush();
    flush();
    sleep(5);
     */
    $cnt = count($pw_version);
    for($i=$_GET['version'];$i<=$cnt;$i++){
        $pw_version[$i]['fun']($pw_version[$i]['lockfile']);
    }
    //关闭数据库连接
//    mysql_close($con);
    //升级结束，提示升级成功
    $success_text = "恭喜！您的站点已成功升级至phpwind 9.0.1移动最新版本！
                    感谢您使用phpwind，在使用或者升级过程中有任何问题，请反馈至phpwind官方论坛<a href='http://www.phpwind.net' target='_blank'>（http://www.phpwind.net）</a> 
                    <a href='$http_server' target='_blank'>返回站点首页</a>";
//    echo "升级结束...<br>";
    showMsg($success_text);
}else{//参数错误
    echo "args error";
}


class PW_DB {

//保存类实例的静态成员变量
    private static $_instance;
    public $dbpre;
    public $charset;

//private标记的构造方法
    private function __construct() {
        $db_conf = include_once './conf/database.php';
        $username = $db_conf['user'];
        $password = $db_conf['pwd'];
        $this->charset = $db_conf['charset'];
        $this->dbpre = $db_conf['tableprefix'];
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
    }

//创建__clone方法防止对象被复制克隆
    public function __clone() {
        trigger_error('Clone is not allow!', E_USER_ERROR);
    }

//单例方法,用于访问实例的公共的静态方法
    public static function getInstance() {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    public function query($sql) {
        $result = mysql_query($sql) or die("Invalid query: " . mysql_error());
        $res = array();
        while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
            $res[] = $row;
        }
        
        return $res;
    }

}

/*错误信息页面*/
function showError($msg ,$title="phpwind9.0.1移动版", $url = false) {
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
<title>{$title} 升级程序</title>
<meta charset="utf8" />
<link rel="stylesheet" href="res/css/install.css" />
</head>
<body>
	<div class="wrap">
		<div class="header">
			<h1 class="logo">logo</h1>
			<div class="icon_update">升级向导</div>
			<div class="version">{$title}</div>
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
function showMsg($msg,$title="phpwind9.0.1移动版") {
	global $action,$token;
	
	echo <<<EOT
<!doctype html>
<html>
<head>
<title>{$title} 升级程序</title>
<meta charset="utf8" />
<link rel="stylesheet" href="res/css/install.css" />
</head>
<body>
	<div class="wrap">
		<div class="header">
			<h1 class="logo">logo</h1>
			<div class="icon_update">升级向导</div>
			<div class="version">{$title}</div>
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


/** 
 * 取得输入目录所包含的所有文件 
 * 以数组形式返回 
 * author: flynetcn 
 */  
function get_dir_files($dir){
    if (is_file($dir)) {  
        return array($dir);  
    }  
    $files = array();  
    if (is_dir($dir) && ($dir_p = opendir($dir))) {  
        $ds = DIRECTORY_SEPARATOR;  
        while (($filename = readdir($dir_p)) !== false) {  
            if ($filename=='.' || $filename=='..') { continue; }  
            $filetype = filetype($dir.$ds.$filename);  
            if ($filetype == 'dir') {  
//                $files = array_merge($files, get_dir_files($dir.$ds.$filename));  
            } elseif ($filetype == 'file') {  
//                $files[] = $dir.$ds.$filename;
                $files[] = $filename;  
            }  
        }  
        closedir($dir_p);  
    }  
    return $files;  
}


/**
 * 选择本次需要升级的版本
 */
function choose_version(){
    global $pw_version;
    $current_version = 0;
    $update_version = 0;
    //获取data下的lock文件
    $lockfiles = get_dir_files("./data");
    is_array($lockfiles) || $lockfiles = array();
    foreach($lockfiles as $k=>$v){
        if(strpos($v,".lock")===false){
            unset($lockfiles[$k]);
        }else{
            $lockfiles[$k] = "./data/$v";
        }
    }
    //升级脚本根据data下的lock文件判断当前pw版本，仅以data目录下的最近一次的lock文件为判断依据，判断最近一次lock文件的内容查看升级是否成功
    foreach($pw_version as $k=>$v){
        in_array($v['lockfile'],$lockfiles) && $current_version = $k;
    }
    $pw_db = PW_DB::getInstance();
    if(!$current_version || $current_version==1){//没检测到lock文件，没有执行过升级,或升级后删除lock文件 || 只执行过第一版的升级
        $sql = "SHOW TABLES LIKE '{$pw_db->dbpre}bbs_forum_life'";
        $pw_db->query($sql) && $current_version = 1;
        $sql = "SHOW TABLES LIKE '{$pw_db->dbpre}fresh_site'";
        $pw_db->query($sql) && $current_version = 2;
        $update_version = $current_version+1;
    }else{//存在lock文件，用户执行过升级
        isset($pw_version[++$current_version]) && $update_version = $current_version;
    }
    
    return $update_version;
}


/**
 * 移动版第一版升级
 */
function update01($lockfile){
    //检查lock文件是否存在
//    echo "检查lock文件是否存在...<br>";
    if (file_exists($lockfile)) {
	showError("升级程序已被锁定, 如需重新运行，请先删除{$lockfile}");
    }
    //判断native目录是否存在
//    echo "判断native目录是否存在...<br>";
    if(!is_dir("./src/applications/native")){
        showError("./src/applications/native 目录不存在,请确保升级包中文件正确覆盖，并且不要修改目录名字");
    }
//    echo "检查扩展是否缺少...<br>";
    $extensions = get_loaded_extensions();
    if(!in_array("mcrypt", $extensions))showError("缺少mcrypt扩展");
    if(!in_array("curl", $extensions))showError("缺少curl扩展");
    $curl_version = curl_version();
    $ssl_version = $curl_version['ssl_version'];
    if(strpos($ssl_version,"NSS")!==false){
        $arr = explode("/", $ssl_version);
        $arr = explode(".", $arr[1]);
        if($arr[1]<16){
            showError("检测失败！您当前主机的操作系统curl库依赖的SSL版本为".$curl_version['ssl_version']."，NSS版本过低，请您联系主机运营商将NSS库升级为3.16或以上（如果您拥有主机的管理员权限也可以自行升级），否则会影响APP的聊天功能。");
        }
    }
    $pw_db = PW_DB::getInstance();
    //执行数据库升级
//    echo "引入数据库脚本...<br>";
    $sql_source = file_get_contents("./pw_new.sql");
//    var_dump($sql_source);
    $err_msg = '';
//    echo "格式化数据库脚本...<br>";
      
    $sql_source = str_replace(array("{pre}","{charset}","{time}"),array($pw_db->dbpre,$pw_db->charset,time()), $sql_source);
//    var_dump($sql_source);
//    $con = mysql_connect($host.":".$port,$username,$password) or die('Could not connect: ' . mysql_error());
    $sql_source = explode(';', $sql_source);
//    echo "校验sql语句...<br>";
    foreach($sql_source as $k => $v){
        $sql_source[$k] = trim($v);
        if(!$sql_source[$k])unset($sql_source[$k]);
    }
//    echo "逐条执行sql...<br>";
    foreach($sql_source as $sql){
        //执行sql
        $result = $pw_db->query($sql);
    }
    //将站点所有一级版面设置为移动端可显示版面
//    echo "查找站点一级版面...<br>";
    $sql = "SELECT fid FROM `".$pw_db->dbpre."bbs_forum` WHERE TYPE='forum'";
    $result = mysql_query($sql) or showError("Invalid query: " . mysql_error());
    $fids = array();
    while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
        $fids[$row['fid']] = 0;
    }
//    echo "将一级版面默认设置为移动端可显示版面...<br>";
    $fids = serialize($fids);
//    var_dump($fids);exit;
    $sql = "REPLACE INTO `".$pw_db->dbpre."common_config` (`name`, `namespace`, `value`, `vtype`) VALUES ('forum.fids', 'native', '".$fids."', 'array');";
    $pw_db->query($sql);
    
    //热帖权重计算
    $dirname = dirname($_SERVER['SCRIPT_NAME']);
    $dirname = $dirname == "\\" ? "" : $dirname;
    $http_host = "http://".((isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:'')).$dirname."/index.php?m=cron";
    $http_server = "http://".((isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:''));
//    echo "触发热帖权重计算...<br>";
    @file_get_contents($http_host);
    //生成lock文件
//    echo "生成lock文件...<br>";
    file_put_contents($lockfile, "pw9.0.1移动版");
    
}


/**
 * 移动版第二版补充升级，增加新表
 */
function update02($lockfile){
    if (file_exists($lockfile)) {
	showError("升级程序已被锁定, 如需重新运行，请先删除{$lockfile}");
    }
    $pw_db = PW_DB::getInstance();
    $sql_source = "DROP TABLE IF EXISTS `{pre}fresh_site`;
                   CREATE TABLE `{pre}fresh_site` (
                        `fresh_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                        `title` varchar(50) DEFAULT NULL COMMENT '说明，标题',
                        `href` varchar(100) DEFAULT NULL COMMENT '链接',
                        `img` varchar(100) DEFAULT NULL COMMENT '图片',
                        `des` varchar(100) DEFAULT NULL COMMENT '说明',
                        `vieworder` int(10) unsigned DEFAULT NULL COMMENT '排序',
                        PRIMARY KEY (`fresh_id`)
                    ) ENGINE=MyISAM DEFAULT CHARSET={charset};";
    $sql_source = str_replace(array("{pre}","{charset}","{time}"),array($pw_db->dbpre,$pw_db->charset,time()), $sql_source);
    $sql_source = explode(';', $sql_source);
    foreach($sql_source as $k => $v){
        $sql_source[$k] = trim($v);
        if(!$sql_source[$k])unset($sql_source[$k]);
    }
    foreach($sql_source as $sql){
        $result = $pw_db->query($sql);
    }
    
    file_put_contents($lockfile, "pw9.0.1移动版");
}

/**
 * 移动版第三版升级，增加新表情包
 */
function update03($lockfile){
    if (file_exists($lockfile)) {
	showError("升级程序已被锁定, 如需重新运行，请先删除{$lockfile}");
    }
    /* app生成平台已改成用户自定义上传表情
    $pw_db = PW_DB::getInstance();
    //如果是二次运行，本次导入表情包前先删除旧数据
    $sql = "DELETE FROM `{$pw_db->dbpre}common_emotion_category` WHERE `emotion_folder`='wangwang'";
    $pw_db->query($sql);
    $sql = "DELETE FROM `{$pw_db->dbpre}common_emotion` WHERE `emotion_folder`='wangwang'";
    $pw_db->query($sql);
    //添加旺旺表情包分类
    $sql = "INSERT INTO {$pw_db->dbpre}common_emotion_category (`category_name`, `emotion_folder`, `emotion_apps`, `orderid`, `isopen`) VALUES ('旺旺', 'wangwang', 'bbs', 0, 1);";
    $pw_db->query($sql);
    //获取旺旺表情包分类id
    $sql = "SELECT `category_id` FROM `".$pw_db->dbpre."common_emotion_category` WHERE `emotion_folder`='wangwang'";
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
    $sql = "INSERT INTO `{$pw_db->dbpre}common_emotion` (`category_id`,`emotion_name`,`emotion_folder`,`emotion_icon`,`vieworder`,`isused`) VALUES {$values};";
    $pw_db->query($sql);
    //删除表情包缓存数据
    $sql = "DELETE FROM `{$pw_db->dbpre}cache` WHERE `cache_key`='all_emotions'";
    $pw_db->query($sql);
     * 
     */
    //生成lock文件
//    echo "生成lock文件...<br>";
    file_put_contents($lockfile, "pw9.0.1移动版");
}
?>

