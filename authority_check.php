<?PHP
include_once('config.inc.php');
use google\appengine\api\users\User;
use google\appengine\api\users\UserService;

if(!isset($logoutBackPath)){
	$logoutBackPath = $progPath;
}
$user = UserService::getCurrentUser();
$LogonFailureReason = 0;

if(isset($user)){
	$userEmail = $user->getEmail();
	$query = "Select name "
			."From account "
			."Where google_account = '".$userEmail."' ";
	$rs = $db->Execute($query);
	$logoutUrl = UserService::createLogoutUrl($logoutBackPath);
	if($row = $rs->FetchRow()){
		session_start();
		$_SESSION['curUser'] = array('email' => $user->getEmail(), 'name' => $row['name'], 'nickname' => $user->getNickname());
	}
	else{
		unset($_SESSION['curUser']);
		//echo '<p style="font-size:14pt;line-height:12px;">' + $progName + '</p>';
		$LogonFailureReason = Constants::LogonFailureReasonAccessProgramDenied;
		//die('您的Google帳號無法使用本服務，請向浩棟(<a href="mailto:haotung@gmail.com">haotung@gmail.com</a>)申請，或<a href="'.$logoutBackPath.'"><span style="color:#0000FF;">登出</span></a>更換Google帳號。');
	}
}
else{
	unset($_SESSION['curUser']);
	$logonFailureReason = Constants::LogonFailureReasonWithoutGoogleAccount;
	$loginUrl = UserService::createLoginUrl($progPath);
	//echo '<p style="font-size:14pt;line-height:12px;">Mobile GIP App資料管理</p>';
	//die('請以Google帳號<a href="'.$loginUrl.'"><span style="color:#0000FF;">登入</span></a>');
	
}

if(!array_key_exists('curUser', $_SESSION)){
?>


<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="initial-scale=1.0, maximum-scale=2.0, user-scalable=no, width=device-width">
<link rel="stylesheet" type="text/css" href="css/admin.css">
<link rel="stylesheet" type="text/css" href="css/admin.home.css">
<script src="http://crypto-js.googlecode.com/svn/tags/3.1.2/build/rollups/aes.js"></script>
<script src="js/jquery-1.11.0.min.js"></script>
</head>
<body leftmargin="0" topmargin="0">
	
<table id="headerTable" border="0" width="100%" cellspacing="0" style="color:#F2FDF2;min-width:800px;">
	<tr>
		<td class="pageTitle" ><span><?PHP echo $progName;?></span><br/></td>
		<td align="right" valign="center">
			
		</td>
	</tr>
</table>
<?PHP
	if($logonFailureReason == Constants::LogonFailureReasonWithoutGoogleAccount){
		echo '請以Google帳號<a href="'.$loginUrl.'"><span style="color:#0000FF;">登入</span></a>';
	}
	else if($logonFailureReason == Constants::LogonFailureReasonAccessProgramDenied){
		echo ('您的Google帳號無法使用本服務，請向浩棟(<a href="mailto:haotung@gmail.com">haotung@gmail.com</a>)申請，或<a href="'.$logoutUrl.'"><span style="color:#0000FF;">登出</span></a>更換Google帳號。');
	}
?>
</body>
</html>
<?PHP
	die();	
}
?>