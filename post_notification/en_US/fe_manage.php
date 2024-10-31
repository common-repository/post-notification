<?php
/**
 * post_notification_profile_manage() will be called when a user is loged in.
 * $param is whatever link is passed to post_notification_fe_make_link($param).
 * p_n_p_manage() returns an array. It contains two elements: 'body' and 'header'
 * All Variables are replaced passing the Variables back to the super function.
 * The usage can be siplified by using post_notification_ldfile which loads the
 * first line of a file into 'header' and the rest into 'body'
 */


function post_notification_profile_manage($param){
	
	if($param == 'settings'){
		$content = post_notification_ldfile("man_settings.tmpl");
	} else if($param == 'post_cat'){
		$content = post_notification_ldfile("man_post_cat.tmpl");
	} else if($param == 'com_cat'){
		$content = post_notification_ldfile("man_com_cat.tmpl");
	} else if($param == 'com_post'){
		$content = post_notification_ldfile("man_com_post.tmpl");
	} else {
		$content = post_notification_ldfile("man_info.tmpl");
	}
	
	$body = &$content['body'];

	$navbar = '<a href="'. post_notification_fe_make_link('info') . '">Info</a> &middot; ' .
		'<a href="'. post_notification_fe_make_link('settings') . '">Settings</a> &middot; '.
		'<a href="'. post_notification_fe_make_link('post_cat') . '">Subscribed categories</a> &middot; ' .
		'<a href="'. post_notification_fe_make_link('com_cat') . '">Subscribed comments (by cat)</a> &middot; ' .
		'<a href="'. post_notification_fe_make_link('com_post') . '">Subscribed comments (by post)</a>';
		
	$body = str_replace('@@navbar',$navbar,$body);
	return $content;
	
	
	
}
?>