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
abstract class WPTC_Processed_Base
{
    protected
        $db,
        $processed = array()
        ;

    public function __construct()
    {
        $this->db = WPTC_Factory::db();
			
		if($this->getTableName() == 'files' || $this->getTableName() == 'dbtables')
		{
			$ret = $this->db->get_results("SELECT * FROM {$this->db->prefix}wptc_processed_{$this->getTableName()}");
		}
		else
		{
			$ret = $this->db->get_results("SELECT * FROM {$this->db->prefix}wptc_processed_{$this->getTableName()} WHERE download_status = 'done'");
		}
        if (is_array($ret)) {
            $this->processed = $ret;
        }
    }

    abstract protected function getTableName();
	
	abstract protected function getProcessType();
	
	abstract protected function getRestoreTableName();

    abstract protected function getId();
    
    abstract protected function getRevisionId();


    abstract protected function getFileId();					//file column is not unique now .. so we should update using file_id 
	
	abstract protected function getUploadMtime();
	
	protected function getBackupID()
	{
		return 'backupID';
	}
	
    protected function getVar($val)   
    {
		//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ------trying to get values from-------- ".var_export($this->getTableName(),true)."\n",FILE_APPEND);
        return $this->db->get_var(
            $this->db->prepare("SELECT * FROM {$this->db->prefix}wptc_processed_{$this->getTableName()} WHERE {$this->getId()} = %s", $val)
        );
    }
	
	protected function get_processed_restores($this_backup_id = null)
    {
		//$current_time = microtime(true);
		//$last_month_time = strtotime(date('Y-m-d', strtotime(date('Y-m')." -1 month")));
		
		//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ------coming insdie getRestores-------- ".var_export($this->getRestoreTableName(),true)."\n",FILE_APPEND);
			$all_restores = $this->db->get_results(
			$this->db->prepare("
				SELECT * 
				FROM {$this->db->prefix}wptc_processed_{$this->getRestoreTableName()} ")
			);
		
		//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----get Restore Result------ ".var_export($all_backups,true)."\n",FILE_APPEND);	
		
		return $all_restores;
	}
	
	protected function getBackups($this_backup_id = null)
    {
		$current_time = microtime(true);
		$last_month_time = strtotime(date('Y-m-d', strtotime(date('Y-m')." -1 month")));
		
		if(empty($this_backup_id))
		{
			$all_backups = $this->db->get_results(
			$this->db->prepare("
				SELECT * 
				FROM {$this->db->prefix}wptc_processed_{$this->getTableName()}
				WHERE {$this->getBackupID()} > %s ", $last_month_time )
			);
		}
		else
		{
			$all_backups = $this->db->get_results(
			$this->db->prepare("
				SELECT * 
				FROM {$this->db->prefix}wptc_processed_{$this->getTableName()}
				WHERE {$this->getBackupID()} = %s ", $this_backup_id )
			);
		}
		
		return $all_backups;
	}
	
	protected function get_all_restores(){
		$all_restores = $this->db->get_results("
			SELECT * 
			FROM {$this->db->prefix}wptc_processed_{$this->getTableName()}  "					//manual
		);
		return $all_restores;
	}
	
	
    protected function upsert($data)
    {
		//file_put_contents(WP_CONTENT_DIR .'/DEBUG.php',"\n ------upsertData-------- ".var_export($data,true)."\n",FILE_APPEND);
		if(!empty($data[$this->getUploadMtime()]))			//am introducing this condition to avoid conflicts with multipart upload     manual
		{
                        //file_put_contents(WP_CONTENT_DIR .'/DEBUG.php',"\n -------Here getuploadmtime------- ".var_export($data,true)."\n",FILE_APPEND);
			//am adding an extra condition to check the modified time (if the modified time is different then add the values to DB or else leave it)
			$exists = $this->db->get_var(
			$this->db->prepare("SELECT * FROM {$this->db->prefix}wptc_processed_{$this->getTableName()} WHERE {$this->getId()} = %s AND {$this->getUploadMtime()} = %s AND {$this->getBackupID()} = %s", $data[$this->getId()], $data[$this->getUploadMtime()], $data['backupID'])  );
		}
		else
		{	
			//file_put_contents(WP_CONTENT_DIR .'/DEBUG.php',"\n -----empty getuploadmtime------\n",FILE_APPEND);
			$exists = $this->db->get_var(
			$this->db->prepare("SELECT * FROM {$this->db->prefix}wptc_processed_{$this->getTableName()} WHERE {$this->getId()} = %s ", $data[$this->getId()] )  );		//nust be used only for restoring , i guess
		}
		//file_put_contents(WP_CONTENT_DIR .'/DEBUG.php',"\n ----file ".var_export($data[$this->getId()],true)."-----exist result--- ".var_export($exists,true)."\n",FILE_APPEND);
                $config=WPTC_Factory::get('config');
                $last_restore =$config->get_option('last_process_restore');
                $restore_progress = $config->get_option('in_progress_restore');
                //file_put_contents(WP_CONTENT_DIR .'/DEBUG.php',"\n ----last restore ".$last_restore,FILE_APPEND);
        if (is_null($exists) || ( $last_restore && ! $restore_progress )) {
			$this_insert_result = $this->db->insert("{$this->db->prefix}wptc_processed_{$this->getTableName()}", $data);
			//file_put_contents(WP_CONTENT_DIR .'/DEBUG.php',"\n -----this_insert_result------- ".var_export($this_insert_result,true)."\n",FILE_APPEND);
			if($this_insert_result)
			{
				$data['file_id'] = $this->db->insert_id;			//am not adding file_id to the processed restored file array
			}
			else{
				//file_put_contents(WP_CONTENT_DIR .'/DEBUG.php',"\n -----this_insert_result_error------- ".var_export(mysql_error(),true)."\n",FILE_APPEND);
			}
            $this->processed[] = (object)$data;
        } else {
			if(!empty($data['file_id']))
			{
				//file_put_contents(WP_CONTENT_DIR .'/DEBUG.php',"\n -----upsertData by id------ ".var_export($data,true)."\n",FILE_APPEND);
				$this->db->update(																//am changing the whole update process to file_id
					"{$this->db->prefix}wptc_processed_{$this->getTableName()}",
					$data,
					array($this->getFileId() => $data[$this->getFileId()])
				);
			}
			else
			{
				 if(isset($data[$this->getRevisionId()])&&$data['uploaded_file_size']>4024000){
                                    $this->db->update(																//am changing the whole update process to file_id
					"{$this->db->prefix}wptc_processed_{$this->getTableName()}",
					$data,
					array($this->getId() => $data[$this->getId()])
				);   
                                }
                                else{
				$this->db->update(																//am changing the whole update process to file_id
					"{$this->db->prefix}wptc_processed_{$this->getTableName()}",
					$data,
					array($this->getId() => $data[$this->getId()],$this->getRevisionId() => $data[$this->getRevisionId()])
				);
                                }
			}
            

            $i = 0;
            foreach ($this->processed as $p) {
                $id = $this->getId();
                if ($p->$id == $data[$this->getId()]) {
                    break;
                }
                $i++;
            }

            $this->processed[$i] = (object)$data;
        }
    }

    public function truncate()
    {
        $this->db->query("TRUNCATE {$this->db->prefix}wptc_processed_{$this->getTableName()}");
    }
	
	public function get_stored_backup_name($backup_id = null){
		$this_name = $this->db->get_results("SELECT backup_name FROM {$this->db->prefix}wptc_backup_names WHERE backup_id = '$backup_id'" );
		if(isset($this_name[0])){
			return $this_name[0]->backup_name;
		}
		else{
			return '';
		}
	}
	
	public function get_backup_id_details($backup_id){
		$this_name = $this->db->get_results("SELECT * FROM {$this->db->prefix}wptc_processed_{$this->getTableName()} WHERE backup_id = '$backup_id'" );
	}
	
	public function delete_last_month_backups($days = null, $backup_id = null){
		$keep_rev_days = WPTC_Factory::get('config')->get_option('revision_limit');
		//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----keep_rev_days-------- ".var_export($keep_rev_days,true)."\n",FILE_APPEND);
		if(!$keep_rev_days){
			return true;
		}
		else{
			if($keep_rev_days != 'unlimited'){
				$rev_limit_time = strtotime("-$keep_rev_days days");
				$this_name = $this->db->get_results("DELETE FROM {$this->db->prefix}wptc_processed_files WHERE backupID < '$rev_limit_time'" );
				//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----deleted 30------ ".var_export($rev_limit_time,true)."\n",FILE_APPEND);
				//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----any errors------- ".var_export(mysql_error(),true)."\n",FILE_APPEND);
			}
		}
	}
	
	public function get_future_delete_files($backup_id){
		$delete_files = $this->db->get_results("SELECT DISTINCT file FROM {$this->db->prefix}wptc_processed_files WHERE backupID > '$backup_id'" );
		return $delete_files;
	}
	
	public function get_most_recent_revision($file, $backup_id = ''){
		//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----get_most_recent_revision backup id------ ".var_export($file,true)."\n",FILE_APPEND);
		$this_revision = $this->db->get_results($this->db->prepare(" SELECT revision_id,uploaded_file_size FROM {$this->db->prefix}wptc_processed_files WHERE file = %s AND backupID < %s ORDER BY backupID DESC LIMIT 0,1 ", $file, $backup_id));
		//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----this _rev eroors----- ".var_export(mysql_error(),true)."\n",FILE_APPEND);
		return $this_revision;
	}
	
	public function get_past_replace_files($backup_id){
		$replace_files = $this->db->get_results("SELECT DISTINCT file FROM {$this->db->prefix}wptc_processed_files WHERE backupID < '$backup_id'" );
		return $replace_files;
	}
        public function get_all_processed_files()
        {
            $unknown_files = $this->db->get_results("SELECT DISTINCT file FROM {$this->db->prefix}wptc_processed_files",ARRAY_N);
            return $unknown_files;
        }
	
}
