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
?>
<div class="wrap" id="wptc">
    <div class="icon32"><img width="36px" height="36px"	 src="<?php echo $uri ?>/Images/WordPressTimeCapsule_64.png" alt="WordPress TimeCapsule"></div>
    <h2><?php _e('WordPress Time Capsule', 'wptc'); ?></h2>
    <p class="description"><?php printf(__('Version %s', 'wptc'), WPTC_VERSION) ?></p>
    <p>
        <?php _e(sprintf('
            <p>Hey there,</p>
            <p>I am excited that you have installed WP Time Capsule. We are continuously working to make it very useful for you. But, in order to do so, it needs to use the latest technologies available.</p>
            <p>Unfortunately <b>your version of PHP (%s) is lesser than the minimum required version of 5.2.17</b>. We strongly recommend that you update your PHP to v5.3 or higher because, <a href="%s" target="_blank">as of January 2011</a> version 5.2 is no longer supported by the PHP community. Or, alternatively update at least to PHP v5.2.17.</p>
            <p>Cheers,<br />Babu</p>
            ',
            $v,
            'http://php.net/archive/2011.php#id2011-01-06-1'), 'wptc'); ?>
    </p>
    
    <b>if your site is hosted by a hosting provider, send them this email asking to update the PHP</b>
    <br>
    <div style="background: none repeat scroll 0% 0% rgb(255, 255, 255); padding: 18px; margin-top: 13px; margin-bottom: 23px; width: 653px;">Support for latest PHP version<br><br>I'm interested in using the Wordpress backup plugin <https: wordpress.org="" plugins="" wp-time-capsule=""> in<br>my website - website.com and was wondering if my account supported PHP 5.4 or greater.<br>if not can you please update the PHP to at least v5.2.17<br>Thanks!</https:></div>
    <p><a href="mailto:?Subject=Support%20for%20latest%20PHP%20version&Body=I%27m%20interested%20in%20using%20the%20WordPress%20backup%20plugin%20%3Chttps%3A//wordpress.org/plugins/wp-time-capsule/%3E%20in%20my%20website%20-%20website.com%20and%20was%20wondering%20if%20my%20account%20supported%20PHP%205.4%20or%20greater.If%20not%20can%20you%20please%20update%20the%20PHP%20to%20at%20least%20v5.2.17.%0AThanks%21" target="_top" class="add-new-h2" style="cursor:pointer">Send Mail to your Hosting Provider</a></p>
</div>
