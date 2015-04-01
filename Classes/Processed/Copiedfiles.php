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
class WPTC_Processed_Copiedfiles extends WPTC_Processed_Base
{
    protected function getTableName()
    {
        return 'restored_files';
    }

    protected function getId()
    {
        return 'file';
    }
	
	
	protected function getRestoreTableName()
    {
        return 'restored_files';
    }
	
	protected function getFileId()
    {
        return 'file_id';
    }
	
    protected function getRevisionId() {
        return 'file_id';
    }
    
    protected function getUploadMtime()
    {
        return 'mtime_during_upload';
    }
	
	public function get_restored_files_from_base()
	{
		return $this->get_processed_restores();
	}
	
	public function get_restore_queue_from_base()
	{
		return $this->get_all_restores();
	}
	
    public function get_file_count()
    {
		//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ------calling this get_file_count-------- \n",FILE_APPEND);
        return count($this->processed);
    }

    public function get_file($file_name)
    {
		//////file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ------processed file-------- ".var_export($this -> processed,true)."\n",FILE_APPEND);
        foreach ($this->processed as $file) {
            if ($file->file == $file_name){
				return $file;
            }
        }
    }

    public function file_complete($file)
    {
        $this->update_file($file, 0, 0);
    }

    public function update_file($file, $upload_id = null, $offset, $backupID = 0, $chunked = null)
    {
		////file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----getTcCookie---- ".var_export(getTcCookie('backupID'),true)."\n",FILE_APPEND);
		//am adding few conditions to insert the new file with new backup id if the file is modified				//manual
		
		$may_be_stored_file_obj = $this->get_file($file);
		if($offset > 0)
		{
			$download_status = 'done';
		}
		else
		{
			$download_status = 'notdone';
		}
		if($may_be_stored_file_obj)
		{
			$may_be_stored_file_id = $may_be_stored_file_obj -> file_id;
		}
		if(!empty($may_be_stored_file_obj) && !empty($may_be_stored_file_id))
		{																			//this condition is to update the tables based on file_id 
			$upsert_array = array(
				'file' => $file,
				'offset' => $offset,
				//'backupID' => $_GLOBALS['this_cookie'],                           //get the backup ID from cookie
				'file_id' => $may_be_stored_file_id,
			);
		}
		else
		{
			$upsert_array = array(
				'file' => $file,
				'offset' => $offset,
				'download_status' => $download_status,
				//'backupID' => $_GLOBALS['this_cookie'],
			);
		}
		//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----track download offset update file------- ".var_export($upsert_array,true)."\n",FILE_APPEND);
		
        $this->upsert($upsert_array);
    }
	
	public function add_files_for_restoring($files_to_restore){
		//this is totally a new function to store the files-to-be-restored in the DB table
		if(!empty($files_to_restore))
		{
			foreach ($files_to_restore as $revision => $file_dets) {
				$this->upsert(array(
					'file' => $file_dets['file_name'],
					'revision_id' => $revision,
					'offset' => null,
					'backupID' => getTcCookie('backupID'),
					'uploaded_file_size' => $file_dets['file_size'],
					'download_status' => ($file_dets['file_size'] > 4024000) ? 'done' : 'notDone',				//am adding an extra condition for chunked download
				));
			}
		}
	}
	
    public function add_files($new_files)
    {
		foreach ($new_files as $file) {
			
			//am adding few conditions to insert the new file with new backup id if the file is modified				//manual
			$may_be_stored_file_obj = $this->get_file($file['filename']);
			$may_be_stored_filename = $may_be_stored_file_obj -> file;
			$may_be_stored_upload_mtime = $may_be_stored_file_obj -> mtime_during_upload;
			
            //if ($this->get_file($file['filename'])) {																	//manual
			if (!empty($may_be_stored_file_obj) && ($may_be_stored_upload_mtime == $file['mtime_during_upload'])) {
				//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ------skipping-------- ".var_export($file['file'],true)."\n",FILE_APPEND);
                continue;
            }

            $this->upsert(array(
                'file' => $file['file'],
                'uploadid' => null,
                'offset' => null,
				'backupID' => getTcCookie('backupID'),
				'revision_number' => $file['revision_number'],
				'revision_id' => $file['revision_id'],
				'mtime_during_upload' => $file['mtime_during_upload'],
				'download_status' => 'done',
            ));
        }

        return $this;
    }
}
