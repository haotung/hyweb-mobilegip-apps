<?PHP
include_once('config.inc.php');
use google\appengine\api\users\User;
use google\appengine\api\users\UserService;

$user = UserService::getCurrentUser();

if(isset($user)){
	$userEmail = $user->getEmail();
	$query = "Select name "
			."From account "
			."Where google_account = '".$userEmail."' ";
	$rs = $db->Execute($query);
	$logoutUrl = UserService::createLogoutUrl('/notificationConsole');
	if($row = $rs->FetchRow()){		//暫時限定Google帳號
		session_start();
		$_SESSION['curUser'] = array('email' => $user->getEmail(), 'name' => $row['name'], 'nickname' => $user->getNickname());
	}
	else{
		echo '<p style="font-size:14pt;line-height:12px;">Mobile GIP Push Notification手動發送測試</p>';
		die('您的Google帳號無法使用本服務，請向浩棟(<a href="mailto:haotung@gmail.com">haotung@gmail.com</a>)申請，或<a href="'.$logoutUrl.'"><span style="color:#0000FF;">登出</span></a>更換Google帳號。');
	}
}
else{
	$loginUrl = UserService::createLoginUrl('/notificationConsole');
	echo '<p style="font-size:14pt;line-height:12px;">Mobile GIP Push Notification手動發送測試</p>';
	die('請以Google帳號<a href="'.$loginUrl.'"><span style="color:#0000FF;">登入</span></a>');
	
}


?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8"/>
<script src="js/aes.js"></script>
<script src="js/jquery-1.11.0.min.js"></script>
<script src="js/dataSamples.js"></script>
<script src="js/notificationConsole.js"></script>
<style>
.hint{
	font-size:9pt;
	color:#0000E0;
}
</style>
</head>
<body>

<table border="0" cellpadding="3">
	<tr>
		<td colspan="2" valign="top">
			<p style="font-size:14pt;line-height:12px;">Mobile GIP Push Notification手動發送測試</p>
		</td>
		<td align="right">
			<?PHP 
				echo $_SESSION['curUser']['name'].'您好（<a href="'.$logoutUrl.'">登出</a>)';
			?>
		</td>
	</tr>
	<tr>
		<td colspan="2" valign="top" style="background-color:#E0E0E4;">
			範例
			<select size="4" id="selTemplate">
				<option value="0" selected>無(自訂下方資料)</option>
				<option value="1">台水-水費即將到期通知</option>
				<option value="2">台水-水費帳單通知</option>
				<option value="3">台水-停水通知</option>
				<option value="4">台水-通知所有App user</option>
				<option value="5">台東HyLib-圖書即將到期通知</option>
			</select>
		</td>
		<td valign="top" style="padding:6px;padding-top:3px;color:#007000;background-color:#FFFFC0;line-height:12pt;" rowspan="2">
			<pre id="jsonPanel" style="display:block;border:solid 1px #007000;width:460px;max-height:290px;font-size:10pt;padding:3px;overflow-x:auto;">
要POST到service的JSON
			</pre>
			<input type="button" id="btnPost" value="傳送" onclick="postData()"/>至http://hyweb-mobilegip-apps.appspot.com/sendNotification.php<br/>
			<pre id="receivedJSONPanel" style="display:block;border:solid 1px #B0B0B0;color:#B0B0B0;width:460px;max-height:180px;font-size:10pt;padding:3px;overflow-x:auto;text-align:left;">
(傳送結果)
			</pre>
			
		</td>
	</tr>
	<tr>
		<td style="background-color:#D8EAFB;" valign="top">
			App ID <span class="hint">(AppId)</span><select id="selAppId" name="AppId"></select><span id="lblHashHeader"></span><br/>
			平台 <span class="hint">(targetOS)</span><input type="checkbox" id="chkOSiOS" checked="checked"/>iOS　
				<input type="checkbox" id="chkOSAndroid" />Android　
				<input type="checkbox" id="chkOSWinPhone" />Windows Phone<br/>
			訊息文字 <span class="hint">(message)</span><input type="text" id="txtMessage" value="" size="30" onkeyup="updateJSONData()"/><br/>		
			附加資料 <span class="hint">(additionalData)</span><input type="text" id="txtAttachedData" size="30" onkeyup="updateJSONData()"/><br/>
			<span style="font-size:10pt;color:#F00000;">附加資料請輸入有效的JSON格式字串</span><br/>	
			標記數量(iOS用的) <span class="hint">(badge)</span><select id="selBadge" onchange="updateJSONData()"></select><br/>
			<input type="checkbox" id="chkSilentNotification">Silent Notification(由App判斷是否跳出通知)<span class="hint">(isSilent)</span><br/>
			<input type="checkbox" id="chkForDevelopment" checked="checked">推到測試server<span class="hint">(forDevelopment)</span><br/>
			
			通知對象 <span class="hint">(targetDevices)</span><br/>
			<input type="radio" name="rdoTarget" id="rdoTargetAllDevices" value="AllDevices" checked="checked">全部裝置<br/>
			<input type="radio" name="rdoTarget" id="rdoTargetAllRegisteredAccounts" value="AllRegisteredAccounts">全部有綁定帳號的裝置<br/>
			<input type="radio" name="rdoTarget" id="rdoTargetSpecifiedAccounts" value="SpecifiedAccounts">指定的帳號 
			<input type="button" id="btnCheckAllAccounts" value="全選" onclick="checkAllAccounts()" disabled="true"/>
			<input type="button" id="btnUncheckAllAccounts" value="清除" onclick="uncheckAllAccounts()" disabled="true"/><br/>
			<span class="hint">(specAccounts)</span><br/>
			<div id="userIdsPanel" style="border:solid 1px #A0A0A0;color:#A0A0A0;width:400px;max-height:360px;height:180px;overflow-y:auto;padding:3px;">
			</div>
		</td>
	</tr>
</table>
</body>
</html>
