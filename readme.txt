=== Hacklog Remote Image Autosave ===
Contributors: ihacklog
Donate link: http://ihacklog.com/donate
Tags: images, auto,autosave,remote
Requires at least: 3.2.1
Tested up to: 3.5
Stable tag: 2.0.8

save remote images in the posts to local server and add it as an attachment to the post.


== Description ==
升级注意：2.0.8 版是对WP 3.5的更新，如果你使用的WP版本低于 3.5，请不要更新。

This plugin can save remote images in the posts to local server automatically and 
add it as an attachment to the post.

* capabile with Hacklog Remote Attachment plugin and Watermark Reloaded plugin
* admin_icon.png was modified from runescapeautotyper.com 's donwload icon 
 
此插件的作用是自动保存日志中的远程图片到本地服务器并将保存后的文件作为日志的附件。

* 与Hacklog Remote Attachment 插件兼容性良好　
* 与Watermark Reloaded 插件兼容性良好　

* 2.0.0 版完全重写。相比于原来1.0.2版的插件，有非常大的改进。
* 原来的插件是在文章更新时自动下载远程图片，如果图片非常多的话，这样容易导致执行超时或只有部分图片被下载了。
* 这次的新版采用的是ajax异步请求的方式让多个文件同时下载。效率和易用性都得到很大改善。
 
== Installation ==

1. Upload the whole `hacklog-remote-image-autosave` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to `Settings`==>`Hacklog RIA` to setup the options.

== Frequently Asked Questions ==

== Screenshots ==
1. screenshot-1.png
2. screenshot-2.png
3. screenshot-3.png
4. screenshot-4.png


== Changelog ==

= 2.0.8 =
* fixed: added support for WP 3.5 for the new TinyMCE.

= 2.0.7 =
* modified: changed image preload from css to js.
* removed some un-needed comments.

= 2.0.6 =
* improved: DO NOT load plugin in front end.
* fixed: changed to use the WP 3.0 version Roles and Capabilities permission value

= 2.0.5 =
* improved: the method to check whether a link resource is a picture
* fixed: the compatibility with Hacklog Remote Attachment plugin (resolved the dumplicated filename bug)

= 2.0.4 =
* fixed: corrected the logic to check if a url is remote or not.

= 2.0.3 =
* improved: get PHP execution timeout errors being caught.
* improved: get HTTP server 500 Internal Server Error being caught.
* improved: update the downloading status image.
* improved: added notice message after all images has been downloaded.
* added: thumbnails creating function. 

= 2.0.2 =
* improved: added https support(the ssl verify has been set to FALSE due to some reasons).
* improved: added "Retry" button if the first time the downloading was failed.

= 2.0.1 =
* fixed: libcurl "Operation timed out after 5008 milliseconds with 122371 out of 315645 bytes received" Error.
* modified: shortened the time interval to auto click the "OK" button.
* fixed: bug when POST data via jQuery with query string style data param the post content will be cutted strangely.

= 2.0.0 =
* rewrtie the plugin at all.Now ,many bugs has been fixed.the plugin now works well.

= 1.0.2 =
* fixed the bug when the hacklog remote attachment does not exists,the plugin will not save remote images.

= 1.0.1 =
* improved the capability with Hacklog Remote Attachment plugin and Watermark Reloaded plugin.

= 1.0.0 =
* released the first version.

== Arbitrary section ==

	if remote server is　 unreachable OR remote server Set against hotlinking，then the image url will remain as what it is in the post.
 	also ,this plugin will not handel with the situation when the remote server returns 302 http status.
 	the ssl verify has been set to FALSE due to some reasons.

