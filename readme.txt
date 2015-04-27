=== WP Time Capsule ===

Contributors: WPTimeCapsule, dark-prince, developerbabu

Tags: wptimecapsule, wordpress backup, dropbox backup, wordpress incremental backup, backup without zip

Requires at least: 3.2

Tested up to: 4.2

Stable tag: 1.0.0beta1



Backup only the changed files and DB of your site to your Dropbox. And time-warp (ahem.. restore) your site back to the past.



== Description ==

[WP Time Capsule](http://wptimecapsule.com/ "Incremental Backup for WordPress") was created to ensure peace of mind with WP updates and put the fun back into WordPress. It uses Dropbox to detect the changes and backups just the changed file and db entries to your Dropbox account. 



**How is WP Time Capsule different than other backup plugins?**

WPTC is unique in 3 ways -<br>
1. It backs up only the changes and not the entire site every time you take a backup.<br>
2. We have used Dropbox's unrivalled and time-tested file versioning system to detect changes. With Dropbox as the backend, backups and restores are as reliable as they get.<br>
3. When you apply updates, we will take a backup of your site and then run the update so that we will be restore-ready if needed.
<br><br>
**How does it work?**
<br>
An initial backup of your whole WordPress site is taken once. After that, only the files and database entries that have changed are backed up and restored. This is done by using Dropbox’s native file versioning system.
<br><br>
**Backup**: Looks for files / db changed since the last backup &rarr; Uploads only the changes &rarr; Data stored securely in your Dropbox account.
<br>
**Restore**: Checks Dropbox revision history and displays it &rarr; You choose the version to restore &rarr; Restores only the changed files of the selected version.
<br><br>
**How is it better?**<br>
BACKUP METHOD<br>
Traditionally - Backups are compressed and zipped. The Bad: Heavy server resource consumption.<br>
WPTC - No zipping. Changed files are dropped into your Dropbox. The Good: ***Uses very less server resource*** 
<br><br>
BACKUP FILE<br>
Traditionally - Multiple zip files are created every time you backup. The Bad: Precious storage space is wasted.<br>
WPTC - Backs up incrementally. No multiple copies of files. The Good: ***Uses very less disk space***
<br><br>
RESTORE<br>
Traditionally - Unzip backup and restore the whole site. The Bad: Consumes time and server resource.<br>
WPTC - Restores only selected files. The Good: ***Faster restore***
<br><br>
BACKUP BEFORE UPDATE<br>
Traditionally - Whole site is backed up manually before every update. The Bad: It is laborious and time-consuming.<br>
WPTC - Takes a backup when you update anything. The Good: ***Automatic backup just before updating***
<br><br>
**WP Time Capsule Pro (Coming Soon)**<br>
AUTO BACKUP<br>
Changes to the files / database will be auto-detected and backed up instantly.
<br><br>
ROLL BACK<br>
In case your website goes down, we will automatically restore the latest backup and notify you.
<br><br>
MULTIPLE STAGING ENVIRONMENTS<br>
Be it for development or for previewing major updates, the benefits of having multiple staging environments are clear enough.
<br><br>
Visit us at [wptimecapsule.com](http://wptimecapsule.com/ "Incremental Backup for WordPress")

Credits: Michael De Wildt for his WordPress Backup to Dropbox plugin based on which this plugin is being developed.


== Installation ==
= Minimum Requirements =
 * PHP version 5.2.17 or greater (recommended: PHP 5.4 or greater)
 * MySQL version 5.0.15 or greater (recommended: MySQL 5.5 or greater)

= Installation =
Installing WP Time Capsule is simple and easy. Install it like any other WordPress plugin.
<ol>
  <li>Login to your WordPress dashboard, under Plugins click Add New</li>
  <li>In the plugin repository search for WP Time Capsule or upload the download the plugin zip and install it</li>
  <li>Once installed click Activate plugin. You can see the WP Time Capsule plugin in dashboard</li>
</ol>

== Screenshots ==

1. **Backup calendar view** - You can view and restore files + database from a calendar view.
2. **Restore selective files** - View a list of files that have changed and been backed up since the previous backup and selectively restore them.
3. **Warp back your site in time** - You can restore the complete site to a specific point in time using the backups.

== Changelog ==

= 1.0.0beta1(27 Apr 2015) =

* Beta release


= 1.0.0alpha5 =

* Feature: Backup scheduling added

* Improvement: Report sending added

* Improvement: Activity log added

* Improvement: UI improvements

* Fix: Bug fixes



= 1.0.0alpha4 =

* Improvement: Background backup process

* Fix: Bug fixes



= 1.0.0alpha3 =

* Initial release.

