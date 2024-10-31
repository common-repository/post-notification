<?php
#------------------------------------------------------
# INFO
#------------------------------------------------------
# This is part of the Post Notification Plugin for 
# Wordpress. Please see the Readme2.txt for details.
#------------------------------------------------------

function post_notification_fe_subscribe_comment(){
	global $post_notification_addr, $post_notification_code, $post_notification_action, $wpdb;
	$addr = &$post_notification_addr;
	$code = &$post_notification_code;
	$action = &$post_notification_action;
	$t_emails = $wpdb->prefix . 'post_notification_emails';
		
	if(isset($_POST['PID'])){
		$PID = $_POST['PID'];
	} else {
		$PID = $_GET['PID'];
	}
	
	
	
	if(!is_email($addr)){ //We havent't got an Email - Lets get one
		$ret = post_notification_ldfile('subscribe_comment_email.tmpl');
		$post = get_post($PID);

		$ret['header'] = str_replace('@@title',$post->post_title,$ret['header']);
		$ret['body'] = str_replace('@@title',$post->post_title,$ret['body']);
		$ret['body'] = str_replace('@@addr',$addr,$ret['body']);
		
		$forminput .= '<input type="hidden" name="PID" value="' . $PID . '">';
		$forminput .= '<input type="hidden" name="action" value="subscribe_comment">';
				
		$ret['body'] = str_replace('@@vars',$forminput,$ret['body']);
		$ret['body'] = str_replace('@@action',post_notification_get_link(). 'POST_NOTIFICATION_FE_CHECK=1',$ret['body']);
		//var_dump($ret);
		return $ret;
		
	} else {
		if($addr != post_notification_get_addr()){
			setcookie('comment_author_email_' . COOKIEHASH, $addr, time() + 30000000, COOKIEPATH, COOKIE_DOMAIN);
		}
		$res = $wpdb->get_row("SELECT id, gets_mail FROM $t_emails WHERE email_addr = '$addr'");
		//post_notification_vardump($res);
		if($res === false){
			//There is no entry. Lets add one
			post_notification_add_email($addr);
			$res = $wpdb->get_row("SELECT id, gets_mail FROM $t_emails WHERE email_addr = '$addr'");
			//Now there should be an entry
		}
		if($_GET['unsub'] == 1){ //This si always GET!
			//echo "----------UNSUB";
			post_notification_fe_update_subscriptions($res->id, array($PID),2,2);
		} else{
			post_notification_fe_update_subscriptions($res->id, array($PID),2,0);
		}
		if($res->gets_mail == 1){
			$link = get_permalink($PID) . '#commentform';
		} else {
			$link = post_notification_get_link() . 'addr=' . urlencode($addr) . '&action=subscribe';
		}
		wp_redirect($link);
		echo "Redirecting to <a href=\"$link\">$link</a>";
		exit();
		
	}
	
	
}
?>