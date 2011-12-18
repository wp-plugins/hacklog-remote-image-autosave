<?php
/* 
Plugin Name: Hacklog Remote Image Autosave
Version: 1.0.2
Plugin URI: http://ihacklog.com/?p=5087
Description: save remote images in the posts to local server and add it as an attachment to the post.
Author: 荒野无灯
Author URI: http://ihacklog.com
*/

/**
 * $Id$
 * $Revision$
 * $Date$
 * @package Hacklog Remote Image Autosave
 * @encoding UTF-8
 * @author 荒野无灯 <HuangYeWuDeng>
 * @link http://ihacklog.com
 * @copyright Copyright (C) 2011 荒野无灯
 * @license http://www.gnu.org/licenses/
 */


class hacklog_remote_image_autosave 
{
	const opt = 'hacklog_ria_auto_down';
	private static $plugin_name = 'Hacklog Remote Image Autosave';
	private static $mime_to_ext =  array(
			'image/jpeg' => 'jpg',
			'image/png'  => 'png',
			'image/gif'  => 'gif',
			'image/bmp'  => 'bmp',
			'image/tiff' => 'tif',
	);
	
	/**
	 * do the stuff
	 */
	public static function init() 
	{
		add_action( 'admin_menu', array (__CLASS__, 'add_setting_menu' ) );
		add_filter( 'content_save_pre', array (__CLASS__, 'save_post' ) );
		add_action( 'admin_footer', array (__CLASS__, 'footer_js' ) );
	}
	
	/**
	 * just add an select field to title div
	 */
	function footer_js() 
	{
		if (basename ( $_SERVER ['SCRIPT_FILENAME'] ) == 'post.php' || basename ( $_SERVER ['SCRIPT_FILENAME'] ) == 'post-new.php') 
		{
			$select = '';
			if(get_option(self::opt,0) == 1)
			{
				$select = 'selected';
			}
			$html = '<select name="hacklog_ria"><option value="-1">==Remote images==</option><option value="0">DO NOT DOWNLOAD</option><option value="1" '. $select. '>DOWNLOAD</option></select>';
			?>
<script type="text/javascript">
	//append the field
	(function (){
	var $obj = document.getElementById("edit-slug-box");	
	//alert($obj);
	$obj.innerHTML += '<?php echo $html;?>';
	})();
	</script>
<?php
		}
	}
	
	
	/**
	 * mime type to file extension
	 * @param string $mime
	 */
	public static function mime_to_ext($mime)
	{
		$mime = strtolower($mime);
		return self::$mime_to_ext[$mime];
	}
	
	
	/**
	 * find and save remote image file to local server and then 
	 * insert it as an attachment into the database
	 * remember that the $content variable is addslashed.
	 * @param string $content
	 */
	public static function save_post($content) 
	{
		//save remote image file
		if ($_POST ['hacklog_ria'] == 1) 
		{
			$content1 = $content;
			//begin to save pic;
			$img_array = array ();
			$content1 = stripslashes($content1);
// 			var_dump($content1);
			preg_match_all( "/ src=(\"|\'){0,}(http:\/\/(.+?))(\"|\'|\s)/is", $content1, $matches );
// 			var_export($matches);exit;
			if(!isset($matches[2]) || !is_array($matches[2]))
			{
				return $content;
			}
			$img_array = array_unique( $matches[2] );

// 			var_dump($img_array);exit;
			if (isset( $_REQUEST['post_id'] ))
			{
				$post_id = (int) $_REQUEST['post_id'];
			}
			else
			{
				$post_id = $_POST['post_ID'];
			}
			
			$home_url = home_url('/');
			//Compatible with Hacklog Remote Attachment plugin
			$my_remote_baseurl = '';
			if(class_exists('hacklogra') )
			{
				$hacklogra_opt = get_option(hacklogra::opt_primary);
				$my_remote_baseurl = $hacklogra_opt['remote_baseurl'];
			}
			foreach ( $img_array as $key => $url ) 
			{
				$is_remote_file = FALSE;
				if( strpos($url,$home_url) !== 0 )
				{
					$is_remote_file = TRUE;
				}
				
				if( !empty($my_remote_baseurl) && (strpos($url,$my_remote_baseurl) === FALSE))
				{
					$is_remote_file = TRUE;
				}

				//set_time_limit ( 60 );
				//if is remote image
// 				var_dump($is_remote_file);exit;
				if ( $is_remote_file ) 
				{
					$remote_image_url = $url ;
					$headers = wp_remote_head( $remote_image_url );
					$response_code = wp_remote_retrieve_response_code($headers);
// 					var_dump($response_code);exit;
					//302 防盗链的，不下载
					if( 200 != $response_code )
					{
						continue;
					}
					$mime = $headers['headers']['content-type' ];
					$file_ext = self::mime_to_ext($mime);
					$allowed_filetype = array ('jpg', 'gif', 'png', 'bmp' );
// 					var_dump($file_ext);exit;
					if (in_array ( $file_ext, $allowed_filetype )) 
					{
						$http = wp_remote_get($remote_image_url);
						if ( 200 == $http['response']['code']) 
						{
							$file_content = $http['body'];
						} 
						else 
						{
							//time out or 302 redirect (remote site anti-leech)
							continue;
						}
						$filename = sanitize_file_name(basename($remote_image_url) );
						$type = $mime;
						//download remote file and save it into database;
						$result = self::handle_upload($filename, $file_content,$type, $post_id);
// 						var_dump($result);exit;
						if ( !is_wp_error($result['id']) ) 
						{
							$content1 = str_replace ( $remote_image_url,$result['url'], $content1 ); //替换文章里面的图片地址
						}
					}
				}
			}//end foreach
			$content = addslashes($content1);
		}//end if
		return $content;
	}
	
	
	/**
	 * download remote image to local server and save it to database
	 * This will not create thumbs.
	 * @param string $filename The base filename
	 * @param string $data binary data
	 * @param string $type mime type
	 * @param int $post_id
	 */
	public static function handle_upload( $filename,$data,$type, $post_id) 
	{
		$mimes = false;
		$time = FALSE;
		if ($post = get_post($post_id))
		{
			if (substr($post->post_date, 0, 4) > 0)
			{
				$time = $post->post_date;
			}
		}
		// A writable uploads dir will pass this test. Again, there's no point overriding this one.
		$uploads = wp_upload_dir($time);
		
		$unique_filename_callback = null;
		$filename = wp_unique_filename( $uploads['path'], $filename, $unique_filename_callback );
	
		// Move the file to the uploads dir
		$new_file = $uploads['path'] . "/$filename";
// 		var_dump($new_file);exit;
		if ( false === file_put_contents($new_file, $data) )
			return FALSE;
	
		// Set correct file permissions
		$stat = stat( dirname( $new_file ));
		$perms = $stat['mode'] & 0000666;
		@ chmod( $new_file, $perms );
	
		// Compute the URL
		$url = $uploads['url'] . "/$filename";
		
		//Compatible with Hacklog Remote Attachment plugin
		if(class_exists('hacklogra') )
		{
			$url = hacklogra::replace_attachurl($url);
		}
	
		if ( is_multisite() )
			delete_transient( 'dirsize_cache' );
	
		//array( 'file' => $new_file, 'url' => $url, 'type' => $type );
		$name_parts = pathinfo($filename);
		$name = trim( substr( $filename, 0, -(1 + strlen($name_parts['extension'])) ) );
		
		$file = $new_file;
		$title = $name;
		$content = '';
		
		// use image exif/iptc data for title and caption defaults if possible
		if ( $image_meta = @wp_read_image_metadata($file) ) {
			if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) )
				$title = $image_meta['title'];
			if ( trim( $image_meta['caption'] ) )
				$content = $image_meta['caption'];
		}
		
		// Construct the attachment array
		$attachment = array(
				'post_mime_type' => $type,
				'guid' => $url,
				'post_parent' => $post_id,
				'post_title' => $title,
				'post_content' => $content,
		);
		// This should never be set as it would then overwrite an existing attachment.
		if ( isset( $attachment['ID'] ) )
			unset( $attachment['ID'] );
		// Save the data
		//remove_filter('media_send_to_editor', array('hacklogra', 'replace_attachurl'), -999);
		//remove_filter('wp_generate_attachment_metadata', array('hacklogra', 'upload_images'),999);
		$id = wp_insert_attachment($attachment, $file, $post_id);
		
		if ( !is_wp_error($id) ) 
		{
			//Compatible with Watermark Reloaded plugin
			$metadata = self::generate_attachment_metadata( $id, $file );
			//Compatible with Hacklog Remote Attachment plugin
			//if Hacklog Remote Attachment failed to upload file to remote FTP server
			//then,it will return an error.if this was not stopped,the image will be un-viewable. 
			//if failed,delete the attachment we just added from the database.
			if( is_wp_error($metadata) || !isset($metadata['file']))
			{
				wp_delete_attachment( $id,TRUE);
				wp_die(sprintf(__('<h2>Error:</h2><h3 style="color:#f00;">%s</h3>'),$metadata['error']));
			}
			wp_update_attachment_metadata( $id, $metadata );
		}
		
		return array('id'=>$id,'url'=>$url);
	}

	/**
	 * 
	 * @param int $attachment_id
	 * @param string $file absolute file path
	 */
	public static function generate_attachment_metadata($attachment_id,$file)
	{
		$attachment = get_post( $attachment_id );
		$metadata = array();
		if ( preg_match('!^image/!', get_post_mime_type( $attachment )) && file_is_displayable_image($file) )
		{
			$imagesize = getimagesize( $file );
			$metadata['width'] = $imagesize[0];
			$metadata['height'] = $imagesize[1];
			list($uwidth, $uheight) = wp_constrain_dimensions($metadata['width'], $metadata['height'], 128, 96);
			$metadata['hwstring_small'] = "height='$uheight' width='$uwidth'";
		
			// Make the file path relative to the upload dir
			$metadata['file'] = _wp_relative_upload_path($file);
			//work with some watermark plugin
			$metadata = apply_filters( 'wp_generate_attachment_metadata', $metadata, $attachment_id );
		}
		return $metadata;
	}
	
	//add option menu to Settings menu
	function add_setting_menu() 
	{
		add_options_page( self::$plugin_name. ' Options', 'Hacklog RIA', 8, __FILE__, array(__CLASS__,'option_page') );
	}
	
	//option page
	public static function option_page() 
	{
		if(isset($_POST['submit']))
		{
			$value = (int) $_POST['hacklog_ria_auto_download'];
			update_option(self::opt,$value);
		}
		?>
	<div class="wrap">
			<?php screen_icon(); ?>
	<h2><?php _e(self::$plugin_name) ?> Options</h2>
	<form method="post">
	<table width="100%" cellpadding="5" class="form-table">
					<tr valign="top">
						<th scope="row">	
	Save remote images to local server by default：
		</th>
		<td>
	<input type="checkbox" name="hacklog_ria_auto_download" value="1" <?php if(get_option(self::opt,0) == 1) echo 'checked';?>/>
	</td>
	</tr>
	</table>
	<p class="submit">
			<input type="submit" class="button-primary" name="submit" value="<?php _e('Save Options');?>" />
	</p>
	</form>
	</div>
<?php
	}

} //end class

//ok,let's go,have fun-_-
hacklog_remote_image_autosave::init();