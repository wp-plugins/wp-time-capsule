<?php
/*
------time capsule------
1.this file is totally used to move the files from the tempFolder to the actual root folder of wp
2.this file uses files from wordpress and also plugins to perform the copying actions
*/
set_time_limit(0);

$start_time_tc_bridge = microtime(true);
require_once("wp-tc-config.php");
require_once("wp-db-custom.php");
require_once("tc-config.php");
require_once("Base.php");
require_once("DBTables.php");
require_once("Factory.php");
require_once("Files.php");
require_once("file.php");
require_once("Restoredfiles.php");
require_once("FileList.php");
require_once("class-wp-filesystem-base.php");
require_once("class-wp-filesystem-direct.php");
require_once("formatting.php");
require_once("class-wp-error.php");
//require_once("plugin-ajax.php");

//setting any missing define constants

if ( ! defined('FS_CHMOD_FILE') )
	define('FS_CHMOD_FILE', 0644 );
if ( ! defined('FS_CHMOD_DIR') )
		define('FS_CHMOD_DIR', 0755 );

//initialize wpdb since we are using it independently
global $wpdb;
$wpdb = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );

//setting the prefix from post value;
 if(isset($_REQUEST['wp_prefix'])){
	$wpdb->prefix = $_REQUEST['wp_prefix'];
 }
	
////file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----wpdb prefix------ ".var_export($wpdb->prefix,true)."\n",FILE_APPEND);

//get all the POST fields
$post_data = $_REQUEST;
//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----requestData from JS----- ".var_export($_REQUEST,true)."\n",FILE_APPEND);

//initialize options table object
$options_obj = WPTC_Factory::get('config');

//initialize file_system obj
/* $creds = request_filesystem_credentials('tc-init.php', "", false, false, null);
if ( ! WP_Filesystem($creds) ) {
	// any problems and we exit 
	//return false;
} */
global $wp_filesystem;

//update the options table to indicate that bridge process is going on , only on the first call
if(!empty($post_data) && isset($post_data['initialize']) && $post_data['initialize'] == 'true')
{
	$options_obj->set_option('is_bridge_process', true);
	$options_obj->set_option('restore_db_index', 0);
	$options_obj->set_option('restore_db_process', true);
}

//$post_data['continue'] will come only when call the bridge manually
if(!($options_obj->get_option('is_bridge_process')) && empty($post_data['continue']))
{
	//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----exitinnnggg   ------ ".var_export($options_obj->get_option('is_bridge_process'),true)."\n",FILE_APPEND);
	echo json_encode('over');
	return;
}

if(empty($post_data['continue']))
{
	//prepare the from and to folders
	$actual_main_folder = ABSPATH;
	$this_site_name = basename($actual_main_folder); 
	$actual_wp_content_folder = WP_CONTENT_DIR;
	$restore_temp_folder = WP_CONTENT_DIR . '/tCapsule';
	$content_name = basename(WP_CONTENT_DIR);
	$restore_db_dump_file =  $restore_temp_folder .'/'.$content_name.'/tCapsule/backups/'.DB_NAME.'-backup.sql';

	$wp_filesystem = new WP_Filesystem_Direct('');
	//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n --restore_db_dump_file------- ".var_export($restore_db_dump_file,true)."\n",FILE_APPEND);


	//check if the db restore process is already completed; if it is not completed do the DB restore
	//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----restore_db_process option ------- ".var_export($options_obj->get_option('restore_db_process'),true)."\n",FILE_APPEND);
	if(($options_obj->get_option('restore_db_process')))
	{
		//check if the sql file is selected during restore process ; if it doesnt exist then we dont need to do the restore db process
		//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----is exist------ ".var_export($wp_filesystem->exists($restore_db_dump_file),true)."\n",FILE_APPEND);
		if($wp_filesystem->exists($restore_db_dump_file))
		{
			global $start_db_time_tc;
			$start_db_time_tc = microtime(true);
			$db_restore_result = tc_database_restore($restore_db_dump_file);
			$end_time_db_tc = microtime(true) - $start_db_time_tc;
			//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----end_time_db_tc-------- ".var_export($end_time_db_tc,true)."\n",FILE_APPEND);
			
			//on db restore completion - set the following values
			if($db_restore_result)
			{
				$options_obj->set_option('restore_db_process', false);
				$options_obj->set_option('restore_db_index', 0);
				echo json_encode('callAgain');
				exit;
			}
			else
			{
				//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----false------ ".var_export(mysql_error(),true)."\n",FILE_APPEND);
				$error_array = array('error' => mysql_error());
				echo json_encode($error_array);
				exit;
			}
		}
		else
		{
			$options_obj->set_option('restore_db_process', false);
			$options_obj->set_option('restore_db_index', 0);
			echo json_encode('callAgain');
			exit;
		}
	}
	else
	//if(!($options_obj->get_option('restore_db_process')))
	{
		//first delete the sql file then carryout the copying files process
		if($wp_filesystem->exists($restore_db_dump_file))
		{
			$wp_filesystem->delete($restore_db_dump_file);
		}
		
		//if the db restore process is over ; call the function to perform copy
		$full_copy_result = tc_file_system_copy_dir($restore_temp_folder, $actual_main_folder);
		if(is_array($full_copy_result) && array_key_exists('error', $full_copy_result))
		{
			echo json_encode($full_copy_result);
		}
		else
		{
			//if we set this value as false ; then the bridge process for copying is completed
			//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----dieing soon----- \n",FILE_APPEND);
			$options_obj->set_option('is_bridge_process', false);
			restore_complete();
		}
	}
}
if(false)
{
	//get all the file list to copy and perform the actions on those files using bridge
	if (file_exists($restore_temp_folder)) 
	{
		$source = realpath($restore_temp_folder);
		$files = get_all_files_from_dir($source);
		foreach ($files as $file_name) {
			
		}
	}
}

function restore_complete(){
	//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ---Coming to restore Complete---- \n",FILE_APPEND);
	//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----dirname dirname-------- ".var_export(dirname(__FILE__),true)."\n",FILE_APPEND);
	WPTC_Factory::get('config')->set_option('restore_completed_notice','yes'); 
	//delete the bridge files on completion
	global $wp_filesystem;
	$wp_filesystem->delete(dirname(__FILE__), true);
	$this_temp_backup_folder = WP_CONTENT_DIR.'/tCapsule';
	$wp_filesystem->delete($this_temp_backup_folder, true);
	if ( !$wp_filesystem->is_dir($this_temp_backup_folder) ) {
		if ( !$wp_filesystem->mkdir($this_temp_backup_folder, FS_CHMOD_DIR) )
			{
				//return error message if any
			}
	}
	echo json_encode('over');
	exit;
}

function getTcCookie($name)
{
	$options_obj = WPTC_Factory::get('config');
	
	if(!$options_obj->get_option('this_cookie'))
	{
		return false;
	}
	else
	{
		$contents = @unserialize($options_obj->get_option('this_cookie'));
		return $contents[$name];
	}
}

function tc_database_restore($file_name){
	//return true;
	//wpdb_reconnect();
	global $wpdb;
	
	//initialize options table object
	$options_obj = WPTC_Factory::get('config');
	
	//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----trying db restore----- ".var_export($file_name,true)."\n",FILE_APPEND);
	$prev_index = 0;
	$prev_index = $options_obj->get_option('restore_db_index');
	//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ---prev_index------ ".var_export($prev_index,true)."\n",FILE_APPEND);
	//if($prev_index == 0)
	//{
		//$wpdb->query("SET NAMES 'utf8'");
	//}
	$current_query = '';
	// Read in entire file
	//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----coming above------- \n",FILE_APPEND);
	$lines  = file($file_name);
	/* $file = fopen($file_name, "r");
	//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----file------ ".var_export($file,true)."\n",FILE_APPEND);
	while(!feof($file)){
		$line = fgets($file);
		# do same stuff with the $line
	}
	fclose($file); */
	//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----lines------ ".var_export($lines,true)."\n",FILE_APPEND);
	// Loop through each line
	if(!empty($lines)){
		$this_lines_count = 0;
		foreach ($lines as $key => $line) {
		
			//check index;if it is previously written ; then continue;
			if(!empty($prev_index) && $key <= $prev_index)
			{
				continue;
			}
			$this_lines_count++;
				//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"------$key------ ",FILE_APPEND);
			// Skip it if it's a comment
			if (substr($line, 0, 2) == '--' || $line == '')
				continue;
			
			// Add this line to the current query
			$current_query .= $line;
			// If it has a semicolon at the end, it's the end of the query
			if (substr(trim($line), -1, 1) == ';') {
				// Perform the query
				$result = $wpdb->query($current_query);
				if ($result == false)
				{
					//LOCK TABLES `wp_comments` WRITE;
					file_put_contents(WP_CONTENT_DIR . '/BRIDGE-SQL-LOG.txt',"\n ----sql error------ ".var_export(mysql_error(), true)."\n",FILE_APPEND);
					file_put_contents(WP_CONTENT_DIR . '/BRIDGE-SQL-LOG.txt',"\n --current_query----- ".var_export($current_query, true)."\n",FILE_APPEND);
					//return false;
				}
					
				// Reset temp variable to empty
				$current_query = '';
				
				//updating the status in db for each 10 lines
				if($this_lines_count >= 10)
				{
					$this_lines_count = 0;
					$options_obj->set_option('restore_db_index', $key);
					maybe_call_again();
				}
			}
		}
	}
	else{
		return false;
	}
	//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----completing------ \n",FILE_APPEND);
	return true;
}

function maybe_call_again(){
	global $start_time_tc_bridge;
	global $start_db_time_tc;
	if((microtime(true) - $start_time_tc_bridge) >= 20)
	{
		//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ------startTimeIs-------- ".var_export($start_time_tc_bridge,true)."\n",FILE_APPEND);
		echo json_encode("callAgain");
		
		$end_db_time_tc = microtime(true) - $start_db_time_tc;
		//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----end_db_time_tc------ ".var_export($end_db_time_tc,true)."\n",FILE_APPEND);
		
		exit;
	}
}

function wpdb_reconnect(){
	global $wpdb;
	$old_wpdb = $wpdb;
	//Reconnect to avoid timeout problem after ZIP files
	if(class_exists('wpdb') && function_exists('wp_set_wpdb_vars')){
		@mysql_close($wpdb->dbh);
		$wpdb = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
		wp_set_wpdb_vars(); 
		$wpdb->options = $old_wpdb->options;//fix for multi site full backup
	}
}

function tc_file_system_copy_dir($from, $to = ''){
	
	global $wp_filesystem;
	
	//get the dirList from wp_filesystem method
	$dirlist = $wp_filesystem->dirlist($from);
	//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----dirList from filesystem------- ".var_export($dirlist,true)."\n",FILE_APPEND);
	$from = trailingslashit($from);
	$to = trailingslashit($to);
	
	foreach ( (array) $dirlist as $filename => $fileinfo ) {
		
		if ( 'f' == $fileinfo['type'] ) {
			if ( ! tc_file_system_copy($from . $filename, $to . $filename, true, FS_CHMOD_FILE) ) {
				// If copy failed, chmod file to 0644 and try again.
				$wp_filesystem->chmod($to . $filename, 0644);
				if ( ! tc_file_system_copy($from . $filename, $to . $filename, true, FS_CHMOD_FILE) )
					{
						continue;
						return array('error' => 'cannot copy file');
					}
			}
			else
			{
				
			}
		} elseif ( 'd' == $fileinfo['type'] ) {
			if ( !$wp_filesystem->is_dir($to . $filename) ) {
				if ( !$wp_filesystem->mkdir($to . $filename, FS_CHMOD_DIR) )
					{
						return array('error' => 'cannot create directory');
					}
			}
			$result = tc_file_system_copy_dir($from . $filename, $to . $filename);
			if(!$result)
			{
				return $result;
			}
		}
	}
	return true;
}

function tc_file_system_copy($source, $destination, $overwrite = false, $mode = false){
	//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----trying to copy this file------- ".var_export($source,true)." to ".var_export($destination,true)."\n",FILE_APPEND);
	
	//initialize processed files object
	$processed_files = WPTC_Factory::get('processed-restoredfiles',true);
	$current_processed_files = array();
	
	global $wp_filesystem;
	if($wp_filesystem->method == 'direct'){
		// check if already processed ; if so dont copy
		$processed_file  = $processed_files->get_file($source);
		//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----processed_file obj------- ".var_export($processed_file,true)."\n",FILE_APPEND);
		if ( !($processed_file) ) {
			$copy_result = $wp_filesystem->move($source, $destination, $overwrite, $mode);
			if($copy_result)
			{
				//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----adding details--------\n",FILE_APPEND);
				//if copied then add the details to DB
				$this_file_detail['file'] = $source;
				$this_file_detail['copy_status'] = true;
				$this_file_detail['revision_number'] = null;
				$this_file_detail['revision_id'] = null;
				$this_file_detail['mtime_during_upload'] = null;
				
				$current_processed_files[] = $this_file_detail;
				//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----current_processed_files------- ".var_export($current_processed_files,true)."\n",FILE_APPEND);
				$processed_files->add_files($current_processed_files);	
				//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ---any sql error------ ".var_export(mysql_error(),true)."\n",FILE_APPEND);
				//set the in_progress option to false on final file copy
			}
			return $copy_result;
		}
		else
		{
			return true;
		}
	}
	elseif($wp_filesystem->method == 'ftpext' || $wp_filesystem->method == 'ftpsockets'){
		if ( ! $overwrite && $wp_filesystem->exists($destination) )
			return false;
		//$content = $this->get_contents($source);
//		if ( false === $content)
//			return false;
			
		//put content	
		//$tempfile = wp_tempnam($file);
		$source_handle = fopen($source, 'r');
		if ( ! $source_handle )
			return false;

		//fwrite($temp, $contents);
		//fseek($temp, 0); //Skip back to the start of the file being written to
		
		$sample_content = fread($source_handle, (1024 * 1024 * 2));//1024 * 1024 * 2 => 2MB
		fseek($source_handle, 0); //Skip back to the start of the file being written to

		$type = $wp_filesystem->is_binary($sample_content) ? FTP_BINARY : FTP_ASCII;
		unset($sample_content);
		if($wp_filesystem->method == 'ftpext'){
			$ret = @ftp_fput($wp_filesystem->link, $destination, $source_handle, $type);
		}
		elseif($wp_filesystem->method == 'ftpsockets'){
			$wp_filesystem->ftp->SetType($type);
			$ret = $wp_filesystem->ftp->fput($destination, $source_handle);
		}

		fclose($source_handle);
		unlink($source);//to immediately save system space
		//unlink($tempfile);

		$wp_filesystem->chmod($destination, $mode);

		return $ret;
		
		//return $this->put_contents($destination, $content, $mode);
	}
}

function get_all_files_from_dir($path, $exclude = array()) 
{
	if ($path[strlen($path) - 1] === "/") $path = substr($path, 0, -1);
	global $directory_tree, $ignore_array;
	$directory_tree = array();
	foreach ($exclude as $file) {
		if (!in_array($file, array('.', '..'))) {
			if ($file[0] === "/") $path = substr($file, 1);
			$ignore_array[] = "$path/$file";
		}
	}
	get_all_files_from_dir_recursive($path);
	return $directory_tree;
}

function get_all_files_from_dir_recursive($path, $ignore_array=array()) {
	if ($path[strlen($path) - 1] === "/") $path = substr($path, 0, -1);
	global $directory_tree, $ignore_array;
	$directory_tree_temp = array();
	$dh = @opendir($path);
	if(empty($ignore_array))
	{
		$ignore_array = array();
	}
	while (false !== ($file = @readdir($dh))) {
		if (!in_array($file, array('.', '..'))) {
			if (!in_array("$path/$file", $ignore_array)) {
				if (!is_dir("$path/$file")) {
					$directory_tree[] = "$path/$file";
				} else {
					get_all_files_from_dir_recursive("$path/$file");
				}
			}
		}
	}
	@closedir($dh);
}

?>
<script type="text/javascript" language="javascript">
	function refreshRestore(){
		startRestore();
		checkIfNoResponse('startRestore');
		
		//calling the progress bar function
		show_backup_progress_dialog('', 'fresh');
	}
	
	/*function startRestore(files_to_restore){
		//register the startTIme
		start_time_tc = Date.now();
		var this_data = '';
		if(typeof files_to_restore != 'undefined')
		{
			this_data = jQuery.param(files_to_restore);
		}
		jQuery.ajax({
			traditional: true,
			type: 'post',
			url: '<?php //echo WP_CONTENT_DIR; ?>/plugins/wp-time-capsule/plugin-ajax.php',
			dataType: 'json',
			data: this_data,
			success: function(request) {
				//console.log(request);
				if(typeof request != 'undefined')
				{
					if(request == 'callAgain')
					{
						startRestore();
					}
					if(request == 'over')
					{
						//clear timeout for startRestore function and then do the bridge copy ..
						clearTimeout(checkIfNoResponseTimeout);
						
						//initialize only when another instance is not running; otherwise continue from the left out.
						<?php //if(!($options_obj->get_option('is_bridge_process'))) { ?>
						var start_bridge = {};
						start_bridge['initialize'] = true;
						startBridgeCopy(start_bridge);
						checkIfNoResponse('startBridgeCopy');
						<?php //} ?>
					}
				}
			},  // End success
			error: function() {
					
			}
		});
		//startRestoreTimeout = setTimeout(function(){startRestore();}, 15000);
	}
	*/
	function startBridgeCopy(data)
	{
		//register the startTIme
		start_time_tc = Date.now();
		
		//console.log("calling startBridgeCopy");
		var this_data = ''
		if(typeof data != 'undefined')
		{
			this_data = jQuery.param(data);
		}
		jQuery.ajax({
			traditional: true,
			type: 'post',
			url: 'tc-init.php',
			dataType: 'json',
			data: this_data,
			success: function(request) {
				//console.log(request);
				if(request == 'callAgain')
				{
					startBridgeCopy();
				}
				if(request == 'over')
				{
					//clear timeout for startBridgeCopy function and then do the stuffs to perform the after restore options
					clearTimeout(checkIfNoResponseTimeout);
					//show the completed dialog box
					var this_html = '<div class="this_modal_div" style="background-color: #f1f1f1;font-family: \'open_sansregular\' !important;color: #444;padding: 0px 34px 26px 34px;"><div class="pu_title">DONE</div><div class="wcard clearfix">  <div class="l1">Your website was restored successfully. Yay! <br> Restoring in 5 secs...</div>  <a class="btn_pri" style="margin: 0 42px 20px; width: 250px; text-align: center;">GO TO <?php echo site_url(); ?></a></div></div>';
					jQuery("#TB_ajaxContent").html(this_html);
					//clearTimeout(consoleMonitorTimeout);
					
					//redirect to the site after 3 secs
					//setInterval(function(){window.location = '<?php echo site_url(); ?>';},5000);
				}
				else if(typeof request != 'undefined' && typeof request != null && request['error'])
				{
					//clear timeout for startBridgeCopy function and then do the stuffs to perform the after restore options
					
				}
			},  // End success
			error: function(errData) {
					////console.log(errData);
			}
		});
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
				startBridgeCopy();
			}
			else
			{
				startRestore();
			}
		}
		checkIfNoResponseTimeout = setTimeout(function(){checkIfNoResponse();}, 15000);
	}
	
	jQuery(document).ready(function ($) {
		//console.log("am reloading");
		<?php if(!empty($post_data) && $post_data['continue'] == true){?>
        refreshRestore();
		<?php } ?>
    });
</script>