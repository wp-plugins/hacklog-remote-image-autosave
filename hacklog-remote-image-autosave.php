<?php
/* 
Plugin Name: Hacklog Remote Image Autosave
Version: 2.0.0
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
	const textdomain = 'hacklog_remote_image_autosave';
	const opt = 'hacklog_ria_auto_down';
	private static $plugin_name = 'Hacklog Remote Image Autosave';
	private static $src_size =  array('thumbnail', 'medium', 'large','full');
	private static $opts = array(	'thumbnail_size'=>'medium',		'min_width'=>'100',	);

	/**
	 * do the stuff
	 */
	public static function init() 
	{
		self::$opts = get_option(self::opt, self::$opts);
		add_action( 'admin_menu', array (__CLASS__, 'add_setting_menu' ) );
		//add_filter( 'content_save_pre', array (__CLASS__, 'save_post' ) );
		//add_action( 'admin_footer', array (__CLASS__, 'footer_js' ) );
		// add editor button
		add_action('media_buttons', array(__CLASS__, 'add_media_button'), 20);
		register_activation_hook(__FILE__, array(__CLASS__, 'my_activation'));
		register_deactivation_hook(__FILE__, array(__CLASS__, 'my_deactivation'));
	}

	/**
	 * do the stuff once the plugin is installed
	 * @static
	 * @return void
	 */
	public static function my_activation()
	{
		add_option(self::opt, self::$opts);
	}
	
	/**
	 * do cleaning stuff when the plugin is deactivated.
	 * @static
	 * @return void
	 */
	public static function my_deactivation()
	{
		delete_option(self::opt);
	}

	public static function get_conf($key,$default='')
	{
		return isset(self::$opts[$key]) ? self::$opts[$key] : $default;
	}

	public static function set_conf($key,$value='')
	{
		if( in_array( $key, array('thumbnail_size','min_width') ))
			{
				self::$opts[$key] = $value;
			}
	}

	public static function update_config()
	{
		update_option(self::opt, self::$opts);
	}
	public static function add_media_button($editor_id = 'content')
	{
		global $post_ID;
		$url = WP_PLUGIN_URL . "/hacklog-remote-image-autosave/handle.php?post_id={$post_ID}&tab=download&TB_iframe=true&width=740&height=500";
		$admin_icon = WP_PLUGIN_URL . '/hacklog-remote-image-autosave/images/admin_icon.png';
		if (is_ssl())
		{
			$url = str_replace('http://', 'https://', $url);
		}
		$alt = __('Download remote images to local server', self::textdomain);
		$img = '<img src="' . esc_url($admin_icon) . '" width="15" height="15" alt="' . esc_attr($alt) . '" />';

		echo '<a href="' . esc_url($url) . '" class="thickbox hacklog-ria-button" id="' . esc_attr($editor_id) . '-hacklog_ria" title="' . esc_attr__('Hacklog Remote Image Autosave', self::textdomain) . '" onclick="return false;">' . $img . '</a>';
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
			$min_width = (int) trim($_POST['min_width']);
			self::set_conf('min_width',$min_width);
			$thumbnail_size = $_POST['thumbnail_size'];
			self::set_conf('thumbnail_size',$thumbnail_size);
			
			self::update_config();

		}
		?>
	<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php _e(self::$plugin_name) ?> Options</h2>
	<form method="post">
	<table width="100%" cellpadding="5" class="form-table">
	<tr valign="top">
		<th scope="row">	
	thumbnail size：
		</th>
		<td>
			<select name="thumbnail_size" style="width:120px;">
				<?php $selected = self::get_conf('thumbnail_size');?>
				<?php foreach(self::$src_size as $size):?>
				<option value="<?php echo $size;?>" <?php selected( $selected, $size, true );?>> <?php echo $size;?> </option>
			<?php endforeach;?>
			</select>
		</td>
	</tr>
	<tr valign="top">
		<th scope="row">	
	min width image to download：
		</th>
		<td>
			<input type="text" name="min_width" value="<?php echo self::get_conf('min_width');?>"/>
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