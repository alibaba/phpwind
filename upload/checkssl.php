<?php
/**
 * 本次升级包中加入了一个检测脚本checkssl.php。
 * 此脚本专门针对安装了3月19日的升级包后，app登录提示“消息系统登录失败”无法用App聊天的用户。
 * 能正常登录并聊天的用户可以忽略以下内容，只需要完成本次升级包的覆盖升级即可，无需执行任何脚本。
 * 
 * 第一次对站点进行升级的用户，请先将本次的升级包覆盖至您的站点根目录，然后在浏览器中访问up9xto91.php升级脚本完成整个升级过程。
 * 
 * 以下内容仅针对“消息系统登录失败”的用户：
 * 根据我们对部分站长环境的排查，发现很多提示“消息系统登录失败”的站长，主机系统的SSL库版本过低。
 * 该脚本可以检测主机系统的SSL库版本是否符合要求，请您将3月26日的升级包上传服务器完成覆盖升级后，在根目录可看到checkssl.php文件。
 * 在浏览器中访问checkssl.php执行脚本。
 * 结果提示为检测失败的，请根据提示的文案自主进行系统的升级。
 * 结果提示为检测成功的说明您的系统环境没有问题，可能是由于app的通讯秘钥损坏所致，针对这一部分用户我们在管理后台中新增了“一键修复”功能，请您登陆到站长管理后台。
 * 在“工具”->App聊天修复->点击“一键修复”完成修复操作。
 * 重新生成App，下载并运行。
 * checkssl.php检测完成后在服务器上删除即可。
 * 使用中如果您遇到任何问题请反馈至http://www.phpwind.net/，我们会第一时间排查处理。
 * 
 */
//ini_set("display_errors",1);
//error_reporting(E_ALL);
error_reporting(0);
ini_set( 'display_errors', 'Off' );
header("Content-Type:text/html;charset=utf-8"); 

$extensions = get_loaded_extensions();
if(!in_array("curl", $extensions))showError("缺少curl扩展");
$curl_version = curl_version();
$ssl_version = isset($curl_version['ssl_version']) ? $curl_version['ssl_version'] : "";
//$ssl_version = "NSS/3.14.5";
$error_txt = "";
if(strpos($ssl_version,"NSS")!==false){
    $error_txt = "如果您是刚升级完NSS，请您将php也同时升级到新版";
    $arr = explode("/", $ssl_version);
    $arr = explode(".", $arr[1]);
    if($arr[1]<16){
        showMsg("检测失败！您当前主机的操作系统curl库依赖的SSL版本为".$curl_version['ssl_version']."，NSS版本过低，请您联系主机运营商将NSS库升级为3.16或以上（如果您拥有主机的管理员权限也可以自行升级），否则会影响APP的聊天功能。您可以点击<a href='http://www.phpwind.net/read/3420114'>这里</a>查看解决办法。");
    }
}elseif(!$ssl_version){
    showMsg("检测失败！您当前主机的操作系统没有安装SSL库，请联系您的主机运营商为您安装OpenSSL（如果您拥有主机的管理员权限也可以自行升级），否则会影响APP的聊天功能。<br>curl_version:$ssl_version");
}


$url='https://wkapi.laiwang.com/v1/user/profile';//查看用户信息    
$params=array(
    'openid'=>14,
);
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_VERBOSE, 1);
curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch,CURLOPT_TIMEOUT,10);
curl_setopt($ch, CURLOPT_SSLVERSION , 1);
curl_setopt($ch, CURLOPT_VERBOSE,true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params) );
if(strpos($ssl_version,"OpenSSL")!==false){
    curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'TLSv1');
}

$x = curl_exec($ch);
$error_msg = "";
if(!$x){
    $error_msg = curl_error($ch);
}
$httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
//        var_dump(curl_getinfo($ch, CURLINFO_HEADER_OUT));
//            $error_message = curl_strerror($errno);
//            echo "cURL error ({$errno}):\n {$error_message}"."<br>";


if($httpCode){
    showMsg("检测成功！您当前的系统curl库依赖的SSL库版本为".$curl_version['ssl_version']."<br>httpCode:{$httpCode}");
}else{
    $errno = curl_errno($ch);
    showMsg("检测失败！{$error_txt}<br>httpCode:{$httpCode}<br>errorMsg:{$error_msg}<br>errorno:{$errno}<br>curl_version:{$curl_version['ssl_version']}");
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
<title>phpwind 9.0.1移动版环境检测</title>
<meta charset="utf8" />
<link rel="stylesheet" href="res/css/install.css" />
</head>
<body>
	<div class="wrap">
		<div class="header">
			<h1 class="logo">logo</h1>
			<div class="icon_update">环境检测向导</div>
			<div class="version">phpwind 9.0.1移动版环境检测</div>
		</div>

		<div class="success_tip cc error_tip">
			<div class="mb10 f14">$msg</div>
			<div class="error_return">{$url}</div>
		</div>
	</div>
	<div class="footer">
		&copy; 2003-2015 <a href="http://www.phpwind.com" target="_blank">phpwind.com</a>（阿里巴巴集团旗下品牌）
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
<title>phpwind 9.0.1移动版环境检测</title>
<meta charset="utf8" />
<link rel="stylesheet" href="res/css/install.css" />
</head>
<body>
	<div class="wrap">
		<div class="header">
			<h1 class="logo">logo</h1>
			<div class="icon_update">环境检测向导</div>
			<div class="version">phpwind 9.0.1移动版环境检测</div>
		</div>

		<div class="success_tip cc error_tip">
			<div class="mb10 f14">$msg</div>
		</div>
	</div>
	<div class="footer">
		&copy; 2003-2015 <a href="http://www.phpwind.com" target="_blank">phpwind.com</a>（阿里巴巴集团旗下品牌）
	</div>
</body>
</html>
EOT;
	exit;
}
