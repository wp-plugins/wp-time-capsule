<?php
//setting the script start time
global $start_time_tc;
$start_time_tc= microtime(true);

//get file system credentials
$creds = request_filesystem_credentials('plugin-ajax.php', "", false, false, null);
if ( false === $creds ) {
	return; 
}

//initialize file_system obj
if ( ! WP_Filesystem($creds) ) {
	/* any problems and we exit */
	return false;
}
global $wp_filesystem;
//$wp_filesystem = new WP_Filesystem_Direct('');

//starting the restore process
if(isset($_REQUEST['files_to_restore'])){
	$files_to_restore = $_REQUEST['files_to_restore'];
}
if(isset($_REQUEST['cur_res_b_id'])){
	$cur_res_b_id = $_REQUEST['cur_res_b_id'];
}

$config = WPTC_Factory::get('config');
$backup = new WPTC_BackupController();

//just to send the bridge file url via ajax to javascript
if(isset($_REQUEST['getAndStoreBridgeURL']) && !empty($_REQUEST['getAndStoreBridgeURL'])){
	//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----coming insisde storing bridge folder name------ ".var_export($_REQUEST,true)."\n",FILE_APPEND);
	//prepare a bridge filename with hash
	$current_bridge_file_name = "wp-tcapsule-bridge-".hash("crc32", microtime(true));
	$config->set_option('current_bridge_file_name', $current_bridge_file_name);
	
	echo json_encode($current_bridge_file_name);
	exit;
}

if(isset($_REQUEST['files_to_restore']) && !empty($files_to_restore))
{
	//store the current b_id in options table; the current b_id will be used to determine the future old files which are to be restored to the prev restore point
	if(isset($_REQUEST['cur_res_b_id']) && !empty($cur_res_b_id)){
		$config->set_option('cur_res_b_id', $cur_res_b_id);
		$config->set_option('in_restore_deletion', false);
	}
	
	//send email to the admin indicating that the restore process is started
	$this_admin_email = get_option("admin_email");
	$message = site_url() . "/".$current_bridge_file_name."/tc-init.php?continue=true";  //the link to the bridge init file
	
	if(!empty($this_admin_email)){
		//mail($this_admin_email, 'WPTC - Restore Started', $message);
	}
	
	//set this to false; indicating that we are doing download process only; not the bridge copy process; (on the first call only)
	$config->set_option('is_bridge_process', false);
	$backup->restore_now($files_to_restore);
	$started = true;
}
else
{
	//if there is a bridge process going on ; then dont do restore-download
	if($config->get_option('is_bridge_process'))
	{
		echo json_encode('over');
		return;
	}
	
	//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ---direct new restore execute------- \n",FILE_APPEND);
	$restore_result = $backup->new_restore_execute();
}


function getFileSystemMethod($args = array(), $context = false) {
	$method = defined('FS_METHOD') ? FS_METHOD : false; //Please ensure that this is either 'direct', 'ssh', 'FTPExt' or 'ftpsockets'

	if ( ! $method && function_exists('getmyuid') && function_exists('fileowner') ){
		if ( !$context )
			$context = dirname(dirname(__FILE__));
		$context = addTrailingSlash($context);
		$tempFileName = $context . 'tempWriteTest_' . time();
		$tempHandle = @fopen($tempFileName, 'w');
		if ( $tempHandle ) {
			if ( getmyuid() == @fileowner($tempFileName) )
				$method = 'direct';
			@fclose($tempHandle);
			@unlink($tempFileName);
		}
 	}

	//if ( ! $method && ($args['use_sftp']==1) && extension_loaded('ssh2') && function_exists('stream_get_contents') ) $method = 'SSH2Ext';
	if ( ! $method && ($args['use_sftp']==1)) $method = 'SFTPExt';
	if ( ! $method && extension_loaded('ftp') ) $method = 'FTPExt';
	//if ( ! $method && ( extension_loaded('sockets') || function_exists('fsockopen') ) ) $method = 'ftpsockets'; //Sockets: Socket extension; PHP Mode: FSockopen / fwrite / fread
	
	if( !$method ){
		$method = 'direct';//fail safe value
		status("No file system method is detected so using direct as a fail safe method.", $success=true, $return=false);
	}
	//$method = 'SFTPExt';
	return $method;
}


function initFileSystem($args = false, $context = false){
	
	//initialize file_system obj
	global $wp_filesystem;
	
	if(empty($args)){
		$args = array('hostname' => APP_FTP_HOST, 'port' => APP_FTP_PORT, 'username' => APP_FTP_USER, 'password' => APP_FTP_PASS, 'base' => APP_FTP_BASE, 'connectionType' => (defined('APP_FTP_SSL') && APP_FTP_SSL) ? 'ftps' : '');
	}

	$method = getFileSystemMethod($args, $context);

	if (!$method)
		return false;

	$method = "fileSystem".ucfirst($method);
	
	$GLOBALS['FileSystemObj'] = new $method($args);
	$wp_filesystem = new $method($args);

	//Define the timeouts for the connections. Only available after the construct is called to allow for per-transport overriding of the default.
	if ( ! defined('FS_CONNECT_TIMEOUT') )
		define('FS_CONNECT_TIMEOUT', 30);
	if ( ! defined('FS_TIMEOUT') )
		define('FS_TIMEOUT', 30);

	//if ( is_error($FileSystemObj->errors) && $FileSystemObj->errors->get_error_code() )
	//	return false;

	if ( !$GLOBALS['FileSystemObj']->connect() )
		return false; //There was an error connecting to the server.

	// Set the permission constants if not already set.
	if ( ! defined('FS_CHMOD_DIR') )
		define('FS_CHMOD_DIR', 0755 );
	if ( ! defined('FS_CHMOD_FILE') )
		define('FS_CHMOD_FILE', 0644 );

	return true;
}
