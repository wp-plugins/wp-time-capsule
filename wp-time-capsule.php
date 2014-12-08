<?php
/*
Plugin Name: WP Time Capsule
Plugin URI: http://wptimecapsule.com
Description: Keep your valuable WordPress website, its media and database backed up in Dropbox!
Author: Revmakx
Version: 1.0.0alpha4
Author URI: http://www.revmakx.com.
/************************************************************
 * This plugin was modified by Revmakx						*
 * Copyright (c) 2014 Revmakx								*
 * www.revmakx.com		
 *															*
 ************************************************************
*/
define('WPTC_TEMP_COOKIE_FILE', str_replace('/', DIRECTORY_SEPARATOR, WP_CONTENT_DIR.'/backups/tempCookie.txt'));
define('WPTC_VERSION', '1.0.0alpha4');
define('WPTC_DATABASE_VERSION', '2');
define('EXTENSIONS_DIR', str_replace('/', DIRECTORY_SEPARATOR, plugin_dir_path(__FILE__).'Classes/Extension/'));
define('CHUNKED_UPLOAD_THREASHOLD', 4194304); //10 MB
define('MINUMUM_PHP_VERSION', '5.2.16');
define('NO_ACTIVITY_WAIT_TIME', 60); //5 mins to allow for socket timeouts and long uploads
define('TC_PLUGIN_PREFIX', 'wptc');
define('TC_PLUGIN_NAME', 'wp-time-capsule');
//define('WPTC_TEST_MODE', true);
if (!function_exists('spl_autoload_register')) {
    spl_autoload_register('wptc_autoload');
} else {
	require_once 'Dropbox/Dropbox/API.php';
    require_once 'Dropbox/Dropbox/OAuth/Consumer/ConsumerAbstract.php';
    require_once 'Dropbox/Dropbox/OAuth/Consumer/Curl.php';

    require_once 'Classes/Extension/Base.php';
    require_once 'Classes/Extension/Manager.php';
    require_once 'Classes/Extension/DefaultOutput.php';

    require_once 'Classes/Processed/Base.php';
    require_once 'Classes/Processed/Files.php';
	require_once 'Classes/Processed/Restoredfiles.php';
    require_once 'Classes/Processed/DBTables.php';

    require_once 'Classes/DatabaseBackup.php';
    require_once 'Classes/FileList.php';
    require_once 'Classes/DropboxFacade.php';
    require_once 'Classes/Config.php';
    require_once 'Classes/BackupController.php';
    require_once 'Classes/Logger.php';
    require_once 'Classes/Factory.php';
    require_once 'Classes/UploadTracker.php';
}

function wptc_autoload($className)
{
	
    $fileName = str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
	$temp = $fileName." - ";
    if (preg_match('/^WPTC/', $fileName)) {
        $fileName = 'Classes' . str_replace('WPTC', '', $fileName);
    } elseif (preg_match('/^Dropbox/', $fileName)) {
        $fileName = 'Dropbox' . DIRECTORY_SEPARATOR . $fileName;
    } else {
        return false;
    }

    $path = dirname(__FILE__) . DIRECTORY_SEPARATOR . $fileName;
    if (file_exists($path)) {
		require_once $path;
    }
}

function wptc_style()
{
    //Register stylesheet
    wp_register_style('wptc-style', plugins_url('wp-time-capsule.css', __FILE__) );
    wp_enqueue_style('wptc-style');
}

/**
 * A wrapper function that adds an options page to setup Dropbox Backup
 * @return void
 */
function wordpress_time_capsule_admin_menu()
{
	$imgUrl = rtrim(plugins_url('wp-time-capsule'), '/') . '/images/icon-16x16.png';
      $text = __('WP Time Capsule', 'wptc');
    add_menu_page($text, $text, 'activate_plugins', 'wp-time-capsule-monitor', 'wordpress_time_capsule_monitor', 'dashicons-backup', '80.0564');
	
    if (version_compare(PHP_VERSION, MINUMUM_PHP_VERSION) >= 0) {
        $text = __('Backups', 'wptc');
        add_submenu_page('wp-time-capsule-monitor', $text, $text, 'activate_plugins', 'wp-time-capsule-monitor', 'wordpress_time_capsule_monitor');

        //WPTC_Extension_Manager::construct()->add_menu_items();

        /* $text = __('Premium Extensions', 'wptc');
        add_submenu_page('wp-time-capsule', $text, $text, 'activate_plugins', 'wp-time-capsule-premium', 'tc_backup_premium'); */
    }
	
	$text = __('Settings', 'wptc');
    add_submenu_page('wp-time-capsule-monitor', $text, $text, 'activate_plugins', 'wp-time-capsule', 'wordpress_time_capsule_admin_menu_contents');
    
    $text = __('WPTC PRO', 'wptc');
    add_submenu_page('wp-time-capsule-monitor', $text, $text, 'activate_plugins', 'wp-time-capsule-pro', 'wordpress_time_capsule_pro_contents');
}

/**
 * A wrapper function that includes the backup to Dropbox options page
 * @return void
 */
function wordpress_time_capsule_admin_menu_contents()
{
    $uri = rtrim(plugins_url('wp-time-capsule'), '/') ;

    if(version_compare(PHP_VERSION, MINUMUM_PHP_VERSION) >= 0) {
        include 'Views/wptc-options.php';
    } else {
        include 'Views/wptc-deprecated.php';
    }
}

/**
 * A wrapper function that includes the pro version of Time Capsule Contents and options
 * @return void
 */
function wordpress_time_capsule_pro_contents()
{
    $uri = rtrim(plugins_url('wp-time-capsule-pro'), '/') ;

    include 'Views/wptc-pro.php';
}

/**
 * A wrapper function that includes the backup to Dropbox monitor page
 * @return void
 */
function wordpress_time_capsule_monitor()
{
	////file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----coming to this func------ \n",FILE_APPEND);
    if (!WPTC_Factory::get('dropbox')->is_authorized()) {
        wordpress_time_capsule_admin_menu_contents();
    } else {
        $uri = rtrim(plugins_url('wp-time-capsule'), '/');
        include 'Views/wptc-monitor.php';
    }
}

/**
 * A wrapper function that includes the backup to Dropbox premium page
 * @return void
 */
function tc_backup_premium()
{
    /* wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-tabs'); */

    $uri = rtrim(plugins_url('wp-time-capsule'), '/');
    include 'Views/wptc-premium.php';
}

/**
 * A wrapper function for the file tree AJAX request
 * @return void
 */
function tc_backup_file_tree()
{
    include 'Views/wptc-file-tree.php';
    die();
}

/**
 * A wrapper function for the progress AJAX request
 * @return void
 */
function tc_backup_progress()
{
    include 'Views/wptc-progress.php';
    die();
}

/**
 * A wrapper function for the progress AJAX request
 * @return void
 */
function get_this_day_backups_callback()
{
	//note that we are getting the ajax function data via $_POST.
	$backupIds = $_POST['data'];
	
	//getting the backups
	$processed_files = WPTC_Factory::get('processed-files');
	echo $processed_files->get_this_backups_html($backupIds);
	
}

function get_in_progress_tcbackup_callback(){
	$in_progress_status = WPTC_Factory::get('config')->get_option('in_progress');
	echo $in_progress_status;
}

function start_backup_tc_callback(){
	//for backup during update
	$backup = new WPTC_BackupController();
	$backup->backup_now();
	
	//give the name for this backup
	store_name_for_this_backup_callback("Updated on ".date('H-i', time()));
}

function start_fresh_backup_tc_callback(){
	//for fresh backup
	$backup = new WPTC_BackupController();
	
	//delete the previous DB values based on keep revision value
	$backup->delete_prev_records();
	
	//do the backup process
	$backup->backup_now();
}

function stop_fresh_backup_tc_callback(){
	//for backup during update
	$backup = new WPTC_BackupController();
	$backup->stop();

    add_settings_error('wptc_monitor', 'backup_stopped', __('Backup stopped.', 'wptc'), 'updated');
}

function get_check_to_show_dialog_callback(){
	if(!WPTC_Factory::get('config')->get_option('before_backup')){
		$before_backup['before_backup'] = 'no';
	}
	else{
		$before_backup['before_backup'] = WPTC_Factory::get('config')->get_option('before_backup');
	}
	//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----before_backups------- ".var_export($before_backup,true)."\n",FILE_APPEND);
	echo json_encode($before_backup);
}

function store_name_for_this_backup_callback($this_name = null){
	if(empty($this_name))
	{
		$this_name = $_POST['data'];
	}
	return store_backup_name($this_name);
	
	/* //process to store the name in db
	$dbObj = WPTC_Factory::db();
	$dbObj->insert("{$dbObj->prefix}wptc_backup_names", $data); */
	
}

function delete_last_month_backups_callback(){
	$processed_files = WPTC_Factory::get('processed-files');
	echo $processed_files->delete_last_month_backups();
}

function start_restore_tc_callback(){
	require_once(ABSPATH . "/wp-admin/includes/class-wp-filesystem-base.php");
	require_once(ABSPATH . "/wp-admin/includes/class-wp-filesystem-direct.php");
	require_once(ABSPATH . "/wp-admin/includes/class-wp-filesystem-ftpext.php");
	require_once(ABSPATH . "/wp-admin/includes/class-wp-filesystem-ssh2.php");
	require_once(ABSPATH . "/wp-admin/includes/file.php");
	if(isset($_POST['data'])){
		$data = $_POST['data'];
	}
	else{
		$data = array();
	}
	//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ------whats gonna be callback data------- ".var_export($data,true)."\n",FILE_APPEND);
	
	//doing all the process of restore-downloading here
	//setting the script start time
	global $start_time_tc;
	$start_time_tc= microtime(true);
	
	//get file system credentials
	$creds = request_filesystem_credentials('wp-time-capsule.php', "", false, false, null);
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
	if(isset($data['files_to_restore'])){
		$files_to_restore = $data['files_to_restore'];
	}
	if(isset($data['cur_res_b_id'])){
            $cur_res_b_id = $data['cur_res_b_id'];
            $Process = new WPTC_Processed_Files;  // correct
            $backup_datas = $Process->get_this_backups($cur_res_b_id);
            $files_to_restore[$rev]=array();
            foreach ($backup_datas[$cur_res_b_id] as $filesdata)
            {
                $rev = $filesdata->revision_id;
                if($filesdata->file!=""){
                    $files_to_restore[$rev] = array('file_name'=>$filesdata->file,'file_size'=>$filesdata->uploaded_file_size);
                }
            }
	}

	$config = WPTC_Factory::get('config');
	$backup = new WPTC_BackupController();

	//just to send the bridge file url via ajax to javascript
	if(isset($data['getAndStoreBridgeURL']) && !empty($data['getAndStoreBridgeURL'])){
		//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----coming insisde storing bridge folder name------ ".var_export($data,true)."\n",FILE_APPEND);
		//prepare a bridge filename with hash
		$current_bridge_file_name = "wp-tcapsule-bridge-".hash("crc32", microtime(true));
		$config->set_option('current_bridge_file_name', $current_bridge_file_name);
		
		echo $current_bridge_file_name;
		exit;
	}

	if(isset($data['files_to_restore']) && !empty($files_to_restore))
	{
		//store the current b_id in options table; the current b_id will be used to determine the future old files which are to be restored to the prev restore point
		if(isset($data['cur_res_b_id']) && !empty($cur_res_b_id) && $cur_res_b_id != 'false'){
			$config->set_option('cur_res_b_id', $cur_res_b_id);
			$config->set_option('in_restore_deletion', false);
		}
		
		//send email to the admin indicating that the restore process is started
		$this_admin_email = get_option("admin_email");
		//$message = site_url() . "/".$current_bridge_file_name."/tc-init.php?continue=true";  //the link to the bridge init file
		
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
			//echo json_encode('over');
			echo "over";
			die();
			return;
		}
		
		//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ---direct new restore execute------- \n",FILE_APPEND);
		$restore_result = $backup->new_restore_execute();
	}
}

function get_and_store_before_backup_callback(){
	$current_value_before_backup = WPTC_Factory::get('config')->get_option('before_backup');
	if($current_value_before_backup == 'no'){
		$is_show = 'no';
	}
	else{
		$is_show = 'yes';
	}
	echo $is_show;
	die();
}

function store_backup_name($backup_name = '', $backup_id = null){
	if(empty($backup_id)){
		$backup_id = getTcCookie('backupID');
		if(empty($backup_id)){
			return false;
		}
	}
	$dbObj = WPTC_Factory::db();
	$data['backup_name'] = $backup_name;
	$data['backup_id'] = $backup_id;
	$this_insert_result = $dbObj->insert("{$dbObj->prefix}wptc_backup_names", $data);
	//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ---to be inserted------- ".var_export($data,true)."\n",FILE_APPEND);
	//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----this_insert_result of backup name------- ".var_export(mysql_error(),true)."\n",FILE_APPEND);
	if($this_insert_result)
	{
		return true;
	}
	else{
		return false;
	}
}


/**
 * A wrapper function that executes the backup
 * @return void
 */
function execute_tcdropbox_backup()
{
	WPTC_Factory::get('logger')->delete_log();
	WPTC_Factory::get('logger')->log(sprintf(__('Backup started on %s.', 'wptc'), date("l F j, Y", strtotime(current_time('mysql')))));
	
    $time = ini_get('max_execution_time');
    WPTC_Factory::get('logger')->log(sprintf(
        __('Your time limit is %s and your memory limit is %s'),
        $time ? $time . ' ' . __('seconds', 'wptc') : __('unlimited', 'wptc'),
        ini_get('memory_limit')
    ));

    if (ini_get('safe_mode')) {
        WPTC_Factory::get('logger')->log(__("Safe mode is enabled on your server so the PHP time and memory limit cannot be set by the backup process. So if your backup fails it's highly probable that these settings are too low.", 'wptc'));
    }

    WPTC_Factory::get('config')->set_option('in_progress', true);
	
	WPTC_Factory::get('config')->set_option('ignored_files_count', 0);
	//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ---coming just above running------ ".var_export($args,true)."\n",FILE_APPEND);
    if (defined('WPTC_TEST_MODE')) {
        run_tc_backup();
    } else {
		wp_schedule_single_event(time(), 'run_tc_backup_hook');
        wp_schedule_event(time(), 'every_min', 'monitor_tcdropbox_backup_hook');
    }
}



function execute_tcdrobox_restore()
{
    WPTC_Factory::get('logger')->delete_log();
    WPTC_Factory::get('logger')->log(sprintf(__('REstore started on %s.', 'wptc'), date("l F j, Y", strtotime(current_time('mysql')))));

    $time = ini_get('max_execution_time');
    WPTC_Factory::get('logger')->log(sprintf(
        __('Your time limit is %s and your memory limit is %s'),
        $time ? $time . ' ' . __('seconds', 'wptc') : __('unlimited', 'wptc'),
        ini_get('memory_limit')
    ));

    if (ini_get('safe_mode')) {
        WPTC_Factory::get('logger')->log(__("Safe mode is enabled on your server so the PHP time and memory limit cannot be set by the REstore process. So if your Restore fails it's highly probable that these settings are too low.", 'wptc'));
    }
	WPTC_Factory::get('config')->set_option('in_progress_restore', true);
	
	run_tcdropbox_restore();				//since we are using manual ajax function
	
}

/**
 * @return void
 */
function monitor_tcdropbox_backup()
{
	//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----am i even coming inside------\n",FILE_APPEND);
    $config = WPTC_Factory::get('config');
	//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ---is a log file coming------ ".var_export(WPTC_Factory::get('logger')->get_log_file(),true)."\n",FILE_APPEND);
    $mtime = filemtime(WPTC_Factory::get('logger')->get_log_file());

    if ($config->get_option('in_progress') && ($mtime < time() - NO_ACTIVITY_WAIT_TIME)) {
        WPTC_Factory::get('logger')->log(sprintf(__('There has been no backup activity for a long time. Attempting to resume the backup.' , 'wptc'), 5));
        $config->set_option('is_running', false);

        wp_schedule_single_event(time(), 'run_tc_backup_hook');
    }
}

/**
 * @return void
 */
function run_tc_backup()
{
	file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----trying to run dropbox backup------ \n",FILE_APPEND);
    $options = WPTC_Factory::get('config');
    if (!$options->get_option('is_running')) {
        $options->set_option('is_running', true);
        WPTC_BackupController::construct()->execute();
    }
}


function run_tcdropbox_restore()
{
	//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ------calling run dropbox restore func-------- \n",FILE_APPEND);
	$options = WPTC_Factory::get('config');
    if (!$options->get_option('is_running_restore')) {
		$options->set_option('is_running_restore', true);
        WPTC_BackupController::construct()->new_restore_execute();
	}
	else
	{
		return 'over';
	}
}

/**
 * Adds a set of custom intervals to the cron schedule list
 * @param  $schedules
 * @return array
 */
function backup_tc_cron_schedules($schedules)
{
    $new_schedules = array(
        'every_min' => array(
            'interval' => 60,
            'display' => 'WPTC - Monitor'
        ),
        'daily' => array(
            'interval' => 86400,
            'display' => 'WPTC - Daily'
        ),
        'weekly' => array(
            'interval' => 604800,
            'display' => 'WPTC - Weekly'
        ),
        'fortnightly' => array(
            'interval' => 1209600,
            'display' => 'WPTC - Fortnightly'
        ),
        'monthly' => array(
            'interval' => 2419200,
            'display' => 'WPTC - Once Every 4 weeks'
        ),
        'two_monthly' => array(
            'interval' => 4838400,
            'display' => 'WPTC - Once Every 8 weeks'
        ),
        'three_monthly' => array(
            'interval' => 7257600,
            'display' => 'WPTC - Once Every 12 weeks'
        ),
    );

    return array_merge($schedules, $new_schedules);
}

function wptc_install()
{
    $wpdb = WPTC_Factory::db();

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $table_name = $wpdb->prefix . 'wptc_options';
    dbDelta("CREATE TABLE $table_name (
        name varchar(50) NOT NULL,
        value varchar(255) NOT NULL,
        UNIQUE KEY name (name)
    );");
	

    $table_name = $wpdb->prefix . 'wptc_processed_files';
    dbDelta("CREATE TABLE $table_name (
	  `file` varchar(255) DEFAULT NULL,
	  `offset` int(11) NOT NULL DEFAULT '0',
	  `uploadid` varchar(50) DEFAULT NULL,
	  `file_id` int(11) NOT NULL AUTO_INCREMENT,
	  `backupID` varchar(50) DEFAULT NULL,
	  `revision_number` varchar(50) DEFAULT NULL,
	  `revision_id` varchar(50) DEFAULT NULL,
	  `mtime_during_upload` varchar(22) DEFAULT NULL,
	  `uploaded_file_size` text,
	  PRIMARY KEY (`file_id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=latin1;");

    $table_name = $wpdb->prefix . 'wptc_processed_dbtables';
    dbDelta("CREATE TABLE $table_name (
        name varchar(255) NOT NULL,
        count int NOT NULL DEFAULT 0,
        UNIQUE KEY name (name)
    );");

    $table_name = $wpdb->prefix . 'wptc_excluded_files';
    dbDelta("CREATE TABLE $table_name (
        file varchar(255) NOT NULL,
        isdir tinyint(1) NOT NULL,
        UNIQUE KEY file (file)
    );");
	
	$table_name = $wpdb->prefix . 'wptc_processed_restored_files';
    dbDelta("CREATE TABLE $table_name (
	  `file` varchar(255) NOT NULL,
	  `offset` int(50) DEFAULT '0',
	  `uploadid` varchar(50) DEFAULT NULL,
	  `file_id` int(11) NOT NULL AUTO_INCREMENT,
	  `backupID` double DEFAULT NULL,
	  `revision_number` varchar(50) DEFAULT NULL,
	  `revision_id` varchar(50) DEFAULT NULL,
	  `mtime_during_upload` varchar(22) DEFAULT NULL,
	  `download_status` text,
	  `uploaded_file_size` text,
	  `process_type` text,
	  `copy_status` text,
	  PRIMARY KEY (`file_id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=latin1;");
	
	$table_name = $wpdb->prefix . 'wptc_backup_names';
    dbDelta("CREATE TABLE $table_name (
	  `this_id` int(11) NOT NULL AUTO_INCREMENT,
	  `backup_name` text,
	  `backup_id` text,
	  PRIMARY KEY (`this_id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=latin1;");
        $table_name = $wpdb->prefix . 'wptc_current_process';
    dbDelta("CREATE TABLE IF NOT EXISTS $table_name (
           `id` bigint(20) NOT NULL AUTO_INCREMENT,
           `file_path` varchar(600) NOT NULL,
           `status` char(1) NOT NULL DEFAULT 'Q' COMMENT 'P=Processed, Q= In Queue, S- Skipped',
           `processed_time` varchar(30) NOT NULL,
           PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");
	/* $table_name = $wpdb->prefix . 'wptc_to_be_restored_files';
    dbDelta("CREATE TABLE $table_name` (
	  `file` text,
	  `revision_id` int(50) DEFAULT NULL,
	  `file_id` int(255) NOT NULL AUTO_INCREMENT,
	  `offset` int(255) DEFAULT NULL,
	  PRIMARY KEY (`file_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=latin1;"); */

    //Ensure that there where no insert errors
    $errors = array();

    global $EZSQL_ERROR;
    if ($EZSQL_ERROR) {
        foreach ($EZSQL_ERROR as $error) {
            if (preg_match("/^CREATE TABLE {$wpdb->prefix}wptc_/", $error['query']))
                $errors[] = $error['error_str'];
        }

        delete_option('wptc-init-errors');
        add_option('wptc-init-errors', implode($errors, '<br />'), false, 'no');
    }

    //Only set the DB version if there are no errors
    if (empty($errors)) {
        WPTC_Factory::get('config')->set_option('database_version', WPTC_DATABASE_VERSION);
    }
}

function wptc_init()
{
    try {
        if (WPTC_Factory::get('config')->get_option('database_version') < WPTC_DATABASE_VERSION) {
            //wptc_install();
        }

        if (!get_option('wptc-premium-extensions')) {
            add_option('wptc-premium-extensions', array(), false, 'no');
        }
		
		if (!WPTC_Factory::get('config')->get_option('before_backup')) {
            WPTC_Factory::get('config')->set_option('before_backup', 'yes_no');
        }
		
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
}

function get_tcsanitized_home_path()
{
    //Needed for get_home_path() function and may not be loaded
    require_once(ABSPATH . 'wp-admin/includes/file.php');

    //If site address and WordPress address differ but are not in a different directory
    //then get_home_path will return '/' and cause issues.
    $home_path = get_home_path();
    if ($home_path == '/') {
        $home_path = ABSPATH;
    }

    return rtrim(str_replace('/', DIRECTORY_SEPARATOR, $home_path), DIRECTORY_SEPARATOR);
}


function setTcCookieNow($name, $value = false)
{
	$options_obj = WPTC_Factory::get('config');
	
	if(!$value)
	{
		$value = microtime(true);
	}
	
	$contents[$name] = $value;
	$_GLOBALS['this_cookie'] = $contents;
	$options_obj->set_option('this_cookie', serialize($contents));

	return true;
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

function write_in_tcmonitor_console($contents, $comments){
	return true;
	$this_handle = fopen(WP_CONTENT_DIR .'/monitor-console.php', 'r+');
	$contents_array = array();
	$contents_array['comments'] = $comments;
	$contents_array['contents'] = $contents;
	
	if(!$this_handle)
	{
		$this_handle = fopen(WP_CONTENT_DIR .'/monitor-console.php', 'w+');
		$file_contents .= '<?php ';
		$file_contents .= '$console_array = array();$return_console_array = array();';
	}
	else
	{
		fseek($this_handle, 0, SEEK_END);
		if(ftell($this_handle) < 3)
		{
			$file_contents .= '<?php ';
			$file_contents .= '$console_array = array();$return_console_array = array();';
		}
		else
		{
			fseek($this_handle, -168, SEEK_END);
		}
	}
	//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----constents inside------- ".var_export($contents,true)."\n",FILE_APPEND);
	$file_contents .= '$console_array['.microtime(true).'] = '."'".serialize($contents_array)."'".'; ';
	$file_contents .= 'foreach($console_array as $key => $value){if((microtime()-5000000) <= $key){$return_console_array[$key] = unserialize($value);}}echo json_encode($return_console_array);';
	
	fwrite($this_handle, $file_contents);
}

function deleteTcCookie(){
	
	$options_obj = WPTC_Factory::get('config');
	$options_obj->set_option('this_cookie', false);
	
	return true;
}

function my_tcadmin_notice() {
	$options_obj = WPTC_Factory::get('config');
	if(!$options_obj->get_option('restore_completed_notice')){
		//do nothing
	}
	else{
		$options_obj->set_option('restore_completed_notice', false);
		/* $notice_message = "<div class='updated'> <p> "._e( 'Restored Successfully', 'my-text-domain' )."</p> </div>";
		echo $notice_message; */
    }
}

//admin notice
add_action( 'admin_notices', 'my_tcadmin_notice' );

//More cron shedules
add_filter('cron_schedules', 'backup_tc_cron_schedules');

//Backup hooks
add_action('monitor_tcdropbox_backup_hook', 'monitor_tcdropbox_backup');
add_action('run_tc_backup_hook', 'run_tc_backup');
add_action('execute_periodic_drobox_backup', 'execute_tcdropbox_backup');
add_action('execute_instant_drobox_backup', 'execute_tcdropbox_backup');

//Register database install
register_activation_hook(__FILE__, 'wptc_install');

add_action('admin_init', 'wptc_init');
add_action('admin_enqueue_scripts', 'wptc_style');

//i18n language text domain
load_plugin_textdomain('wptc', false, 'wp-time-capsule/Languages/');

//update hooks
function register_the_js_events($hook) {
	wp_enqueue_style('tc-ui', plugins_url() . '/' . basename(dirname(__FILE__)) . '/tc-ui.css' );
	wp_enqueue_script('jquery');
	wp_enqueue_script( 'time-capsule-update-actions', plugins_url() . '/' . basename(dirname(__FILE__)) . '/time-capsule-update-actions.js' );
}
add_action('admin_enqueue_scripts', 'register_the_js_events');

if (is_admin()) {
    //WordPress filters and actions
    add_action('wp_ajax_file_tree', 'tc_backup_file_tree');
    add_action('wp_ajax_progress', 'tc_backup_progress');
	add_action('wp_ajax_get_this_day_backups', 'get_this_day_backups_callback');
	add_action('wp_ajax_get_in_progress_backup', 'get_in_progress_tcbackup_callback');
	add_action('wp_ajax_start_backup_tc', 'start_backup_tc_callback');  
	add_action('wp_ajax_store_name_for_this_backup', 'store_name_for_this_backup_callback');
	add_action('wp_ajax_start_fresh_backup_tc', 'start_fresh_backup_tc_callback');
	add_action('wp_ajax_stop_fresh_backup_tc', 'stop_fresh_backup_tc_callback');
	add_action('wp_ajax_get_check_to_show_dialog', 'get_check_to_show_dialog_callback');
	add_action('wp_ajax_start_restore_tc', 'start_restore_tc_callback');
	add_action('wp_ajax_get_and_store_before_backup', 'get_and_store_before_backup_callback');

    if (defined('MULTISITE') && MULTISITE) {
        add_action('network_admin_menu', 'wordpress_time_capsule_admin_menu');
    } else {
        add_action('admin_menu', 'wordpress_time_capsule_admin_menu');
    }
}


