<?php
/**
 * This file contains the contents of the Dropbox admin monitor page.
 *
 * @copyright Copyright (C) 2011-2014 Awesoft Pty. Ltd. All rights reserved.
 * @author Michael De Wildt (http://www.mikeyd.com.au/)
 * @license This program is free software; you can redistribute it and/or modify
 *          it under the terms of the GNU General Public License as published by
 *          the Free Software Foundation; either version 2 of the License, or
 *          (at your option) any later version.
 *
 *          This program is distributed in the hope that it will be useful,
 *          but WITHOUT ANY WARRANTY; without even the implied warranty of
 *          MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *          GNU General Public License for more details.
 *
 *          You should have received a copy of the GNU General Public License
 *          along with this program; if not, write to the Free Software
 *          Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110, USA.
 */
$config = WPTC_Factory::get('config');
$backup = new WPTC_BackupController();

$backup->create_dump_dir();  			//creating backup folder in the beginning if its not there
$schedule_backup_run=$config->get_option('schedule_backup_running');
$schedule_backup = $config->get_option('schedule_backup');
//Checking fresh backup 
global $wpdb;
$fcount=$wpdb->get_results( 'SELECT COUNT(*) as files FROM '.$wpdb->prefix.'wptc_processed_files' );
if(!($fcount[0]->files > 0))
    $fresh="yes";
else
    $fresh="no";


if (array_key_exists('stop_backup', $_POST)) {
    check_admin_referer('wordpress_time_capsule_monitor_stop');
    $backup->stop();

    add_settings_error('wptc_monitor', 'backup_stopped', __('Backup stopped.', 'wptc'), 'updated');

} elseif (array_key_exists('start_backup', $_POST)) {
    check_admin_referer('wordpress_time_capsule_monitor_stop');
    $backup->backup_now();
	//$backup->restore_execute();
    $started = true;
    add_settings_error('wptc_monitor', 'backup_started', __('Backup started.', 'wptc'), 'updated');
}
//Initial backup option
$freshbackupPopUp=false;
if(isset($_GET['action']))
{
    $initial_setup=$_GET['action'];
}
if($fresh=='yes'&&$initial_setup=='initial_setup')
{
    $freshbackupPopUp = true;
}
?>
<link href='<?php echo $uri;?>/fullcalendar-2.0.2/fullcalendar.css' rel='stylesheet' />
<link href='<?php echo $uri;?>/fullcalendar-2.0.2/fullcalendar.print.css' rel='stylesheet' media='print' />
<link href='<?php echo $uri;?>/tc-ui.css' rel='stylesheet' />
<script src='<?php echo $uri;?>/fullcalendar-2.0.2/lib/moment.min.js'></script>
<script src='<?php echo $uri;?>/fullcalendar-2.0.2/fullcalendar.js'></script>
<?php add_thickbox(); ?>
<script type="text/javascript" language="javascript">
    var sitename='<?php echo get_bloginfo( 'name' );?>';
    var fresh='<?php echo $fresh;?>';
    function wtc_reload() {
		//console.log('calling reload');
		jQuery('.files').hide();
        jQuery.post(ajaxurl, { action : 'progress' }, function(data) {
            if (data.length) {
                jQuery('#progress').html('<div class="calendar_wrapper"></div>');
				jQuery("#progress").append('<div id="dialog_content_id" style="display:none;"> <p> This is my hidden content! It will appear in ThickBox when the link is clicked. </p></div><a class="thickbox" style="display:none" href="#TB_inline?width=500&height=500&inlineId=dialog_content_id&modal=true"></a>');
				
				data = jQuery.parseJSON(data);
				//console.log(data);
				if(typeof data != 'undefined')
				{
					if((typeof data['backup_progress'] != 'undefined')&&(typeof backup_end != 'undefined')){
						if((data['backup_progress'] == '')&&(backup_end == true))
						{
							jQuery("#start_backup").val('Backup Now');
						}
					}
				}
				jQuery('.calendar_wrapper').fullCalendar({
					theme: false,
					header: {
						left: 'prev,next today',
						center: 'title',
						right: 'month,agendaWeek,agendaDay'
					},
					defaultDate: '<?php echo date('Y-m-d', microtime(true)) ?>',
					editable: false,
					events: data['stored_backups'],
				});
				//org function not required
                /* jQuery('.view-files').on('click', function() {
                    $files = jQuery(this).next();

                    $files.toggle();
                    $files.find('li').each(function() {
                        $this = jQuery(this);
                        $this.css(
                            'background',
                            'url(<?php echo $uri ?>/JQueryFileTree/images/' + $this.text().slice(-3).replace(/^\.+/,'') + '.png) left top no-repeat'
                        );
                    });

                }); */
				/* jQuery('.backup_content_tc').on('click', function(ev) {
					//console.log('clicking');
					var thisDayBackups = getThisDayBackups(jQuery(this).attr("backupids"));
				});  */
				
				
            }
        });
		
		reloadFuncTimeout = setTimeout(function(){wtc_reload();}, 15000);
		
		/* started_fresh_backup = '<?php //echo WPTC_Factory::get('config')->get_option('in_progress');?>';
		if(started_fresh_backup && started_fresh_backup == '1')
		//if((typeof started_fresh_backup != 'undefined')&&(started_fresh_backup != false))
		{
			reloadFuncTimeout = setTimeout(function(){wtc_reload();}, 5000);
			started_fresh_backup = false;
		} */
		
    }
	
	function wtc_stop_backup_func(){
		var this_obj = jQuery(this);
		jQuery.post(ajaxurl, { action : 'stop_fresh_backup_tc' }, function(data) {
			//call the reload function which tells the progress
			//wtc_reload();
			jQuery('#start_backup').text("Stop Backup");
			jQuery(this_obj).hide();
			window.location = '<?php echo admin_url('?page=wp-time-capsule-monitor'); ?>';
		});
	}
	
	function getThisDayBackups_old(backupIds){
		/* jQuery.post(ajaxurl, { action : 'get_this_day_backups', data : backupIds }, function(data) {
			jQuery("#modalDiv").dialog("close");
			jQuery("#modalDiv").dialog("destroy");
			jQuery('#modalDiv').html(data).dialog({width:'auto',modal:true,position: 'center',resizable: false, open: function(event, ui) { dialogOpenCallBack();},close: function(event, ui) { dialogCloseCallBack(); jQuery("#modalDiv").html(''); }});
			
			//do the UI action to hide the folders, display the folders based on tree
			jQuery(".this_parent_node .sub_tree_class").hide();
			jQuery(".this_parent_node .this_leaf_node").hide();
			jQuery(".sub_tree_class.sl0").show();
			
			//UI actions for the file selection
			jQuery(".single_group_backup_content").on("click", function(){
				if(!jQuery(this).hasClass("open"))
				{
					jQuery(this).addClass("open");
				}
				else
				{
					jQuery(this).removeClass("open");
				}
			});
			
			jQuery(".sub_tree_class_blah").on("click", function(){
				var main_parent = jQuery(this).closest(".this_parent_node");
				if(!jQuery(this).hasClass("parent_select"))
				{
					
					jQuery(">.folder", this).removeClass("close").addClass("open");
					//show the current folder contents
					jQuery(this).addClass("parent_select");
					jQuery(">.this_parent_node >.sub_tree_class", main_parent).show();
					jQuery(">.this_parent_node >.this_leaf_node", main_parent).show();
				}
				else
				{
					jQuery(">.folder", this).removeClass("open").addClass("close");
					//hide the current folder contents
					jQuery(this).removeClass("parent_select");
					jQuery(">.this_parent_node >.sub_tree_class", main_parent).hide();
					jQuery(">.this_parent_node >.this_leaf_node", main_parent).hide();
				}
				return false;
			});
			
			jQuery(".folder").on("click", function(){
				var main_parent = jQuery(this).closest(".this_parent_node");
				if(!jQuery(this).hasClass("parent_select"))
				{
					
					jQuery(this).removeClass("close").addClass("open");
					//show the current folder contents
					jQuery(this).addClass("parent_select");
					jQuery(">.this_parent_node >.sub_tree_class", main_parent).show();
					jQuery(">.this_parent_node >.this_leaf_node", main_parent).show();
				}
				else
				{
					jQuery(this).removeClass("open").addClass("close");
					//hide the current folder contents
					jQuery(this).removeClass("parent_select");
					jQuery(">.this_parent_node >.sub_tree_class", main_parent).hide();
					jQuery(">.this_parent_node >.this_leaf_node", main_parent).hide();
					jQuery(".folder.open", main_parent).click();
				}
				return false;
			});
			
			jQuery(".sub_tree_class").on("click", function(){
				var main_parent = jQuery(this).closest(".this_parent_node");
				if(!jQuery(this).hasClass("selected"))
				{
					
					//jQuery(">.folder", this).removeClass("close").addClass("open");
					//show the current folder contents
					//jQuery(this).addClass("parent_select");
					jQuery(this).addClass("selected");
					jQuery(">.this_parent_node li", main_parent).removeClass('selected');
					jQuery(">.this_parent_node li", main_parent).addClass('selected');
					//jQuery(">.this_parent_node >.this_leaf_node", main_parent).show();
				}
				else
				{
					//jQuery(">.folder", this).removeClass("open").addClass("close");
					//hide the current folder contents
					//jQuery(this).removeClass("parent_select");
					jQuery(this).removeClass("selected");
					jQuery(">.this_parent_node li", main_parent).removeClass('selected');
					//jQuery(">.this_parent_node >.this_leaf_node", main_parent).hide();
				}
				return false;
			});
			
			jQuery(".this_leaf_node li").on("click", function(){
				
			});
			
			jQuery("li.sub_tree_class:before").on("click", function(){
				//console.log("hii");
				if(!jQuery(this).hasClass('selected'))
				{
					jQuery(this).addClass('selected');
				}
				else
				{
					jQuery(this).removeClass('selected');
				}
				return false;
			});
		}); */
		
	}
	
	function getThisDayBackups(backupIds){
              
                var loading = '<div class="dialog_cont" style="padding:2%"><div class="loaders"><div class="loader_strip"><div class="loader_strip_cl" style="background:url(<?php echo plugins_url('wp-time-capsule');?>/images/loader_line.gif)"></div></div></div></div>';
                jQuery("#dialog_content_id").html(loading);
                jQuery(".thickbox").click();
                styling_thickbox_tc();
                registerDialogBoxEventsTC();
                
                
		//to show all the backup list when a particular date is clicked
		jQuery.post(ajaxurl, { action : 'get_this_day_backups', data : backupIds }, function(data) {
			jQuery(".dialog_cont").remove();
			jQuery("#dialog_content_id").html(data);
			jQuery(".thickbox").click();
			
			styling_thickbox_tc();
			registerDialogBoxEventsTC();
			
			//do the UI action to hide the folders, display the folders based on tree
			jQuery(".this_parent_node .sub_tree_class").hide();
			jQuery(".this_parent_node .this_leaf_node").hide();
			jQuery(".this_leaf_node.leaf_0").show();
			jQuery(".sub_tree_class.sl0").show();
			
			//for hiding the backups folder and its sql-file
			var sqlFileParent = jQuery(".sql_file").parent(".this_parent_node");
			jQuery(sqlFileParent).hide();
			//jQuery(sqlFileParent).parent(".this_parent_node").hide();
			//jQuery(sqlFileParent).parent(".this_parent_node").prev(".sub_tree_class").hide();
			jQuery(sqlFileParent).prev(".sub_tree_class").hide();
		});
		
		
		
	}
	
	function registerDialogBoxEventsTC(){
		jQuery.curCSS = jQuery.css;
		jQuery('.checkbox_click').on('click', function() {
			
			if(!(jQuery(this).hasClass("active")))
			{
				jQuery(this).addClass("active");
			}
			else
			{
				jQuery(this).removeClass("active");
			}
		});
		
		jQuery('.single_backup_head').on('click', function() {
			var this_obj = jQuery(this).closest(".single_group_backup_content");
			
			if(!(jQuery(this).hasClass("active")))
			{
				jQuery(".single_backup_content_body",this_obj).show();
			}
			else
			{
				jQuery(".single_backup_content_body",this_obj).hide();
			}
		});
		
		//UI actions for the file selection
		jQuery(".toggle_files").on("click", function(e){
			var par_obj = jQuery(this).closest(".single_group_backup_content");
			if(!jQuery(par_obj).hasClass("open"))
			{
				//close all other restore tabs ; remove the active items 
				jQuery(".this_leaf_node li").removeClass("selected");
				jQuery(".toggle_files.selection_mode_on").click();
				
				jQuery(par_obj).addClass("open");
				jQuery(".changed_files_count, .this_restore", par_obj).show();
				jQuery(".this_restore_point", par_obj).hide();
				jQuery(this).addClass("selection_mode_on");
			}
			else
			{
				jQuery(par_obj).removeClass("open");
				jQuery(".changed_files_count, .this_restore", par_obj).hide();
				jQuery(".this_restore_point", par_obj).show();
				jQuery(this).removeClass("selection_mode_on");
			}
                        e.stopImmediatePropagation();
			styling_thickbox_tc("");
                        return false;
                       
		});
		
		jQuery(".sub_tree_class_blah").on("click", function(){
			var main_parent = jQuery(this).closest(".this_parent_node");
			if(!jQuery(this).hasClass("parent_select"))
			{
				
				jQuery(">.folder", this).removeClass("close").addClass("open");
				//show the current folder contents
				jQuery(this).addClass("parent_select");
				jQuery(">.this_parent_node >.sub_tree_class", main_parent).show();
				jQuery(">.this_parent_node >.this_leaf_node", main_parent).show();
			}
			else
			{
				jQuery(">.folder", this).removeClass("open").addClass("close");
				//hide the current folder contents
				jQuery(this).removeClass("parent_select");
				jQuery(">.this_parent_node >.sub_tree_class", main_parent).hide();
				jQuery(">.this_parent_node >.this_leaf_node", main_parent).hide();
			}
			return false;
		});
		
		jQuery(".folder").on("click", function(e){
			var main_parent = jQuery(this).closest(".sub_tree_class");
			main_parent = jQuery(main_parent).next('.this_parent_node');
			if(!jQuery(this).hasClass("parent_select"))
			{
				
				jQuery(this).removeClass("close").addClass("open");
				//show the current folder contents
				jQuery(this).addClass("parent_select");
				jQuery(">.sub_tree_class", main_parent).show();
				jQuery(">.this_leaf_node", main_parent).show();
				
				//except the backup folder  - sql file ---trick to hide the backup folder
				var sql_file_root_main_parent = jQuery(".sql_file", main_parent).parent(".this_parent_node").parent(".this_parent_node");
				if((jQuery(".sub_tree_class", sql_file_root_main_parent).length + jQuery(".this_leaf_node", sql_file_root_main_parent).length) == 2){
					jQuery(".sql_file", main_parent).parent(".this_parent_node").prev(".sub_tree_class").hide();
					jQuery(".sql_file", main_parent).parent(".this_parent_node").parent(".this_parent_node").hide();
					jQuery(".sql_file", main_parent).parent(".this_parent_node").parent(".this_parent_node").prev(".sub_tree_class").hide();
				}
			}
			else
			{
				jQuery(this).removeClass("open").addClass("close");
				//hide the current folder contents
				jQuery(this).removeClass("parent_select");
				jQuery(".sub_tree_class", main_parent).hide();
				jQuery(".this_leaf_node", main_parent).hide();
				jQuery(".folder.open", main_parent).removeClass("open").addClass("close").removeClass("parent_select");
			}
			e.stopImmediatePropagation();
			styling_thickbox_tc("");				//to set margin-auto dynamic
			return false;
		});
		
		jQuery(".sub_tree_class").on("click", function(){
			var main_parent = jQuery(this).closest(".this_parent_node");
			var top_main_parent = jQuery(this).closest(".single_group_backup_content");
			
			if(!jQuery(this).hasClass("selected"))
			{
				jQuery(this).addClass("selected");
				jQuery(">.this_parent_node li", main_parent).not(".sql_file_li").removeClass('selected');			//dont touch the sql file
				jQuery(">.this_parent_node li", main_parent).not(".sql_file_li").addClass('selected');
				
				var this_par_total_files = jQuery(".this_leaf_node li", main_parent).length + jQuery(".sub_tree_class", main_parent).length;
				var curr_selected_files = jQuery(".this_leaf_node li.selected", main_parent).length + jQuery(".sub_tree_class.selected", main_parent).length;
				
				//trick to not-include the sql file which is hidden inside
				if(jQuery(this).find(".file_path").text() == "wp-content"){
					curr_selected_files += 1;
				}
				
				//to check the folder if all the files inside it are selected
				if(this_par_total_files == curr_selected_files){
					//jQuery(main_parent).prev(".sub_tree_class").addClass("selected");
					jQuery(main_parent).prev(".sub_tree_class").click();
				}
			}
			else
			{
				jQuery(this).removeClass("selected");
				jQuery(">.this_parent_node li", main_parent).not(".sql_file_li").removeClass('selected');
				
				//to uncheck the folder if all the files inside it are selected
				if(this_par_total_files == curr_selected_files){
					jQuery(main_parent).prev(".sub_tree_class").removeClass("selected");
				}
			}
			if(!jQuery(".restore_the_db").hasClass("selected")){
				jQuery(".sql_file", main_parent).parent(".this_parent_node").prev(".sub_tree_class").removeClass("selected");
				jQuery(".sql_file li", main_parent).removeClass("selected");
			}
			else{
				jQuery(".sql_file", main_parent).parent(".this_parent_node").prev(".sub_tree_class").addClass("selected");
				jQuery(".sql_file li", main_parent).addClass("selected");
			}
			
			//enable the restore selected button
			if((!jQuery(".this_leaf_node li", top_main_parent).hasClass("selected")) && (!jQuery(".sub_tree_class", top_main_parent).hasClass("selected"))){
				jQuery(".this_restore", top_main_parent).addClass("disabled");
			}
			else{
				jQuery(".this_restore", top_main_parent).removeClass("disabled");
			}
			return false;
		});
		
		jQuery(".restore_the_db").on("click", function(){
			var par_obj = jQuery(this).closest(".single_group_backup_content");
			if(!jQuery(this).hasClass("selected")){
				jQuery(".sql_file", par_obj).parent(".this_parent_node").prev(".sub_tree_class").removeClass("selected");
				jQuery(".sql_file li", par_obj).removeClass("selected");
			}
			else{
				jQuery(".sql_file", par_obj).parent(".this_parent_node").prev(".sub_tree_class").addClass("selected");
				jQuery(".sql_file li", par_obj).addClass("selected");
			}
			
			//enable the restore selected button
			if((!jQuery(".this_leaf_node li", par_obj).hasClass("selected")) && (!jQuery(".sub_tree_class", par_obj).hasClass("selected"))){
				jQuery(".this_restore", par_obj).addClass("disabled");
			}
			else{
				jQuery(".this_restore", par_obj).removeClass("disabled");
			}
		});
		
		jQuery(".this_leaf_node li").on("click", function(){
			var parObj  = jQuery(this).closest(".single_group_backup_content");
			var currTreeParent = jQuery(this).closest(".this_parent_node");
			
			if(!jQuery(this).hasClass('selected'))
			{
				jQuery(this).addClass('selected');
				
				/* var this_par_total_files = jQuery(".this_leaf_node li", currTreeParent).length + jQuery(".sub_tree_class", currTreeParent).length;
				var curr_selected_files = jQuery(".this_leaf_node li.selected", currTreeParent).length + jQuery(".sub_tree_class.selected", currTreeParent).length; */
				
				var this_par_total_files = jQuery(".this_leaf_node li", currTreeParent).length ;
				var curr_selected_files = jQuery(".this_leaf_node li.selected", currTreeParent).length ;
				
				//trick to exclude sql file while taking the count
				if(jQuery(currTreeParent).prev(".sub_tree_class").find(".file_path").text() == "wp-content"){
					//console.log("total is ");
					//console.log(this_par_total_files);
					curr_selected_files += 1;
					//console.log("current selected is");
					//console.log(curr_selected_files);
				}
				
				//if all the files are selected; then select the folder(show green color) 
				if(this_par_total_files == curr_selected_files){
					//to select this parent folder when all the files are selected
					//jQuery(currTreeParent).prev(".sub_tree_class").addClass("selected");
					jQuery(currTreeParent).prev(".sub_tree_class").click();
					//to select all the parent folder when all the files are selected
					/* jQuery(this).parents(".this_parent_node").each(function(){
						var this_par_obj = jQuery(this);
						if(jQuery(".this_leaf_node li", this_par_obj).not(".sql_file_li").hasClass("selected")){
							jQuery(this).prev(".sub_tree_class").addClass("selected");
						}
					}); */
				}
			}
			else
			{
				jQuery(this).removeClass('selected');
				//to select this parent folder when all the files are selected
				jQuery(currTreeParent).prev(".sub_tree_class").removeClass("selected");
				
				//to deselect all the parent folder when all the files are selected
				/* jQuery(this).parents(".this_parent_node").each(function(){
					var this_par_obj = jQuery(this);
					if(!(jQuery(".this_leaf_node li", this_par_obj).not(".sql_file_li").hasClass("selected"))){
						jQuery(this).prev(".sub_tree_class").removeClass("selected");
					}
				}); */
			}
			
			//enable the restore selected button
			if((!jQuery(".this_leaf_node li", parObj).hasClass("selected")) && (!jQuery(".restore_the_db", parObj).hasClass("selected"))){
				jQuery(".this_restore", parObj).addClass("disabled");
			}
			else{
				jQuery(".this_restore", parObj).removeClass("disabled");
			}
			return false;
		});
		
		jQuery('.this_restore').on('click', function(e) {
			if(jQuery(this).hasClass("disabled")){
				return false;
			}
			//var group_par = jQuery(this).closest(".single_group_backup_content");
			var files_to_restore = {};
			//files_to_restore = wtc_initializeRestore();
			
			files_to_restore = wtc_initializeRestore(jQuery(this), 'single');
			//the follwing two functions are the ajax functions which performs restore operations
			startRestore(files_to_restore, false);
			checkIfNoResponse('startRestore');
			getTcRestoreProgress();
			getAndStoreBridgeURL();
			
			e.stopImmediatePropagation();
			return false;
		});
		
		jQuery('.this_restore_point').on('click', function(e) {
			//var group_par = jQuery(this).closest(".single_group_backup_content");
			var files_to_restore = {};
			files_to_restore = wtc_initializeRestore(jQuery(this), 'all');
			var cur_res_b_id = jQuery(this).closest(".single_group_backup_content").attr("this_backup_id");
			//return false;
			//the follwing two functions are the ajax functions which performs restore operations
			startRestore(files_to_restore, cur_res_b_id);
			checkIfNoResponse('startRestore');
			getTcRestoreProgress();
			getAndStoreBridgeURL();
			
			e.stopImmediatePropagation();
			return false;
		});
		
		jQuery("#TB_overlay").on("click", function(){
			//console.log("clicky");
			if(typeof is_backup_started == 'undefined' || is_backup_started == false){					//for enabling dialog close on complete
				tb_remove();
                                backupclickProgress=false;
			}
		});
		
		jQuery(".dialog_close").on("click", function(){
			tb_remove();
		});
	}
	
	function wtc_initializeRestore(obj, type){
		//this function returns the files to be restored ; shows the dialog box ; clear the reload timeout for backup ajax function
		var files_to_restore = {};
		
		if(type == 'all'){
			var is_selected = '';			//a trick to include all files during restoring-at-a-point;
		}
		else{
			var is_selected = '.selected';			
		}
		
		var par_obj = jQuery(obj).closest('.single_group_backup_content');
		jQuery(".this_leaf_node li"+is_selected, par_obj).each(function(ee){
			var this_rev_id = jQuery(this).find(".file_path").attr("rev_id");
			var this_file_name = jQuery(this).find(".file_path").attr("file_name");
			var this_uploaded_size = jQuery(this).find(".file_path").attr("file_size");
			files_to_restore[this_rev_id] = {};
			files_to_restore[this_rev_id]['file_name'] = this_file_name;
			files_to_restore[this_rev_id]['file_size'] = this_uploaded_size;
		});
		//console.log(files_to_restore);
		
		//show the progress bar dialog
		var this_html = '<div class="this_modal_div" style="background-color: #f1f1f1;font-family: \'open_sansregular\' !important;color: #444;padding: 0px 34px 26px 34px;"><div class="pu_title">Restoring '+sitename+'</div><div class="wcard progress_reverse" style="height:60px; padding:0;"><div class="progress_bar" style="width:2%;"></div>  <div class="progress_cont">Warping back in time... Hold on tight!</div></div></div>';
		
		jQuery("#TB_ajaxContent").html(this_html);
		styling_thickbox_tc('restore');
		
		if(typeof reloadFuncTimeout != 'undefined')
		{
			//console.log('claearing the old backup ajax reload');
			clearTimeout(reloadFuncTimeout);
		}
		return files_to_restore;
	}
	
	function getAndStoreBridgeURL(){
		//console.log('getAndStoreBridgeURL');
		//this function is used to get the bridge folder name with hash and store it in a JS variable;
		var this_plugin_url = '<?php echo plugins_url(); ?>' ;
		var this_data = '';
		var post_array = {};
		post_array['getAndStoreBridgeURL'] = 1;
		this_data = jQuery.param(post_array);
		
		jQuery.post(ajaxurl, { action : 'start_restore_tc', data: post_array }, function(request) {
			//console.log(request);
			if((typeof request != 'undefined') && request != null)
			{
				cuurent_bridge_file_name = request;
			}
			
		});
	}
	
	function dialogOpenCallBack(){
		
	}
	
	function consoleMonitor(data){
		var content_url = '<?php echo content_url(); ?>';
		
		jQuery.ajax({
			traditional: true,
			type: 'post',
			url: content_url+'/monitor-console.php',
			dataType: 'json',
			data: '',
			success: function(request) {
				if(typeof request != 'undefined' && request != null && !(jQuery.isEmptyObject(request)))
				{
					////console.log(request);//console.log(consoledArray);
					jQuery.each(request,function(key, val){
						var printSafe = true;
						jQuery.each(consoledArray, function(k, v){
							if(k == key)
							{
								printSafe = false;
								return false;
							}
						});
						if(printSafe == true)
						{
							//console.log(val['comments']);
							//console.log(val['contents']);
							consoledArray[key] = 1;
						}
						
					});
				}
			},  // End success
			error: function() {
					
			}
		});
		//consoleMonitorTimeout = setTimeout(function(){consoleMonitor();}, 10000);
	}
	
	function stripquotes(a) {
		if (a.charAt(0) === "'" && a.charAt(a.length-1) === "'") {
			return a.substr(1, a.length-2);
		}
		return a;
	}
	
	function getTcRestoreProgress(){
		var this_plugin_url = '<?php echo plugins_url('wp-time-capsule'); ?>' ;
		var this_data = '';
		
		jQuery.ajax({
			traditional: true,
			type: 'post',
			url: this_plugin_url+'/restore-progress-ajax.php',
			dataType: 'json',
			data: this_data,
			success: function(request) {
				if((typeof request != 'undefined') && request != null)
				{
					if(typeof request['downloaded_files_percent'] != 'undefined')
					{
						//console.log(request);
						
						var this_html = '<div class="this_modal_div" style="background-color: #f1f1f1;font-family: \'open_sansregular\' !important;color: #444;padding: 0px 34px 26px 34px;"><div class="pu_title">Restoring '+sitename+'</div><div class="wcard progress_reverse" style="height:60px; padding:0;"><div class="progress_bar" style="width:'+request['downloaded_files_percent']+'%;"></div>  <div class="progress_cont">Warping back in time... Hold on tight!</div></div></div>';
						jQuery("#TB_ajaxContent").html(this_html);
					}
					if(typeof request['copied_files_percent'] != 'undefined')
					{
						//console.log(request);
						
						var this_html = '<div class="this_modal_div" style="background-color: #f1f1f1;font-family: \'open_sansregular\' !important;color: #444;padding: 0px 34px 26px 34px;padding: 0px 34px 26px 34px;"><div class="pu_title">Restoring '+sitename+'</div><div class="wcard progress_reverse" style="height:60px; padding:0;"><div class="progress_bar" style="width:'+request['copied_files_percent']+'%;"></div>  <div class="progress_cont">Coping Files... Hold on tight!</div></div></div>';
						jQuery("#TB_ajaxContent").html(this_html);
					}
				}
				else if(request == null)
				{
					//console.log('clearing restore progress timeout');
					if(typeof getRestoreProgressTimeout != 'undefined')
					{
						clearTimeout(getRestoreProgressTimeout);
					}
				}
			},  // End success
			error: function() {
					
			}
		});
		getRestoreProgressTimeout = setTimeout(function(){getTcRestoreProgress();}, 5000);
	}
	
	function startRestore(files_to_restore, cur_res_b_id){
		//register the startTIme
		start_time_tc = Date.now();    //global variable which will be used to see the activity so as to trigger new call when there is no activity for 60secs
		
		var this_plugin_url = '<?php echo plugins_url('wp-time-capsule'); ?>' ;
		var this_data = '';
		var post_array = {};
        post_array['cur_res_b_id'] = cur_res_b_id;
		post_array['files_to_restore'] = files_to_restore;
//		if((typeof post_array['files_to_restore'] != 'undefined') && (typeof post_array['cur_res_b_id'] != 'undefined')){
//			this_data = jQuery.param(post_array);
//		}
		var this_ajax_url = this_plugin_url+'/plugin-ajax.php';
		jQuery.post(ajaxurl, { action : 'start_restore_tc', data: post_array }, function(request) {
			//console.log(request);
			if((typeof request != 'undefined') && request != null)
			{
				if(typeof request['error'] != 'undefined')
				{
					show_error_dialog_and_clear_timeout(request);
				}
				else if(request == 'callAgain')
				{
					startRestore();
				}
				else if(request == 'over')
				{
					//clear timeout for startRestore function and then do the bridge copy ..
					if(typeof checkIfNoResponseTimeout != 'undefined')
					{
						clearTimeout(checkIfNoResponseTimeout);
					}
					var start_bridge = {};
					start_bridge['initialize'] = true;
					start_bridge['wp_prefix'] = '<?php global $wpdb; echo $wpdb->base_prefix; ?>';			//sending the prefix to bridge
					startBridgeCopy(start_bridge);
					checkIfNoResponse('startBridgeCopy');
				}
			}
		  // End success
		});
		//startRestoreTimeout = setTimeout(function(){startRestore();}, 15000);
	}
	
	function show_error_dialog_and_clear_timeout(request){
		//for restore 
		//console.log(request['error']);
		//clear timeout for startRestore function and then do the bridge copy ..
		if(typeof checkIfNoResponseTimeout != 'undefined')
		{
			clearTimeout(checkIfNoResponseTimeout);
		}
		//clear timeout for startRestore function and then do the bridge copy ..
		if(typeof getRestoreProgressTimeout != 'undefined')
		{
			clearTimeout(getRestoreProgressTimeout);
		}
		
		//assuming restore dialog box is already alive
		var this_html = '<div class="this_modal_div" style="background-color: #f1f1f1;font-family: \'open_sansregular\' !important;color: #444;padding: 0px 34px 26px 34px;"><div class="pu_title">ERROR DURING RESTORE</div><div class="wcard progress_reverse error" style="height:60px; padding:0;">  <div class="progress_cont">'+request['error']+'</div></div></div>';
		jQuery("#TB_ajaxContent").html(this_html);
	}
	
	function startBridgeCopy(data)
	{
		//register the startTIme
		start_time_tc = Date.now();
		
		//console.log("calling startBridgeCopy");
		var this_home_url = '<?php echo site_url(); ?>' ;
		//console.log(this_home_url);
		//cuurent_bridge_file_name = '<?php echo WPTC_Factory::get('config')->get_option('current_bridge_file_name', true); ?>';
		var this_data = '';
		var this_url = this_home_url+'/'+cuurent_bridge_file_name+'/tc-init.php';     //cuurent_bridge_file_name is a global variable and is set already
		//console.log(this_url);
		if(typeof data != 'undefined')
		{
			this_data = jQuery.param(data);
		}
		jQuery.ajax({
			traditional: true,
			type: 'post',
			url: this_url,
			dataType: 'json',
			data: this_data,
			success: function(request) {
				//console.log(request);
				if(typeof request != 'undefined' && request != null)
				{
					if(request == 'callAgain')
					{
						var continue_bridge = {};
						continue_bridge['wp_prefix'] = '<?php global $wpdb; echo $wpdb->base_prefix; ?>';		//am sending the prefix ; since it is a bridge
						startBridgeCopy(continue_bridge);
					}
					else if(request == 'over')
					{
						//clear timeout for startBridgeCopy function and then do the stuffs to perform the after restore options
						clearTimeout(checkIfNoResponseTimeout);
						if(typeof getRestoreProgressTimeout != 'undefined')
						{
							//console.log('clearing timeout');
							clearTimeout(getRestoreProgressTimeout);
						}
						
						//show the completed dialog box
						var this_html = '<div class="this_modal_div" style="background-color: #f1f1f1;font-family: \'open_sansregular\' !important;color: #444;padding: 0px 34px 26px 34px;"><span class="dialog_close"></span><div class="pu_title">DONE</div><div class="wcard clearfix" style="width:375px">  <div class="l1">Your website was restored successfully. Yay! <br> Redirecting in 5 secs...</div>  </div></div>';
						jQuery("#TB_ajaxContent").html(this_html);
						//clearTimeout(consoleMonitorTimeout);
						
						//redirect to the site after 3 secs and set restore completed options
						setInterval(function(){window.location = '<?php echo admin_url('?page=wp-time-capsule-monitor'); ?>' },3000);
						
						
						
					}
					else if(typeof request['error'] != 'undefined')
					{
						//clear timeout for startBridgeCopy function and then do the stuffs to perform the after restore options
						show_error_dialog_and_clear_timeout(request);
					}
					//clearTimeout(startBridgeCopyTimeout);
				}
			},  // End success
			error: function(errData) {
					////console.log(errData);
			}
		});
		//startBridgeCopyTimeout = setTimeout(function(){startBridgeCopy();}, 5000);
	}
	
	function checkIfNoResponse(this_func){
		//this function is called every 15 secs to see if there is any activity.
		//if there is no response for the last 60 secs .. then this function calls the respective ajax functions.
		
		//set global ajax_function variable
		if(typeof this_func != 'undefined' && this_func != null)
		{
			ajax_function_tc = this_func;
		}
		
		var this_time_tc = Date.now();
		//console.log(this_time_tc);
		//console.log(this_time_tc);
		if((this_time_tc - start_time_tc) >= 60000)
		{
			if(ajax_function_tc == 'startBridgeCopy')
			{
				var continue_bridge = {};
				continue_bridge['wp_prefix'] = '<?php global $wpdb; echo $wpdb->base_prefix; ?>';		//am sending the prefix ; since it is a bridge
				startBridgeCopy(continue_bridge);
			}
			else
			{
				startRestore();
			}
		}
		if(typeof checkIfNoResponseTimeout != 'undefined')
		clearTimeout(checkIfNoResponseTimeout);
		checkIfNoResponseTimeout = setTimeout(function(){checkIfNoResponse();}, 15000);
	}
	
	function doBridgeRestore(data){
		/* var this_home_url = '<?php echo home_url(); ?>' ;
		jQuery.ajax({
			traditional: true,
			type: 'post',
			url: this_home_url+'/wp-tcapsule-bridge/tc-init.php',
			dataType: 'json',
			data: jQuery.param(data),
			success: function(request) {
			
			},  // End success
			error: function() {
					
			}
		}); */
	}
	
	function monitorRestore(files_to_restore){
		//console.log("calling monitor restore here");
		if(typeof files_to_restore != 'undefined')
		{
			jQuery.post(ajaxurl, { action :'do_restore', data : files_to_restore }, function(data) {
					//console.log(data);
			}).error(function(data){
			//console.log(data);
			});
			setTimeout("monitorRestore()", 15000);
		}
	}
	
	function dialogCloseCallBack(){
		
	}
	
	function show_get_name_dialog_tc(){
		var this_content = '<div class="wcard clearfix backup_name_dialog" style="margin-top:30px;">  <div class="l1" style="padding-top: 0px;">Do you want to name this backup?</div>  <input type="text" placeholder="Backup Name" class="backup_name_tc"><a class="btn_pri backup_name_enter">SAVE</a>  <a class="skip">NO, SKIP THIS</a> </div>';
		
		//jQuery(".backup_name_dialog").remove();
		//jQuery("#wpwrap").append('<div class="backup_name_dialog" id="modalDiv" style="padding: 224px 0px 0px 600px;"></div>');
		
		//since the progress bar is already on the DOM; we are appending this
		jQuery(".backup_progress_tc").parent().append(this_content);
		//jQuery(".thickbox").click();
		
		/* jQuery(".backup_name_dialog").dialog("close");
		jQuery(".backup_name_dialog").dialog("destroy");
		jQuery('.backup_name_dialog').html(this_content).dialog({width:'auto',modal:true,position: 'center',resizable: false, open: function(event, ui) { },close: function(event, ui) { show_backup_progress_dialog('', 'fresh'); jQuery(".backup_name_dialog").html(''); }}); */
		
		jQuery(".skip").on("click", function(){
			//jQuery("#TB_closeWindowButton").click();
			jQuery(".backup_name_dialog").remove();
		});
		
		jQuery(".backup_name_enter").on("click", function(){
			store_this_name_tc();
		});
	}
	
	function store_this_name_tc(){
		var this_name = jQuery(".backup_name_tc").val();
		jQuery.post(ajaxurl, { action : 'store_name_for_this_backup', data : this_name }, function(data) {
			//console.log(data);
			if(data){
				jQuery(".backup_name_dialog").hide();
				/* jQuery(".backup_name_dialog").dialog("close");
				jQuery(".backup_name_dialog").dialog("destroy");
				jQuery(".backup_name_dialog").remove(); */
			}
		});
	}
	
    jQuery(document).ready(function () {
		//console.log("hi");
		/* jQuery(function(){
			jQuery('.fc-event-inner').on('click', function() {
				var thisDayBackups = getThisDayBackups(jQuery(this).find(".fc-event-title").attr("backupids"));	
				//console.log(jQuery(this).find(".fc-event-title").attr("backupids"));
			});
		}); */
                
        wtc_reload();
		
		jQuery('#start_backup').on('click', function(){
			if(jQuery(this).text() != 'Stop Backup'){
				is_backup_started = true;				//setting global variable for backup status
				//console.log('backup starting');
				//changing the button name
				jQuery(this).text("Stop Backup");
				
				//call the ajax function to perform the backup actions
				jQuery.post(ajaxurl, { action : 'start_fresh_backup_tc' }, function(data) {
					started_fresh_backup = true;
				
					//calling the progress bar function
					show_backup_progress_dialog('', 'fresh');
                                        
                                        if(fresh=='yes'){
                                            var inicontent='<div style="margin-top: 24px; background: none repeat scroll 0% 0% rgb(255, 255, 255); padding: 0px 7px; box-shadow: 0px 1px 2px rgba(0, 0, 0, 0.2);"><p style="text-align: center; line-height: 24px;">This is your first backup. We are now copying all your files and db to your Dropbox account. This might take a while. Subsequent backups will be instantaneous since they are incremental. <br>Please be patient and don’t close the window.</p></div>';
                                            jQuery(".backup_progress_tc").parent().append(inicontent);
                                        }
					//call the name storing funciton to give this backup process a name 
					show_get_name_dialog_tc();
					backup_end = true;
				});
			}
			else{
				//console.log('backup stopping');
				//call the ajax function to perform the backup actions
				wtc_stop_backup_func();
			}
		}); 
		
		jQuery('#stop_backup').on('click', function(){
			if(jQuery(this).text() != 'Stop Backup'){
				//changing the button name
				jQuery(this).text("Stop Backup");
				
				//call the ajax function to perform the backup actions
				jQuery.post(ajaxurl, { action : 'start_fresh_backup_tc' }, function(data) {
					started_fresh_backup = true;
				
					//calling the progress bar function
					show_backup_progress_dialog('', 'fresh');
					
					//call the name storing funciton to give this backup process a name 
					show_get_name_dialog_tc();
					backup_end = true;
				});
			}
			else{
				//console.log('backup stopping');
				//call the ajax function to perform the backup actions
				wtc_stop_backup_func();
			}
		});
		
		<?php if (isset($started)): ?>
		show_get_name_dialog_tc();
		<?php endif; ?>
                    
                <?php if($schedule_backup_run){?>
                    jQuery('#sch_backup').show();
                <?php }
                else{
                ?>
                    jQuery('#sch_backup').hide();
                <?php } ?>        
                    
                 <?php if($freshbackupPopUp){?>
                        freshBackupPopUpShow();
                <?php }?>
    });
</script>
<?php 

?>
<div class="wrap" id="wptc">
    
    <?php settings_errors(); ?>

    
	<form id="backup_to_dropbox_options" name="backup_to_dropbox_options" action="admin.php?page=wp-time-capsule-monitor" method="post" style=" width: 100%;">
            <h2 style="width: 195px; display: inline;"><?php _e('Backups', 'wptc'); ?>
        <?php if ($config->get_option('in_progress') || isset($started)): ?>
            <a id="stop_backup" name="stop_backup" class="add-new-h2" style="cursor:pointer"><?php _e('Stop Backup', 'wptc'); ?></a>
        <?php else: ?>
            <a id="start_backup" name="start_backup" class="add-new-h2" style="cursor:pointer"><?php _e('Backup Now', 'wptc'); ?></a>
        <?php endif; ?>
            <div id="sch_backup" style="display: inline-block; font-size: 12px; color: red; padding: 0% 1%;">Currently scheduled backup running</div>
            </h2>
            <?php if($schedule_backup=='off'){ ?>
            <a style="margin-top: 15px; display: inline;" href="<?php echo admin_url().'admin.php?page=wp-time-capsule&highlight=schedule'?>">Turn on scheduled backup</a>
            <?php } ?>
        <?php wp_nonce_field('wordpress_time_capsule_monitor_stop'); ?>
            <!--<a style="float: right;display: inline-block;margin-top:8px;padding-left:13px;" href="https://wptimecapsule.uservoice.com/" target="_blank">Get support</a>-->
            <a style="float: right;display: inline-block;margin-top:8px" href="https://wptimecapsule.uservoice.com/" target="_blank">Suggest a Feature</a>
          
<!--            <a class="dashicons-before dashicons-backup" id="report_issue" style="float: right; color: rgb(196, 63, 63); font-style: italic; text-decoration: none; padding: 5px; border-radius: 25px;" href="#">Report issue</a>-->
    </form>

    <div id="progress">
        <div id="circleG">
            <div id="circleG_1" class="circleG"></div>
            <div id="circleG_2" class="circleG"></div>
            <div id="circleG_3" class="circleG"></div>
        </div>
        <div class="loading"><?php _e('Loading...') ?></div>
    </div>
    
</div>
