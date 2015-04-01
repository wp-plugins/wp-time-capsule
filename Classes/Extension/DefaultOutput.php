<?php
/**
 * A class with functions the perform a backup of WordPress
 *
 * @copyright Copyright (C) 2011-2013 Michael De Wild. All rights reserved.
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
class WPTC_Extension_DefaultOutput extends WPTC_Extension_Base
{
    const MAX_ERRORS = 10;

    private
        $error_count,
        $root
        ;

    public function set_root($root)
    {
        $this->root = $root;

        return $this;
    }

    public function out($source, $file, $processed_file = null)
    {
        if ($this->error_count > self::MAX_ERRORS)
        {
            WPTC_Factory::get('logger')->log(sprintf(__("The backup is having trouble uploading files to Dropbox, it has failed %s times and is aborting the backup.", 'wptc'), self::MAX_ERRORS),'backup_error');
            throw new Exception(sprintf(__('The backup is having trouble uploading files to Dropbox, it has failed %s times and is aborting the backup.', 'wptc'), self::MAX_ERRORS));
        }
        if (!$this->dropbox){
            WPTC_Factory::get('logger')->log(sprintf(__("The backup is having trouble uploading files to Dropbox, it has failed %s times and is aborting the backup.", 'wptc'), self::MAX_ERRORS),'backup_error');
            throw new Exception(__("Dropbox API not set"));
        }
        $dropbox_path = $this->config->get_dropbox_path($source, $file, $this->root);
		//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----this dropboxx path------- ".var_export($dropbox_path,true)."\n",FILE_APPEND);
		
		try {
			$drop_time = microtime(true);
            $directory_contents = $this->dropbox->get_directory_contents($dropbox_path);
			$endTimeDrop = microtime(true) - $drop_time;
			//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----time to get drop dir------ ".var_export($endTimeDrop,true)."\n",FILE_APPEND);
			//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----directoery contents-------- ".var_export($directory_contents,true)."\n",FILE_APPEND);
			//get all the directory contents from dropbox; check if the file already exists in dropbox
			if((!in_array(basename($file), $directory_contents)) || (filemtime($file) > $this->config->get_option('last_backup_time'))) {
				//if(filemtime($file) > $this->config->get_option('last_backup_time') ){
					$file_size = filesize($file);
					if ($file_size > $this->get_chunked_upload_threashold()) {

						$msg = __("Uploading large file '%s' (%sMB) in chunks", 'wptc');
						if ($processed_file && $processed_file->offset > 0)
							$msg = __("Resuming upload of large file '%s'", 'wptc');

						WPTC_Factory::get('logger')->log(sprintf(
							$msg,
							basename($file),
							round($file_size / 1048576, 1)
						));

						return $this->dropbox->chunk_upload_file($dropbox_path, $file, $processed_file);
					} else {
						return $this->dropbox->upload_file($dropbox_path, $file);
					}
				//}
			}
			else{
				if(filemtime($file) > $this->config->get_option('last_backup_time')){
					//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----dropbox output from metadata-----for ".var_export($file,true)."\n",FILE_APPEND);
					$dropbox_file_path = $dropbox_path .'\\'. basename($file);
					return $this->dropbox->get_file_details($dropbox_file_path);
				}
				return false;
			}

        } catch (Exception $e) {
            WPTC_Factory::get('logger')->log(sprintf(__("Error uploading '%s' to Dropbox: %s", 'wptc'), $file, strip_tags($e->getMessage())));
            $this->error_count++;
			
			//if there is any error we are showing it via ajax
			$error_array = array();
			$error_array['error'] = strip_tags($e->getMessage());
			echo json_encode($error_array);
			exit;
        }
    }
	
	
	public function drop_download($source, $file, $revision = null, $processed_file = null, $restore_single_file = null)
    {
        if ($this->error_count > self::MAX_ERRORS){
            WPTC_Factory::get('logger')->log(sprintf(__('The backup is having trouble downloading files to Dropbox, it has failed %s times and is aborting the backup.', 'wptc'), self::MAX_ERRORS),'restore_error');
            throw new Exception(sprintf(__('The backup is having trouble downloading files to Dropbox, it has failed %s times and is aborting the backup.', 'wptc'), self::MAX_ERRORS));
        }
        if (!$this->dropbox){
            WPTC_Factory::get('logger')->log(__("Dropbox API not set"),'restore_error');
            throw new Exception(__("Dropbox API not set"));
        }
        $dropbox_path = $this->config->get_dropbox_path($source, $file, $this->root);
		//////file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ---dropbox_file------ ".var_export($file,true)."\n",FILE_APPEND);
        try {
            //$directory_contents = $this->dropbox->get_directory_contents($dropbox_path);
			$dropbox_source_file = $dropbox_path.'/'.basename($file);
			//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----coming abpove if condition------ ".var_export($restore_single_file,true)."\n",FILE_APPEND);
			if($restore_single_file['uploaded_file_size'] < 4024001)
			{
				return $this->dropbox->download_file($dropbox_source_file, $file, $revision);
			}
			else
			{
			//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----processed file chunked_download array ------ ".var_export($processed_file,true)."\n",FILE_APPEND);
				$isChunkDownload['c_offset'] =  (!empty($processed_file->offset)) ? $processed_file->offset : 0;				//here am getting the restored files offset ..
				if(!$processed_file)
				{
				$isChunkDownload['c_limit'] = 4024000;
				}
				else
				$isChunkDownload['c_limit'] = (($isChunkDownload['c_offset'] + 4024000) > $processed_file->uploaded_file_size) ? ($processed_file->uploaded_file_size) : ($isChunkDownload['c_offset'] + 4024000);
				//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----prepared chunked_download array ------ ".var_export($isChunkDownload,true)."\n",FILE_APPEND);
				return $this->dropbox->chunk_download_file($dropbox_source_file, $file, $revision, $isChunkDownload);
			}

        } catch (Exception $e) {
            WPTC_Factory::get('logger')->log(sprintf(__("Error downloading '%s' to Dropbox: %s", 'wptc'), $file, strip_tags($e->getMessage())),'restore_error');
            $this->error_count++;
			
			//if there is any error we are showing it via ajax
			$error_array = array();
			$error_array['error'] = strip_tags($e->getMessage());
			echo json_encode($error_array);
			//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----error_array-------- ".var_export($error_array,true)."\n",FILE_APPEND);
			exit;
        }
    }

    public function start()
    {
        return true;
    }

    public function end() {}
    public function complete() {}
    public function failure() {}

    public function get_menu() {}
    public function get_type() {}

    public function is_enabled() {}
    public function set_enabled($bool) {}
    public function clean_up() {}
}
