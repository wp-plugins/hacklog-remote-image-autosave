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
require dirname(__FILE__) . '/header.php';
require dirname(__FILE__) . '/util.class.php';
//header('Allow: POST');
?>
<?php
$act = isset($_GET['act']) ? $_GET['act'] : '';
switch( $act )
{
	case 'do_download':
		do_download();
	break;
	case 'get_images':
	default:
		do_get_images();
	break;
}

function do_download()
{
	$post_id = (int) $_POST['post_id'];
	$url =  $_POST['url'];
	if( empty($url) )
	{
		echo hacklog_ria_util::rasie_error('Empty url param!');
	}
	else
	{
	$data = hacklog_ria_util::down_remote_file($post_id,$url);
		/*	$data = array(
			//img 和  a> img 全部被替换为 token 了
				'src'=>'http://the-domain.com/xxx.png',
				'html'=>'<img src="http://the-domain.com/xxx.png" />',
			);
		*/	
	if( $data )		
	{
		$data['status'] = 'ok';
		echo hacklog_ria_util::response($data);
	}
	}

}

function do_get_images()
{
	//var_dump($_POST['content']);
	$content = hacklog_ria_util::get_images( stripslashes($_POST['content']) );
	$images = array();
	$blocks = hacklog_ria_util::get_img_block();
	$cnt = count($blocks);
	if( $cnt > 0)
	{
	foreach( $blocks as $k => $item)
	{
		$images[] = array(
			'id'=> $item['id'],
			'token' => $k ,
			'url' => $item['url'],
			);
	}
		$data = array(
			//img 和  a> img 全部被替换为 token 了
				'content'=> $content ,
				'images'=> $images,
			);
		echo hacklog_ria_util::response($data);
	}
	else
	{
		echo json_encode(array('status'=>'no_img'));
	}
}
?>
<?php
//NO need footer.
?>