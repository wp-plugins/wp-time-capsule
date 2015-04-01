<?php
/**
 * Functionality to remove Wordpress Time Capsule from your WordPress installation
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
if (!defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

delete_option('wptc-init-errors');
delete_option('wptc-premium-extensions');

wp_clear_scheduled_hook('execute_periodic_drobox_backup');
wp_clear_scheduled_hook('execute_instant_drobox_backup');
wp_clear_scheduled_hook('monitor_tcdropbox_backup_hook');

remove_action('run_tc_backup_hook', 'run_tc_backup');
remove_action('monitor_tcdropbox_backup_hook', 'monitor_tcdropbox_backup');
remove_action('execute_instant_drobox_backup', 'execute_tcdropbox_backup');
remove_action('execute_periodic_drobox_backup', 'execute_tcdropbox_backup');
remove_action('admin_menu', 'backup_to_dropbox_admin_menu');
remove_action('wp_ajax_file_tree', 'tc_backup_file_tree');
remove_action('wp_ajax_progress', 'tc_backup_progress');
remove_action('wp_ajax_get_this_day_backups', 'get_this_day_backups_callback');
remove_action('wp_ajax_get_in_progress_backup', 'get_in_progress_tcbackup_callback');
remove_action('wp_ajax_start_backup_tc', 'start_backup_tc_callback');
remove_action('wp_ajax_store_name_for_this_backup', 'store_name_for_this_backup_callback');
remove_action('wp_ajax_start_fresh_backup_tc', 'start_fresh_backup_tc_callback');
remove_action('wp_ajax_stop_fresh_backup_tc', 'stop_fresh_backup_tc_callback');
remove_action('wp_ajax_get_check_to_show_dialog', 'get_check_to_show_dialog_callback');
remove_action('wp_ajax_start_restore_tc', 'start_restore_tc_callback');
remove_action('wp_ajax_get_and_store_before_backup', 'get_and_store_before_backup_callback');

global $wpdb;

$table_name = $wpdb->prefix . 'wptc_options';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

$table_name = $wpdb->prefix . 'wptc_processed_files';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

$table_name = $wpdb->prefix . 'wptc_excluded_files';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

$table_name = $wpdb->prefix . 'wptc_premium_extensions';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

$table_name = $wpdb->prefix . 'wptc_processed_dbtables';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

$table_name = $wpdb->prefix . 'wptc_processed_restored_files';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

$table_name = $wpdb->prefix . 'wptc_backup_names';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

$table_name = $wpdb->prefix . 'wptc_current_process';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

$table_name = $wpdb->prefix . 'wptc_activity_log';
$wpdb->query("DROP TABLE IF EXISTS $table_name");