<?php
/** Load WordPress Administration Bootstrap */
define( 'IFRAME_REQUEST' , true );
$bootstrap_file = dirname(dirname(dirname(dirname(__FILE__)))). '/wp-admin/admin.php';

if (file_exists( $bootstrap_file ))
{
	require $bootstrap_file;
}
else
{
	echo sprintf('<p>Failed to load bootstrap file: <strong>%s</strong>.</p>',$bootstrap_file);
	exit;
}


/*Check Whether User Can use the plugin*/
/*
Editor - Somebody who can publish and manage posts and pages as well as manage other users' posts, etc. 
@see http://codex.wordpress.org/Roles_and_Capabilities#Editor
*/
if (!current_user_can('edit_posts'))
{
	wp_die(__('You do not have permission to do this.'));
}
?>
<?php
// IDs should be integers
$post_id = isset( $_REQUEST['post_id'] )? intval( $_REQUEST['post_id'] ) : 0;
$url = WP_PLUGIN_URL . '/hacklog-remote-image-autosave/download.php';
?>
