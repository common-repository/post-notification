<?php

#------------------------------------------------------
# INFO
#------------------------------------------------------
# This is part of the Post Notification Plugin for 
# Wordpress. Please see the Readme2.txt for details.
#------------------------------------------------------


function post_notification_fe_make_link($param){
	global $post_notification_addr, $post_notification_code;
	$addr = &$post_notification_addr;
	$code = &$post_notification_code;
	$url = post_notification_get_mailurl($addr, $code);
	$url.= 'param=' .htmlentities(urlencode($param));
	return $url;
}


function post_notification_fe_manage(){
	global $wpdb, $post_notification_addr, $post_notification_code, $post_notification_action, $post_notification_strings;
	$t_subs = $wpdb->prefix . 'post_notification_subs';
	$t_emails = $wpdb->prefix . 'post_notification_emails';
	
	$addr = &$post_notification_addr;
	$code = &$post_notification_code;
	$action = &$post_notification_action;
	
	require_once(post_notification_get_profile_dir() . '/strings.php');
	// ******************************************************** //
	//                    Manage passed parameters
	// ******************************************************** //
	
	
	if ($action == "unsubscribe") {

		$mid = $wpdb->get_var("SELECT id FROM $t_emails WHERE email_addr = '$addr'"); 
		if($mid != ''){
			post_notification_remove_email($addr);
		}
		
		$content = post_notification_ldfile('unsubscribed.tmpl');
		$body = &$content['body'];
		$body = str_replace('@@addr', $addr, $body);		
		return $content; 
	}

	$datasaved = false;
	
	
	
	
	
	//Get the mail-id
	$mid = post_notification_get_mid($addr); 

	// ----------------------------------------  Update Settings
	//Make sure that the Email is subscirbed
	$query =  "UPDATE $t_emails SET gets_mail = 1 " ;
	
	if($_POST['format'] == 'format_html'){
		$query .= ', format = 1';
	} else if($_POST['format'] == 'format_text') {
		$query .= ', format = 0';
	}
	if($_POST['interval'] == 'interval_istantly'){
		$query .= ', send_type = 0';
	} else if($_POST['interval'] == 'interval_dayly') {
		$query .= ', send_type = 1';
		list($hour, $minute) = explode(':', $_POST['daylytime']);
		$minute += $hour * 60;
		$query .= ", send_datum = $minute";
		
	} else if($_POST['interval'] == 'interval_weekly') {
		$query .= ', send_type = 2';
		$update_date = 1;
		list($hour, $minute) = explode( ':' , $_POST['weeklytime']);
		$minute += $hour * 60;
		list(,$day) = explode( '_' , $_POST['day']);
		$minute += $day * 60 * 24;		
		$query .= ", send_datum = $minute";
	}
	

	$query .= " WHERE id = '$mid'";
	$wpdb->query($query); 
	// ----------------------------------------  Update Subscirbe Categories
	
	if($_POST['pn_set_post_cat']){		
		post_notification_fe_update_subscriptions($mid,$_POST['pn_post_cat'] ,0);
		$datasaved = true;
	}

	
	if($_POST['pn_set_com_cat']){		
		post_notification_fe_update_subscriptions($mid,$_POST['pn_com_cat'] ,1);
		$datasaved = true;
	}
	
	
	if($_POST['pn_set_com_post']){
		$arr = (is_array($_POST['pn_com_posts']))?$_POST['pn_com_post']:array();
		$arr = array_merge($arr, explode(',', $_POST['pn_com_post']));
		post_notification_fe_update_subscriptions($mid, $arr ,2);
		$datasaved = true;
	}
	

		
	// ********************************************************//
	//                     Create content
	// ********************************************************//
	
	
	require_once(post_notification_get_profile_dir() . '/fe_manage.php'); 
	
	//Get the content
	if(!isset($param)){
		if(isset($_GET['param'])){ //Override with GET
			$param = $_GET['param'];
		} else if(isset($_POST['param'])){ //If no get use hidden post
			$param = $_POST['param'];
		} else {
			$param = '';	//Use default
		}
	}
	$content = post_notification_profile_manage($param);

	//Collect data from DB
	/// \todo: Only create strings if needed.
	
	 list($edata) = $wpdb->get_results("SELECT id, format, send_type, send_datum FROM $t_emails  WHERE email_addr = '$addr'");
	 
	 $mid = $edata->id;
	
	//-------------------------  Settings
	
	// 0 = Text; 1 = HTML
	if($edata->format == 1){
		$rep['@@format_html_checked'] = '"format_html" checked="checked"';
		$rep['@@format_text_checked'] = '';
		$rep['@@format_type'] = 'HTML'; 
	} else {
		$rep['@@format_html_checked'] = '';
		$rep['@@format_text_checked'] = '"format_text" checked="checked"';
		$rep['@@format_type'] = $post_notification_strings['format_text'];
	}
	
	if($edata->send_type == 0){
		$rep['"interval_istantly"'] = '"interval_istantly" checked="checked"';
		$rep['@@interval'] =  $post_notification_strings['instantly'];
	} else if($edata->send_type == 1){
		$rep['"interval_dayly"'] = '"interval_dayly" checked="checked"';
		$rep['@@interval'] = $post_notification_strings['interval_dayly'];
	} else if($edata->send_type == 2){
		$rep['"interval_weekly"'] = '"interval_weekly" checked="checked"';
		$rep['@@interval'] = $post_notification_strings['interval_weekly'];
	}
	
	$minute = $edata->send_datum;
	$day = floor($minute / (60* 24));
	$minute %= 60 * 24;
	$hour = floor($minute / (60));
	$minute %= 60;
	$rep['"day_' . $day . '"'] = '"day_' . $day . '" checked="checked" selected="selected"';
	$rep['@@day'] = $post_notification_strings['day'][$day];
	$rep['@@time'] = $hour . ':' . sprintf('%02d',$minute);  
	
	
	
	//-------------------------    Get subscribe post-cats	
	//echo "SELECT obj_id FROM $t_subs  WHERE email_id = $mid AND obj_type = 0";
	$objs = $wpdb->get_results("SELECT obj_id FROM $t_subs  WHERE email_id = $mid AND obj_type = 0");
	$post_cats = array();
	if(isset($objs)){
		foreach($objs as $obj){
			$post_cats[] =  $obj->obj_id;
		}
	}
	$rep['@@post_cat_list'] = ''; //Create first
	$rep['@@post_cat'] = '<input type="hidden" name="pn_set_post_cat" value="1" />';
	$rep['@@post_cat'] .= post_notification_get_catselect('post_cat', $post_notification_strings['all'], $post_cats);
	if(count($post_cats) > 0){
		$rep['@@post_cat_list'] .= post_notification_cats_to_str($post_cats, 1);
	} else {
		$rep['@@post_cat_list'] .= $post_notification_strings['none'];
	}
	
	
	//-------------------------  Get subsribed comment cats
	$objs = $wpdb->get_results("SELECT obj_id FROM $t_subs  WHERE email_id = $mid AND obj_type = 1");
	$rep['@@com_cat_list'] = ''; //Create first
	$com_cats = array();
	if(isset($objs)){
		foreach($objs as $obj){
			$com_cats[] =  $obj->obj_id;
		}
	}
	$rep['@@com_cat'] =  '<input type="hidden" name="pn_set_com_cat" value="1" />';
	$rep['@@com_cat'] .= post_notification_get_catselect('com_cat', $post_notification_strings['all'], $com_cats);
	if(count($com_cats) > 0){
		$rep['@@com_cat_list'] .= post_notification_cats_to_str($com_cats, 1);
	} else {
		$rep['@@com_catist'] .= $post_notification_strings['none'];
	}

	//--------------------------  Get subsribed comment posts
	$post_titles = array();
	$objs = $wpdb->get_results("SELECT obj_id FROM $t_subs  WHERE email_id = $mid AND obj_type = 2");
	$rep['@@com_post_list'] = ''; //Create first!
	
	$rep['@@com_post'] = '<input type="hidden" name="pn_set_com_post" value="1" /><ul class="children">';
	if(isset($objs)){
		foreach($objs as $obj){
			$post = get_post($obj->obj_id);
			$rep['@@com_post'] .= '<li><input type="checkbox" name="pn_com_post[]" value="' .$obj->obj_id . 
				' checked="checked"><a href="'. get_permalink($obj->obj_id) .'">' . $post->post_title . 
				'</a></li>';
			 $post_titles[] = $post->post_title;
		}
	}
	$rep['@@com_post'] .= '</ul>';
	if(count($post_titles) > 0){
		$rep['@@com_post_list'] .= implode(', ', $post_titles);
	} else {
		$rep['@@com_post_list'] .= $post_notification_strings['none'];
	}
	
	
		
	// Get cats listing
	
		
	$rep['@@vars'] = '<input type="hidden" name="code" value="' . $code . '" />' .
				'<input type="hidden" name="addr" value="' . $addr . '" />' .
				'<input type="hidden" name="param" value="' . $param . '" />';
		
		
	
	$body = &$content['body'];
	$rep['@@action'] = post_notification_get_link();
	$rep['@@addr'] = $addr;
	
	$body = post_notification_arrayreplace($body,$rep);
	
	
	return $content;
		
}
?>