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
?><h1 style="margin-left: 28%;text-align: center; width: 40%;">WP Time Capsule PRO</h1>
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