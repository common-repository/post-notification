<?php

/**
 * Adds a subsciber to the database, and takes care of a mail being scheduled.
 *
 * @return array(header, body)
 */

function post_notification_fe_subscribe(){
	global $wpdb, $post_notification_addr;
	
	$addr = &$post_notification_addr;
	
	$t_emails = $wpdb->prefix . 'post_notification_emails';
	$t_queue = $wpdb->prefix . 'post_notification_queue';
	$t_subs = $wpdb->prefix . 'post_notification_subs';

	if(isset($_GET['post_id'])){
		$post_id=$_GET['post_id'];
	} else if(isset($_POST['post_id'])) {
		$post_id=$_POST['post_id'];
	} else {
		$post_id = '';
	}
	
	
	require_once(post_notification_get_profile_dir() . '/strings.php');
	

	$code = '';
	if(is_email($addr) && post_notification_check_captcha()){
		// ******************************************************** //
		//                      SUBSCRIBE
		// ******************************************************** //
		if ($action == "subscribe" || $action == '') {				
			$mid = post_notification_get_mid($addr);
			if(!$mid){
				post_notification_add_email($addr); //Ab bit of an overhead, but for historical reasons.
				$mid = post_notification_get_mid($addr);
			}
			
			//Add request to send mail to queue.
			$wpdb->query("INSERT INTO $t_queue (email_id, obj_id, state, type, date) 
							VALUES($mid, 0, 0, 2, '" . post_notification_date2mysql() . "')");
			
			if(is_numeric($post_id)){
				post_notification_fe_update_subscriptions($mid, array($post_id) ,2, false);
			}
			
			//Output Page
			$content['header'] = $post_notification_strings['registration_successful'];
			$content = post_notification_ldfile('reg_success.tmpl');
			return $content; //here it ends - We don't want to show the selection screen.

		}
	}
					
		

		
	//Try to get the email addr
	if($addr == ''){
		$addr = post_notification_get_addr();
	} 
	
	

	if(!is_numeric($post_id)){
		$post_id = '';
	} else {
		$vars = '<input type="hidden" name="post_id" value="' . $post_id . '"/>'; 
	}
	

	$content = post_notification_ldfile('subscribe.tmpl'); //Load template
	$msg = &$content['body'];
	if($addr != ''){
		if(!is_email($addr))
			$pn_error .= '<p class="error">' . $post_notification_strings['check_email'] . '</p>';
		else if(!post_notification_check_captcha() && action != '')
			$pn_error .= '<p class="error">' . $post_notification_strings['wrong_captcha'] . '</p>';
	}
	
	$msg = str_replace('@@error',$pn_error,$msg);
	$msg = str_replace('@@action',post_notification_get_link($addr),$msg);
	$msg = str_replace('@@addr',$addr,$msg);
	$msg = str_replace('@@vars',$vars,$msg);
	
	//Do Captcha-Stuff
	if(get_option('post_notification_captcha') == 0){ 
		$msg = preg_replace('/<!--capt-->(.*?)<!--cha-->/is', '', $msg); //remove captcha
	} else {
		require_once( POST_NOTIFICATION_PATH . 'class.captcha.php' );
		$captcha_code = md5(round(rand(0,40000))); 
		$my_captcha = new captcha($captcha_code, POST_NOTIFICATION_PATH . '_temp');
		$captchaimg = POST_NOTIFICATION_PATH_URL . '_temp/cap_' . $my_captcha->get_pic(get_option('post_notification_captcha')) . '.jpg';
		$msg = str_replace('@@captchaimg',$captchaimg,$msg);
		$msg = str_replace('@@captchacode',$captcha_code,$msg);
		
	}
	return $content;
}

?>