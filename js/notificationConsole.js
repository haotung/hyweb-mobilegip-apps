var Apps;
var curAppIndex = -1;
var postObject = new Object();

$(document).ready(function(){
	initialize();
	
});

function initialize(){
	initializeUI();
	getMembersOfAppIds();
	
}

function initializeUI(){
	for(var i = 0; i < 15; i++){
		$('#selBadge').append($('<option></option>').attr('value', i).text(i));
	}
	$('#selBadge').prop('selectedIndex', 1);
	$('#selAppId').change(function(){
		initializeUIAndDataForAppId(this.selectedIndex);
	});
	$('input:radio').change(function(){
		if($(this).attr('name') == 'rdoTarget'){
			targetChangedHandler();
		}
	});
	$('input:checkbox').change(function(){
		updateJSONData();
	});
	
	$('#selTemplate').change(function(){
		restoreTemplateObjectToUI(this.selectedIndex);	
		generatePostJSON();
		//$('#jsonPanel').html('要POST到service的JSON<br/>' + JSON.stringify(postObject, null, 4));
	});
	
}

function getMembersOfAppIds(){
	$.ajax({
		// url:'AJAX_getMembersOfAppIds.php',
		url: 'AJAX_getAppIds.php',
		dataType: 'json',
		success: function(data){			
			Apps = data;
			for(var i = 0; i < Apps.length; i++){
				var appId = Apps[i].AppId;
				var appName = Apps[i].name;
				$('#selAppId').append($('<option></option>').attr('value', appId).text(appId + '(' + appName + ')'));
			}
			if(Apps.length > 0){
				initializeUIAndDataForAppId(0);
				setInterval(generatePostJSON, 1000);
			}
		}
	});
}

function assignSpecifiedAccountsFromCheckBoxes(){
	var selectedAccounts = new Array();
	if($('#rdoTargetSpecifiedAccounts').is(':checked')){
		$('#btnCheckAllAccounts').prop('disabled', false);
		$('#btnUncheckAllAccounts').prop('disabled', false);
		$('#userIdsPanel').css({'border-color':'#333333', 'color':'#333333'});
		for(var i = 0; i < Apps[curAppIndex].accounts.length; i++){
			//alert($('#chkAccount' + i));
			$('#chkAccount' + i).prop('disabled', false);
			if($('#chkAccount' + i).is(':checked')){
				selectedAccounts[selectedAccounts.length] = Apps[curAppIndex].accounts[i];
			}
		}
		postObject.specAccounts = selectedAccounts;
	}
	else{
	
		$('#btnUncheckAllAccounts').prop('disabled', true);
		$('#btnCheckAllAccounts').prop('disabled', true);
		$('#userIdsPanel').css({'border-color':'#A0A0A0', 'color':'#A0A0A0'});
		for(var i = 0; i < Apps[curAppIndex].accounts.length; i++){
			//alert($('#chkAccount' + i));
			$('#chkAccount' + i).prop('disabled', true);
		}
		delete postObject.specAccounts;
	}
	updateJSONData();
}

function targetChangedHandler(){
	
	postObject.targetDevices = $('input[name="rdoTarget"]:radio:checked').val();
	assignSpecifiedAccountsFromCheckBoxes();
	
	updateJSONData();
}

function initializeUIAndDataForAppId(index){
	curAppIndex = index;
	postObject = new Object();
	postObject.AppId = Apps[index].AppId;
	postObject.hashHeader = Apps[index].hashHeader;
	postObject.targetDevices = 'AllDevices';
	postObject.isSilent = false;
	$('#rdoTargetAllDevices').prop('checked', true);
	$('#userIdsPanel').html('');
	$('#lblHashHeader').html(Apps[index].hashHeader);
	if(Apps[index].accounts.length > 0){
		for(var i = 0; i < Apps[index].accounts.length; i++){
			$('#userIdsPanel').append($('<input/>').attr('type', 'checkbox').attr('id', 'chkAccount' + i));
			$('#chkAccount' + i).change(assignSpecifiedAccountsFromCheckBoxes);
			$('#userIdsPanel').append($('<span>' + Apps[index].accounts[i] + '</span>')).append($('<br/>'));
		}
	}
	else{
		$.ajax({
			url: 'AJAX_getMembersOfAnAppId.php?appId=' + Apps[index].AppId,
			dataType: 'json',
			success: function(data){			
				userIds = data;
				Apps[index].accounts = userIds;
				for(var i = 0; i < Apps[index].accounts.length; i++){
					$('#userIdsPanel').append($('<input/>').attr('type', 'checkbox').attr('id', 'chkAccount' + i));
					$('#chkAccount' + i).change(assignSpecifiedAccountsFromCheckBoxes);
					$('#userIdsPanel').append($('<span>' + Apps[index].accounts[i] + '</span>')).append($('<br/>'));
				}
			}
		});
	}

	
	targetChangedHandler();
}

function checkAllAccounts(){
	for(var i = 0; i < Apps[curAppIndex].accounts.length; i++){
		$('#chkAccount' + i).prop('checked', true);
	}
	assignSpecifiedAccountsFromCheckBoxes();
}

function uncheckAllAccounts(){
	for(var i = 0; i < Apps[curAppIndex].accounts.length; i++){
		$('#chkAccount' + i).prop('checked', false);
	}
	assignSpecifiedAccountsFromCheckBoxes();
}

function updateJSONData(){
	
	var targetOS = new Array();
	if($('#chkOSiOS').is(':checked')) targetOS[targetOS.length] = 'iOS';
	if($('#chkOSAndroid').is(':checked')) targetOS[targetOS.length] = 'Android';
	if($('#chkOSWinPhone').is(':checked')) targetOS[targetOS.length] = 'Windows Phone';
	postObject.targetOS = targetOS;
	postObject.message = $('#txtMessage').val();
	postObject.badge = parseInt($('#selBadge').val());
	if($('#txtAttachedData').val().length > 0){
		try{
			postObject.additionalData = JSON.parse($('#txtAttachedData').val());
		}
		catch(e){
			postObject.additionalData = $('#txtAttachedData').val();
		}
	}
	else{
		delete postObject.additionalData;
	}
	postObject.isSilent = $('#chkSilentNotification').is(':checked');
	postObject.forDevelopment = $('#chkForDevelopment').is(':checked');
	generatePostJSON();
	//$('#jsonPanel').html('要POST到service的JSON<br/>' + JSON.stringify(postObject, null, 4));
	$('#selTemplate').prop('selectedIndex', 0);
	$('#receivedJSONPanel').html('(傳送結果)');	
	$('#receivedJSONPanel').css({'border-color':'#B0B0B0', 'color':'#B0B0B0'});
}



function restoreTemplateObjectToUI(templateIndex){
	if(templateIndex > 0){
		postObject = $.extend(true, {}, dataSamples[templateIndex]);
		//AppId
		$('#selAppId > option').each(function(){
			if(this.value == postObject.AppId){
				$(this).attr('selected', true);
				return;
			}
		});
		curAppIndex = $('#selAppId').prop('selectedIndex');		
		$('#userIdsPanel').html('');
		for(var i = 0; i < Apps[curAppIndex].accounts.length; i++){
			$('#userIdsPanel').append($('<input/>').attr('type', 'checkbox').attr('id', 'chkAccount' + i));
			$('#chkAccount' + i).change(assignSpecifiedAccountsFromCheckBoxes);
			$('#userIdsPanel').append($('<span>' + Apps[curAppIndex].accounts[i] + '</span>')).append($('<br/>'));
		}		
		
		//TargetOS
		$('#chkOSiOS').prop('checked', false);
		$('#chkOSAndroid').prop('checked', false);
		$('#chkOSWinPhone').prop('checked', false);
		for(var i = 0; i < postObject.targetOS.length; i++){
			if(postObject.targetOS[i] == 'iOS')	$('#chkOSiOS').prop('checked', true);
			else if(postObject.targetOS[i] == 'Android')	$('#chkOSAndroid').prop('checked', true);
			else if(postObject.targetOS[i] == 'WindowsPhone') $('#chkOSWinPhone').prop('checked', true);
		}
		
		//Message and Additional data
		$('#txtMessage').val(postObject.message);
		$('#txtAttachedData').val(JSON.stringify(postObject.additionalData));
		//Badge
		$('#selBadge').prop('selectedIndex', postObject.badge);
		//silent notification & push for development
		$('#chkSilentNotification').prop('checked', postObject.isSilent);
		$('#chkForDevelopment').prop('checked', postObject.forDevelopment);
		
		//TargetDevices
		$('input[name="rdoTarget"]:radio').each(function(){
			if(postObject.targetDevices == $(this).val()){
				$(this).prop('checked', true);
				if(postObject.targetDevices == 'SpecifiedAccounts'){
					$('#btnCheckAllAccounts').prop('disabled', false);
					$('#btnUncheckAllAccounts').prop('disabled', false);
					$('#userIdsPanel').css({'border-color':'#333333', 'color':'#333333'});
					for(var i = 0; i < Apps[curAppIndex].accounts.length; i++){
						//alert($('#chkAccount' + i));
						$('#chkAccount' + i).prop('disabled', false);
						if($.inArray(Apps[curAppIndex].accounts[i], postObject.specAccounts) >= 0){
							$('#chkAccount' + i).prop('checked', true);
						}
						else{
							$('#chkAccount' + i).prop('checked', false);
						}
					}
				}
				else{
				
					$('#btnUncheckAllAccounts').prop('disabled', true);
					$('#btnCheckAllAccounts').prop('disabled', true);
					$('#userIdsPanel').css({'border-color':'#A0A0A0', 'color':'#A0A0A0'});
					for(var i = 0; i < Apps[curAppIndex].accounts.length; i++){
						$('#chkAccount' + i).prop('disabled', true);
					}
				}
				return;
			}
		});
	}
}

function addZero(num){
	if(num < 10){
		return "0" + num;	
	}
	else{
		return "" + num;
	}	
}


function generatePostJSON(){
	var curPostObject = $.extend(true, {}, postObject);
	var now = new Date();
	var timeStamp = now.getFullYear() + addZero(now.getMonth() + 1) + addZero(now.getDate()) + " " + addZero(now.getHours()) + addZero(now.getMinutes()) + addZero(now.getSeconds());
	var hashStr = postObject.hashHeader + postObject.AppId + timeStamp + postObject.message;
	curPostObject.timeStamp = timeStamp;
	curPostObject.checkStr = '' + CryptoJS.MD5(hashStr);
	delete curPostObject.hashHeader;
	$('#jsonPanel').html('要POST到service的JSON<br/>' + JSON.stringify(curPostObject, null, 4));
	return curPostObject;
}

function postData(){
	$('#receivedJSONPanel').css({'border-color':'#B0B0B0', 'color':'#B0B0B0'});
	$('#btnPost').prop('disabled', true);
	var curPostObject = generatePostJSON();
	$.ajax({
		type:"POST",
		url:"sendNotification.php",
		data: JSON.stringify(curPostObject),
		contentType: "application/json; charset=utf-8",
		dataType: "json",
		success:function(responseJSON){
			$('#receivedJSONPanel').html('回傳JSON<br/>' + JSON.stringify(responseJSON, null, 4));
			$('#receivedJSONPanel').css({'border-color':'#0020F0', 'color':'#0020F0'});
		},
		error:function(errObj) {
			$('#receivedJSONPanel').html('發生錯誤：<br/>' + 'status code: ' + errObj.status + '<br/>' + JSON.stringify(errObj, null, 4));
			$('#receivedJSONPanel').css({'border-color':'#F00000', 'color':'#F00000'});
		},
		complete:function(data){
			$('#btnPost').prop('disabled', false);
			//alert('complete:' + JSON.stringify(data));
		}
	});

}






