<?php
/**
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
$config = WPTC_Factory::get('config');

if (!$config->get_option('in_progress'))
    spawn_cron();

//$log = WPTC_Factory::get('logger')->get_log();

 
	//getting the previous backups
	$processed_files = WPTC_Factory::get('processed-files');
	$return_array = array();
	$return_array['stored_backups'] = $processed_files->get_stored_backups();
	$return_array['backup_progress'] = '';
	//prepare the progress of backup using total files count and currently processed files
	if($config->get_option('in_progress'))
	{
		//get the current backupID
		/* $current_backup_ID = getTcCookie('backupID');
		$return_array['backup_progress'][$current_backup_ID]['total_files'] = $config->get_option('supposed_total_files_count');
		$return_array['backup_progress'][$current_backup_ID]['processed_files'] =  $processed_files->get_processed_files_count($current_backup_ID);
		$return_array['backup_progress'][$current_backup_ID]['progress_percent'] =  !empty($return_array['backup_progress'][$current_backup_ID]['processed_files'])?($return_array['backup_progress'][$current_backup_ID]['processed_files']/$return_array['backup_progress'][$current_backup_ID]['total_files']):0; */
		$current_backup_ID = getTcCookie('backupID');
                $return_array['backup_progress']['overall_files'] = $config->get_option('total_file_count');
                $return_array['backup_progress']['total_files'] = $config->get_option('supposed_total_files_count');
                $return_array['backup_progress']['processed_files'] =  $processed_files->get_processed_files_count($current_backup_ID);
                $return_array['backup_progress']['processed_totcount'] = ($return_array['backup_progress']['overall_files']-$return_array['backup_progress']['total_files'])+$return_array['backup_progress']['processed_files'];
		$prog_percent = 0.00002;
		if(!empty($return_array['backup_progress']['processed_files']) && !empty($return_array['backup_progress']['total_files'])){
			$prog_percent = $return_array['backup_progress']['processed_totcount']/$return_array['backup_progress']['overall_files'];
			if($prog_percent > 99){
				$prog_percent = 0.00002;
			}
		}
		$return_array['backup_progress']['progress_percent'] =  $prog_percent;
	}
	echo json_encode($return_array);

exit;
if (empty($log)): ?>
    <p><?php _e('You have not run a backup yet. When you do you will see a log of it here.'); ?></p>
<?php else: ?>
    <ul>
        <?php foreach (array_reverse($log) as $log_item): ?>
            <li>
            <?php
                if (preg_match('/^Uploaded Files:/', $log_item)) {
                    $files = json_decode(preg_replace('/^Uploaded Files:/', '', $log_item), true);
                    continue;
                }
                echo esc_attr($log_item);
            ?>
            <?php if (!empty($files)): ?>
                <a class="view-files" href="#"><?php _e('View uploaded', 'wptc') ?> &raquo;</a>
                <ul class="files">
                    <?php foreach ($files as $file): ?>
                        <li title="<?php echo sprintf(__('Last modified: %s', 'wptc'), date('F j, Y, H:i:s', $file['mtime'])) ?>"><?php echo esc_attr($file['file']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php $files = null; endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
	<div class="calendar_wrapper">
		<?php 
			//getting the previous backups
			$processed_files = WPTC_Factory::get('processed-files');
			echo json_encode($processed_files->get_stored_backups());
		?>
	</div>
<?php endif;
