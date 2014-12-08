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
class WPTC_UploadTracker
{
    private $processed_files;
	private $processed_restored_files;

    public function __construct()
    {
        $this->processed_files = new WPTC_Processed_Files();
		//$this->processed_restored_files = new WPTC_Processed_Restoredfiles();
    }

    public function track_upload($file, $upload_id, $offset)
    {
        WPTC_Factory::get('config')->die_if_stopped();

        $this_processed_file = $this->processed_files->update_file($file, $upload_id, $offset);

        WPTC_Factory::get('logger')->log(sprintf(
            __("Uploaded %sMB of %sMB", 'wptc'),
            round($offset / 1048576, 1),
            round(filesize($file) / 1048576, 1)
        ));
    }
	
	public function track_download($file, $upload_id = null, $offset = null, $isChunkDownload)
    {
		//file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----calling tracker------- ".var_export($file,true)."\n",FILE_APPEND);
        //WPTC_Factory::get('config')->die_if_stopped();
		
		 //$offset = filesize($file);
		 //file_put_contents(WP_CONTENT_DIR .'/DE_clientPluginSIde.php',"\n -----tracker offset------ ".var_export($offset,true)."\n",FILE_APPEND);
		/* if($offset >= $isChunkDownload['c_limit'] && !empty($offset))
		{
			$chunked = false;							//am setting the offset to zero so that it indicates that upload is over
		} */
		$this->processed_restored_files = new WPTC_Processed_Restoredfiles();
        $this->processed_restored_files->update_file($file, $upload_id, $offset);

		WPTC_Factory::get('logger')->log(sprintf(
            __("Downloaded %sMB ", 'wptc'),
            round($offset / 1048576, 1)
        ));
    }
}
