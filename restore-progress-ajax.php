<?php
/* echo json_encode('over');
exit; */

//ajax file to handle restore progress
require_once("../../../wp-config.php");
require_once("wp-time-capsule.php");

//initialize a db object and config object
$dbObj = WPTC_Factory::db();
$config = WPTC_Factory::get('config');

$echo_array =array();

if(!$config->get_option('is_bridge_process'))
{
	if ($config->get_option('in_progress_restore')) 
	{
		//query to get all the files which are to be downloaded
		$total_files_array = $dbObj->get_results("SELECT * FROM {$dbObj->prefix}wptc_processed_restored_files ");
		$total_files_count = count($total_files_array);

		//query to get all the files which are downloaded
		$downloaded_files_array = $dbObj->get_results("SELECT * FROM {$dbObj->prefix}wptc_processed_restored_files WHERE download_status = 'done'");
		$downloaded_files_count = count($downloaded_files_array);

		$downloaded_files_percent = (($downloaded_files_count / $total_files_count) * 100);

		$echo_array['total_files_count'] = $total_files_count;
		$echo_array['downloaded_files_count'] = $downloaded_files_count;
		$echo_array['downloaded_files_percent'] = $downloaded_files_percent;
	}
}
else
{
	//query to get all the files which are to be copied
	$total_files_array = $dbObj->get_results("SELECT * FROM {$dbObj->prefix}wptc_processed_restored_files ");
	$total_files_count = count($total_files_array);

	//query to get all the files which are copied
	$copied_files_array = $dbObj->get_results("SELECT * FROM {$dbObj->prefix}wptc_processed_restored_files WHERE copy_status = true");
	$copied_files_count = count($copied_files_array);

	$copied_files_percent = (($copied_files_count / $total_files_count) * 100);

	$echo_array['total_files_count'] = $total_files_count;
	$echo_array['copied_files_count'] = $copied_files_count;
	$echo_array['copied_files_percent'] = $copied_files_percent;
}
echo json_encode($echo_array);