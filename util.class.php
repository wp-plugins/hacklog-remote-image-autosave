<?php
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
	class hacklog_ria_util
	{

	private static $mime_to_ext =  array(
			'image/jpeg' => 'jpg',
			'image/png'  => 'png',
			'image/gif'  => 'gif',
			'image/bmp'  => 'bmp',
			'image/tiff' => 'tif',
	);

	private static $code_block = array();
	private static $code_block_num = 0;
	private static $code_block_index = '::__IHACKLOG_REMOTE_IMAGE_AUTODOWN_BLOCK__::%d';

	/**
	 * must call after get_images() !
	 */ 
	public static function get_img_block()
	{
		return self::$code_block;
	}

	static function is_remote_file($url)
	{
		$home_url = home_url('/');
		$is_remote_file = FALSE;
					$my_remote_baseurl = '';
			if(class_exists('hacklogra') )
			{
				$hacklogra_opt = get_option(hacklogra::opt_primary);
				$my_remote_baseurl = $hacklogra_opt['remote_baseurl'];
			}
			//var_dump( ( 0 !== stripos($url,$home_url) ) );
				if( 0 !== stripos($url,$home_url) )
				{
					$is_remote_file = TRUE;
				}
				elseif( !empty($my_remote_baseurl) && (stripos($url,$my_remote_baseurl) === FALSE))
				{
					$is_remote_file = TRUE;
				}
			return $is_remote_file;	
	}

	public static function img_tag_callback($matches)
	{

		//var_dump($matches);
		$index = sprintf(self::$code_block_index, self::$code_block_num);
		$replaced_content = $index;
		$img_src = $matches[2];
		//var_dump(self::is_remote_file($img_src));
		if( self::is_remote_file($img_src))
		{
		self::$code_block[$index] = array( 
			'id' => self::$code_block_num ,
			'url' => $img_src,
			);
		
		self::$code_block_num++;
		return $replaced_content;
		}
		else
		{
			return $matches[0];
		}
	}

	/**
	 * @TODO: add extra to check wether a link resource is a picture
	 */ 
	public static function link_img_tag_callback($matches)
	{
		//var_dump($matches);
		$index = sprintf(self::$code_block_index, self::$code_block_num);
		$replaced_content = $index;
		$src = $matches[5];
		//if the link is not a picture
		$href = strpos(basename($matches[2]),'.') !== FALSE ? $matches[2] : $src;
		if( self::is_remote_file($href))
		{
		self::$code_block[$index] = array( 
			'id' => self::$code_block_num ,
			'url' => $href,
			);
		
		self::$code_block_num++;
		return $replaced_content;
		}
		else
		{
			return $matches[0];
		}
	}
	
	static function get_link_images($content)
	{
		/*
		$content = 	preg_replace_callback("/(\s*)<a[^>]*?href=('|\"|)?([^'\"]*)(\\1)[^>]*?>\s*<img[^>]*?src=('|\"|)?([^'\"]*?)(\\4)[^>]*?>\s*<\/a>(\s*)/is", 'hacklog_ria_util::link_img_tag_callback', $content);
		*/
		$content = preg_replace_callback("/<a[^>]*?href=('|\"|)?([^'\"]+)(\\1)[^>]*?>\s*<img[^>]*?src=('|\"|)?([^'\"]+)(\\4)[^>]*?>\s*<\/a>/is", 'hacklog_ria_util::link_img_tag_callback', $content);
		return $content;
	}
	
	static function get_images($content)
	{
		$content = self::get_link_images($content);
		/*
		$content = 	preg_replace_callback("/(\s*)<img[^>]*?src=('|\"|)?([^'\"]*?)(\\1)[^>]*?>(\s*)/is", 'hacklog_ria_util::img_tag_callback', $content);
		*/
		$content = preg_replace_callback("/<img[^>]*?src=('|\"|)?([^'\"]+)(\\1)[^>]*?>/is", 'hacklog_ria_util::img_tag_callback', $content);
		return $content;
	}

		static function response($data)
		{
			header('Content-type:text/json;charset=UTF-8');
			return json_encode($data);
		}

		static function raise_error($msg='')
		{
			header('Content-type:text/json;charset=UTF-8');
			//header('Allow: POST');
			header('HTTP/1.1 405 Method Not Allowed');
			return self::response( array('status'=>'error','error_msg'=> $msg)) ;
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
	 * php > 5.4 , getimagesizefromstring
	 * @TODO add min_height support 
	 */ 
	static function check_image_size($img_data)
	{
		$min_width = hacklog_remote_image_autosave::get_conf('min_width');
		if( $min_width > 0 )
		{

		$img_res = imagecreatefromstring($img_data);
		$width = imagesx($img_res);
		
			if( $width <= $min_width)
			{
				return FALSE;
			}
		}
		return TRUE;
	}

		public static function down_remote_file($post_id,$url)
		{
			//begin to save pic;	
			$home_url = home_url('/');
			//Compatible with Hacklog Remote Attachment plugin
			$my_remote_baseurl = '';
			if(class_exists('hacklogra') )
			{
				$hacklogra_opt = get_option(hacklogra::opt_primary);
				$my_remote_baseurl = $hacklogra_opt['remote_baseurl'];
			}
				$is_remote_file = FALSE;
				if( strpos($url,$home_url) !== 0 )
				{
					$is_remote_file = TRUE;
				}
				
				if( !empty($my_remote_baseurl) && (strpos($url,$my_remote_baseurl) === FALSE))
				{
					$is_remote_file = TRUE;
				}

				set_time_limit ( 60 );
				//if is remote image
// 				var_dump($is_remote_file);exit;
				if ( $is_remote_file ) 
				{
					$remote_image_url = $url ;
					$headers = wp_remote_head( $remote_image_url );
					//var_dump($headers);
					$response_code = wp_remote_retrieve_response_code($headers);
// 					var_dump($response_code);exit;
					//302 防盗链的，不下载
					if( 200 != $response_code )
					{
						echo self::raise_error('fetch error!') ;
						return FALSE;
					}
					$mime = $headers['headers']['content-type' ];
					$file_ext = self::mime_to_ext($mime);
					$allowed_filetype = array ('jpg', 'gif', 'png', 'bmp' );
// 					var_dump($file_ext);exit;
					if (in_array ( $file_ext, $allowed_filetype )) 
					{
						$http = wp_remote_get($remote_image_url);
						//ignore  WP_Error
						if(is_wp_error($http))
						{
							echo self::raise_error('fetch error!') ;
							return FALSE;
						}
												
						if ( 200 == $http['response']['code']) 
						{
							$file_content = $http['body'];
						} 
						else 
						{
							//time out or 302 redirect (remote site anti-leech)
							echo self::raise_error('Can not fetch remote image file!') ;
							return FALSE;
						}
						if( !self::check_image_size($file_content) )
						{
							return array('src'=> $remote_image_url,
								'html' => '<img src="' . $remote_image_url . '" alt=""/>',
								) ;
						}
						$filename = sanitize_file_name(basename($remote_image_url) );
						$type = $mime;
						//download remote file and save it into database;
						$result = self::handle_upload($filename, $file_content,$type, $post_id);
// 						var_dump($result);exit;
						if ( !is_wp_error($result['id']) ) 
						{
							//wp_get_attachment_image($attachment_id, $size = 'thumbnail', $icon = false, $attr = '')
							//array('thumbnail', 'medium', 'large'); // Standard sizes
							$size = hacklog_remote_image_autosave::get_conf('thumbnail_size', 'medium');
							$img = wp_get_attachment_image($result['id'], $size, $icon = false, $attr = '');
							$full_image=wp_get_attachment_image_src($result['id'], 'full');
							$html = '<a href="'. $full_image[0] .'">'. $img . '</a>';
							return array('src'=>$result['url'],
								'html' => $html,
								) ;
						}
					}
				}
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
}//end class
