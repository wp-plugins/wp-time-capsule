<?php
/**
 * A class with functions the perform a backup of WordPress
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
class WPTC_BackupController
{
    private
        $dropbox,
        $config,
        $output,
        $processed_file_count
        ;

    public static function construct()
    {
        return new self();
    }

    public function __construct($output = null)
    {
        $this->config = WPTC_Factory::get('config');
        $this->dropbox = WPTC_Factory::get('dropbox');
        $this->output = $output ? $output : WPTC_Extension_Manager::construct()->get_output();
    }

    public function backup_path($path, $content_flag, $always_include = null)
    {
		if (!$this->config->get_option('in_progress')) {
			//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----returning not getting in_progress------- \n",FILE_APPEND);
            return;
        }
        
        
//        if (!$dropbox_path) {
            $dropbox_path = get_tcsanitized_home_path();
//        }

        $file_list = WPTC_Factory::get('fileList');

        $current_processed_files = $uploaded_files = array();

        $next_check = time() + 5;
        $total_files = $this->config->get_option('total_file_count');

        $processed_files = WPTC_Factory::get('processed-files');

        $this->processed_file_count = $processed_files->get_processed_files_count();

        $last_percent = 0;
//        file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----Called------- \n",FILE_APPEND);
        if (file_exists($path)) {
            $source = realpath($path);
            
			$Mdir  = new RecursiveDirectoryIterator($source);
			$Mfile = new RecursiveIteratorIterator($Mdir, RecursiveIteratorIterator::SELF_FIRST,RecursiveIteratorIterator::CATCH_GET_CHILD);
                        $total_files = iterator_count($Mfile);
                        if(!$content_flag)
                        {
                             $contDir = new RecursiveDirectoryIterator(realpath(WP_CONTENT_DIR));
                             $contfiles = new RecursiveIteratorIterator($contDir, RecursiveIteratorIterator::SELF_FIRST,RecursiveIteratorIterator::CATCH_GET_CHILD);
                             $TFiles[0] = $contfiles;
                             $TFiles[1] = $Mfile;
                             $total_files = $total_files+iterator_count($contfiles);
                        }
                        else
                        {
                            $TFiles[0]=$Mfile;
                            
                        }
                         global $wpdb;
                        //Getting files from iterator and insert into database
                    if (!$this->config->get_option('getfileslist')) {
//                        file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----set files------- \n",FILE_APPEND);
                            $this->iteratorIntoDb($TFiles);
                    }  
                    
                    $Qfiles=$wpdb->get_results("SELECT * FROM ".$wpdb->prefix."wptc_current_process WHERE status='Q' ORDER BY file_path DESC");
//                    if(!count($Qfiles)>0)
//                    {
//                        
//                    }
//                    ob_start();
//                    echo "<pre>";
//                    foreach($Qfiles as $filess){
//                    print_r($filess->id);
//                    }
//           file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',  ob_get_clean(),FILE_APPEND); 
            //$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD);
                        
			$ignored_files_count = $this->config->get_option('ignored_files_count');
			$current_file = $this->replace_slashes(WP_PLUGIN_DIR) . DIRECTORY_SEPARATOR .TC_PLUGIN_NAME;			//current plugin's file path
			//$ignored_files_count = 0;
//			file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----total files are------ ".$total_files."\n",FILE_APPEND);
			$this->config->set_option('total_file_count', $total_files);
			$thisLoopCOunt = 0;
                        
                        
                        
                            foreach ($Qfiles as $file_info) {
				$fid=$file_info->id;
				$current_processed_files = $uploaded_files = array();
				
//				file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----loop -----".$file_info->file_path,FILE_APPEND);
                $file = $file_info->file_path;
                if (time() > $next_check) {
                    $this->config->die_if_stopped();
					
					//am adding file to the DB after dropbox upload itself so am commenting the line below 
                    //$processed_files->add_files($current_processed_files);									//manual
                    $msg = null;

                    if ($this->processed_file_count > 0) {
                        $msg = _n(__('Processed 1 file.', 'wptc'), sprintf(__('Processed %s files.', 'wptc'), $this->processed_file_count), $this->processed_file_count, 'wptc');
                    }

                    if ($total_files > 0) {
                        $percent_done = round(($this->processed_file_count / $total_files) * 100, 0);
                        if ($percent_done < 100) {
                            if ($percent_done < 1) {
                                $percent_done = 1;
                            }

                            if ($percent_done > $last_percent) {
                                $msg .= sprintf(__('Approximately %s%% complete.', 'wptc'), $percent_done);
                                $last_percent = $percent_done;
                            }
                        }
                    }

                    if ($msg) {
                        WPTC_Factory::get('logger')->log($msg, $uploaded_files);
                    }

                    $next_check = time() + 5;
                    $uploaded_files = $current_processed_files = array();
                }

                if ($file != $always_include && $file_list->is_excluded($file)) {
//                    file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----ignored is_excluded-----\n".$file,FILE_APPEND);
                    $this->writeStatusofFile($fid,'S');
					$ignored_files_count += 1;
                                        
                    continue;
                }

                if ($file_list->in_ignore_list($file)) {
//                    file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----ignored in ignore list-----".$file,FILE_APPEND);
                    $this->writeStatusofFile($fid,'S');
					$ignored_files_count += 1;
                    continue;
                }

                if (is_file($file)) {
					//if it is our plugin file ; ignore it;
					//$current_file = substr(dirname(__FILE__), 0, (strlen(dirname(__FILE__)) - 8));
					if(strpos($file, substr(ABSPATH, 0, -1) . DIRECTORY_SEPARATOR . 'wp-config.php') !== false){
						// $ignored_files_count += 1;
						// continue;
					}
					if(strpos($file, $current_file) !== false)
					{
						$ignored_files_count += 1;
//                                                file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----ignored is_excluded-----\n".$file,FILE_APPEND);
						//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----skipping plugin file------- ".var_export($current_file,true)."\n",FILE_APPEND);
                                                $this->writeStatusofFile($fid,'S');
						continue;
					}
                    $processed_file  = $processed_files->get_file($file);
                    if ( $processed_file && $processed_file->offset == 0 && (filemtime($file) == $processed_file->mtime_during_upload) ) {
						if(filemtime($file) == $processed_file->mtime_during_upload)
						{
							//this if loop is only for testing
							//update a count ; for progress information
							//$ignored_files_count += $this->config->get_option('ignored_files_count');
							
							//write_in_tcmonitor_console($file, '--contiuing by mtime-');
							////file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----contiuing by mtime------- ".var_export($file,true)."\n",FILE_APPEND);
						}
						//$ignored_files_count += 1;
//                                                file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----contiuing by mtime------- ".var_export($file,true)."\n",FILE_APPEND);
                                                $this->writeStatusofFile($fid,'S');
						continue;
                    }
					
					//the following if statement is only for taking the files count
                    if (dirname($file) == $this->config->get_backup_dir() && $file != $always_include) {
//                     file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----backup path------- ".var_export($file,true)."\n",FILE_APPEND);
						$ignored_files_count += 1;
                                                $this->writeStatusofFile($fid,'S');
                        continue;
                    }
					$this->config->set_option('ignored_files_count', $ignored_files_count);
					$this->config->set_option('supposed_total_files_count', ($total_files - $ignored_files_count));
					
					$forced_break = false;
					//write_in_tcmonitor_console(print_r($file), 'uploading this file');
					//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----plugin_direc------- ".var_export(substr(dirname(__FILE__), 0, (strlen(dirname(__FILE__)) - 8)),true)."\n",FILE_APPEND);
					//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----uploading this file ------ ".var_export($file,true)."\n",FILE_APPEND);
					$dropboxOutput = $this->output->out($dropbox_path, $file, $processed_file);
					//write_in_tcmonitor_console((array)$dropboxOutput, '----dropboxOutput----');
					if(!empty($dropboxOutput)){
//						file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----file added------- ".$file."\n",FILE_APPEND);
//						file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----dropboxOutput------- ".var_export((array)$dropboxOutput,true)."\n",FILE_APPEND);
                                                $this->writeStatusofFile($fid,'P');
					}
					else{
//						file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----some error in Drop------- ",FILE_APPEND);
					}
                    if ($dropboxOutput) {
						$thisLoopCOunt++;
						//adding revision details 				//manual
						$this_dropbox_array = (array)$dropboxOutput['body'];
						$version_array = array();
						$version_array['revision_number'] = $this_dropbox_array['revision'];
						$version_array['revision_id'] = $this_dropbox_array['rev'];
						$version_array['uploaded_file_size'] = $this_dropbox_array['bytes'];
						
                        $uploaded_files[] = array(
                            'file' => str_replace($dropbox_path . DIRECTORY_SEPARATOR, '', WPTC_DropboxFacade::remove_secret($file)),
                            'mtime' => filemtime($file),
                        );
						
						//refreshing the processed file obj ; this is necessary only for chunked upload
						$processed_file  = $processed_files->get_file($file);
						
                        if ($processed_file && $processed_file->offset > 0) {
							//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----calling file complete------- ".var_export($processed_file,true)."\n",FILE_APPEND);
                            $processed_files->file_complete($file);
                        }
						////file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----thisLoopCOunt------- ".var_export($thisLoopCOunt,true)."\n",FILE_APPEND);
						if($thisLoopCOunt > 3)
						{
							$forced_break = true;
						}
						
						//am adding version details to DB using this code							manual
						$file_with_version = array();
						
						if(!empty($version_array))
						{
							$file_with_version = $version_array;
						}
						$file_with_version['filename'] = $file;
						$file_with_version['mtime_during_upload'] = filemtime($file);
						
						$current_processed_files[] = $file_with_version;			//manual
						$this->processed_file_count++;
						
						////file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----processed_file------ ".var_export($processed_file,true)."\n",FILE_APPEND);
						//adding files to DB here
						//if ($processed_file && $processed_file->offset != 0) {
							$processed_files->add_files($current_processed_files);									//manual
						//}
						
						if($forced_break == true)
						{
							//break;
						}
                    }
					
                }
				else{
//                                        file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----not a file------ ".$file,FILE_APPEND);
					$ignored_files_count += 1;
                                        $this->writeStatusofFile($fid,'S');
				}
            }
                       
			$this->config->set_option('ignored_files_count', $ignored_files_count);
        }
    }
	
	public function replace_slashes($directory_name){
		return str_replace(array("/"), DIRECTORY_SEPARATOR, $directory_name);
	}

    public function execute()
    {
        $options_obj = WPTC_Factory::get('config');
        $contents=@unserialize($options_obj->get_option('this_cookie'));
        $backup_id=$contents['backupID'];
	$manager = WPTC_Extension_Manager::construct();
        $logger = WPTC_Factory::get('logger');
        $dbBackup = WPTC_Factory::get('databaseBackup');

        $this->config->set_time_limit();
        $this->config->set_memory_limit();
        
        try {

            if (!$this->dropbox->is_authorized()) {
                $logger->log(__('Your Dropbox account is not authorized yet.', 'wptc'),'backup_error',$backup_id);

                return;
            }
            $start=  microtime(true);
            //Create the SQL backups
            $dbStatus = $dbBackup->get_status();
			if ($dbStatus == WPTC_DatabaseBackup::NOT_STARTED) {
                if ($dbStatus == WPTC_DatabaseBackup::IN_PROGRESS) {
                    $logger->log(__('Resuming SQL backup.', 'wptc'),'backup_progress',$backup_id);
                } else {
                    $logger->log(__('Starting SQL backup.', 'wptc'),'backup_progress',$backup_id);
                }

                $dbBackup->execute();

                $logger->log(__('SQL backup complete. Starting file backup.', 'wptc'),'backup_progress',$backup_id);
            }
            $timetaken=  microtime(true)-$start;
//            file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php','time Taken for db'.$timetaken,FILE_APPEND);
              $start=  microtime(true);
            if ($this->output->start()) {
                $home_path=get_tcsanitized_home_path();
                $content_path=WP_CONTENT_DIR;
                if($content_path!="")
                {
                    $Check =  str_replace($home_path,'MaTcHeD_cOnTeNt', $content_path);
                    $MPos = strpos($Check,'MaTcHeD_cOnTeNt');
                    if($MPos !== false)
                    {
                        $inside=true;
                    }
                    else 
                    {
                        $inside=false;
                    }
                }
                //Finding the Content Dir inside the rootpath or not
                
                 
                
                
                //Backup the content dir first
                
                
                
                //$this->backup_path(WP_CONTENT_DIR, dirname(WP_CONTENT_DIR), $dbBackup->get_file());
                
				//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',WP_CONTENT_DIR,FILE_APPEND);
				
				//Now backup the blog root
                $this->backup_path(get_tcsanitized_home_path(),$inside,$dbBackup->get_file());
				
                //End any output extensions
                $this->output->end();

                //Record the number of files processed to make the progress meter more accurate
                $this->config->set_option('total_file_count', $this->processed_file_count);
            }
            $timetaken=  microtime(true)-$start;
//            file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php','time Taken for file'.$timetaken,FILE_APPEND);
			//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----going to go inside complete------ \n",FILE_APPEND);
            $manager->complete();
            $this->config->set_option('last_process_restore',false);
            //Update log file with stats
            $logger->log(__('Backup complete.', 'wptc'),'backup_complete',$backup_id);
            $logger->log(sprintf(__('A total of %s files were processed.', 'wptc'), $this->processed_file_count),'backup_complete',$backup_id);
            $logger->log(sprintf(
                __('A total of %dMB of memory was used to complete this backup.', 'wptc'),
                (memory_get_usage(true) / 1048576)
            ),'backup_complete',$backup_id);

            //Process the log file using the default backup output
            $root = false;
            if (get_class($this->output) != 'WPTC_Extension_DefaultOutput') {
                $this->output = new WPTC_Extension_DefaultOutput();
                $root = true;
            }
			
			//this is the log file upload; am cutting the log file upload to dropbox
            //$this->output->set_root($root)->out(get_tcsanitized_home_path(), $logger->get_log_file());				//manual
			
			$dbBackup->clean_up();		//to clear the db-sql file
			
            $this->config
                ->complete()
                ->log_finished_time()
                ;
            $this->clean_up();

        } catch (Exception $e) {
            if ($e->getMessage() == 'Unauthorized') {
                $logger->log(__('The plugin is no longer authorized with Dropbox.', 'wptc'),'backup_error',$backup_id);
            } else {
                $logger->log(__('A fatal error occured: ', 'wptc') . $e->getMessage(),'backup_error',$backup_id);
            }

            $manager->failure();
            $this->stop();
        }
    }
	
	public function restore_path($file = null, $version = null, $dropbox_path = null)
    {
        if (!$this->config->get_option('in_progress_restore')) {
			//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----returning not getting in_progress------- \n",FILE_APPEND);
            return;
        }

        if (!$dropbox_path) {
            $dropbox_path = get_tcsanitized_home_path();
        }
        
       
        $current_processed_files = $uploaded_files = array();

        $next_check = time() + 5;
        //$total_files = $this->config->get_option('total_file_count');

        //$processed_files = WPTC_Factory::get('processed-files');
		$processed_files = WPTC_Factory::get('processed-restoredfiles',true);   //this variable holds all the files which are already restored along with some info.
				
        $this->processed_file_count = $processed_files->get_file_count();

        $last_percent = 0;
		//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ------db COMMAND EXECUTED below to get files to be restored-------- \n",FILE_APPEND);
		if(true)
		{
			//we might wanna add any condition to avoid getting all files list again ... if we are doing bridge copy
			$restore_queue = $processed_files -> get_restore_queue_from_base();		//this variable holds all the files which are to be restored
			//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ------files_to_be_restored-------- ".var_export($restore_queue,true)."\n",FILE_APPEND);
		}
		
		//refreshing
		$processed_files = WPTC_Factory::get('processed-restoredfiles',true);
		
		foreach ($restore_queue as $key => $value) {
			$value_array = array();
			$value_array = (array)$value;
			$file = $value_array['file'];
			$version = $value_array['revision_id'];
			
			$current_processed_files = $uploaded_files = array();
			
			$processed_file  = $processed_files->get_file($file);
			//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----Before update check------ ".var_export($processed_file,true)."\n",FILE_APPEND);
			if ( ($processed_file && $processed_file->offset == 0 && $processed_file->uploaded_file_size < 4024000 && $processed_file->download_status != "notDone") || ($processed_file && $processed_file->offset >= $processed_file->uploaded_file_size && $processed_file->download_status != "notDone")  ) {
				//if processed_file value is not null , then the file is already restored so it needs to be skipped; but in case of chunked restored we should not skip it until the whole file is restored
				//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----contiuing by already restored file- or chunked upload complete------ ".var_export($processed_file->file,true)."\n",FILE_APPEND);
				$this->config->set_option('chunked', false);
				continue;
			}
			
			//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----downloading this file ------ ".var_export(stripslashes($file),true)."\n",FILE_APPEND);
			$dropboxOutput = $this->output->drop_download($dropbox_path, $file, $version, $processed_file, (array)$value);
			//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----dropboxOutput------- ".var_export((array)$dropboxOutput,true)."\n",FILE_APPEND);
			if ($dropboxOutput) {
			
				$uploaded_files[] = array(
					'file' => str_replace($dropbox_path . DIRECTORY_SEPARATOR, '', WPTC_DropboxFacade::remove_secret($file)),
					'mtime' => 0,
				);
				
				//am setting the is_running as false by this condition for now
				$dbox_array = (array)$dropboxOutput;
				if(!empty($dbox_array['chunked']))
				{
					$this->config->set_option('is_running_restore', false);
					$this->config->set_option('chunked', true);
				}
				else
				{
					$this->config->set_option('chunked', false);
				}
				 //adding files to DB here
				$processed_file  = $processed_files->get_file($file);
				if (empty($processed_file) && !($this->config->get_option('chunked'))) {
					
					$value_array['download_status'] = 'done';
					//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ---Inside the loop------ ".var_export($value_array,true)."\n",FILE_APPEND);
					
					$current_processed_files[] = $value_array;			//manual
					$this->processed_file_count++;
					
					$processed_files->add_files($current_processed_files);									//manual
				}
			}
			//check timeout and echo "callAgain" for each download files
			$this->maybe_call_again_tc();
		}
		
    }
	
	public function maybe_call_again_tc(){
		global $start_time_tc;
		if((microtime(true) - $start_time_tc) >= 20)
		{
			//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ------startTimeIs-------- ".var_export($start_time_tc,true)."\n",FILE_APPEND);
			echo json_encode("callAgain");
			exit;
		}
    }
	
	public function new_restore_execute()
    {
		//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----trying to run new restore execute------ \n",FILE_APPEND);
		$this->config = WPTC_Factory::get('config');
        $manager = WPTC_Extension_Manager::construct();
        $logger = WPTC_Factory::get('logger');
		$this->dropbox = WPTC_Factory::get('dropbox');
        //$dbBackup = WPTC_Factory::get('databaseBackup');

        $this->config->set_time_limit();
        $this->config->set_memory_limit();
        $restore_action_id = $this->config->get_option('restore_action_id');
        try {

            if (!$this->dropbox->is_authorized()) {
                $logger->log(__('Your Dropbox account is not authorized yet.', 'wptc'),'restore_error',$restore_action_id);

                return;
            }
//            $logger->log(__('Restore in Progress.', 'wptc'),'restore_start');
            if ($this->output->start()) {
			
				if(true)
				{
					$this->restore_path();
                }
	
                $this->output->end();

                //Record the number of files processed to make the progress meter more accurate
                $this->config->set_option('total_file_count', $this->processed_file_count);
            }

            //$manager->complete();

            //Update log file with stats
            $this->config->set_option('last_process_restore',true);
            $logger->log(__('Restore complete.', 'wptc'),'restore_complete',$restore_action_id);
            $logger->log(sprintf(__('A total of %s files were processed.', 'wptc'), $this->processed_file_count),'restore_complete',$restore_action_id);
            $logger->log(sprintf(
                __('A total of %dMB of memory was used to complete this restore.', 'wptc'),
                (memory_get_usage(true) / 1048576)
            ),'restore_complete',$restore_action_id);
            //Process the log file using the default backup output
            $root = false;
            if (get_class($this->output) != 'WPTC_Extension_DefaultOutput') {
                $this->output = new WPTC_Extension_DefaultOutput();
                $root = true;
            }

            //$this->output->set_root($root)->out(get_tcsanitized_home_path(), $logger->get_log_file());				//manual
                        $check_chunked_alive = $this->Chunked_download_check();
			if( !$this->config->get_option('chunked') && !$check_chunked_alive )			//if chunked download is not going ; or if bridge copy is not going do this completion step.
			{
				$this->config
					->complete('restore')
					->log_finished_time()
					;

				$this->clean_up();
				
				//copy the bridge files to the root folder and then set "over";
				$this->copy_bridge_files_tc();
				echo "over";
				die();
			}
			else{
				echo json_encode("callAgain");
				exit;
			}
        } catch (Exception $e) {
            if ($e->getMessage() == 'Unauthorized') {
                $logger->log(__('The plugin is no longer authorized with Dropbox.', 'wptc'),'restore_error',$restore_action_id);
            } else {
                $logger->log(__('A fatal error occured: ', 'wptc') . $e->getMessage(),'restore_error',$restore_action_id);
            }

            $manager->failure();
            $this->stop();
        }
    }
	
	public function copy_bridge_files_tc(){
		//function to copy thr bridge files from plugin to the wordpress root
		$plugin_path_tc = WP_PLUGIN_DIR . '/' . TC_PLUGIN_NAME;
		$plugin_bridge_file_path = $plugin_path_tc . '/wp-tcapsule-bridge';
		$current_bridge_file_name = $this->config->get_option('current_bridge_file_name');
		$root_bridge_file_path = ABSPATH . '/' . $current_bridge_file_name;
		$restore_id = WPTC_Factory::get('config')->get_option('restore_action_id');
                $logger = WPTC_Factory::get('logger');
		$this_config_like_file = $this->create_config_like_file();
		
		$files_other_than_bridge = array();
		$files_other_than_bridge['DBTables.php'] = $plugin_path_tc . '/Classes/Processed/DBTables.php';
		$files_other_than_bridge['Factory.php'] = $plugin_path_tc . '/Classes/Factory.php';
		$files_other_than_bridge['FileList.php'] = $plugin_path_tc . '/Classes/FileList.php';
		$files_other_than_bridge['tc-config.php'] = $plugin_path_tc . '/Classes/Config.php';
		$files_other_than_bridge['wp-tc-config.php'] = $this_config_like_file;						//config-like-file which was prepared already
		$files_other_than_bridge['Files.php'] = $plugin_path_tc . '/Classes/Processed/Files.php';
		$files_other_than_bridge['Restoredfiles.php'] = $plugin_path_tc . '/Classes/Processed/Restoredfiles.php';
		$files_other_than_bridge['class-wp-filesystem-base.php'] = ABSPATH . '/wp-admin/includes/class-wp-filesystem-base.php';
		$files_other_than_bridge['class-wp-filesystem-direct.php'] = ABSPATH . '/wp-admin/includes/class-wp-filesystem-direct.php';
		$files_other_than_bridge['class-wp-filesystem-ftpext.php'] = ABSPATH . '/wp-admin/includes/class-wp-filesystem-ftpext.php';
		$files_other_than_bridge['class-wp-error.php'] = ABSPATH . '/wp-includes/class-wp-error.php';
		$files_other_than_bridge['jquery.min.js'] = ABSPATH . '/wp-includes/js/jquery/jquery.js';
		$files_other_than_bridge['formatting.php'] = ABSPATH . '/wp-includes/formatting.php';
		
		//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----plugin_bridge_file_path------- ".var_export($plugin_bridge_file_path,true)."\n",FILE_APPEND);
		
		//create the folder in root directory if it doesnt exist
		global $wp_filesystem;
		if ( !$wp_filesystem->is_dir($root_bridge_file_path) ) 
		{
			if ( !$wp_filesystem->mkdir($root_bridge_file_path, FS_CHMOD_DIR) )
			{
				//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----root folder creation error------- ".var_export($root_bridge_file_path,true)."\n",FILE_APPEND);
                                $logger->log('Failed to create bridge directory while restoring . Check your folder permissions','restore_error',$restore_id);
				return false;
			}
		}
		
		//call the copy function to copy the bridge folder files
		$copy_res = $this->tc_file_system_copy_dir($plugin_bridge_file_path, $root_bridge_file_path, true);
		if(!$copy_res)
		{
			//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----copy false------- \n",FILE_APPEND);
                        $logger->log('Failed to Copying Downloaded files','restore_error',$restore_id);
			return false;
		}
		
		//copy the files which the bridge will use
		foreach($files_other_than_bridge as $key => $value){
			$this->tc_file_system_copy($value, $root_bridge_file_path . '/' . $key, true);
		}
		
		return true;
	}
	
	public function create_config_like_file(){
		//first check if the folder is writable ;
		
		//create a file if its not present there
		
		
		$dump_dir = WPTC_Factory::get('config')->get_backup_dir();
		$dump_dir_parent = substr($dump_dir, 0, -8);
		$this_config_like_file = $dump_dir_parent . '/' . 'config-like-file.php';
		
		$fh = fopen($this_config_like_file, 'a');
		if(ftell($fh) < 2){
			fclose($fh);
			$fh = fopen($this_config_like_file, 'w');
		}
        fwrite($fh, '');
		fclose($fh);
		
		$contents_to_be_written = "
		<?php  
		/** The name of the database for WordPress */
		if(!defined('DB_NAME'))
		define('DB_NAME', '".DB_NAME."');

		/** MySQL database username */
		if(!defined('DB_USER'))
		define('DB_USER', '".DB_USER."');

		/** MySQL database password */
		if(!defined('DB_PASSWORD'))
		define('DB_PASSWORD', '".DB_PASSWORD."');

		/** MySQL hostname */
		if(!defined('DB_HOST'))
		define('DB_HOST', '".DB_HOST."');

		/** Database Charset to use in creating database tables. */
		if(!defined('DB_CHARSET'))
		define('DB_CHARSET', '".DB_CHARSET."');

		/** The Database Collate type. Don't change this if in doubt. */
		if(!defined('DB_COLLATE'))
		define('DB_COLLATE', '".DB_COLLATE."');
		
		if(!defined('WP_CONTENT_DIR'))
		define('WP_CONTENT_DIR', '".WP_CONTENT_DIR."');
		
		if(!defined('WP_DEBUG'))
		define('WP_DEBUG', false);
		/** Absolute path to the WordPress directory. */
		if ( !defined('ABSPATH') )
                define('ABSPATH', dirname(dirname(__FILE__))); 
                if ( !defined('TC_PLUGIN_NAME') )
                define('TC_PLUGIN_NAME', 'wp-time-capsule');
                ?>
                ";
		
		file_put_contents($this_config_like_file, $contents_to_be_written, FILE_APPEND);
		
		return $this_config_like_file;
		
	}
	
	public function tc_file_system_copy_dir($from, $to = ''){
		
		global $wp_filesystem;
		
		//get the dirList from wp_filesystem method
		$dirlist = $wp_filesystem->dirlist($from);
		$from = trailingslashit($from);
		$to = trailingslashit($to);
		
		
		foreach ( (array) $dirlist as $filename => $fileinfo ) {
			
			if ( 'f' == $fileinfo['type'] && $filename!='.htaccess') {
				if ( ! $this->tc_file_system_copy($from . $filename, $to . $filename, true, FS_CHMOD_FILE) ) {
					// If copy failed, chmod file to 0644 and try again.
					$wp_filesystem->chmod($to . $filename, 0644);
					if ( ! $this->tc_file_system_copy($from . $filename, $to . $filename, true, FS_CHMOD_FILE) )
						{
							continue;
							//return array('error' => 'cannot copy file');
						}
				}
			} elseif ( 'd' == $fileinfo['type'] ) {
				if ( !$wp_filesystem->is_dir($to . $filename) ) {
					if ( !$wp_filesystem->mkdir($to . $filename, FS_CHMOD_DIR) )
						{
							return false;
							//return array('error' => 'cannot create directory');
						}
				}
				$result = $this->tc_file_system_copy_dir($from . $filename, $to . $filename);
				if(!$result)
				{
					return $result;
				}
			}
		}
		return true;
	}

	public function tc_file_system_copy($source, $destination, $overwrite = false, $mode = false){
		global $wp_filesystem;
		if($wp_filesystem->method == 'direct'){
				$copy_result = $wp_filesystem->copy($source, $destination, true, $mode);
				return $copy_result;
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
		return true;
	}

    public function backup_now()
    {
		//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----backup_now------- \n",FILE_APPEND);
		$old_cookie = getTcCookie('backupID');
		//delete any previous cookie
		deleteTcCookie();
		
		//prepare the backupID and then execute
		setTcCookieNow("backupID");
		if (defined('WPTC_TEST_MODE')) {
            execute_tcdropbox_backup();
        } else {
			wp_schedule_single_event(time(), 'execute_instant_drobox_backup');
        }
    }
	
	public function restore_now($files_to_restore)
    {
		//this is the initial step ; this function will do the deletion of future files if needed or it ll get the respective revision id and then it ll appened it to the files to be restored array
//		file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',var_export($files_to_restore,true)."\n",FILE_APPEND);
		//add_files_for_restoring()			//manual
		if(!empty($files_to_restore))
		{
			//prepare the backupID and then execute
			setTcCookieNow("backupID");
			
			//deleting the temp folder first;then create it;
			$this_temp_backup_folder = WP_CONTENT_DIR.'/tCapsule';
			global $wp_filesystem;
			$wp_filesystem->delete($this_temp_backup_folder, true);
			if ( !$wp_filesystem->is_dir($this_temp_backup_folder) ) {
				if ( !$wp_filesystem->mkdir($this_temp_backup_folder, FS_CHMOD_DIR) )
					{
						//return error message if any
					}
			}
			global $wpdb;
			//truncating the database
			$processed_files = WPTC_Factory::get('processed-restoredfiles',true);
			$processed_files->truncate();
			
			$past_and_future_files_restore = array();
			$future_files_restore = array();
			$past_files_restore = array();
			//do the deletion part first;
			$cur_res_b_id = $this->config->get_option("cur_res_b_id");
			if(!empty($cur_res_b_id)){
				global $wp_filesystem;
				//get all the future files to be deleted or restore to previous thing
				$future_delete_files = $processed_files->get_future_delete_files($cur_res_b_id);
				$past_replace_files = $processed_files->get_past_replace_files($cur_res_b_id);
                                $unknown=$this->config->get_option("unknown_files_delete");
                                if($unknown==1){
                                    $all_processed_files=$processed_files->get_all_processed_files();
                                    $this->delete_unknown_future_files($all_processed_files);
                                }

				//get the revsion id for the future files and also the folders involved;
				$this_revisions = array();
				$folders_involved = array();
				foreach($future_delete_files as $key => $value){
					$this_revisions[$value->file] = $processed_files->get_most_recent_revision($value->file, $cur_res_b_id);
					
					//below is the logic to get the folder path from the file name.
					$this_file = $value->file;

					$abspath_index_count = (count(explode(DIRECTORY_SEPARATOR, substr(ABSPATH, 0, -1))) - 1);
					$exploded_this_file = explode(DIRECTORY_SEPARATOR, $this_file);
					$new_exploded_this_file = array();
					foreach($exploded_this_file as $k => $v){
						if($k > $abspath_index_count){
							if($v == 'tCapsule'){
								break;
							}
							$new_exploded_this_file[] = $v;
							if(($k == ($abspath_index_count + 1)) && ($v == 'wp-content')){
								continue;
							}
							else{
								break;
							}
						}
					}
					$new_imploded_this_file = implode(DIRECTORY_SEPARATOR, $new_exploded_this_file);
					if(!empty($new_exploded_this_file) && ($new_imploded_this_file != 'wp-content')){
						$new_this_file = substr(ABSPATH, 0, -1) . DIRECTORY_SEPARATOR . $new_imploded_this_file;
						$folders_involved[$new_this_file] = 1;
					}
				}
				
				//deleting the files with no revision ID
				foreach($this_revisions as $key => $value){
					$is_skip = false;
					foreach($files_to_restore as $k => $v){
						$formatted_file_name = str_replace("\\\\", "\\", $v['file_name']);
						if($formatted_file_name == $key){			//ignoring present files; which are also in the future files array; 
							////file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----ignoring ------ ".var_export($formatted_file_name,true)."\n",FILE_APPEND);
							unset($this_revisions[$key]);
							$is_skip = true;
						}
					}
					if($is_skip == false){
						if(empty($value[0]->revision_id)){
							if(strpos($key, 'wptc-secret') === false){			//ignoring sql file
								$wp_filesystem->delete($key);
							}
							unset($this_revisions[$key]);
						}
						else{
							$future_files_restore[$value[0]->revision_id]['file_name'] = str_replace("\\", "\\\\", $key);
							$future_files_restore[$value[0]->revision_id]['file_size'] = $value[0]->uploaded_file_size;
						}
					}
				}
				
				
				//get all the past files to be deleted or restore to previous thing
				$past_replace_files = $processed_files->get_past_replace_files($cur_res_b_id);
				
				//get the revision id for all the past replace files
				$this_past_replace_revisions = array();
				foreach($past_replace_files as $key => $value){
					$this_past_replace_revisions[$value->file] = $processed_files->get_most_recent_revision($value->file, $cur_res_b_id);
				}
				
				//replacing the past files with most recent revision id
				foreach($this_past_replace_revisions as $key => $value){
					$is_skip = false;
					foreach($files_to_restore as $k => $v){
						$formatted_file_name = str_replace("\\\\", "\\", $v['file_name']);
						if($formatted_file_name == $key){			//ignoring present files; which are also in the future files array; 
							////file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----ignoring ------ ".var_export($formatted_file_name,true)."\n",FILE_APPEND);
							unset($this_past_replace_revisions[$key]);
							$is_skip = true;
						}
					}
                                        if(strpos($key, 'wptc-secret') !== false)
                                        {
                                            $is_skip=true;
                                        }
					if($is_skip == false){
						if(empty($value[0]->revision_id)){
							unset($this_past_replace_revisions[$key]);
						}
						else{
							$past_files_restore[$value[0]->revision_id]['file_name'] = str_replace("\\", "\\\\", $key);
							$past_files_restore[$value[0]->revision_id]['file_size'] = $value[0]->uploaded_file_size;
						}
					}
				}
				
				$past_and_future_files_restore = array_merge($future_files_restore, $past_files_restore);
				//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----past_files_restore---- ".var_export($past_files_restore,true)."\n",FILE_APPEND);
				//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----future_files_restore---- ".var_export($future_files_restore,true)."\n",FILE_APPEND);
				//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----folders_involved------- ".var_export($folders_involved,true)."\n",FILE_APPEND);
				
				
				
				//after deleting the future-delete-files check if the folders-invloved is empty; clear all the empty folders
				if(!empty($folders_involved)){
					foreach($folders_involved as $kk => $vv){
						$this->delete_empty_folders($kk);
					}
				}
			}
			$total_files_to_restore = array_merge($past_and_future_files_restore, $files_to_restore);
			//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----total_files_to_restore---- ".var_export($total_files_to_restore,true)."\n",FILE_APPEND);
			//and then do the adding of files to the DB
			$processed_files->add_files_for_restoring($total_files_to_restore);
			
			execute_tcdrobox_restore();					//since we are using a manual ajax function
//		    if (defined('WPTC_TEST_MODE')) {
//				execute_tcdrobox_restore();
//			} else {
//				wp_schedule_single_event(time(), 'execute_instant_dropbox_restore');
//			}
		} 
    }
	public function delete_unknown_future_files($all_processed_files)
        {
            global $wp_filesystem;
            //Get the known files (backup done files ) and eliminate the known value's in unknown array
            $homepath=get_tcsanitized_home_path();
            $DirIter = new RecursiveDirectoryIterator($homepath);
            $FileIter = new RecursiveIteratorIterator($DirIter, RecursiveIteratorIterator::SELF_FIRST,RecursiveIteratorIterator::CATCH_GET_CHILD);
            foreach($all_processed_files as $value)
            {
                $all_processed_arr[] = $value[0];
            }
            foreach($FileIter as $key => $file)
            {
                $current_file=$file->getPathname();
                if(in_array($current_file,$all_processed_arr))
                {
                    //echo $current_file.'is known skipped/n';
                }
                else
                {
                    //echo $current_file.'is unknown/n';
                    $needtodelete[]=$current_file;
                }
            }
            if(count($needtodelete)>0){
            foreach ($needtodelete as $val)
            {
                if(file_exists($val)){
                    if(is_dir($val)){
                        $RmDirList[]=$val;
                    }
                    else{
                        $is_skip=false;
                        if (strpos($val,'wp-time-capsule') !== false||strpos($val,'tCapsule') !== false||strpos($val,'error_log') !== false){
                            $is_skip=true;
                        }
                       if(!$is_skip){
                            $wp_filesystem->delete($val);
                       }
                       else
                       {
                           $dfiles[]=$val;
                       }
                    }
                }
            }
            
            //remove empty directories
            usort($RmDirList,'lensort');
            foreach($RmDirList as $val)
            {
                if(!(strpos($val,'wp-time-capsule') !== false||strpos($val,'tCapsule') !== false||strpos($val,'wp-tcapsule') !== false))
                {
                if(file_exists($val)){
                    $MainDir=scandir($val);
                    //dir count is 2 then folder is empty we can remove that folder
                    if(count($MainDir) == 2)
                    {
                        $wp_filesystem->delete($val);
                        $deleted[]=$val;
                    }
                    }
                }
                }
            }
          
        }
	public function delete_empty_folders($this_folder = null, $prev_dir_deleted_count = 0){
		//this function will list out all the folders and files and check if no directories files are present; if there are no files then it ll delete that folder;
		global $wp_filesystem;
		$this_folder_dir_list = $wp_filesystem->dirlist($this_folder);
		$is_any_exists_count = 0; 
		if(!empty($this_folder_dir_list)){
			foreach($this_folder_dir_list as $key => $value){
				$is_any_exists_count++;
				if($value['type'] == 'd'){
					$prev_del_count = $this->delete_empty_folders($this_folder . DIRECTORY_SEPARATOR . $key, $prev_dir_deleted_count);
					$is_any_exists_count -= $prev_del_count;
				}
			}
		}
		if($is_any_exists_count < 1){
			//delete
			////file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----deleting empty------- ".var_export($this_folder,true)."\n",FILE_APPEND);
			$prev_dir_deleted_count++;
			$wp_filesystem->delete($this_folder);
			return $prev_dir_deleted_count;
		}
	}
	
	public function delete_prev_records(){
		//call the function to delete the prev records records 
		$processed_files = WPTC_Factory::get('processed-files');
		$processed_files->delete_last_month_backups();
	}
	
    public function stop()
    {
        $this->config->complete();
        $this->clean_up();
    }

    private function clean_up()
    {
		WPTC_Factory::get('databaseBackup')->clean_up();
		WPTC_Extension_Manager::construct()->get_output()->clean_up();
    }

    private static function create_silence_file()
    {
        $silence = WPTC_Factory::get('config')->get_backup_dir() . DIRECTORY_SEPARATOR . 'index.php';
        if (!file_exists($silence)) {
            $fh = @fopen($silence, 'w');
            if (!$fh) {
                throw new Exception(
                    sprintf(
                        __("WordPress does not have write access to '%s'. Please grant it write privileges before using this plugin."),
                        WPTC_Factory::get('config')->get_backup_dir()
                    )
                );
            }
            fwrite($fh, "<?php\n// Silence is golden.\n");
            fclose($fh);
        }
    }

    public static function create_dump_dir()
    {
		require_once(ABSPATH."wp-admin/includes/class-wp-filesystem-base.php");
		require_once(ABSPATH."wp-admin/includes/class-wp-filesystem-direct.php");
		require_once(ABSPATH."wp-admin/includes/class-wp-filesystem-ftpext.php");
		require_once(ABSPATH."wp-admin/includes/class-wp-filesystem-ssh2.php");		
		
		//get file system credentials
		$creds = request_filesystem_credentials("", "", false, false, null);
		if ( false === $creds ) {
			return false; 
		}

		//initialize file_system obj
		if ( ! WP_Filesystem($creds) ) {
			/* any problems and we exit */
			return false;
		}
		
		global $wp_filesystem;
		$dump_dir = WPTC_Factory::get('config')->get_backup_dir();
		$dump_dir_parent = substr($dump_dir, 0, -8);
        $error_message  = sprintf(__("WordPress Time Capsule requires write access to '%s', please ensure it exists and has write permissions.", 'wptc'), $dump_dir);
		
		if ( !$wp_filesystem->is_dir($dump_dir_parent) ) {
			if ( !$wp_filesystem->mkdir($dump_dir_parent, FS_CHMOD_DIR) )
				{
					//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----throwing 1------- \n",FILE_APPEND);
					throw new Exception($error_message);
					//return array('error' => 'cannot create directory');
				}
		}
		
		if ( !$wp_filesystem->is_dir($dump_dir) ) {
			if ( !$wp_filesystem->mkdir($dump_dir, FS_CHMOD_DIR) )
				{
					//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----throwing 2------- \n",FILE_APPEND);
					throw new Exception($error_message);
					//return array('error' => 'cannot create directory');
				}
		}
        /* if (!file_exists($dump_dir)) {
            //It really pains me to use the error suppressor here but PHP error handling sucks :-(
            if (!@mkdir($dump_dir)) {
                throw new Exception($error_message);
            }
        } elseif (!is_writable($dump_dir)) {
            throw new Exception($error_message);
        } */
		self::create_silence_file();
		return true;
    }
    public function unlink_CurrentAccAndBackups(){
        
        //delete backup history and data
        global $wpdb;
        $logger = WPTC_Factory::get('logger');
        
        $wpdb->query("TRUNCATE TABLE `".$wpdb->prefix."wptc_processed_dbtables`");
        $wpdb->query("TRUNCATE TABLE `".$wpdb->prefix."wptc_processed_files`");
        $wpdb->query("TRUNCATE TABLE `".$wpdb->prefix."wptc_processed_restored_files`");
        $wpdb->query("TRUNCATE TABLE `".$wpdb->prefix."wptc_backup_names`");
        $wpdb->query("TRUNCATE TABLE `".$wpdb->prefix."wptc_activity_log`");
        $wpdb->query("TRUNCATE TABLE `".$wpdb->prefix."wptc_current_process`");
        
        $this->config->set_option('in_progress', 0);
        $this->config->set_option('is_running', 0);
        $logger->log('Current Account was Removed','remove_currentacc');
    }
    public function writeStatusofFile($id,$status){
        global $wpdb;
        $in=$wpdb->query("UPDATE `".$wpdb->prefix."wptc_current_process` 
	SET status = '$status'
	WHERE id = $id");
    }
    public function iteratorIntoDb($TFiles)
    {
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE `".$wpdb->prefix."wptc_current_process`");
                            $FilesArray=array();
                            foreach($TFiles as $Ofiles)
                            {
                                foreach($Ofiles as $currentfile)
                                {
                                    $FilesArray[]=$currentfile->getPathname();
                                }
                            }

                            $mcount=count($FilesArray)%100;
                            if($mcount>0)
                            {
                                $lastloop=1;
                            }
                            else
                            {
                                $lastloop=0;
                            }
                            $itercount=count($FilesArray);
                            $numofinsert=round($itercount/100);
                            $point=0;
                            for($inloop=0;$inloop < $numofinsert+$lastloop;$inloop++)
                            {
                                if($point < $itercount){
                                    $qry="";
                                    for($deep=0;$deep<100;$deep++)
                                    {
                                        if($FilesArray[$point]){
                                            if($deep==0){
                                                $qry.="('";
                                            }
                                            else{
                                                $qry.=",('";
                                            }
                                            $qry.=addslashes($FilesArray[$point])."', 'Q')";
                                            $point++;
                                        }
                                        else
                                        {
                                            continue;
                                        }
                                    }
                                $wpdb->query("insert into ".$wpdb->prefix."wptc_current_process (file_path, status) values $qry");
                                }
                                else {
                                    continue;
                                }
                            }
                            $this->config->set_option('getfileslist',1);
    }
    public function WTCreportIssue($id=null){
        global $wpdb;
        if($id==null){
            $logger = WPTC_Factory::get('logger');
            $logs=$logger->get_log();
            if($logs&&!empty($logs))
            {
                $report=array();
                foreach($logs as $log)
                {
                    $record=explode('@',$log);
                    $Recordtime=new DateTime($record[0]);
                    $Today = new DateTime(date('Y-m-d 00:00:00'));
                    if($Recordtime>$Today){
                    $report[]=$record;
                    }
                }

            }
        }
        else
        {
            
            //Get the action id
            $specficlog=$wpdb->get_row( 'SELECT * FROM '.$wpdb->prefix.'wptc_activity_log WHERE id = '.$id, OBJECT );
            if($specficlog)
            {
                if($specficlog->action_id!='')
                {
                    //Getting all logs relate with this action id
                    $action_log=$wpdb->get_results( 'SELECT * FROM '.$wpdb->prefix.'wptc_activity_log WHERE action_id = '.$specficlog->action_id, OBJECT );
                    if(count($action_log)>0){
                        foreach ($action_log as $all)
                        {
                            $report[]=unserialize($all->log_data);
                        }
                    }
                    else
                    {
                        $report = unserialize($specficlog->log_data);
                    }
                }
                else
                {
                    $report = unserialize($specficlog->log_data);
                }
                
            }
//            return json_encode($specficlog);
        }
        $reportIssue = array();
      
	$reportIssue['reportTime'] = time();
        $reportIssue['log']=$report;
        
        
        $current_user = wp_get_current_user();
        $result['fname'] = $current_user->user_firstname;
        $result['lname'] = $current_user->user_lastname;
        $result['cemail'] = $current_user->user_email;
        $result['admin_email'] = get_option('admin_email');
        $result['idata'] = serialize($reportIssue);
        return json_encode($result);
    }
    public function WTCGetAnonymousData(){
        global $wpdb;
        $anonymous=array();
        $anonymous['server']['PHP_VERSION'] 	= phpversion();
	$anonymous['server']['PHP_CURL_VERSION']= curl_version();
	$anonymous['server']['PHP_WITH_OPEN_SSL'] = function_exists('openssl_verify');
	$anonymous['server']['PHP_MAX_EXECUTION_TIME'] =  ini_get('max_execution_time');
	$anonymous['server']['MYSQL_VERSION']= $wpdb->get_var("select version() as V");
	$anonymous['server']['OS'] =  php_uname('s');
	$anonymous['server']['OSVersion'] =  php_uname('v');
	$anonymous['server']['Machine'] =  php_uname('m');
	
	$anonymous['server']['PHPDisabledFunctions'] = explode(',', ini_get('disable_functions'));	
	array_walk($anonymous['server']['PHPDisabledFunctions'], 'trimValue');
	
	$anonymous['server']['PHPDisabledClasses'] = explode(',', ini_get('disable_classes'));	
	array_walk($anonymous['server']['PHPDisabledClasses'], 'trimValue');
        
        return $anonymous;
    }
    
    //Checking the chunked download is in progress or completed 
    public function Chunked_download_check(){
        global $wpdb;
        $unfinished_downloads=$wpdb->get_var( 'SELECT COUNT(*) FROM `'.$wpdb->prefix.'wptc_processed_restored_files` WHERE `uploaded_file_size`!=`offset`  AND `uploaded_file_size` > 4024000');
        if($unfinished_downloads > 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
}
function lensort($a,$b){
    return strlen($b)-strlen($a);
}