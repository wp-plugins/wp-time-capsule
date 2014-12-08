<?php
/**
 * This file contains the contents of the Dropbox admin options page.
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
try {
    if ($errors = get_option('wptc-init-errors')) {
        delete_option('wptc-init-errors');
        throw new Exception(__('WordPress Time Capsule failed to initialize due to these database errors.', 'wptc') . '<br /><br />' . $errors);
    }

    $validation_errors = null;

    $dropbox = WPTC_Factory::get('dropbox');
    $config = WPTC_Factory::get('config');

    $backup = new WPTC_BackupController();
	
    $backup->create_dump_dir();

    $disable_backup_now = $config->get_option('in_progress');
	//We have a form submit so update the schedule and options
    if (array_key_exists('wptc_save_changes', $_POST)) {
        check_admin_referer('wordpress_time_capsule_options_save');

        if (isset($_POST['dropbox_location']) && preg_match('/[^A-Za-z0-9-_.\/]/', $_POST['dropbox_location'])) {
            add_settings_error('wptc_options', 'invalid_subfolder', __('The sub directory must only contain alphanumeric characters.', 'wptc'), 'error');

            $dropbox_location = $_POST['dropbox_location'];
            $store_in_subfolder = true;
        } else {
            $config
                //->set_schedule($_POST['day'], $_POST['time'], $_POST['frequency'])
                //->set_option('store_in_subfolder', $_POST['store_in_subfolder'] == "on")
				->set_option('before_backup', $_POST['before_backup'])
				->set_option('revision_limit', $_POST['revision_limit'])
                ->set_option('dropbox_location', $config->get_dropbox_folder_tc());

            add_settings_error('general', 'settings_updated', __('Settings saved.'), 'updated');
        }
    } elseif (array_key_exists('unlink', $_POST)) {
        check_admin_referer('wordpress_time_capsule_options_save');
        $backup->unlink_CurrentAccAndBackups();
        $dropbox->unlink_account()->init();
      
    } elseif (array_key_exists('clear_history', $_POST)) {
        check_admin_referer('wordpress_time_capsule_options_save');
        $config->clear_history();
    }

    //Lets grab the schedule and the options to display to the user
    list($unixtime, $frequency) = $config->get_schedule();
    if (!$frequency) {
        $frequency = 'weekly';
    }

    if (!get_settings_errors('wptc_options')) {
        $dropbox_location = $config->get_option('dropbox_location');
        $store_in_subfolder = $config->get_option('store_in_subfolder');
    }

    $time = date('H:i', $unixtime);
    $day = date('D', $unixtime);
    add_thickbox();
    ?>
<link rel="stylesheet" type="text/css" href="<?php echo $uri ?>/JQueryFileTree/jqueryFileTree.css"/>
<script src="<?php echo $uri ?>/JQueryFileTree/jqueryFileTree.js" type="text/javascript" language="javascript"></script>
<script src="<?php echo $uri ?>/wp-time-capsule.js" type="text/javascript" language="javascript"></script>
<script type="text/javascript" language="javascript">
    jQuery(document).ready(function ($) {
        $('#store_in_subfolder').click(function (e) {
            if ($('#store_in_subfolder').is(':checked')) {
                $('.dropbox_location').show('fast', function() {
                    $('#dropbox_location').focus();
                });
            } else {
                $('#dropbox_location').val('');
                $('.dropbox_location').hide();
            }
        });
    });

    /**
     * Display the Dropbox authorize url, hide the authorize button and then show the continue button.
     * @param url
     */
    function yes_change_acc(){
            document.getElementById('unlink').click();
    }
    function no_change(){
             tb_remove();
    }
    function dropbox_authorize(url) {
        window.open(url);
        document.getElementById('continue').style.display = "block";
        document.getElementById('authorize').style.display = "none";
    }
    function ChangeAccount(){
        dialog_for_changeAccount();
    }
</script>
    <div class="wrap" id="wptc">
	<form id="backup_to_dropbox_options" name="backup_to_dropbox_options" action="admin.php?page=wp-time-capsule" method="post">

    <?php if ($dropbox->is_authorized()) {
        $account_info = $dropbox->get_account_info();
		$used = round(($account_info->quota_info->quota - ($account_info->quota_info->normal + $account_info->quota_info->shared)) / 1073741824, 1);
        $quota = round($account_info->quota_info->quota / 1073741824, 1);
		$used = $quota - $used;				//simple fix for dropbox quota display
    ?>
            <div style="width:100%">
                <h2 style="width: 6%; display: inline-block;"><?php _e('Settings', 'wptc'); ?></h2>
                <div style="width: 43%; display: inline-block;">
                    <a style="width: 74%;" href="https://wptimecapsule.uservoice.com/" target="_blank">Got an Idea?</a>
                </div>
            </div>
    <?php settings_errors(); ?>

    <table class="form-table">
        <tbody>
		<tr valign="top">
            <th scope="row"><label ><?php _e("Dropbox Account", 'wptc'); ?></label>
            </th>
            <td>
                <div>
					<?php echo
							$account_info->email;
					?>
                    <a class="change_dbox_user_tc" onclick="ChangeAccount()" style="margin-left: 5px;">Change</a>
				</div>
				<p class="description">
					<?php echo $used . 'GB of ' . $quota . 'GB used'; ?>
				</p>
				<div class="dbox_quota"> <span style="width:<?php echo round(($used / $quota) * 100, 0);?>%"> </span> </div>
				<input type="submit" style="display:none" id="unlink" name="unlink" class="bump button-secondary" value="<?php _e('Unlink Account', 'wptc'); ?>">
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"><label for="revision_limit"><?php _e("Keep File revisions for", 'wptc'); ?></label>
            </th>
            <td>
                <select name="revision_limit" id="revision_limit">
					<option selected="selected" value="30">30 days</option>
					<option value="365">One year</option>
					<option value="unlimited">Unlimited</option>
				</select>
				<p class="description">30 days of file revision history is included with the Free Dropbox account.<br>For one year of revisions, upgrade your Dropbox to Pro and subscribe to <a href="https://www.dropbox.com/en/help/113" target="_blank">Extended Version History</a>.<br>For unlimited revision history, upgrade to Dropbox for Business.</p>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row"> <label>
			<?php _e("Backups before updating", 'wptc'); ?> </label>
            </th>
            <td>
				<fieldset>
					<legend class="screen-reader-text"><span>Backup Before Updating</span></legend>
					<label title="Always(Dont ask me everytime)">
						<input name="before_backup" type="radio" id="before_backup_yes" <?php if($config->get_option('before_backup') == 'yes') echo 'checked';?> value="yes">
						<span class="">
							Always (Dont ask me everytime)
						</span>
					</label>
					<br>
					<label title="Ask me everytime">
						<input name="before_backup" type="radio" id="before_backup_yes_no" <?php if($config->get_option('before_backup') == 'yes_no') echo 'checked';?> value="yes_no">
						<span class="">
							Ask me everytime
						</span>
					</label>
					<br>
					<label>
						<input name="before_backup" type="radio" id="before_backup_no" <?php if($config->get_option('before_backup') == 'no') echo 'checked';?> value="no" >
						<span class="">
							Never
						</span>
					</label>
					<br>
				</fieldset>
            </td>
        </tr>
        </tbody>
    </table>
    <!--[if !IE | gt IE 7]><!-->
    <!--<![endif]-->
    <p class="submit">
        <input type="submit" id="wptc_save_changes" name="wptc_save_changes" class="button-primary" value="<?php _e('Save Changes', 'wptc'); ?>">
    </p>
        <?php wp_nonce_field('wordpress_time_capsule_options_save'); ?>
    </form>
        <?php

    } else {
        ?>
		
		<div class="pu_title"><?php _e('Welcome to WPTimeCapsule', 'wptc'); ?></div>
		<div class="wcard clearfix">
	    <div class="l1"><?php _e('Once you connect your Dropbox account, the backups that
	    you create will be stored in the assigned folder in your account.', 'wptc'); ?></div>
		<form id="backup_to_dropbox_continue" name="backup_to_dropbox_continue" method="post">
			<input type="button" name="authorize" id="authorize" class="btn_pri" style="margin: 20px 59px; width: 330px; text-align: center;" value="<?php _e('Connect my Dropbox account', 'wptc'); ?>" onclick="dropbox_authorize('<?php echo $dropbox->get_authorize_url() ?>')"/>
			<input type="submit" name="continue" id="continue" class="btn_pri" style="margin: 0 95px 20px; width: 250px; text-align: center; display: none;" value="<?php _e('Continue', 'wptc'); ?>" />
		</form>
		</div>

        <?php if (array_key_exists('continue', $_POST) && !$dropbox->is_authorized()): ?>
            <?php $dropbox->unlink_account()->init(); ?>
                <div style="width: 100%">
                    <p style="width: 40%; color: red; margin-left: 30%; text-align: center; padding: 1%; border-radius: 40px; background: none repeat scroll 0% 0% rgb(255, 255, 255); box-shadow: 0px 1px 3px rgb(0, 0, 0); font-weight: bolder;"><?php _e('There was an error authorizing the plugin with your Dropbox account. Please try again.', 'wptc'); ?></p>
                </div>
        <?php endif; ?>
		
        <?php

    }
} catch (Exception $e) {
    echo '<h3>Error</h3>';
    echo '<p>' . __('There was a fatal error loading WordPress Time Capsule. Please fix the problems listed and reload the page.', 'wptc') . '</h3>';
    echo '<p>' . __('If the problem persists please re-install WordPress Time Capsule.', 'wptc') . '</h3>';
    echo '<p><strong>' . __('Error message:') . '</strong> ' . $e->getMessage() . '</p>';

    if ($dropbox)
        $dropbox->unlink_account();
}
?>
<div id="dialog_content_id" style="display:none;"> <p> This is my hidden content! It will appear in ThickBox when the link is clicked. </p></div>
<a style="display:none" href="#TB_inline?width=600&height=550&inlineId=dialog_content_id" class="thickbox">View my inline content!</a>	
</div>
