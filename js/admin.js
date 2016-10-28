var Apps;

$(document).ready(function(){
	//loadAppList();
	setEvents();
});


function editApp(obj){
	location.href = 'editApp.php?appId=' + $(obj).attr('appId');
	
}

function setEvents(){
	$('#searchBar').on('input', function() {
		var curText = $(this).val();
		
		$('.appContainer').each(function(){
			if(curText.length > 0){
				
				var container = $(this);
				$(container).find('.appName').each(function(){
					var appName = $(this).html();
					if(appName.indexOf(curText) >= 0){
						$(container).css('display', 'inline-block');
					}
					else{
						$(container).css('display', 'none');
					}
					return false;
					
				});
			}
			else{
				$(this).css('display', 'inline-block');
			}
			
		});
		
	});
	
}

function loadAppList(){
	$.ajax({
		url:'AJAX_appList.php',
		dataType: 'json',
		success: function(data){			
			Apps = data;
			for(var i = 0; i < Apps.length; i++){
				var appId = Apps[i].AppId;
				var appName = Apps[i].name;
				var appContainer = $('<div/>').addClass('appContainer').attr('appId', appId);
				appContainer.append($('<img src="getAppIcon.php?app_id=' + appId + '&size=328" width="164" height="164" class="appIcon"/><br/>'));
				appContainer.append($('<span>' + appName + '</span>'));
				appContainer.click(function(){
					location.href = 'editApp.php?appId=' + $(this).attr('appId');
				});
				$('#appsContainer').append(appContainer);
			}
		}
	});
}
