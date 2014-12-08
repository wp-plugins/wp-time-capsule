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
class WPTC_Config
{
    const MAX_HISTORY_ITEMS = 20;

    private
        $db,
        $options
        ;

    public function __construct()
    {
        $this->db = WPTC_Factory::db();
    }

    public static function get_backup_dir()
    {
        return str_replace('/', DIRECTORY_SEPARATOR, WP_CONTENT_DIR . '/tCapsule/backups');
    }

    public function set_option($name, $value)
    {
		if($name == 'ignored_files_count'){
			//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n --ignored_files_count--- ".var_export($value,true)."\n",FILE_APPEND);
		}
		if(($name == 'is_bridge_process'))
		{
			//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n ---setting option properly----- ".var_export($value,true)."\n",FILE_APPEND);
			//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n --prefix---- ".var_export($this->db->prefix,true)."\n",FILE_APPEND);
		}
        //Short circut if not changed
        if ($this->get_option($name) === $value) {
            return $this;
        }

        $exists = $this->db->get_var(
            $this->db->prepare("SELECT * FROM {$this->db->prefix}wptc_options WHERE name = %s", $name)
        );

        if (is_null($exists)) {
            $this->db->insert($this->db->prefix . "wptc_options", array(
                'name' => $name,
                'value' => $value,
            ));
        } else {
            $this->db->update(
                $this->db->prefix . 'wptc_options',
                array('value' => $value),
                array('name' => $name)
            );
        }

        $this->options[$name] = $value;

        return $this;
    }

    public function get_option($name, $no_cache = false)
    {
        if (!isset($this->options[$name]) || $no_cache) {
            $this->options[$name] = $this->db->get_var(
                $this->db->prepare("SELECT value FROM {$this->db->prefix}wptc_options WHERE name = %s", $name)
            );
        }

        return $this->options[$name];
    }

    public static function set_time_limit()
    {
        @set_time_limit(0);
    }

    public static function set_memory_limit()
    {
        @ini_set('memory_limit', WP_MAX_MEMORY_LIMIT);
    }

    public function is_scheduled()
    {
        return wp_get_schedule('execute_instant_drobox_backup') !== false;
    }

    public function set_schedule($day, $time, $frequency)
    {
        $blog_time = strtotime(date('Y-m-d H', strtotime(current_time('mysql'))) . ':00:00');

        //Grab the date in the blogs timezone
        $date = date('Y-m-d', $blog_time);

        //Check if we need to schedule the backup in the future
        $time_arr = explode(':', $time);
        $current_day = date('D', $blog_time);
        if ($day && ($current_day != $day)) {
            $date = date('Y-m-d', strtotime("next $day"));
        } elseif ((int) $time_arr[0] <= (int) date('H', $blog_time)) {
            if ($day) {
                $date = date('Y-m-d', strtotime("+7 days", $blog_time));
            } else {
                $date = date('Y-m-d', strtotime("+1 day", $blog_time));
            }
        }

        $timestamp = wp_next_scheduled('execute_periodic_drobox_backup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'execute_periodic_drobox_backup');
        }

        //This will be in the blogs timezone
        $scheduled_time = strtotime($date . ' ' . $time);

        //Convert the selected time to that of the server
        $server_time = strtotime(date('Y-m-d H') . ':00:00') + ($scheduled_time - $blog_time);

        wp_schedule_event($server_time, $frequency, 'execute_periodic_drobox_backup');

        return $this;
    }

    public function get_schedule()
    {
        $time = wp_next_scheduled('execute_periodic_drobox_backup');
        $frequency = wp_get_schedule('execute_periodic_drobox_backup');
        $schedule = null;

        if ($time && $frequency) {
            //Convert the time to the blogs timezone
            $blog_time = strtotime(date('Y-m-d H', strtotime(current_time('mysql'))) . ':00:00');
            $blog_time += $time - strtotime(date('Y-m-d H') . ':00:00');
            $schedule = array($blog_time, $frequency);
        }

        return $schedule;
    }

    public function clear_history()
    {
        $this->set_option('history', null);
    }

    public function get_history()
    {
        $history = $this->get_option('history');
        if (!$history){
            return array();
        }

        return explode(',', $history);
    }

    public function get_dropbox_path($source, $file, $root = false)
    {
        $dropbox_location = null;
        //if ($this->get_option('store_in_subfolder')){
			if(!$this->get_option('dropbox_location')){
				$dropbox_location = $this->get_dropbox_folder_tc();
				//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----manual dropbox------- ".var_export($dropbox_location,true)."\n",FILE_APPEND);
				$this->set_option('dropbox_location', $dropbox_location);
			}
			else{
				$dropbox_location = $this->get_option('dropbox_location');
			}
        //}
		
		//$dropbox_location = basename(site_url());

        if ($root){
            return $dropbox_location;
        }

        $source = rtrim($source, DIRECTORY_SEPARATOR);

        return ltrim(dirname(str_replace($source, $dropbox_location, $file)), DIRECTORY_SEPARATOR);
    }
	
	public function get_dropbox_folder_tc(){
		$this_site_name = str_replace(array(
            "_",
            "/",
	    			"~"
        ), array(
            "",
            "-",
            "-"
        ), rtrim($this->remove_http(get_bloginfo('url')), "/"));
		return $this_site_name;
	}
	
	public function remove_http($url = '')
    {
        if ($url == 'http://' OR $url == 'https://') {
            return $url;
        }
        return preg_replace('/^(http|https)\:\/\/(www.)?/i', '', $url);
        
    }

    public function log_finished_time()
    {
        $history = $this->get_history();
        $history[] = time();

        if (count($history) > self::MAX_HISTORY_ITEMS) {
            array_shift($history);
        }

        $this->set_option('history', implode(',', $history));

        return $this;
    }

    public function complete($this_process = null)
    {
		//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----coming inside complete------- \n",FILE_APPEND);
		if($this_process == 'restore')
		{
			$this->set_option('in_progress_restore', false);
			$this->set_option('is_running_restore', false);
			$this->set_option('cur_res_b_id', false);
			return $this;
		}
		
		wp_clear_scheduled_hook('monitor_tcdropbox_backup_hook');
        wp_clear_scheduled_hook('run_tc_backup_hook');
        wp_clear_scheduled_hook('execute_instant_drobox_backup');

		$processed = new WPTC_Processed_DBTables();
        $processed->truncate();
		
        $processed = new WPTC_Processed_Files();
        //$processed->truncate();
        $this->set_option('getfileslist', false);
        $this->set_option('in_progress', false);
		$this->set_option('is_running', false);
		$this->set_option('ignored_files_count', 0);
		$this->set_option('supposed_total_files_count', 0);
		//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----completeing backup progress--------\n",FILE_APPEND);

        $this->set_option('last_backup_time', time());
		
        return $this;
    }

    public function die_if_stopped()
    {
        $in_progress = $this->db->get_var("SELECT value FROM {$this->db->prefix}wptc_options WHERE name = 'in_progress'");
        if (!$in_progress) {
            $msg = __('Backup stopped by user.', 'wptc');
            WPTC_Factory::get('logger')->log($msg);
            die($msg);
        }
    }
}
