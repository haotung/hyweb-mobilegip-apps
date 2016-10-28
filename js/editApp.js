

$(document).ready(function(){
	//loadAppData();
	setEventHandlers();
	loadVersions();
	initializeUI();
	loadRecords();
});


function setEventHandlers(){
	setDragingEventHandlers();
	setButtonClickEventHandlers();
	setCheckBoxChangeEventHandlers();
	setTextChangeEventHandlers();
}

function initializeUI(){
	$('input[type="radio"][name="rdoAppType"][value="GIP"]:checked').each(function(){
		$('#sectionHyLib').css('display', 'none');
		$('#sectionHyLibSettings').css('display', 'none');
	});
	$('#txtNewVersion').keyup(function(){
		if(($(this).val()).length > 5){
			var ipaCommands = 'xcodebuild archive -project MobileGIPv2.xcodeproj -scheme ' + appId + ' -archivePath ' + appId + '.xcarchive<br/>\n'
			+ 'xcodebuild -exportArchive -archivePath ' + appId + '.xcarchive -exportPath ' + appId + '_v' + $(this).val() + ' -exportFormat ipa -exportProvisioningProfile ""';
			$('#txtGenIPA').html(ipaCommands);
		}
		else{
			$('#txtGenIPA').html('');
		}
	});
	initHyLibSettingsUI();
}

function loadRecords(){
	loadPushTasks();
}

function initHyLibSettingsUI(){
	drawTestAccountsUI();
	drawRelatedLinksUI();
	$('#chkUseLBS').prop('checked', useLBS);
	$('#chkSpecifiedBooks').prop('checked', useSpecifiedBooks);
	$('#chkNews').prop('checked', displayNews);
}

function drawTestAccountsUI(){
	$('#testAccountsContainer').html('');
	for(var i = 0; i < testAccounts.length; i++){
		$('#testAccountsContainer').append($('<img src="images/icon_delete.png" class="imgDeleteTestAccount" index="' + i + '" style="cursor:pointer;"/>'));
		$('#testAccountsContainer').append('帳號&nbsp;').append($('<input type="text" class="txtTestAccountId" size="15" placeholder="帳號" index="' + i + '" value="' + testAccounts[i]['id'] + '" />&nbsp;'));
		$('#testAccountsContainer').append('&nbsp;密碼&nbsp;').append($('<input type="text" class="txtTestAccountPwd" size="15" placeholder="密碼" index="' + i + '" value="' + testAccounts[i]['password'] + '" />'));
		$('#testAccountsContainer').append('<br/>');
	}
	$('#testAccountsContainer').append($('<img src="images/icon_add.png" id="imgAddTestAccount" style="cursor:pointer;"/>')).append('<br/>');
	
	/*
	$('#tblHyLibSettings input[type="text"]').keydown(function(){
		$('#btnSaveHyLibSettings').attr('class', 'saveBtn').val('儲存');
	});
	*/	
	
	$('#testAccountsContainer input[type="text"]').keyup(function(){
		var index = parseInt($(this).attr('index'));
		if($(this).hasClass('txtTestAccountId')){
			testAccounts[index]['id'] = $(this).val();
		}
		else if($(this).hasClass('txtTestAccountPwd')){
			testAccounts[index]['password'] = $(this).val();
		}
		$('#btnSaveHyLibSettings').attr('class', 'saveBtn').val('儲存');
	});
	
	$('.imgDeleteTestAccount').click(function(){
		var index = parseInt($(this).attr('index'));
		if((testAccounts[index]['id'].length == 0) || confirm('您確定要刪除 ' + testAccounts[index]['id'] + ' 這組測試帳號嗎？')){
			testAccounts.splice(index, 1);
			$('#btnSaveHyLibSettings').attr('class', 'saveBtn').val('儲存');
			drawTestAccountsUI();
		}
	});
	
	$('#imgAddTestAccount').click(function(){
		testAccounts.push({"id":"", "password":""});
		$('#btnSaveHyLibSettings').attr('class', 'saveBtn').val('儲存');
		drawTestAccountsUI();
	});
	
	
}

function drawRelatedLinksUI(){
	//relatedLinksContainer
	$('#relatedLinksContainer').html('');
	for(var i = 0; i < relatedLinks.length; i++){
		$('#relatedLinksContainer').append($('<img src="images/icon_delete.png" class="imgDeleteRelatedLink" index="' + i + '" style="cursor:pointer;"/>'));
		$('#relatedLinksContainer').append('名稱&nbsp;').append($('<input type="text" class="txtRelatedLinkText" size="14" placeholder="顯示在App上的名稱" index="' + i + '" value="' + relatedLinks[i]['text'] + '" />&nbsp;'));
		$('#relatedLinksContainer').append('&nbsp;網址&nbsp;').append($('<input type="text" class="txtRelatedLinkUrl" size="32" placeholder="網址（包含http://）" index="' + i + '" value="' + relatedLinks[i]['url'] + '" />'));
		$('#relatedLinksContainer').append('<br/>');
	}
	$('#relatedLinksContainer').append($('<img src="images/icon_add.png" id="imgAddRelatedLinks" style="cursor:pointer;"/>')).append('<br/>');
	

	
	$('#relatedLinksContainer input[type="text"]').keyup(function(){
		var index = parseInt($(this).attr('index'));
		if($(this).hasClass('txtRelatedLinkText')){
			relatedLinks[index]['text'] = $(this).val();
		}
		else if($(this).hasClass('txtRelatedLinkUrl')){
			relatedLinks[index]['url'] = $(this).val();
		}
		$('#btnSaveHyLibSettings').attr('class', 'saveBtn').val('儲存');
	});
	
	$('.imgDeleteRelatedLink').click(function(){
		var index = parseInt($(this).attr('index'));
		if((relatedLinks[index]['text'].length == 0) || confirm('您確定要刪除 ' + relatedLinks[index]['text'] + ' 這組連結嗎？')){
			relatedLinks.splice(index, 1);
			$('#btnSaveHyLibSettings').attr('class', 'saveBtn').val('儲存');
			drawRelatedLinksUI();
		}
	});
	
	$('#imgAddRelatedLinks').click(function(){
		relatedLinks.push({"text":"", "url":""});
		$('#btnSaveHyLibSettings').attr('class', 'saveBtn').val('儲存');
		drawRelatedLinksUI();
	});
}

function setTextChangeEventHandlers(){
	$('#tblMetadata input[type="text"]').keyup(function(){
		$('#btnSaveMetadata').attr('class', 'saveBtn').val('儲存');
		
	});
	$('#tblStoreData input[type="text"]').keyup(function(){
		$('#btnSaveStoreData').attr('class', 'saveBtn').val('儲存');
	});
	$('#txtKeywords').keyup(function(){
		$(this).val($(this).val().replace(/；/gi, ', '));
		
	});
	$('#tblStoreData textarea').keydown(function(){
		$('#btnSaveStoreData').attr('class', 'saveBtn').val('儲存');
	});
	
	$('#txtNewVersion').keyup(function(){
		enableReleaseBtn();
		console.log('version keydown');
	});
	
	$('#txtWhatsNew').keyup(function(){
		
		enableReleaseBtn();
		console.log('whats new keydown');
	});
	
	$('#txtFormShortIntroduction').keyup(function(){
		displayCharCountForShortIntro();
	});
	displayCharCountForShortIntro();
}

function displayCharCountForShortIntro(){
	var newText = $('#txtFormShortIntroduction').val();
	$('#lblCharCount').html('已輸入 ' + newText.length + ' 個字元(最多 80 個字元)');
	$('#lblCharCount').css('color',((newText.length > 80)?'#E00000':'#808080'));
}

function setCheckBoxChangeEventHandlers(){
	$('#chkDownloadiOSLaunchImage').change(function(){
		var textColor = ($(this).is(":checked"))?'#000000':'#888888';
		var checkBoxDisabled = !$(this).is(":checked");
		
		$('#iosLaunchImageDownloadOption').css('color', textColor);
		$('#iosLaunchImageDownloadOption input[type="checkbox"]').each(function(){
			$(this).prop("disabled", checkBoxDisabled);
			
		});
	});	
	
	$('input[name="rdoAppType"]:radio').change(function(){
		$('#btnSaveMetadata').attr('class', 'saveBtn').val('儲存');
		$('#sectionHyLib').css('display', ($(this).val() == 'HyLib')?'block':'none');
		$('#sectionHyLibSettings').css('display', ($(this).val() == 'HyLib')?'block':'none');
		if($(this).val() == 'HyLib'){
			$('#btnDownloadHyLibMenuXML').addClass('enabledBtn').removeClass('disabledBtn');
		}
		else{
			$('#btnDownloadHyLibMenuXML').addClass('disabledBtn').removeClass('enabledBtn');
		}
		$('#hylibMenuXmlDisabledHint').css('display', ($(this).val() == 'HyLib')?'none':'inline');
	});
	
	$('#sectionHyLibSettings input[type="checkbox"]').change(function(){
		$('#btnSaveHyLibSettings').attr('class', 'saveBtn').val('儲存');
		
	});
	
}



function setButtonClickEventHandlers(){
	$('#btnSaveMetadata').click(function(){
		if($(this).attr('class') == 'saveBtn'){
			$(this).attr('class', 'savingBtn').val('儲存中...');
			saveAppData('tblMetadata', $(this));
		}
	});
	
	$('#btnSaveStoreData').click(function(){
		if($(this).attr('class') == 'saveBtn'){
			$(this).attr('class', 'savingBtn').val('儲存中...');
			saveAppData('tblStoreData', $(this));
		}
	});
	
	$('#btnSaveHyLibSettings').click(function(){
		if($(this).attr('class') == 'saveBtn'){
			$(this).attr('class', 'savingBtn').val('儲存中...');
			saveAdditionalAttrs($(this));
		}
	});
	
	$('#btnDownloadXCAssets').click(function(){
		if($(this).hasClass('enabledBtn')){
			/*
			<input id="chkDownloadiOSAppIcon type="checkbox" checked="checked"/>App icon<br/>
					<input id="chkDownloadiOSLaunchImage" type="checkbox" checked="checked" />啟動畫面<br/>
					<div id="iosLaunchImageDownloadOption">
					　<input id="chkGenerateiPhone6LaunchImage" type="checkbox" checked="checked" />支援iPhone 6 / iPhone 6 plus<br/>
					　<input id="chkGenerateiPadLaunchImage" type="checkbox" />支援iPad<br/>
					</div>
					<input id="chkDownloadHyLibLoginBG" type="checkbox" checked="checked" /
					*/
			
			
			var downloadUrl = '/downloadXcodeProjectContent.php?appId=' + appId;
			var optionsParams = '';
			if($('#chkDownloadiOSAppIcon').is(':checked')) optionsParams += '&appIcon=yes';
			if($('#chkDownloadiOSLaunchImage').is(':checked')){
				optionsParams += '&launchImage=yes';
				if($('#chkGenerateiPhone6LaunchImage').is(':checked')) optionsParams += '&iPhone6=yes';
				if($('#chkGenerateiPadLaunchImage').is(':checked')) optionsParams += '&iPad=yes';
			}
			if($('#chkDownloadHyLibLoginBG').is(':checked')) optionsParams += '&hylibLoginBG=yes';
			if($('#chkDownloadiOSAppDataFolder').is(':checked')) optionsParams += '&appDataFolder=yes';
			
			
			
			if(optionsParams.length == 0){
				alert("請至少勾選一個項目");
				return;
			}
			location.href = downloadUrl + optionsParams;	
		}
	});
	
	
	$('#btnDownloadAndroidAssets').click(function(){
		if($(this).hasClass('enabledBtn')){
			var downloadUrl = '/generateAndroidIconAssets.php?appId=' + appId;
			var optionsParams = '';
			
			if($('#chkDownloadAndroidAppIcon').is(':checked')) optionsParams += '&appIcon=yes';
			if($('#chkDownloadAndroidAssets').is(':checked')) optionsParams += '&assets=yes';
			if($('#chkDownloadGooglePlayFiles').is(':checked')) optionsParams += '&googlePlayFiles=yes';
			if($('#chkDownloadAndroidHyLibXMLs').is(':checked')) optionsParams += '&hylibXml=yes';
			if(optionsParams.length == 0){
				alert("請至少勾選一個項目");
				return;
			}
			optionsParams += '&outputFileName=' + $('#txtIconFileName').val();
			
			location.href = downloadUrl + optionsParams;	
		}
	});
	
	$('#btnDownloadHyLibMenuXML').click(function(){
		if($(this).hasClass('enabledBtn')){
			var downloadUrl = '/generateMenuXML.php?appId=' + appId;
			location.href = downloadUrl;
		}
	});
	
	
	$('.delIconBasicImage').click(function(){
		removeContentFile($(this).attr('itemName'));
	});
	
	$('#btnRelease').click(function(){
		releaseTestingInstaller();
	});
	//generateAndroidIconAssets.php
}

function setDragingEventHandlers(){
	//App icon
	var fileDragableArea = $("#fileDragableArea");
	fileDragableArea.on('dragenter', function (e) 
	{
	    e.stopPropagation();
	    e.preventDefault();
	    $(this).css('background', 'rgba(255, 255, 100, 0.5)');
	    //$(this).css('border', '2px solid #0B85A1');
	});
	fileDragableArea.on('dragover', function (e) 
	{
	     e.stopPropagation();
	     e.preventDefault();
	});
	fileDragableArea.on('drop', function (e) 
	{
	 
		$(this).css('background', 'rgba(255, 255, 100, 0)');
	    e.preventDefault();
	    var files = e.originalEvent.dataTransfer.files;
	 
	 	if(files.length > 1){
	 		alert('僅限上傳一個檔案');	
	 	}
	 	else if(files.length == 1){
	 		uploadAppIcon(files[0], fileDragableArea);
	 	}
	     //We need to send dropped files to Server
	     //handleFileUpload(files,obj);
	});
	
	//Basic images
	$('.noBasicImageBG').on('dragenter', function (e) 
	{
	    e.stopPropagation();
	    e.preventDefault();
	    $(this).css('background-color', '#C0C060');
	});
	$('.noBasicImageBG').on('dragleave', function (e) 
	{
	    e.stopPropagation();
	    e.preventDefault();
	    $(this).css('background-color', '#B0B0B0');
	});
	$('.noBasicImageBG').on('drop', function (e) 
	{
	    e.stopPropagation();
	    e.preventDefault();
	    var files = e.originalEvent.dataTransfer.files;
	 
	 	if(files.length > 1){
	 		alert('僅限上傳一個檔案');	
	 	}
	 	else if(files.length == 1){
	 		uploadContentFile(files[0], $(this).attr('itemName'), $(this));
	 	}
	    $(this).css('background-color', '#B0B0B0');
	});
	
	
	//Basic images
	$('#installerUploadArea').on('dragenter', function (e) 
	{
	    e.stopPropagation();
	    e.preventDefault();
	    if($(this).attr('uploading') == 'true'){
	    	return;	
	    }
	    if($(this).hasClass('uploaded')){
	    	return;	
	    }
	    $(this).css('background-color', '#C0C060');
	});
	$('#installerUploadArea').on('dragleave', function (e) 
	{
	    e.stopPropagation();
	    e.preventDefault();
	    if($(this).hasClass('uploaded')){
	    	return;	
	    }
	    $(this).css('background-color', '#B0B0B0');
	});
	$('#installerUploadArea').on('drop', function (e) 
	{
	    e.stopPropagation();
	    e.preventDefault();
	    if($(this).hasClass('uploaded')){
	    	return;	
	    }
	    $(this).css('background-color', '#B0B0B0');
	    if($(this).attr('uploading') == 'true'){
	    	return;	
	    }
	    var files = e.originalEvent.dataTransfer.files;
	 	if(files.length > 1){
	 		alert('僅限上傳一個檔案');	
	 	}
	 	else if(files.length == 1){
	 		uploadInstaller(files[0], $(this));
	 	}
	});
	
	//防止檔案拖拉到感應區外放開導致頁面變成開檔
	$(document).on('dragenter', function (e) 
	{
	    e.stopPropagation();
	    e.preventDefault();
	});
	$(document).on('dragover', function (e) 
	{
		e.stopPropagation();
		e.preventDefault();
	});
	$(document).on('drop', function (e) 
	{
	    e.stopPropagation();
	    e.preventDefault();
	});
}

function uploadContentFile(file, itemName, dragingArea){
	var formData = new FormData();
	formData.append('file', file);
	formData.append('appId', appId);
	formData.append('itemName', itemName);
	
	$.ajax({
		url: 'getGSUploadUrl.php?purpose=appBasicImage',
		success: function(response){
			var uploadUrl = response;
			var jqXHR=$.ajax({
			    xhr: function() {
			        var xhrobj = $.ajaxSettings.xhr();
			        if (xhrobj.upload) {
			        	
			            xhrobj.upload.addEventListener('progress', function(event) {
			            	
			                var percent = 0;
			                var position = event.loaded || event.position;
			                var total = event.total;
			                if (event.lengthComputable) {
			                    percent = Math.ceil(position / total * 100);
			                }
			                //Set progress
			                if(percent < 100){
				                $(dragingArea).html('上傳中...' + percent + '%');
				            }
				            else{
				            	$(dragingArea).html('處理中...');
				            }
				            
			            }, false);
			        }
			        return xhrobj;
			    },
			    url: uploadUrl,
			    type: "POST",
			    contentType:false,
			    dataType:"json",
			    processData: false,
			    cache: false,
			    data: formData,
			    success: function(responseJson){
			        if(responseJson.success){
			        	$('.basicImage').each(function(){
			        		if($(this).attr('itemName') == itemName){
			        			if(responseJson.isImage){
				        			$(this).attr('src', 'getContentFile.php?app_id=' + appId + '&itemName=' + itemName + '&maxHeight=200');
				        		}
				        		else{
				        			$(this).html('已上傳');	
				        		}
			        			$(this).css('display', 'block');
			        		}
			        		
			        	});
			        	$('.noBasicImageBG').each(function(){
			        		if($(this).attr('itemName') == itemName){
			        			$(this).css('display', 'none');
			        		}
			        	});
				        $('.delIconBasicImage').each(function(){
				        	if($(this).attr('itemName') == itemName){
			        			$(this).css('display', 'block');
			        		}
				        });
			        }
			        else{
			        	if(responseJson.errorMsg != undefined){
			        		alert(responseJson.errorMsg);	
			        		$(dragingArea).html('拖拉圖檔至此上傳');
			        	}	
			        }
			        
			         
			    },
			    error:function(error){
			    	alert('error:' + error);
			    	$(dragingArea).html('拖拉圖檔至此上傳');
			    }
			}); 
			
		}
	});
	
		
	
}

function uploadAppIcon(file, obj){
	var formData = new FormData();
	formData.append('file', file);
	formData.append('appId', appId);
	
	$.ajax({
		url: 'getGSUploadUrl.php',
		success: function(response){
			var uploadUrl = response;
			
			var jqXHR=$.ajax({
			    xhr: function() {
			        var xhrobj = $.ajaxSettings.xhr();
			        if (xhrobj.upload) {
			        	
			            xhrobj.upload.addEventListener('progress', function(event) {
			                var percent = 0;
			                var position = event.loaded || event.position;
			                var total = event.total;
			                if (event.lengthComputable) {
			                    percent = Math.ceil(position / total * 100);
			                }
			                //Set progress
			                if(percent < 100){
				                $('#iconHint').html('上傳中...' + percent + '%');
				            }
				            else{
				            	$('#iconHint').html('');
				            }
			            }, false);
			        }
			        return xhrobj;
			    },
			    url: uploadUrl,
			    type: "POST",
			    contentType:false,
			    dataType:"json",
			    processData: false,
			    cache: false,
			    data: formData,
			    success: function(responseJson){
			        //status.setProgress(100);
					//alert('uploaded, response:' + data);
			        //$("#status1").append("File upload Done<br>");   
			        
			        if(responseJson.success){
			        	
				        $('#uploadedAppIcon').attr('src', 'getAppIcon.php?app_id=' + appId);   
				        $('#fileDragableArea').css('display','none');
				        $('#delIcon').css('display', 'block');
		        		$('.btnIconDownload').removeClass("disabledBtn").addClass("enabledBtn");
			        	if(responseJson.warningMsg != undefined){
			        		alert(responseJson.warningMsg);	
			        	}
			        }
			        else{
			        	if(responseJson.errorMsg != undefined){
			        		alert(responseJson.errorMsg);	
			        	}	
			        }
			        
			         
			    },
			    error:function(error){
			    	alert('error:' + error);
			    }
			}); 
			
		}
	});
	
}



function removeContentFile(itemName){
	$.ajax({
		url: 'removeContentFile.php?app_id=' + appId + '&itemName=' + itemName,
		success:function(data){
	        $('.basicImage').each(function(){
        		if($(this).attr('itemName') == itemName){
        			$(this).css('display', 'none');
        		}
        		
        	});
        	$('.noBasicImageBG').each(function(){
        		if($(this).attr('itemName') == itemName){
        			$(this).html('拖拉圖片至此上傳');
        			$(this).css('display', 'block');
        		}
        	});
	        $('.delIconBasicImage').each(function(){
	        	if($(this).attr('itemName') == itemName){
        			$(this).css('display', 'none');
        		}
	        });
		}
	});
	
}

function removeAppIcon(){
	$.ajax({
		url: 'removeAppIcon.php?app_id=' + appId,
		success:function(data){
	        $('#uploadedAppIcon').attr('src', '');   
	        $('#fileDragableArea').css('display','block');
	        $('#delIcon').css('display', 'none');
	        $('#iconHint').html('拖拉1024x1024圖檔至此');
	        $('.btnIconDownload').removeClass("enabledBtn").addClass("disabledBtn");
		}
	});
}

function saveAdditionalAttrs(saveBtn){
	var newData = {};
	$('#tblHyLibSettings input[type="checkbox"]').each(function(){
		newData[$(this).attr('attrName')] = ($(this).prop('checked'))?'true':'false';
	});
	
	//remove empty test accounts
	
	for(var i = testAccounts.length - 1; i >= 0; i--){
		if(testAccounts[i]['id'].length == 0 && testAccounts[i]['password'].length == 0){
			testAccounts.splice(i, 1);	
		}
	}
	
	newData.testAccounts = JSON.stringify(testAccounts);
	newData.relatedLinks = JSON.stringify(relatedLinks);
	
	var postData = {};
	postData.appId = appId;
	postData.newData = newData;
	postData.targetTable = 'app_additional_attrs';
	
	$.ajax({
		type:"POST",
		url:"AJAX_saveAppMetadata.php",
		data: JSON.stringify(postData),
		contentType: "application/json; charset=utf-8",
		dataType: "json",
		success:function(responseJSON){
			$(saveBtn).attr('class', 'savedBtn').val('已儲存');
			$('#lblAppName').html($('#txtName').val());
		},
		error:function(errObj) {
			alert('連線異常，資料未儲存');
			$(saveBtn).attr('class', 'saveBtn').val('儲存');
		},
		complete:function(data){
			
		}
	});
	
	
}

function saveAppData(tableId, saveBtn){
	var newData = {};
	$('#' + tableId + ' input[type="text"]').each(function(){
		newData[$(this).attr('dbColumn')] = $(this).val();
	});
	$('#' + tableId + ' input[type="radio"]:checked').each(function(){
		newData[$(this).attr('dbColumn')] = $(this).val();
	});
	$('#' + tableId + ' textarea').each(function(){
		newData[$(this).attr('dbColumn')] = $(this).val();
	});
	var postData = {};
	postData.appId = appId;
	postData.newData = newData;
	postData.targetTable = 'app';
	
	$.ajax({
		type:"POST",
		url:"AJAX_saveAppMetadata.php",
		data: JSON.stringify(postData),
		contentType: "application/json; charset=utf-8",
		dataType: "json",
		success:function(responseJSON){
			$(saveBtn).attr('class', 'savedBtn').val('已儲存');
			$('#lblAppName').html($('#txtName').val());
		},
		error:function(errObj) {
			alert('連線異常，資料未儲存');
			$(saveBtn).attr('class', 'saveBtn').val('儲存');
		},
		complete:function(data){
			
		}
	});
	
}




function loadAppData(){
	$.ajax({
		url:'AJAX_appMetadata.php?appId=' + appId,
		dataType: 'json',
		success: function(app){			
			$('#lblAppName').html(app.name);
			$('#txtName').val(app.name);
		}
	});
}


function uploadInstaller(file, dragingArea){
	var formData = new FormData();
	formData.append('file', file);
	formData.append('appId', appId);
	$(dragingArea).attr('uploading', 'true');
	$.ajax({
		url: 'getGSUploadUrl.php?purpose=uploadInstaller',
		success: function(response){
			var uploadUrl = response;
			
			var jqXHR=$.ajax({
			    xhr: function() {
			        var xhrobj = $.ajaxSettings.xhr();
			        if (xhrobj.upload) {
			        	
			            xhrobj.upload.addEventListener('progress', function(event) {
			                var percent = 0;
			                var position = event.loaded || event.position;
			                var total = event.total;
			                if (event.lengthComputable) {
			                    percent = Math.ceil(position / total * 100);
			                }
			                //Set progress
			                if(percent < 100){
				                $(dragingArea).html('上傳中...' + percent + '%');
				            }
				            else{
				            	$(dragingArea).html('處理中');
				            }
				            
			            }, false);
			        }
			        return xhrobj;
			    },
			    url: uploadUrl,
			    type: "POST",
			    contentType:false,
			    dataType:"json",
			    processData: false,
			    cache: false,
			    data: formData,
			    success: function(responseJson){
			        //status.setProgress(100);
					//alert('uploaded, response:' + data);
			        //$("#status1").append("File upload Done<br>");   
			        
			        
			        
					$(dragingArea).attr('uploading', 'false');
			        
			        if(responseJson.success){
			        	$(dragingArea).html(responseJson.oriFileName + ' 已上傳');
			        	$(dragingArea).attr('uploadedFileName', responseJson.tmpFileName);
			        	$(dragingArea).attr('oriFileName', responseJson.oriFileName);
			        	$(dragingArea).css('background-color', '');
			        	$(dragingArea).attr('class', 'uploaded');
			        	enableReleaseBtn();
			        	/*
			        	
				        $('#uploadedAppIcon').attr('src', 'getAppIcon.php?app_id=' + appId);   
				        $('#fileDragableArea').css('display','none');
				        $('#delIcon').css('display', 'block');
		        		$('.btnIconDownload').removeClass("disabledBtn").addClass("enabledBtn");
			        	if(responseJson.warningMsg != undefined){
			        		alert(responseJson.warningMsg);	
			        	}
			        	*/
			        }
			        else{
			        	if(responseJson.errorMsg != undefined){
			        		alert(responseJson.errorMsg);	
			        	}	
			        }
			        
			         
			    },
			    error:function(error){
			    	alert('error:' + error);
					$(dragingArea).attr('uploading', 'false');
			    }
			}); 
			
			
			
			
			
			
		},
		error:function(err){
			alert('error occured when getting upload url:' + err);
		}
		
	});
	
}

function enableReleaseBtn(){
	if($('#txtNewVersion').val().length > 0 && $('#txtWhatsNew').val().length > 0 && $('#installerUploadArea').attr('uploadedFileName').length > 0){
		if(!$('#btnRelease').hasClass('savingBtn')){
			$('#btnRelease').attr('class', 'enabledBtn');
		}
	}
	else{
		if(!$('#btnRelease').hasClass('savingBtn')){
			$('#btnRelease').attr('class', 'disabledBtn');
		}
	}
}

function releaseTestingInstaller(){
	if($('#btnRelease').hasClass('enabledBtn')){
		var formData = new FormData();
		formData.append('appId', appId);
		formData.append('version', $('#txtNewVersion').val());
		formData.append('whatsnew', $('#txtWhatsNew').val());
		formData.append('oriFileName', $('#installerUploadArea').attr('oriFileName'));
		formData.append('storeFileName', $('#installerUploadArea').attr('uploadedFileName'));
		
		$('#btnRelease').attr('class', 'savingBtn').val('發佈中');
		var jqXHR=$.ajax({
		    url: 'installerUploadHandler.php',
		    type: "POST",
		    contentType:false,
		    dataType:"json",
		    processData: false,
		    cache: false,
		    data: formData,
		    success: function(responseJson){
		        $('#txtNewVersion').val('');
		        $('#txtWhatsNew').val('');
		    	$('#installerUploadArea').attr('oriFileName', '');
		    	$('#installerUploadArea').attr('uploadedFileName', '');
		    	$('#installerUploadArea').attr('class', 'beforeUploading');
		    	$('#installerUploadArea').html('拖拉安裝檔於此上傳');
		    	$('#btnRelease').attr('class', 'disabledBtn').val('發佈');
		    	loadVersions();
		    },
		    error:function(error){
		    	alert('error:' + error);
		    	$('#btnRelease').attr('class', 'enabledBtn').val('發佈');
		    }
		});
		
	}
}



function loadVersions(){
	$.ajax({
		url:'AJAX_getVersionList.php?appId=' + appId,
		dataType: 'json',
		success: function(apps){
			$('#installerListContainer').html('');
			
			if(apps.length > 0){
				var versions = apps[0].versions;
				for(i = 0; i < versions.length; i++){
					var version = versions[i];
					var versionContainer = $('<div class="versionContainer"></div>');
					versionContainer.addClass((version.OS == 'iOS')?'iOSVersion':'AndroidVersion');
					var osIcon = $('<img/>').attr('height', '20').attr('src', (version.OS == 'iOS')?'images/Apple-icon-100.png':'images/Android-icon-100.png');
			
					$(versionContainer).append(osIcon);
					$(versionContainer).append(version.version).append($('<br/>'));
					
					var whatsNewContainer = $('<div class="whatsNewContainer"></div>');
					var uList = $('<ul/>');
					var whatsNewLines = version.whats_new.split("\n");
					for(var k = 0; k < whatsNewLines.length; k++){
						uList.append($('<li>' + whatsNewLines[k] + '</li>'));	
					}
					whatsNewContainer.append(uList);
					versionContainer.append(whatsNewContainer);
					
					var delIcon = $('<img class="delIconImage" src="images/icon_delete.png" ifid="' + version.ifid + '"/>');
					$(delIcon).click(function(){
						deleteVersion($(this).attr('ifid'));
						
					});
					versionContainer.append(delIcon);
					$('#installerListContainer').append(versionContainer);
					
				}
				
			}
			
		}
	});
}

function deleteVersion(ifid){
	var ans = confirm('確定要移除此版本？');
	if(ans){
		if(ifid.length > 0){
			console.log('ifid = ' + ifid);	
			var formData = new FormData();
			formData.append('ifid', ifid);
			$.ajax({
			    url: 'AJAX_deleteVersion.php',
			    type: "POST",
			    contentType:false,
			    dataType:"json",
			    processData: false,
			    cache: false,
			    data: formData,
			    success: function(responseJson){
			    	
			    	loadVersions();
			    }
			});
		}
	}
	
}


function loadPushTasks(){
	var formData = new FormData();
	$.ajax({
	    url: 'AJAX_recent_push_task.php?appId=' + appId + '&limitSize=30',
	    type: "GET",
	    contentType:false,
	    dataType:"json",
	    processData: false,
	    cache: false,
	    data: null,
	    success: function(responseJson){
	    	pushTasks = responseJson;
	    	//console.log(responseJson);
	    	console.log('task count = ' + pushTasks.length);
	    	displayPushTasks();
	    }
	});	
}

function displayPushTasks(){
	var table = $('<table/>');
	var headerRow = $('<tr/>');
	$(headerRow).append('<td>IP</td>');
	$(headerRow).append('<td>message</td>');
	$(table).append(headerRow);
	
	$('#pushRecordContainer').html('');
	$('#pushRecordContainer').append(table);
		
	
}