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
class WPTC_Processed_Files extends WPTC_Processed_Base
{
    protected function getTableName()
    {
        return 'files';
    }
	
	protected function getProcessType()
    {
        return 'files';
    }
	
	protected function getRestoreTableName()
    {
        return 'restored_files';
    }
    
    protected function getRevisionId() {
        return 'revision_id';
    }

    protected function getId()
    {
        return 'file';
    }
	
	protected function getFileId()
    {
        return 'file_id';
    }
	
	protected function getUploadMtime()
    {
        return 'mtime_during_upload';
    }

    public function get_file_count()
    {
        return count($this->processed);
    }
	
	
	
    public function get_file($file_name)
    {
		foreach ($this->processed as $file) {
            if( ($file->file == $file_name) && ($file->backupID == getTcCookie('backupID'))){
				return $file;
            }
        }
    }

    public function file_complete($file)
    {
        $this->update_file($file, 0, 0);
    }

    public function update_file($file, $upload_id, $offset, $backupID = 0)
    {
		////file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----getTcCookie---- ".var_export(getTcCookie('backupID'),true)."\n",FILE_APPEND);
		
		//am adding few conditions to insert the new file with new backup id if the file is modified				//manual
		$may_be_stored_file_obj = $this->get_file($file);
		if($may_be_stored_file_obj)
		{
			$may_be_stored_file_id = $may_be_stored_file_obj -> file_id;
		}
		if(!empty($may_be_stored_file_obj) && !empty($may_be_stored_file_id))
		{
			//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----updating y id------ ".var_export($file,true)."\n",FILE_APPEND);
			$upsert_array = array(
				'file' => $file,
				'uploadid' => $upload_id,
				'offset' => $offset,
				'backupID' => getTcCookie('backupID'),                           //get the backup ID from cookie
				'file_id' => $may_be_stored_file_id,
				'mtime_during_upload' => filemtime($file),
				'uploaded_file_size' => filesize($file),
			);
		}
		else
		{
			//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----updating y file------ ".var_export($file,true)."\n",FILE_APPEND);
			$upsert_array = array(
				'file' => $file,
				'uploadid' => $upload_id,
				'offset' => $offset,
				'backupID' => getTcCookie('backupID'),
				'mtime_during_upload' => filemtime($file),
			);
		}
		
		
        $this->upsert($upsert_array);
    }

    public function add_files($new_files)
    {
		foreach ($new_files as $file) {
			
			//am adding few conditions to insert the new file with new backup id if the file is modified				//manual
			/* $may_be_stored_file_obj = $this->get_file($file['filename']);
			$may_be_stored_filename = $may_be_stored_file_obj -> file;
			$may_be_stored_upload_mtime = $may_be_stored_file_obj -> mtime_during_upload;
			
            //if ($this->get_file($file['filename'])) {																	//manual
			if (!empty($may_be_stored_file_obj) && ($may_be_stored_upload_mtime == $file['mtime_during_upload'])) {
				//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ------skipping-------- ".var_export($file['filename'],true)."\n",FILE_APPEND);
                continue;
            } */
			
            $this->upsert(array(
                'file' => $file['filename'],
                'uploadid' => null,
                'offset' => null,
				'backupID' => getTcCookie('backupID'),
				'revision_number' => $file['revision_number'],
				'revision_id' => $file['revision_id'],
				'mtime_during_upload' => $file['mtime_during_upload'],
				'uploaded_file_size' => $file['uploaded_file_size'],
            ));
        }

        return $this;
    }
	
	public function get_this_backups_html($this_backup_ids){
		$backup_dialog_html = '';
		$backup_datas = $this -> get_this_backups($this_backup_ids);
		$this_day = '';
		foreach($backup_datas as $key => $value)
		{
			$sub_content = '';
			$explodedTreeArray = $this->prapare_tree_like_array($value);
			global $treeRecursiveCount;
			$treeRecursiveCount = 0;
			$sub_content = $this->get_tree_div_recursive($explodedTreeArray);
			//<li><div class="folder open"></div><div class="file_path">themes/mystile-child/index.php</div></li>
			//<li class="sl1"><div class="file_path">themes/mystile-child/index.php</div></li>
			/* foreach($value as $k => $v)
			{
				//$stripped_file_name = $this->remove_abs_path($v->file);
				$sub_content .= '<li class="single_backup_content_body checkbox_click"><div class="file_path" id="this_name" file_size='.$v->uploaded_file_size.' rev_id='.$v->revision_id.' mod_time='.$v->mtime_during_upload.'>'.$v->file.'</div></li>';
			} */
			$res_files_count = count($value) - 1;
			$this_plural = '';
			if($res_files_count > 1)
			{
				$this_plural = 's';
			}
			$backup_dialog_html .= '<li class="single_group_backup_content bu_list" this_backup_id="'.$key.'"><div class="single_backup_head bu_meta"><div class="toggle_files"></div><div class="time">'.date('g:i a', $key).'</div><div class="bu_name">'.$this->get_stored_backup_name($key).'</div><a class="this_restore disabled btn" style="display:none">Restore Selected</a><div class="changed_files_count" style="display:none">'.$res_files_count.' file'.$this_plural.' changed</div><a class="btn this_restore_point">RESTORE SITE TO THIS POINT</a></div><div class="clear"></div><div class="bu_files_list_cont"><div class="item_label">Database</div><ul class="bu_files_list "><li class="restore_the_db sub_tree_class"><div class="file_path">Restore the database</div></li></ul><div class="clear"></div><div class="item_label">Files</div><ul class="bu_files_list">'.$sub_content.'</ul></div><div class="clear"></div></li>';
			$this_day = $key;
		}
		return '<div class="dialog_cont"><span class="dialog_close"></span><div class="pu_title">Backups Taken on '.date('jS F', $this_day).'</div><ul class="bu_list_cont">'.$backup_dialog_html.'</ul></div>';
	}
	
	public function get_tree_div_recursive($explodedTreeArray, $sub_tree_class_no = 0, $total_sub_content = '' ){
		global $treeRecursiveCount;
		$this_subtree_class_no = $sub_tree_class_no;
		foreach($explodedTreeArray as $top_tree_name => $sublings_array)
		{
			if(is_array($sublings_array))
			{
				$total_sub_content .= '<li class="sl'.$treeRecursiveCount.' sub_tree_class" style="margin-left:'.(($treeRecursiveCount * 50) - $treeRecursiveCount).'px" ><div class="folder close"></div><div class="file_path">'.$top_tree_name.'</div></li>';
				$this_subtree_class_no += 1;
				$treeRecursiveCount += 1;
				$total_sub_content .= $this->get_tree_div_recursive($sublings_array, $this_subtree_class_no, '');
			}
			else
			{
				$is_sql_class = "";
				$is_sql_li = "";
				if((strpos($top_tree_name, 'wptc-secret') !== false) )
				{
					$is_sql_class = "sql_file";
					$is_sql_li = "sql_file_li";
				}
				$total_sub_content .= '<div class="this_leaf_node '.$is_sql_class.' leaf_'.$treeRecursiveCount.'"><li class="sl'.$treeRecursiveCount.' '.$is_sql_li.'" style="margin-left:'.(($treeRecursiveCount * 50) - $treeRecursiveCount).'px"><div class="file_path" '.$sublings_array.'>'.$top_tree_name.'</div></li></div>';
			}
		}
		$treeRecursiveCount -= 1;
		return '<div class="this_parent_node">' . $total_sub_content . '</div>';
	}
	
	public function prapare_tree_like_array($this_file_name_array){
		$stripped_file_name_array = array();
		foreach($this_file_name_array as $k => $v){
			$this_removed_abs_file_name = $this->remove_abs_path($v->file);
			$stripped_file_name_array[$this_removed_abs_file_name] = ' file_name='. $v->file .' file_size=' . $v->uploaded_file_size . ' rev_id=' . $v->revision_id . ' mod_time=' . $v->mtime_during_upload . ' ';  //am appending file_size,rev_id,mod_time
		}
		$explodedTreeArray = $this->explodeTree($stripped_file_name_array, '/', false);
		////file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----explodedTreeArray------ ".var_export($explodedTreeArray,true)."\n",FILE_APPEND);
		return $explodedTreeArray;
	}
	
	public function explodeTree($array, $delimiter = '_', $baseval = false)
	{
		if(!is_array($array)) return false;
		$splitRE   = '/' . preg_quote($delimiter, '/') . '/';
		$returnArr = array();
		foreach ($array as $key => $val) {
			// Get parent parts and the current leaf
			$parts  = preg_split($splitRE, $key, -1, PREG_SPLIT_NO_EMPTY);
			$leafPart = array_pop($parts);

			// Build parent structure
			// Might be slow for really deep and large structures
			$parentArr = &$returnArr;
			foreach ($parts as $part) {
				if (!isset($parentArr[$part])) {
					$parentArr[$part] = array();
				} elseif (!is_array($parentArr[$part])) {
					if ($baseval) {
						$parentArr[$part] = array('__base_val' => $parentArr[$part]);
					} else {
						$parentArr[$part] = array();
					}
				}
				$parentArr = &$parentArr[$part];
			}

			// Add the final part to the structure
			if (empty($parentArr[$leafPart])) {
				$parentArr[$leafPart] = $val;
			} elseif ($baseval && is_array($parentArr[$leafPart])) {
				$parentArr[$leafPart]['__base_val'] = $val;
			}
		}
		return $returnArr;
	}
	
	public function remove_abs_path($this_file_name){
		//this function will remove the abspath value from the filename path and then replace the "\\" with "/";
		$proper_abs_path = substr(ABSPATH, 0, -1);			//for removing (/) in the end of ABSPATH
		$abs_path_pos = strpos($this_file_name, $proper_abs_path);
		if($abs_path_pos !== false)
		{
			$abs_path_length = strlen($proper_abs_path) ;		//for removing (//)
			$rem_file_name = substr($this_file_name, $abs_path_length);
			$split_array = explode("\\", $rem_file_name);
			$implode_string = implode("/", $split_array);
			return $implode_string;
		}	
	}
	
	
	public function get_this_backups($this_backup_ids){
		//getting all the backups for each backup IDs and then prepare the html for displaying in dialog box
		$backups_for_backupIds = array();
		if(!empty($this_backup_ids))
		{
			$backup_id_array = explode(",", $this_backup_ids);
			if(empty($backup_id_array))
			{
				$backup_id_array[0] = $this_backup_ids;
			}
			foreach($backup_id_array as $key => $value){
				$single_backups = array();
				$single_backups = $this->getBackups($value);
				if(!empty($single_backups))
				{
					$backups_for_backupIds[$value] = $single_backups;
				}
			}
		}
		return $backups_for_backupIds;
	}
	
	public function get_stored_backups($this_backup_ids = null)
    {
		$all_backups = $this->getBackups();
		$formatted_backups = array();
		$backupIDs = array();
		foreach($all_backups as $key => $value)
		{
			$value_array = (array)$value;
			$formatted_backups[$value_array['backupID']][]= $value_array;
		}
		
		$backups_count = count($formatted_backups);
		
		$calendar_format_values = array();
		$all_days = array();
		$all_backup_id = array();
		foreach($formatted_backups as $k => $v)
		{
			//this loop is only to calculate the number of backups in a particular day
			$this_day = date("Y-m-d", $k);
			
			$is_day_exists = array_key_exists($this_day, $all_days);
			if($is_day_exists)
			{
				if(!empty($all_days[$this_day]))
				{
					$all_days[$this_day] += 1;
					$all_backup_id[$this_day][] = $k;
				}
			}
			else
			{
				$all_days[$this_day] = 1;
				$all_backup_id[$this_day][] = $k;
			}
		}
		
		$this_count = 0;
		foreach($all_days as $key => $value)
		{
                        asort($all_backup_id[$key]);
			if($value < 2) $this_plural = ''; else $this_plural = 's';
			$calendar_format_values[$this_count]['title'] = $value." Backup".$this_plural;
			$calendar_format_values[$this_count]['start'] = $key;
			$calendar_format_values[$this_count]['end'] = $key;
			$calendar_format_values[$this_count]['backupIDs'] = implode(",", $all_backup_id[$key]);					//am adding an extra value here to pass an ID 
			$this_count += 1;
		}
		unset($this_count);
		//the code below shows all the backup files
		/* $calendar_format_values = array();
		foreach($formatted_backups as $k => $v)
		{
			foreach($v as $key => $value)
			{
				$calendar_format_values[]['title'] = $value['file'];
				$calendar_format_values[]['start'] = date("y-m-d", $k);
			}
		} */
		
		////file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----calendar_format_values----- ".var_export($calendar_format_values,true)."\n",FILE_APPEND);
		return $calendar_format_values;
	}
	
	public function get_processed_files_count($backup_id = null){
		if(empty($backup_id)){
			$backup_id = getTcCookie('backupID');
		}
		$current_backups = $this->getBackups($backup_id);
		return count($current_backups);
	}
}
