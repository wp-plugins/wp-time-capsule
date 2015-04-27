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
$v = phpversion();
if ($pos = strpos($v, '-'))
    $v = substr($v, 0, $pos);
$current_user = wp_get_current_user();
//Checking this user subscribed the PRO or not
$options = WPTC_Factory::get('config');
$list = $options->get_option('wptc_subscribe');
$showsubscribe = true; 
if($list!=""&&$list!=null)
{
    $Sarr = unserialize($list);
    if(in_array($current_user->user_email, $Sarr))
    {
        $showsubscribe = false; 
    }
}
$imageurl = plugins_url('wp-time-capsule').'/images/';
?>
<!--<h1 style="margin-left: 28%;text-align: center; width: 40%;">WP Time Capsule PRO</h1>
<div style="padding: 0px; text-align: center; width: 40%; background: none repeat scroll 0% 0% rgb(255, 255, 255); margin-left: 28%;">
    <div>
        <div style="font-size:14px; font-weight:600; padding:20px 0 10px;">AUTO BACKUP</div>
        <div style="padding:0 0 20px; border-bottom:1px solid #e5e5e5; line-height: 22px;">Backup every change on your website<br>automatically to Dropbox</div>
        <div style="font-size:14px; font-weight:600; padding:20px 0 10px;">ROLL BACK</div>
        <div style="padding:0 0 20px; border-bottom:1px solid #e5e5e5; line-height: 22px;">Roll-back your WordPress site automatically<br>if anything breaks</div>
        <div style="font-size:14px; font-weight:600; padding:20px 0 10px;">STAGING</div>
        <div style="padding:0 0 20px; border-bottom:1px solid #e5e5e5; line-height: 22px;">Quickly copy your live site to a safe<br>staging environment for you to play in</div>
    </div>
    <div style="height: 63px; padding-top: 9%;">
        <a href="http://wptimecapsule.com/#footer" target="_blank" style="background: #24A1CE; text-decoration: none;width: 288px; text-align: center; color: white; padding: 3% 35%; font-weight: bolder ! important; border-radius: 7px;">Upgrade to PRO</a>
    </div>
</div>-->
<h1 style="margin: 30px auto;text-align: center; width: 40%;">WP Time Capsule PRO</h1>
<div style="text-align: center; padding-bottom: 20px;">Coming Soon...</div>
<div style="padding-top: 30px; text-align: center; width: 60%; background: none repeat scroll 0% 0% rgb(255, 255, 255); margin: auto;border-radius: 5px;">
    <ul style="border-bottom:1px solid #e5e5e5; margin:0;" class="cf">
    <li style="float: left; width: 33.33%;"> <img src="<?php echo $imageurl;?>auto_backup.png">
      <div style="font-size:14px; font-weight:600; padding:20px 0 10px;">AUTO BACKUP</div>
      <div style="padding-bottom:30px; line-height: 22px;">Backup every change on your website<br>
        automatically to Dropbox</div>      <div style="clear:both"></div>
    </li>
    <li style="float: left;width: 33.33%;"> <img src="<?php echo $imageurl;?>roll_backup.png">
      <div style="font-size:14px; font-weight:600; padding:20px 0 10px;">ROLL BACK</div>
      <div style="padding-bottom:30px; line-height: 22px;">Roll-back your WordPress site automatically<br>
        if anything breaks</div>      <div style="clear:both"></div>
    </li>
    <li style="float: left;width: 33.33%;"> <img src="<?php echo $imageurl;?>staging.png">
      <div style="font-size:14px; font-weight:600; padding:20px 0 10px;">STAGING</div>
      <div style="padding-bottom:30px; line-height: 22px;">Quickly copy your live site to a safe<br>
        staging environment for you to play in</div>      <div style="clear:both"></div>
    </li>
  </ul>
  <?php if($showsubscribe){ ?>
    <div class="subscribe_me" style="height: 110px;padding-bottom: 20px;padding-top: 30px;">
      <div style="margin-bottom: 2em; font-weight: 700;text-align: center;">Sounds interesting? Give us your email and we'll keep you updated.</div>
      <div class="row half mailContainer" style="position:relative;">
        <div style="margin: auto;width: 540px;height: 38px;">
          <input style="-moz-appearance: none; color: rgb(85, 85, 85); border: medium none; display: block; outline: 0px none; padding: 6px 7px 7px; text-decoration: none; width: 340px; border-radius: 0;text-align: center;font-family: 'Open Sans',sans-serif;font-size: 12pt;font-weight: 300;float: left;border: 1px solid #e5e5e5;box-shadow: 0 0;margin: 0;border-right: 0;" class="text" name="name" id="mcemail" placeholder="Your email address" type="text" value='<?php echo $current_user->user_email;?>'>
          <a class="email_collect_btn" style="background-color: #E84C3D;color: #FFF;text-decoration: none;display: inline-block;padding: 10px 20px;cursor: pointer;float: left;" onclick="sendMC();">Keep me updated</a>
          <div class="error_cont" style="display:none;position: absolute; top: 40px; font-size: 12px; color: #E84C3D;">Bummer. Your email doesn't look ok. Please check that once.</div>
        </div>
      </div>
    </div>
  <?php }
  else{?>
    <div style="padding:40px 0; line-height: 22px;" class="subscribe_success">
        We will mail you at <b><?php echo $current_user->user_email; ?></b> once this is ready for you.
    </div>
  <?php }?>
  <div id='sub_suc' style="display:none; padding:40px 0; line-height: 22px;" class="subscribe_success"></div>
</div>
<br/>
<br/>
<style type="text/css">
    .error_email{
        border-color: #E84C3D !important;
        color: #E84C3D !important;
    }
    .error_msg{
        color: rgba(255, 0, 0, 0.59);
        background: rgba(255, 0, 0, 0.1);
        font-weight: bold;
    }
</style>
<script>
    function sendMC(){
        var smail=jQuery('#mcemail');
        var validate = IsEmail(smail.val());
        if(validate)
        {
            //email is valid
            smail.removeClass('error_email');
            jQuery('.error_cont').hide();
            SubscribeMe(smail.val());
        }
        else
        {
            jQuery('.error_cont').show();
            smail.addClass('error_email');
        }
    }
    //Validation for email
    function IsEmail(email) {
        var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        return regex.test(email);
    }
    //Subscription on pro
    function SubscribeMe(email){
         jQuery.post(ajaxurl, { action : 'subscribe_me',email:email }, function(data) {
             if(data=='added')
             {
                 jQuery('.subscribe_me').hide();
                 jQuery('#sub_suc').html('Thanks for your interest. We have sent a confirmation link to <strong>'+email+'</strong>.<br>Please click on the link to subscribe to updates. :)');
                 jQuery('#sub_suc').show();
             }
             else if(data=='exist')
             {
                 jQuery('.subscribe_me').toggle();
                 jQuery('#sub_suc').html('Woah! Looks like you really want this :)<br/>We are working hard right now to get this out asap. Thanks again for your interest.');
                 jQuery('#sub_suc').show();
             }
             else
             {
                 jQuery('#sub_suc').addClass('error_msg');
                 jQuery('#sub_suc').html('Oopsie daisy... Something\'s gone haywire. Can you please try this after some time?');
                 jQuery('#sub_suc').show();
             }
         });
    }
   
</script>


    