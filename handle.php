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

//enqueue the needed media stylesheet
//wp_enqueue_style('media');

@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));

$GLOBALS['body_id'] = 'media-upload';
iframe_header( __('Hacklog Remote Images Autodown',hacklog_remote_image_autosave::textdomain), false );
?>
<style type="text/css" media="screen">
	.hack { background-image:url(<?php echo WP_PLUGIN_URL . '/hacklog-remote-image-autosave/images/ok_24.png';?>);}
</style>
<script type="text/javascript">
	var check_down_interval = 1000;
	var img_arr = [];
	var mce = typeof(parent.tinyMCE) != 'undefined' ? parent.tinyMCE.activeEditor : false;
	var check_down = function()
				{
					var button_obj = document.getElementById('replace-token');
					var len = jQuery('#img-cnt').val();
					if( img_arr.length == len )
					{
						button_obj.click();
						button_obj.style.display= 'none';
					}
					else
					{
						setTimeout("check_down();",check_down_interval);
					}
				};
// @see http://www.tinymce.com/wiki.php/API3:class.tinymce.dom.Selection
// @see http://www.tinymce.com/wiki.php/API3:method.tinymce.dom.Selection.getContent
jQuery(function($){

	$.ajaxSetup({
  			url:'<?php echo $url;?>',
			type:'post',
			dataType: 'json',
			async: false,
			cache: false,
			complete: function(jqXHR, textStatus){
				if( textStatus == 'success')
				{

				}
			},			
			error: function (jqXHR, textStatus, errorThrown)
			{

			},
			statusCode: 
			{
    			404: function() {
      				alert('404,page not found');
    			},
    			405: function() {
      				alert('fetch error!');
    			}
  			}
	});

var getContent = function(){
	return mce.getContent({format : 'text'});
};

var setContent = function(new_content )
{
	return mce.setContent(new_content,{format : 'text'});
};

var set_status_downloading = function(id)
{
		wp_url = ajaxurl.substr(0, ajaxurl.indexOf('wp-admin')),
		pic_spin = wp_url + 'wp-admin/images/wpspin_dark.gif', // 提交 icon
	$('#img-'+ id ).parent().append('<span id="img-status-' + id + '"><img src="' + pic_spin + '" alt="downloading">下载中...</span>');
};

var set_status_done = function(id)
{
	$('#img-status-'+ id ).html('<img src="<?php echo WP_PLUGIN_URL . '/hacklog-remote-image-autosave/images/ok_24.png';?>" alt="done">');
};

var set_status_failed = function(id,msg)
{
	//$('#img-status-'+ id + ' img').hide();
	$('#img-status-'+ id ).html('Error: ' + msg);
};


var replace_token =  function()
{
	var content = getContent();
	var len = img_arr.length;
	for(var i=0; i< len; ++i)
	{
				var token = $('#img-' + img_arr[i].id ).attr('rel');
				var img_html =  img_arr[i].html;
				console.log('token: '+ token);
				content = content.replace( token, img_html );
				console.log('set new content:'+ content);

	}
	setContent( content );
};

	$('#replace-token').click(
	function(e)
	{
		replace_token();
		return false;
	});
	
	var down_single_img = function(id,post_id,url)
	{
		console.log(url);
		set_status_downloading(id);

		$.ajax(
		{
			url: '<?php echo $url;?>?act=do_download',
			data: {'url': url, 'post_id': post_id},
			async: true,
			success: function(data,textStatus){
				if( 'ok' == data.status )
				{
				$('#img-'+ id ).val(data.src);
				//$('#img-'+ id ).parent().append('<input id="img-' + id + '-html" type="hidden" name="img-hidden[]" value="' + data.html + '">');
				//id ,token, data
				var token = $('#img-'+ id ).attr('rel');
				console.log(token);
				img_arr.push({'id':id,'token': token,'html':data.html});
				set_status_done(id);
				}
				else
				{
					set_status_failed(id,data.error_msg);
				}
			}

		}
			);

	};


	var get_images = function()
	{

		//@see http://api.jquery.com/jQuery.ajax/
		var content = getContent();
		$.ajax({
			url:'<?php echo $url;?>?act=get_images',
			type:'post',
			data: {'content':  content},
			dataType: 'json',
			success: function(data,textStatus){
				//alert( data.content );
				if( data && data.status != 'no_img' )
				{
				//设置把图片置空后的内容
				setContent(data.content);
				console.log('replaced content:  ' + data.content);
				//帖出图片信息
				var html = $('<ol>');
				for(var i=0;i<data.images.length;++i)
				{
					html.append('<li><input size="85" type="text" name="img[]" id="img-' + data.images[i].id + '" rel="' + data.images[i].token + '"  value="' + data.images[i].url + '" /></li>');
					//alert( data.images[i].url);
				}
				$('#image-list').html(html);
				$('#img-cnt').val( data.images.length );
				for(var i=0;i<data.images.length;++i)
				{
					var id =  data.images[i].id;
					var post_id = $('#post_id').val();
					down_single_img(id,post_id, data.images[i].url);

				}	
				setTimeout("check_down();",check_down_interval);
			}
			else
			{
				$('#image-list').html('<p style="font-size:24px;">No remote images to download!</p>');
				$('#img-cnt').val( 0 );
				$('#replace-token').attr('disabled',true);
				$('#replace-token').css('display','none');
			}

			}
		}

			);
	};

	if( !mce )
	{
		alert('This can only be run under tinyMCE editor!');
	}
	else
	{
		//alert(mce.getContent({format : 'text'}));
		//mce.setContent(mce.getContent({format : 'text'})+'<a href="#aaa">aaa</a>',{format : 'text'});
		get_images();
	}
});
//alert(parent.location.href);
//parent.document.getElementById('content').value = parent.document.getElementById('content').value + '<a href="#test">test</a>';
//alert( parent.document.getElementById('content').value );
</script>
<form action="" method="post" accept-charset="utf-8" style="margin:8px auto;padding:10px;">
<input type="hidden" id="post_id" name="post_id" value="<?php echo $post_id;?>">
<input type="hidden" id="img-cnt" name="img_cnt" value="0">
<div id="image-list">
	
</div>
<input type="button" class="button-primary" style="position:absolute;right:60px;" id="replace-token" name="update" value="OK">
</form>
<?php
require dirname(__FILE__) . '/footer.php';
?>