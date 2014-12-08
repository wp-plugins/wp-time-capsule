jQuery(document).ready(function ($) {
	get_and_store_before_backup_var();
	
	jQuery(".plugin-update-tr .update-message a, .available-theme .action-links p a, #current-theme a").on("click", function(e){
		e.preventDefault();
		e.stopImmediatePropagation();
		if(jQuery(this).text() == 'update now')
		{
			//console.log("blah");
			check_to_show_dialog(jQuery(this));
		}
	});
	
	jQuery(".theme-wrap .theme-update-message p a").on("click", function(e){
//		console.log("moooooo");
		e.preventDefault();
		e.stopImmediatePropagation();
		if(jQuery(this).text() == 'update now')
		{
			//console.log("blah");
			check_to_show_dialog(jQuery(this));
		}
	});
	
        
	jQuery(".update-nag a").on("click", function(e){
		//console.log("blah above");
		e.preventDefault();
		e.stopImmediatePropagation();
		if(jQuery(this).text() == 'Please update now')
		{
			//console.log("blah");
			check_to_show_dialog(jQuery(this));
		}
	});
	
	jQuery(".theme-screenshot").on("click", function(e){
		//console.log('clicking theme screenshot');
		/* //console.log("must be registering after 3 secs lets see");
		//registering events after 2 secs
		setInterval(function(){
			//console.log("am i registering");
			jQuery(".theme-wrap .theme-update-message p a").on("click", function(e){
			//console.log("moooooo");
			e.preventDefault();
			e.stopImmediatePropagation();
			if(jQuery(this).text() == 'update now')
			{
				//console.log("blah");
				check_to_show_dialog(jQuery(this));
			}
		}); },3000); */
		
		/* if(jQuery(this).text() == "Update Available"){
			reg_dashboard_update = jQuery(this).text();
			reg_theme_name_id = jQuery(this).siblings("theme-name").attr("id");				//used for continuing theme update after backup.
			//console.log(reg_theme_name_id);
			if((tc_prevent_default_event != "undefined" && tc_prevent_default_event == 'yes') && (typeof current_update_action == "undefined")){
				e.preventDefault();
				e.stopImmediatePropagation();
				//console.log("blah");
				check_to_show_dialog(jQuery(this));
			}
			else if(typeof current_update_action != "undefined" && current_update_action == "no"){
				delete current_update_action;
			}
		} */
	});
	
	jQuery(".upgrade input").on("click", function(e){
		//console.log("blah above");
		if((jQuery(this).val() == 'Update Themes')||(jQuery(this).val() == "Update Plugins")||(jQuery(this).val() == "Update Now"))
		{
			reg_dashboard_update = jQuery(this).val();
			if((tc_prevent_default_event != "undefined" && tc_prevent_default_event == 'yes') && (typeof current_update_action == "undefined")){
				e.preventDefault();
				e.stopImmediatePropagation();
				//console.log("blah");
				check_to_show_dialog(jQuery(this));
			}
			else if(typeof current_update_action != "undefined" && current_update_action == "no"){
				delete current_update_action;
			}
		}
	});
        
        jQuery(".tablenav #doaction").on("click", function(e){
		if((jQuery(this).val() == 'Apply')&&(jQuery('.bulkactions #bulk-action-selector-top').val() == "update-selected"))
		{
			if((tc_prevent_default_event != "undefined" && tc_prevent_default_event == 'yes') && (typeof current_update_action == "undefined")){
				if(jQuery(this).prev("select").val() == "update-selected"){
					e.preventDefault();
					e.stopImmediatePropagation();
					//console.log("blah");
					check_to_show_dialog(jQuery(this));
				}
			}
			else if(typeof current_update_action != "undefined" && current_update_action == "no"){
				delete current_update_action;
			}
		}
	});
        
	
	jQuery(".tablenav #doaction2").on("click", function(e){
		if(jQuery(this).val() == 'Apply')
		{
			reg_dashboard_update = 'pluignBulkUpdate';
			if((tc_prevent_default_event != "undefined" && tc_prevent_default_event == 'yes') && (typeof current_update_action == "undefined")){
				if(jQuery(this).prev("select").val() == "update-selected"){
					e.preventDefault();
					e.stopImmediatePropagation();
					//console.log("blah");
					check_to_show_dialog(jQuery(this));
				}
			}
			else if(typeof current_update_action != "undefined" && current_update_action == "no"){
				delete current_update_action;
			}
		}
	});
	
	jQuery("#wpfooter a").on("click", function(e){
		if(jQuery(this).text() == 'Get Version 4.0')
		{
			e.preventDefault();
			e.stopImmediatePropagation();
			//console.log("blah");
			check_to_show_dialog(jQuery(this));
		}
	});
	
	jQuery("#wp-version-message a").on("click", function(e){
		if(jQuery(this).text() == 'Update to 4.0')
		{
			e.preventDefault();
			e.stopImmediatePropagation();
			//console.log("blah");
			check_to_show_dialog(jQuery(this));
		}
	});
	
});

function get_and_store_before_backup_var(){
	//console.log('calling moi');
	jQuery.post(ajaxurl, { action : 'get_and_store_before_backup', data : '' }, function(data) {
			//console.log('get_and_store_before_backup_var');
			//console.log(data);
			tc_prevent_default_event = data;			//this will have the value either yes or no
		});
}

function check_to_show_dialog(obj){
	//to show the backup dialog box before updating plugins , themes etc
	//console.log(tc_prevent_default_event);
	if(tc_prevent_default_event == 'yes'){								//only when the setting is not never; show the dialog box
		//console.log("bubublububu");
		jQuery.post(ajaxurl, { action : 'get_check_to_show_dialog' }, function(data) {
			data = jQuery.parseJSON(data.slice(0,-1));
			//console.log(data);
			//data['before_backup'] = 'yes_no';
			if(typeof data != 'undefined')
			{
				if(data['before_backup'] == 'yes_no'){
					show_is_backup_dialog_box_tc(obj);
				}
				if(data['before_backup'] == 'yes'){
					show_is_backup_dialog_box_tc(obj, 'yes');
					//jQuery(".tc_backup_before_update").click();
					show_backup_progress_dialog(obj, '');
				}
			}
		});
	}
	else{
		//console.log('else part');
		//console.log(obj.attr("href"));
		if((typeof obj.attr("href") != 'undefined') && obj.attr("href") != ''){
			window.location = obj.attr("href");
		}
	}
}


function show_is_backup_dialog_box_tc(obj, direct_backup){
	//this function shows the dialog box to choose backup before updating
	jQuery("#dialog_content_id, .thickbox").remove();
	jQuery(".wrap").append('<div id="dialog_content_id" style="display:none;"> <p> hidden cont. </p></div><a class="thickbox" style="display:none" href="#TB_inline?width=500&height=500&inlineId=dialog_content_id&modal=true"></a>');
	
	//store the update link in a global variable
	//this_update_link = obj.attr("href");
	
	var dialog_content = '<div class="this_modal_div" style="background-color: #f1f1f1;font-family: \'open_sansregular\' !important;color: #444;padding: 0px 34px 26px 34px;"><span class="dialog_close"></span><div class="pu_title">UPDATING ITEMS</div><div class="wcard clearfix" style="width:480px"><div class="l1">Do you want to backup your website before updating?</div>  <a class="btn_pri tc_backup_before_update" update_link='+obj.attr("href")+' >YES, BACKUP &amp; UPDATE</a><a class="btn_sec tc_no_backup " href='+obj.attr("href")+' >NO, JUST UPDATE</a> </div></div>';
	
	//thick box
	jQuery("#dialog_content_id").html(dialog_content);
	jQuery(".thickbox").click();
	if((typeof direct_backup != 'undefined') && (direct_backup == 'yes')){
		styling_thickbox_tc('progress');
	}
	else{
		styling_thickbox_tc('backup_yes_no');
	}
	
	//registering the events
	jQuery(".tc_backup_before_update").on("click", function(e){
                this_update_link=obj.attr("href");
		show_backup_progress_dialog(jQuery(this));
	});
	
	jQuery(".tc_no_backup").on("click", function(e){
		e.preventDefault();
		//console.log(this_update_link);
		/* var orgin_link = window.location.href;
		var orgigin_len = orgin_link.length - 11;
		//console.log(window.location);
		//console.log(window.location.origin);
		var supposed_link = this_update_link.substring(orgigin_len);
		//console.log(supposed_link); */
		if(typeof this_update_link != "undefined" && this_update_link != '' && this_update_link != "undefined"){
			window.location = this_update_link;
		}
		else{
			if((obj.val() == 'Update Themes')||(obj.val() == "Update Plugins")||(obj.val() == "Update Now")||(obj.val() == "Apply")){
				//console.log('ringa rose');
				current_update_action = 'no';				//this global variable is used only for upgrade-input related updates; continuing update after backup process
				jQuery(obj).click();
			}
		}
	});
	
	jQuery(".dialog_close").on("click", function(){
		tb_remove();
	});
	
}

function show_backup_progress_dialog(obj, type){
	//this function updates the progress bar in the dialog box ; during backup
	var dialog_content = '<div class="this_modal_div" style="background-color: #f1f1f1;font-family: \'open_sansregular\' !important;color: #444;padding: 0px 35px 35px 35px; width: 450px;"><span class="dialog_close" style="display:none"></span><div class="wcard hii backup_progress_tc" style="height:60px; padding:0; margin-top:35px; display:inline-block;"><div class="progress_bar" style="width:0.0002%;"></div> <div class="progress_cont">Backing up files before updating...</div></div></div>';
	
	if(type == 'fresh')
	{
		jQuery("#dialog_content_id").html(dialog_content);  //since it is the first call we are generating thickbox like this
		jQuery(".thickbox").click();
		jQuery(".progress_cont").text("Backing up your files...");
		styling_thickbox_tc('progress');
		start_backup_before_update = true;
	}
	else
	{
		jQuery("#TB_ajaxContent").html(dialog_content);
		styling_thickbox_tc('backup_yes');
	}
	
	if(type != 'fresh'){
		//calling the ajax function to perform backup
		jQuery.post(ajaxurl, { action : 'start_backup_tc' }, function(data) {	
		});
		start_backup_before_update = true;
	}
	
	backup_progress_tc();
	
	jQuery("#TB_overlay").on("click", function(){
		//console.log("clicky");
		if(typeof is_backup_completed != 'undefined' && is_backup_completed == true){					//for enabling dialog close on complete
			tb_remove();
		}
	});
	
	jQuery(".dialog_close").on("click", function(){
		tb_remove();
	});
}

function dialogOpenCallBackTC(){
	
}

function dialogCloseCallBackTC(){
	
}

function backup_progress_tc(){
	if(typeof start_backup_before_update != 'undefined' && start_backup_before_update == true)
	{
		reload_backup_tc();
	}
	else
	{
		jQuery.post(ajaxurl, { action : 'get_in_progress_backup' }, function(data) {
			//check if backup progress is going on in the backend; if so start reload_backup_tc() func;
			//console.log(data);
			if(typeof data != 'undefined' && data != null){
				if(data){
					reload_backup_tc();
				}
			}
		});
	}
}

function dialog_for_changeAccount(){
    var dialog_content = '<div class="this_modal_div" style="background-color: #f1f1f1;font-family: \'open_sansregular\' !important;color: #444;padding: 0px 34px 26px 34px;"><span class="dialog_close"></span><div class="pu_title">Disconnect this account?</div><div class="wcard clearfix" style="width:480px"><div class="l1">The files in your dropbox account will not be affected.<br/>But all data in the plugin will be lost.</div><a class="btn_pri" id="yes_change_acc" onclick="yes_change_acc()">Yes. Let me connect another account.</a><a class="btn_sec" id="no_change" onclick="no_change()">Cancel</a></div></div>';
    jQuery("#dialog_content_id").html(dialog_content);  //since it is the first call we are generating thickbox like this
		jQuery(".thickbox").click();
                styling_thickbox_tc('change_account');
}
function reload_backup_tc() {
	//this function runs every 5 sec as long as there is a backup process running and also fills the progress bar
	//jQuery('.files').hide();
	jQuery.post(ajaxurl, { action : 'progress' }, function(data) {
		if (data.length) {
			data = jQuery.parseJSON(data);
			//console.log(data);
			if(typeof data == 'undefined'){
				//console.log('error');
			}
			else if(typeof data != 'undefined' && typeof data['backup_progress'] != 'undefined' && data['backup_progress'] != ''){
				
				var this_text = '';
				var progress_percent = 1;
                                var totalfiles=0;
                                var processedfiles=0;
				if(typeof data['backup_progress'] != 'undefined'){
					jQuery.each(data, function(k, v){
						this_text_processed_files = v['processed_files'];
                                                if (v['overall_files'] != null && v['overall_files'] !== undefined) {
                                                    totalfiles = v['overall_files'];
                                                }
                                                if (v['processed_totcount'] != null && v['processed_totcount'] !== undefined) {
                                                    processedfiles = v['processed_totcount'];
                                                }
						progress_percent = v['progress_percent'];
					});
				}
				if(progress_percent * 100 >= 100){
					progress_percent = 1;
				}
				jQuery('.wcard.backup_progress_tc .progress_cont').html('Backing up your files... [ '+processedfiles+' / '+totalfiles+' ]');
				jQuery('.wcard.backup_progress_tc .progress_bar').attr("style", "width:" + progress_percent * 100 + "%");
				
				//show laoding div in calendar box
				jQuery('.tc-backingup-loading').remove();
				jQuery('.fc-today div').hide();
				jQuery('.fc-today').append('<div class="tc-backingup-loading"></div>');
				is_backup_completed = false;					//setting global variable for backup completing status; used for dialog box close
				is_backup_started = true;
			}
			else if(data['backup_progress'] == '')
			{
				if(typeof reload_backup_tc_timeout != 'undefined'){
					if(typeof start_backup_before_update != 'undefined' && start_backup_before_update == true)
					{
						//small fix for backup completing during - backup-before-update 
						start_backup_before_update = false;
					}
					else
					{
						//revert the loading symbol in calendar box
						jQuery('.tc-backingup-loading').remove();
						jQuery('.fc-today div').show();
						clearTimeout(reload_backup_tc_timeout);
						jQuery('.wcard.backup_progress_tc .progress_bar').attr("style", "width:100%");
						jQuery('.wcard.backup_progress_tc .progress_cont').text('Backup Completed');
						
						//changing the button back to Backup Now
						jQuery("#start_backup").text("Backup Now");
						jQuery("#stop_backup").text("Backup Now");
						//jQuery(".dialog_close").show();
						//var this_html = '<div class="notif s">Yaay! Your site is backed up. :)</div>';
						//jQuery("#TB_ajaxContent").html(this_html);
						is_backup_completed = true;						//setting global variable for backup completing status; used for dialog box close
						is_backup_started = false;
						
						if(typeof this_update_link != 'undefined')
						{
							window.location = this_update_link;
						}
						else{
							current_update_action = 'no';
							if(typeof reg_dashboard_update != 'undefined'){
								if(reg_dashboard_update == 'Update Now'){
									jQuery(".upgrade #upgrade").click();
								}
								else if(reg_dashboard_update == "Update Available"){
									//console.log(reg_theme_name_id);
									var theme_id_obj = "#" + reg_theme_name_id;				//for continuing theme update
									jQuery(theme_id_obj).siblings(".theme-update").click();
								}
								else if(reg_dashboard_update == "Update Themes"){
									jQuery(".upgrade #upgrade-themes").click();				//for continuing bulk theme update on dashboard page
								}
                                                                else if(reg_dashboard_update == "pluignBulkUpdate"){
									jQuery(".tablenav #doaction").click();					//for continuing bulk plugin update on plugin page
								}
								else if(reg_dashboard_update == "pluignBulkUpdate"){
									jQuery(".tablenav #doaction2").click();					//for continuing bulk plugin update on plugin page
								}
								else{
									jQuery(".upgrade #upgrade-plugins").click();			//for continuing bulk plugin update on dashboard page
								}
							}
							else{
								jQuery(".upgrade #upgrade-plugins").click();
							}
						}
					}
				}
			}
		}
	});
	reload_backup_tc_timeout = setTimeout("reload_backup_tc()", 5000);
}

function styling_thickbox_tc(styleType){
	//this function is for styling the whole dialog box thing according to various needs
	jQuery("#TB_title").hide();
	//console.log(styleType);
	if(styleType == 'progress')
	{
		jQuery("#TB_window").width("518px");
		jQuery("#TB_ajaxContent").width("518px");
		jQuery("#TB_ajaxContent").css("padding", "0px");
		jQuery("#TB_ajaxContent").css("overflow", "hidden");
		//jQuery("#TB_ajaxContent").css("max-height", "322px");
		jQuery("#TB_ajaxContent").css("height", "auto");
	}
	else if(styleType == 'backup_yes')
	{
		jQuery("#TB_window").width("518px");
		jQuery("#TB_ajaxContent").width("518px");
		jQuery("#TB_ajaxContent").css("padding", "0px");
		jQuery("#TB_ajaxContent").css("overflow", "hidden");
		jQuery("#TB_window").css("height", "auto");
		jQuery("#TB_ajaxContent").css("height", "auto");
		jQuery("#TB_window").css("margin-top", "66px");
		jQuery("#TB_ajaxContent").css("max-height", "322px");
		jQuery("#TB_window").css("max-height", "322px");
	}
	else if(styleType == 'backup_yes_no'){
		jQuery("#TB_window").width("578px");
		jQuery("#TB_ajaxContent").width("578px");
		jQuery("#TB_ajaxContent").css("padding", "0px");
		jQuery("#TB_ajaxContent").css("overflow", "hidden");
		jQuery("#TB_ajaxContent").css("height", "auto");
		jQuery("#TB_window").css("height", "auto");
		jQuery("#TB_window").css("margin-top", "66px");
		jQuery("#TB_window").css("max-height", "274px");
		jQuery("#TB_ajaxContent").css("max-height", "274px");
		jQuery("#TB_window").css("max-width", "578px");
	}
	else if(styleType == 'restore'){
		jQuery("#TB_window").width("518px");
		jQuery("#TB_ajaxContent").width("518px");
		jQuery("#TB_ajaxContent").css("padding", "0px");
		jQuery("#TB_ajaxContent").css("overflow", "hidden");
		jQuery("#TB_ajaxContent").css("max-height", "322px");
		jQuery("#TB_ajaxContent").css("height", "auto");
	}
        else if(styleType == 'change_account'){
                jQuery("#TB_window").width("578px");
                jQuery("#TB_ajaxContent").width("578px");
                jQuery("#TB_ajaxContent").css("padding", "0px");
		jQuery("#TB_ajaxContent").css("overflow", "hidden");
		jQuery("#TB_ajaxContent").css("max-height", "500px");
		jQuery("#TB_ajaxContent").css("height", "auto");
        }
	else
	{
		jQuery("#TB_window").width("791px");
		jQuery("#TB_ajaxContent").width("791px");
		jQuery("#TB_ajaxContent").css("padding", "0px");
		jQuery("#TB_ajaxContent").css("height", "auto");
		//jQuery("#TB_window").css("overflow", "hidden");
		var this_height = (jQuery(window).height() * .8) + "px";
		jQuery("#TB_ajaxContent").css("max-height", this_height);
		var win_height = (jQuery("#TB_ajaxContent").height() / 2) + "px";
		jQuery("#TB_window").css("margin-top", "-" + win_height);
	}
	/* if(styleType != 'backup_yes_no' && styleType != 'backup_yes'){
		var this_height = (jQuery(window).height() * .8) + "px";
		jQuery("#TB_ajaxContent").css("max-height", this_height);
		var win_height = (jQuery("#TB_ajaxContent").height() / 2) + "px";
		jQuery("#TB_window").css("margin-top", "-" + win_height);
	} */
}
