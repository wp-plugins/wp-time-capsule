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
                ->set_option('dropbox_location', $config->get_dropbox_folder_tc())
                    ->set_option('anonymous_datasent', $_POST['anonymous_datasent'])
                    ->set_option('wptc_timezone',$_POST['wptc_timezone'])
                    ->set_option('schedule_backup',$_POST['schedule_backup']);
            
            //Saving schedule backup options
            if(isset($_POST['schedule_backup'])&&($_POST['schedule_backup']=='on'))
            {
                $interval=$_POST['schedule_interval'];
                $config->set_option('schedule_interval',$interval)
                        ->set_option('schedule_time',$_POST['schedule_time']);
                if($interval=='weekly')
                {
                    $config->set_option('schedule_day',$_POST['schedule_day']);
                }
            }
            wptc_modify_schedule_backup();
            
            
            
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
    
    //getting schedule options 
    $schedule_backup = $config->get_option('schedule_backup');
    $schedule_interval = $config->get_option('schedule_interval');
    $schedule_day = $config->get_option('schedule_day');
    $schedule_time = $config->get_option('schedule_time');
    $wptc_timezone = $config->get_option('wptc_timezone');
    $hightlight ='';
    if(isset($_GET['highlight']))
    {
        $hightlight=$_GET['highlight'];
    }
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
        $('#continue').click(function(){
            jQuery.post(ajaxurl, { action : 'continue_with_wtc' }, function(data) {
			console.log(data);
                        if(data=='authorized'){
                            window.location = '<?php echo admin_url('admin.php?page=wp-time-capsule-monitor&action=initial_setup'); ?>';
                        }
                        else
                        {
                             window.location = '<?php echo admin_url('admin.php?page=wp-time-capsule'); ?>&error';
                        }
		});
        });
        
        $('#schedule_backup_on').click(function(){
            $('.wptc_schedule').fadeIn( "medium");
            $('#schedule_interval').trigger("change");
        });
        
        $('#schedule_backup_off').click(function(){
            $('.wptc_schedule').fadeOut( "medium" );
        });
        $('#schedule_interval').change(function(){
           if($("#schedule_interval option:selected").val()=='weekly')
           {
               $('.week').show();
               
           }
           if($("#schedule_interval option:selected").val()=='daily')
           {
               $('.week').hide();  
               $('.dail').show();
           }
           
        });
        //call trigger when page is load by options on DB
        <?php 
        if($schedule_backup=='on')
        {?>
                
            $('#schedule_backup_on').trigger('click');
        <?php
        }
        else{
        ?>
                $('#schedule_backup_off').trigger('click');
        <?php 
        }
        if($schedule_day!="")
        {?>
                $('#schedule_day').val('<?php echo $schedule_day; ?>');
        <?php
        }       
        if($schedule_time!="")
        {?>
                $('#schedule_time').val('<?php echo $schedule_time; ?>');
        <?php
        }
        if($wptc_timezone!="")
        {?>
                $('#wptc_timezone').val('<?php echo $wptc_timezone;?>');
        <?php
        }
        ?>
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
        document.getElementById('mess').style.display = "none";
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
                    <!--<a style="width: 74%;" href="https://wptimecapsule.uservoice.com/" target="_blank">Got an Idea?</a>-->
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
        <tr valign="top">
            <th scope="row"> <label>
			<?php _e("Send anonymous data", 'wptc'); ?> </label>
            </th>
            <td>
				<fieldset>
					<legend class="screen-reader-text"><span>Send anonymous data</span></legend>
					<label title="Yes">
						<input name="anonymous_datasent" type="radio" id="anonymous_datasent_yes" <?php if($config->get_option('anonymous_datasent') == 'yes') echo 'checked';?> value="yes">
						<span class="">
							Yes
						</span>
					</label>
					<br>
					<label title="No">
						<input name="anonymous_datasent" type="radio" id="anonymous_datasent_no" <?php if($config->get_option('anonymous_datasent') == 'no') echo 'checked';?> value="no">
						<span class="">
							No
						</span>
					</label>
					<br>
				</fieldset>
            </td>
        </tr>
        <tr>
            <?php
            $tzstring = $config -> get_option('wptc_timezone');
            ?>
            <th scope="row"><label for="timezone_string"><?php _e('Timezone') ?></label></th>
            <td>
                <select id="wptc_timezone" name="wptc_timezone"><?php echo select_wptc_timezone(); ?></select>
            </td>
        </tr>
        <tr class="<?php echo ($hightlight=='schedule')?'highlightme':''?>">
            <th scope="row"><label for="schedule_backup"><?php _e('Schedule Backup') ?></label></th>
            <td>
                <fieldset>
                    <legend class="screen-reader-text"><span>Schedule Backup</span></legend>
                        <label title="No">
                            <input name="schedule_backup" type="radio" id="schedule_backup_off" <?php if($config->get_option('schedule_backup') == 'off') echo 'checked';?> value="off">
                            <span class="">Off</span>
			</label>
			<br>
                        <label title="Yes">
                            <input name="schedule_backup" type="radio" id="schedule_backup_on" <?php if($config->get_option('schedule_backup') == 'on') echo 'checked';?> value="on">
                            <span class="">On</span>
			</label>
			<br>
		</fieldset>
            </td>
        </tr>
        <tr class="wptc_schedule" style="display: none">
            <th></th>
            <td>
                <div><p class="description">You need to set a cron job on your server to schedule backups<br><code>*/10 * * * * wget <?php echo site_url( 'wp-cron.php' );?>> /dev/null 2>&1</code><br>This will run wp-cron every 10 minutes. You can change<code>*/10</code> to <code>*/5</code> to make it run every 5 minutes.</p></div>
                <div>
                <br>
                    <div class="sch">
                        <select id="schedule_interval" name="schedule_interval">
                            <option value="daily" <?php if($config->get_option('schedule_interval') == 'daily') echo 'selected';?> >Daily</option>
                            <option value="weekly" <?php if($config->get_option('schedule_interval') == 'weekly') echo 'selected';?> >Weekly</option>
                           
                        </select>
                    </div>
                    <div class="sch week" style="display:none;">
                        Day : 
                        <select id="schedule_day" name="schedule_day">
                            <option value="sunday">Sunday</option>
                            <option value="monday">Monday</option>
                            <option value="tuesday">Tuesday</option>
                            <option value="wednesday">Wednesday</option>
                            <option value="thursday">Thursday</option>
                            <option value="friday">Friday</option>
                            <option value="saturday">Saturday</option>
                        </select>
                    </div>
                    <div class="sch week dail" style="display:none;">
                        Time :
                        <select id="schedule_time" name="schedule_time">
                            <option value="00:00:00">12 am</option>
                            <option value="01:00:00">1 am</option>
                            <option value="02:00:00">2 am</option>
                            <option value="03:00:00">3 am</option>
                            <option value="04:00:00">4 am</option>
                            <option value="05:00:00">5 am</option>
                            <option value="06:00:00">6 am</option>
                            <option value="07:00:00">7 am</option>
                            <option value="08:00:00">8 am</option>
                            <option value="09:00:00">9 am</option>
                            <option value="10:00:00">10 am</option>
                            <option value="11:00:00">11 am</option>
                            <option value="12:00:00">12 pm</option>
                            <option value="13:00:00">1 pm</option>
                            <option value="14:00:00">2 pm</option>
                            <option value="15:00:00">3 pm</option>
                            <option value="16:00:00">4 pm</option>
                            <option value="17:00:00">5 pm</option>
                            <option value="18:00:00">6 pm</option>
                            <option value="19:00:00">7 pm</option>
                            <option value="20:00:00">8 pm</option>
                            <option value="21:00:00">9 pm</option>
                            <option value="22:00:00">10 pm</option>
                            <option value="23:00:00">11 pm</option>                        
                        </select>
                    </div>
                </div> 
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
        <div>For Queries Contact <a href="mailto:help@wptimecapsule.com?Subject=Contact" target="_top">help@wptimecapsule.com</a></div>
        <?php

    } else {
        ?>
		
		<div class="pu_title"><?php _e('Welcome to WP Time Capsule', 'wptc'); ?></div>
		<div class="wcard clearfix">
	    <div class="l1"  style="padding-bottom: 10px;"><?php _e('Once you connect your Dropbox account, the backups that
	    you create will be stored in the assigned folder in your account.', 'wptc'); ?></div>
		<form id="backup_to_dropbox_continue" name="backup_to_dropbox_continue" method="post">
                    <input type="button" name="authorize" id="authorize" class="btn_pri" style="margin: 20px 59px; width: 330px; text-align: center;" value="<?php _e('Connect my Dropbox account', 'wptc'); ?>" onclick="dropbox_authorize('<?php echo $dropbox->get_authorize_url() ?>')"/><div style="clear:both"></div>
                        <div id="mess" style="text-align: center; font-size: 13px; padding-top: 20px; padding-bottom: 10px;">This will connect to Dropbox in a new tab. Once your account is connected, come back to this page and click Continue.</div>
			<input type="button" name="continue" id="continue" class="btn_pri" style="margin: 0 95px 20px; width: 250px; text-align: center; display: none;" value="<?php _e('Continue', 'wptc'); ?>" />
		</form>
		</div>
               <?php if(isset($_GET['error'])&&!$dropbox->is_authorized()): ?>
            <?php $dropbox->unlink_account()->init(); ?>
                <div style="width: 100%">
                    <p style="width: 40%; margin-left: 30%; text-align: center; padding: 1%; font-weight: bolder; background: none repeat scroll 0% 0% rgba(255, 0, 0, 0.1); color: rgba(255, 0, 0, 0.59);">Something went wrong while authorising your Dropbox account.<br>
Please try again after sometime, or <br>
Clear your Dropbox session and try again.</p>
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
<?php
//function for return the timezone select options
function select_wptc_timezone(){
       return '<optgroup label="Africa">
                <option value="Africa/Abidjan">Abidjan</option><option value="Africa/Accra">Accra</option><option value="Africa/Addis_Ababa">Addis Ababa</option><option value="Africa/Algiers">Algiers</option><option value="Africa/Asmara">Asmara</option><option value="Africa/Bamako">Bamako</option><option value="Africa/Bangui">Bangui</option><option value="Africa/Banjul">Banjul</option><option value="Africa/Bissau">Bissau</option><option value="Africa/Blantyre">Blantyre</option><option value="Africa/Brazzaville">Brazzaville</option><option value="Africa/Bujumbura">Bujumbura</option><option value="Africa/Cairo">Cairo</option><option value="Africa/Casablanca">Casablanca</option><option value="Africa/Ceuta">Ceuta</option><option value="Africa/Conakry">Conakry</option><option value="Africa/Dakar">Dakar</option><option value="Africa/Dar_es_Salaam">Dar es Salaam</option><option value="Africa/Djibouti">Djibouti</option><option value="Africa/Douala">Douala</option><option value="Africa/El_Aaiun">El Aaiun</option><option value="Africa/Freetown">Freetown</option><option value="Africa/Gaborone">Gaborone</option><option value="Africa/Harare">Harare</option><option value="Africa/Johannesburg">Johannesburg</option><option value="Africa/Juba">Juba</option><option value="Africa/Kampala">Kampala</option><option value="Africa/Khartoum">Khartoum</option><option value="Africa/Kigali">Kigali</option><option value="Africa/Kinshasa">Kinshasa</option><option value="Africa/Lagos">Lagos</option><option value="Africa/Libreville">Libreville</option><option value="Africa/Lome">Lome</option><option value="Africa/Luanda">Luanda</option><option value="Africa/Lubumbashi">Lubumbashi</option><option value="Africa/Lusaka">Lusaka</option><option value="Africa/Malabo">Malabo</option><option value="Africa/Maputo">Maputo</option><option value="Africa/Maseru">Maseru</option><option value="Africa/Mbabane">Mbabane</option><option value="Africa/Mogadishu">Mogadishu</option><option value="Africa/Monrovia">Monrovia</option><option value="Africa/Nairobi">Nairobi</option><option value="Africa/Ndjamena">Ndjamena</option><option value="Africa/Niamey">Niamey</option><option value="Africa/Nouakchott">Nouakchott</option><option value="Africa/Ouagadougou">Ouagadougou</option><option value="Africa/Porto-Novo">Porto-Novo</option><option value="Africa/Sao_Tome">Sao Tome</option><option value="Africa/Tripoli">Tripoli</option><option value="Africa/Tunis">Tunis</option><option value="Africa/Windhoek">Windhoek</option>
            </optgroup>
            <optgroup label="America">
                <option value="America/Adak">Adak</option><option value="America/Anchorage">Anchorage</option><option value="America/Anguilla">Anguilla</option><option value="America/Antigua">Antigua</option><option value="America/Araguaina">Araguaina</option><option value="America/Argentina/Buenos_Aires">Argentina - Buenos Aires</option><option value="America/Argentina/Catamarca">Argentina - Catamarca</option><option value="America/Argentina/Cordoba">Argentina - Cordoba</option><option value="America/Argentina/Jujuy">Argentina - Jujuy</option><option value="America/Argentina/La_Rioja">Argentina - La Rioja</option><option value="America/Argentina/Mendoza">Argentina - Mendoza</option><option value="America/Argentina/Rio_Gallegos">Argentina - Rio Gallegos</option><option value="America/Argentina/Salta">Argentina - Salta</option><option value="America/Argentina/San_Juan">Argentina - San Juan</option><option value="America/Argentina/San_Luis">Argentina - San Luis</option><option value="America/Argentina/Tucuman">Argentina - Tucuman</option><option value="America/Argentina/Ushuaia">Argentina - Ushuaia</option><option value="America/Aruba">Aruba</option><option value="America/Asuncion">Asuncion</option><option value="America/Atikokan">Atikokan</option><option value="America/Bahia">Bahia</option><option value="America/Bahia_Banderas">Bahia Banderas</option><option value="America/Barbados">Barbados</option><option value="America/Belem">Belem</option><option value="America/Belize">Belize</option><option value="America/Blanc-Sablon">Blanc-Sablon</option><option value="America/Boa_Vista">Boa Vista</option><option value="America/Bogota">Bogota</option><option value="America/Boise">Boise</option><option value="America/Cambridge_Bay">Cambridge Bay</option><option value="America/Campo_Grande">Campo Grande</option><option value="America/Cancun">Cancun</option><option value="America/Caracas">Caracas</option><option value="America/Cayenne">Cayenne</option><option value="America/Cayman">Cayman</option><option value="America/Chicago">Chicago</option><option value="America/Chihuahua">Chihuahua</option><option value="America/Costa_Rica">Costa Rica</option><option value="America/Creston">Creston</option><option value="America/Cuiaba">Cuiaba</option><option value="America/Curacao">Curacao</option><option value="America/Danmarkshavn">Danmarkshavn</option><option value="America/Dawson">Dawson</option><option value="America/Dawson_Creek">Dawson Creek</option><option value="America/Denver">Denver</option><option value="America/Detroit">Detroit</option><option value="America/Dominica">Dominica</option><option value="America/Edmonton">Edmonton</option><option value="America/Eirunepe">Eirunepe</option><option value="America/El_Salvador">El Salvador</option><option value="America/Fortaleza">Fortaleza</option><option value="America/Glace_Bay">Glace Bay</option><option value="America/Godthab">Godthab</option><option value="America/Goose_Bay">Goose Bay</option><option value="America/Grand_Turk">Grand Turk</option><option value="America/Grenada">Grenada</option><option value="America/Guadeloupe">Guadeloupe</option><option value="America/Guatemala">Guatemala</option><option value="America/Guayaquil">Guayaquil</option><option value="America/Guyana">Guyana</option><option value="America/Halifax">Halifax</option><option value="America/Havana">Havana</option><option value="America/Hermosillo">Hermosillo</option><option value="America/Indiana/Indianapolis">Indiana - Indianapolis</option><option value="America/Indiana/Knox">Indiana - Knox</option><option value="America/Indiana/Marengo">Indiana - Marengo</option><option value="America/Indiana/Petersburg">Indiana - Petersburg</option><option value="America/Indiana/Tell_City">Indiana - Tell City</option><option value="America/Indiana/Vevay">Indiana - Vevay</option><option value="America/Indiana/Vincennes">Indiana - Vincennes</option><option value="America/Indiana/Winamac">Indiana - Winamac</option><option value="America/Inuvik">Inuvik</option><option value="America/Iqaluit">Iqaluit</option><option value="America/Jamaica">Jamaica</option><option value="America/Juneau">Juneau</option><option value="America/Kentucky/Louisville">Kentucky - Louisville</option><option value="America/Kentucky/Monticello">Kentucky - Monticello</option><option value="America/Kralendijk">Kralendijk</option><option value="America/La_Paz">La Paz</option><option value="America/Lima">Lima</option><option value="America/Los_Angeles">Los Angeles</option><option value="America/Lower_Princes">Lower Princes</option><option value="America/Maceio">Maceio</option><option value="America/Managua">Managua</option><option value="America/Manaus">Manaus</option><option value="America/Marigot">Marigot</option><option value="America/Martinique">Martinique</option><option value="America/Matamoros">Matamoros</option><option value="America/Mazatlan">Mazatlan</option><option value="America/Menominee">Menominee</option><option value="America/Merida">Merida</option><option value="America/Metlakatla">Metlakatla</option><option value="America/Mexico_City">Mexico City</option><option value="America/Miquelon">Miquelon</option><option value="America/Moncton">Moncton</option><option value="America/Monterrey">Monterrey</option><option value="America/Montevideo">Montevideo</option><option value="America/Montserrat">Montserrat</option><option value="America/Nassau">Nassau</option><option value="America/New_York">New York</option><option value="America/Nipigon">Nipigon</option><option value="America/Nome">Nome</option><option value="America/Noronha">Noronha</option><option value="America/North_Dakota/Beulah">North Dakota - Beulah</option><option value="America/North_Dakota/Center">North Dakota - Center</option><option value="America/North_Dakota/New_Salem">North Dakota - New Salem</option><option value="America/Ojinaga">Ojinaga</option><option value="America/Panama">Panama</option><option value="America/Pangnirtung">Pangnirtung</option><option value="America/Paramaribo">Paramaribo</option><option value="America/Phoenix">Phoenix</option><option value="America/Port-au-Prince">Port-au-Prince</option><option value="America/Port_of_Spain">Port of Spain</option><option value="America/Porto_Velho">Porto Velho</option><option value="America/Puerto_Rico">Puerto Rico</option><option value="America/Rainy_River">Rainy River</option><option value="America/Rankin_Inlet">Rankin Inlet</option><option value="America/Recife">Recife</option><option value="America/Regina">Regina</option><option value="America/Resolute">Resolute</option><option value="America/Rio_Branco">Rio Branco</option><option value="America/Santa_Isabel">Santa Isabel</option><option value="America/Santarem">Santarem</option><option value="America/Santiago">Santiago</option><option value="America/Santo_Domingo">Santo Domingo</option><option value="America/Sao_Paulo">Sao Paulo</option><option value="America/Scoresbysund">Scoresbysund</option><option value="America/Sitka">Sitka</option><option value="America/St_Barthelemy">St Barthelemy</option><option value="America/St_Johns">St Johns</option><option value="America/St_Kitts">St Kitts</option><option value="America/St_Lucia">St Lucia</option><option value="America/St_Thomas">St Thomas</option><option value="America/St_Vincent">St Vincent</option><option value="America/Swift_Current">Swift Current</option><option value="America/Tegucigalpa">Tegucigalpa</option><option value="America/Thule">Thule</option><option value="America/Thunder_Bay">Thunder Bay</option><option value="America/Tijuana">Tijuana</option><option value="America/Toronto">Toronto</option><option value="America/Tortola">Tortola</option><option value="America/Vancouver">Vancouver</option><option value="America/Whitehorse">Whitehorse</option><option value="America/Winnipeg">Winnipeg</option><option value="America/Yakutat">Yakutat</option><option value="America/Yellowknife">Yellowknife</option>
            </optgroup>
            <optgroup label="Antarctica">
                <option value="Antarctica/Casey">Casey</option><option value="Antarctica/Davis">Davis</option><option value="Antarctica/DumontDUrville">DumontDUrville</option><option value="Antarctica/Macquarie">Macquarie</option><option value="Antarctica/Mawson">Mawson</option><option value="Antarctica/McMurdo">McMurdo</option><option value="Antarctica/Palmer">Palmer</option><option value="Antarctica/Rothera">Rothera</option><option value="Antarctica/Syowa">Syowa</option><option value="Antarctica/Troll">Troll</option><option value="Antarctica/Vostok">Vostok</option>
            </optgroup>
            <optgroup label="Arctic">
                <option value="Arctic/Longyearbyen">Longyearbyen</option>
            </optgroup>
            <optgroup label="Asia">
                <option value="Asia/Aden">Aden</option><option value="Asia/Almaty">Almaty</option><option value="Asia/Amman">Amman</option><option value="Asia/Anadyr">Anadyr</option><option value="Asia/Aqtau">Aqtau</option><option value="Asia/Aqtobe">Aqtobe</option><option value="Asia/Ashgabat">Ashgabat</option><option value="Asia/Baghdad">Baghdad</option><option value="Asia/Bahrain">Bahrain</option><option value="Asia/Baku">Baku</option><option value="Asia/Bangkok">Bangkok</option><option value="Asia/Beirut">Beirut</option><option value="Asia/Bishkek">Bishkek</option><option value="Asia/Brunei">Brunei</option><option value="Asia/Chita">Chita</option><option value="Asia/Choibalsan">Choibalsan</option><option value="Asia/Colombo">Colombo</option><option value="Asia/Damascus">Damascus</option><option value="Asia/Dhaka">Dhaka</option><option value="Asia/Dili">Dili</option><option value="Asia/Dubai">Dubai</option><option value="Asia/Dushanbe">Dushanbe</option><option value="Asia/Gaza">Gaza</option><option value="Asia/Hebron">Hebron</option><option value="Asia/Ho_Chi_Minh">Ho Chi Minh</option><option value="Asia/Hong_Kong">Hong Kong</option><option value="Asia/Hovd">Hovd</option><option value="Asia/Irkutsk">Irkutsk</option><option value="Asia/Jakarta">Jakarta</option><option value="Asia/Jayapura">Jayapura</option><option value="Asia/Jerusalem">Jerusalem</option><option value="Asia/Kabul">Kabul</option><option value="Asia/Kamchatka">Kamchatka</option><option value="Asia/Karachi">Karachi</option><option value="Asia/Kathmandu">Kathmandu</option><option value="Asia/Khandyga">Khandyga</option><option value="Asia/Kolkata">Kolkata</option><option value="Asia/Krasnoyarsk">Krasnoyarsk</option><option value="Asia/Kuala_Lumpur">Kuala Lumpur</option><option value="Asia/Kuching">Kuching</option><option value="Asia/Kuwait">Kuwait</option><option value="Asia/Macau">Macau</option><option value="Asia/Magadan">Magadan</option><option value="Asia/Makassar">Makassar</option><option value="Asia/Manila">Manila</option><option value="Asia/Muscat">Muscat</option><option value="Asia/Nicosia">Nicosia</option><option value="Asia/Novokuznetsk">Novokuznetsk</option><option value="Asia/Novosibirsk">Novosibirsk</option><option value="Asia/Omsk">Omsk</option><option value="Asia/Oral">Oral</option><option value="Asia/Phnom_Penh">Phnom Penh</option><option value="Asia/Pontianak">Pontianak</option><option value="Asia/Pyongyang">Pyongyang</option><option value="Asia/Qatar">Qatar</option><option value="Asia/Qyzylorda">Qyzylorda</option><option value="Asia/Rangoon">Rangoon</option><option value="Asia/Riyadh">Riyadh</option><option value="Asia/Sakhalin">Sakhalin</option><option value="Asia/Samarkand">Samarkand</option><option value="Asia/Seoul">Seoul</option><option value="Asia/Shanghai">Shanghai</option><option value="Asia/Singapore">Singapore</option><option value="Asia/Srednekolymsk">Srednekolymsk</option><option value="Asia/Taipei">Taipei</option><option value="Asia/Tashkent">Tashkent</option><option value="Asia/Tbilisi">Tbilisi</option><option value="Asia/Tehran">Tehran</option><option value="Asia/Thimphu">Thimphu</option><option value="Asia/Tokyo">Tokyo</option><option value="Asia/Ulaanbaatar">Ulaanbaatar</option><option value="Asia/Urumqi">Urumqi</option><option value="Asia/Ust-Nera">Ust-Nera</option><option value="Asia/Vientiane">Vientiane</option><option value="Asia/Vladivostok">Vladivostok</option><option value="Asia/Yakutsk">Yakutsk</option><option value="Asia/Yekaterinburg">Yekaterinburg</option><option value="Asia/Yerevan">Yerevan</option>
            </optgroup>
            <optgroup label="Atlantic">
                <option value="Atlantic/Azores">Azores</option><option value="Atlantic/Bermuda">Bermuda</option><option value="Atlantic/Canary">Canary</option><option value="Atlantic/Cape_Verde">Cape Verde</option><option value="Atlantic/Faroe">Faroe</option><option value="Atlantic/Madeira">Madeira</option><option value="Atlantic/Reykjavik">Reykjavik</option><option value="Atlantic/South_Georgia">South Georgia</option><option value="Atlantic/Stanley">Stanley</option><option value="Atlantic/St_Helena">St Helena</option>
            </optgroup>
            <optgroup label="Australia">
                <option value="Australia/Adelaide">Adelaide</option><option value="Australia/Brisbane">Brisbane</option><option value="Australia/Broken_Hill">Broken Hill</option><option value="Australia/Currie">Currie</option><option value="Australia/Darwin">Darwin</option><option value="Australia/Eucla">Eucla</option><option value="Australia/Hobart">Hobart</option><option value="Australia/Lindeman">Lindeman</option><option value="Australia/Lord_Howe">Lord Howe</option><option value="Australia/Melbourne">Melbourne</option><option value="Australia/Perth">Perth</option><option value="Australia/Sydney">Sydney</option>
            </optgroup>
            <optgroup label="Europe">
                <option value="Europe/Amsterdam">Amsterdam</option><option value="Europe/Andorra">Andorra</option><option value="Europe/Athens">Athens</option><option value="Europe/Belgrade">Belgrade</option><option value="Europe/Berlin">Berlin</option><option value="Europe/Bratislava">Bratislava</option><option value="Europe/Brussels">Brussels</option><option value="Europe/Bucharest">Bucharest</option><option value="Europe/Budapest">Budapest</option><option value="Europe/Busingen">Busingen</option><option value="Europe/Chisinau">Chisinau</option><option value="Europe/Copenhagen">Copenhagen</option><option value="Europe/Dublin">Dublin</option><option value="Europe/Gibraltar">Gibraltar</option><option value="Europe/Guernsey">Guernsey</option><option value="Europe/Helsinki">Helsinki</option><option value="Europe/Isle_of_Man">Isle of Man</option><option value="Europe/Istanbul">Istanbul</option><option value="Europe/Jersey">Jersey</option><option value="Europe/Kaliningrad">Kaliningrad</option><option value="Europe/Kiev">Kiev</option><option value="Europe/Lisbon">Lisbon</option><option value="Europe/Ljubljana">Ljubljana</option><option value="Europe/London">London</option><option value="Europe/Luxembourg">Luxembourg</option><option value="Europe/Madrid">Madrid</option><option value="Europe/Malta">Malta</option><option value="Europe/Mariehamn">Mariehamn</option><option value="Europe/Minsk">Minsk</option><option value="Europe/Monaco">Monaco</option><option value="Europe/Moscow">Moscow</option><option value="Europe/Oslo">Oslo</option><option value="Europe/Paris">Paris</option><option value="Europe/Podgorica">Podgorica</option><option value="Europe/Prague">Prague</option><option value="Europe/Riga">Riga</option><option value="Europe/Rome">Rome</option><option value="Europe/Samara">Samara</option><option value="Europe/San_Marino">San Marino</option><option value="Europe/Sarajevo">Sarajevo</option><option value="Europe/Simferopol">Simferopol</option><option value="Europe/Skopje">Skopje</option><option value="Europe/Sofia">Sofia</option><option value="Europe/Stockholm">Stockholm</option><option value="Europe/Tallinn">Tallinn</option><option value="Europe/Tirane">Tirane</option><option value="Europe/Uzhgorod">Uzhgorod</option><option value="Europe/Vaduz">Vaduz</option><option value="Europe/Vatican">Vatican</option><option value="Europe/Vienna">Vienna</option><option value="Europe/Vilnius">Vilnius</option><option value="Europe/Volgograd">Volgograd</option><option value="Europe/Warsaw">Warsaw</option><option value="Europe/Zagreb">Zagreb</option><option value="Europe/Zaporozhye">Zaporozhye</option><option value="Europe/Zurich">Zurich</option>
            </optgroup>
            <optgroup label="Indian">
                <option value="Indian/Antananarivo">Antananarivo</option><option value="Indian/Chagos">Chagos</option><option value="Indian/Christmas">Christmas</option><option value="Indian/Cocos">Cocos</option><option value="Indian/Comoro">Comoro</option><option value="Indian/Kerguelen">Kerguelen</option><option value="Indian/Mahe">Mahe</option><option value="Indian/Maldives">Maldives</option><option value="Indian/Mauritius">Mauritius</option><option value="Indian/Mayotte">Mayotte</option><option value="Indian/Reunion">Reunion</option>
            </optgroup>
            <optgroup label="Pacific">
                <option value="Pacific/Apia">Apia</option><option value="Pacific/Auckland">Auckland</option><option value="Pacific/Chatham">Chatham</option><option value="Pacific/Chuuk">Chuuk</option><option value="Pacific/Easter">Easter</option><option value="Pacific/Efate">Efate</option><option value="Pacific/Enderbury">Enderbury</option><option value="Pacific/Fakaofo">Fakaofo</option><option value="Pacific/Fiji">Fiji</option><option value="Pacific/Funafuti">Funafuti</option><option value="Pacific/Galapagos">Galapagos</option><option value="Pacific/Gambier">Gambier</option><option value="Pacific/Guadalcanal">Guadalcanal</option><option value="Pacific/Guam">Guam</option><option value="Pacific/Honolulu">Honolulu</option><option value="Pacific/Johnston">Johnston</option><option value="Pacific/Kiritimati">Kiritimati</option><option value="Pacific/Kosrae">Kosrae</option><option value="Pacific/Kwajalein">Kwajalein</option><option value="Pacific/Majuro">Majuro</option><option value="Pacific/Marquesas">Marquesas</option><option value="Pacific/Midway">Midway</option><option value="Pacific/Nauru">Nauru</option><option value="Pacific/Niue">Niue</option><option value="Pacific/Norfolk">Norfolk</option><option value="Pacific/Noumea">Noumea</option><option value="Pacific/Pago_Pago">Pago Pago</option><option value="Pacific/Palau">Palau</option><option value="Pacific/Pitcairn">Pitcairn</option><option value="Pacific/Pohnpei">Pohnpei</option><option value="Pacific/Port_Moresby">Port Moresby</option><option value="Pacific/Rarotonga">Rarotonga</option><option value="Pacific/Saipan">Saipan</option><option value="Pacific/Tahiti">Tahiti</option><option value="Pacific/Tarawa">Tarawa</option><option value="Pacific/Tongatapu">Tongatapu</option><option value="Pacific/Wake">Wake</option><option value="Pacific/Wallis">Wallis</option>
            </optgroup>
            <optgroup label="UTC">
                <option value="UTC">UTC</option>
            </optgroup>';
}
?>