<?php
/**
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
class WPTC_Logger
{
    const LOGFILE = 'wptc-backup-log.txt';

    private $logFile = null;

    public function log($msg, $type = "",$action_id = "" )
    {
        $this->set_log_now($type,$msg,$action_id);
		//return true;
        //$fh = fopen($this->get_log_file(), 'a');

        //$msg = iconv('UTF-8', 'UTF-8//IGNORE', $msg);
        $log = sprintf("%s@%s", date('Y-m-d H:i:s', strtotime(current_time('mysql'))), $msg) . "\n";

        /* if (!empty($files)) {
            $log .= "Uploaded Files:" . json_encode($files) . "\n";
        } */
		@file_put_contents($this->get_log_file(), $log, FILE_APPEND);
		//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde_log.php',"\n ----logging-------- ".var_export($log,true)."\n",FILE_APPEND);
		
		/* if (@fwrite($fh, $log) === false || @fclose($fh) === false) {
            throw new Exception('Error writing to log file.');
        } */
    }

    public function get_log()
    {
        $file = $this->get_log_file();
        if (!file_exists($file)) {
            return false;
        }

        $contents = trim(file_get_contents($file));
        if (strlen($contents) < 1) {
            return false;
        }

        return explode("\n", $contents);
    }

    public function delete_log()
    {
        $this->logFile = null;
        @unlink($this->get_log_file());
    }

    public function get_log_file()
    {
		if (!$this->logFile) {
            //WPTC_BackupController::create_dump_dir();
			$path = WPTC_Factory::get('config')->get_backup_dir() . DIRECTORY_SEPARATOR . self::LOGFILE;

            $files = glob($path . '.*');
            if (isset($files[0])) {
                $this->logFile = $files[0];
            } else {
                $this->logFile = $path . '.' . WPTC_Factory::secret(self::LOGFILE);
            }
        }
		//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ----returning a log file------- ".var_export($this->logFile,true)."\n",FILE_APPEND);
		return $this->logFile;
    }
     
    public function set_log_now($type,$msg,$action_id)
    {
        global $wpdb;
        $current_time=time();
        $LogData=  serialize(array('action'=>$type,'log_time'=>$current_time,'msg'=>$msg));
        $DBLogArray=array('activated_plugin','deactivated_plugin','remove_currentacc','backup_start','backup_progress','backup_error','backup_complete', 'restore_start', 'restore_complete', 'restore_error','connection_error');
        if($action_id=="")
        {
            $action_id=null;
        }
        if($type!="")
        {
            if(in_array($type, $DBLogArray))
            {
                //insert log into DB
                $wpdb->insert($wpdb->prefix.'wptc_activity_log',array('type' => $type,'log_data' => $LogData,'action_id'=>$action_id));
                
            }
        }
    }
}
