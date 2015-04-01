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
		$this->db->prefix = 'wp_';
		
		if($this->getTableName() != 'restored_files')
		{
			$ret = $this->db->get_results("SELECT * FROM {$this->db->prefix}wptc_processed_{$this->getTableName()}");
		}
		else
		{
			if(!$this->getProcessType())
			{
				$ret = $this->db->get_results("SELECT * FROM {$this->db->prefix}wptc_processed_{$this->getTableName()} WHERE download_status = 'done'");
			}
			else
			{	//this is a trick to get only the processed copied files;
				$ret = $this->db->get_results("SELECT * FROM {$this->db->prefix}wptc_processed_{$this->getTableName()} WHERE download_status = 'done' AND copy_status = 'true'");
			}
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
	//	//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----all_backups------ ".var_export($all_backups,true)."\n",FILE_APPEND);	
		
		return $all_backups;
	}
	
	protected function get_all_restores(){
		$all_restores = $this->db->get_results(
		$this->db->prepare("
			SELECT * 
			FROM {$this->db->prefix}wptc_processed_{$this->getTableName()}  ")					//manual
		);
		return $all_restores;
	}
	
	
    protected function upsert($data)
    {
		////file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ------upsertData-------- ".var_export($data,true)."\n",FILE_APPEND);
		if(!empty($data[$this->getUploadMtime()]))			//am introducing this condition to avoid conflicts with multipart upload     manual
		{
			//am adding an extra condition to check the modified time (if the modified time is different then add the values to DB or else leave it)
			$exists = $this->db->get_var(
			$this->db->prepare("SELECT * FROM {$this->db->prefix}wptc_processed_{$this->getTableName()} WHERE {$this->getId()} = %s AND {$this->getUploadMtime()} = %s ", $data[$this->getId()], $data[$this->getUploadMtime()])  );
		}
		else
		{	
			////file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----replacing whole thing------\n",FILE_APPEND);
			$exists = $this->db->get_var(
			$this->db->prepare("SELECT * FROM {$this->db->prefix}wptc_processed_{$this->getTableName()} WHERE {$this->getId()} = %s ", $data[$this->getId()] )  );
		}
		////file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----file ".var_export($data[$this->getId()],true)."-----exist result--- ".var_export($exists,true)."\n",FILE_APPEND);
        if (is_null($exists)) {
			$this_insert_result = $this->db->insert("{$this->db->prefix}wptc_processed_{$this->getTableName()}", $data);
			//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----this_insert_table prefix------- ".var_export($this->db->prefix,true)."\n",FILE_APPEND);
			//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----this_insert_result------- ".var_export($this_insert_result,true)."\n",FILE_APPEND);
			//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----mysqlerror------- ".var_export(mysql_error(),true)."\n",FILE_APPEND);
			if($this_insert_result)
			{
				$data['file_id'] = $this_insert_result;				//am not adding file_id to the processed restored file array
			}
            $this->processed[] = (object)$data;
        } else {
			if(!empty($data['file_id']))
			{
				////file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----upsertData------ ".var_export($data,true)."\n",FILE_APPEND);
				$this->db->update(																//am changing the whole update process to file_id
					"{$this->db->prefix}wptc_processed_{$this->getTableName()}",
					$data,
					array($this->getFileId() => $data[$this->getFileId()])
				);
			}
			else
			{
				////file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----upsertData------ ".var_export($data,true)."\n",FILE_APPEND);
				$this->db->update(																//am changing the whole update process to file_id
					"{$this->db->prefix}wptc_processed_{$this->getTableName()}",
					$data,
					array($this->getId() => $data[$this->getId()])
				);
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
		return $this_name;
	}
}
