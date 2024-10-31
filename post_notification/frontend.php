<?php

#------------------------------------------------------
# INFO
#------------------------------------------------------
# This is part of the Post Notification Plugin for 
# Wordpress. Please see the Readme2.txt for details.
#------------------------------------------------------

/**
 * There are several functions that help you integrate. You can call them several times on a single page.
 * 
 * function post_notification_feheader()
 * Returns the header as a string
 * Can be used by a template -> That's why it int the post_notification.php
 * 
 * function post_notification_febody()
 * Returns the Body as a string
 * Can be used by a template -> That's why it int the post_notification.php
 * 
 * function post_notification_fe($class = 'entry')
 * Outputs:
 * <h2>Header</h2>
 * <div class = "$class">
 * output
 * </div>
 * 	
 * function post_notification_page_content()
 * Returns a array with 'header' and 'body' entries.
 * 
*/

/**
 * Writes the frontend to stdout
 * <h2>header</h2>
 * <div class=$class>content</div>
 *
 * @param string $class The class which is to be used for the content
 */
function post_notification_fe($class = 'entry'){
	global $wpdb;
	$content = post_notification_page_content();
	
	echo '<h2>' . $content['header']  . '</h2><div class="' . $class . '">' . $content['body']  . '</div>';
}

/**
 * Validates a Captcha
 *
 * @return true / false depending on whether the Captcha is valid
 */
function post_notification_check_captcha(){
	if(get_option('post_notification_captcha') == 0) return true;
	if($_POST['captchacode'] == '') return false;
	if($_POST['captcha'] == '') return false;
	require_once( POST_NOTIFICATION_PATH . 'class.captcha.php' );
	$my_captcha = new captcha($_POST['captchacode'], POST_NOTIFICATION_PATH . '_temp');
	return $my_captcha->verify( $_POST['captcha']);
}

/**
 * Chenge what a user subscribed to
 * 
 * @param $mid The Mail-ID of the user
 * @param $objs The object IDs
 * @param $type Ths object type: 
 *		0: Posts of a category; id: cat
 * 		1: Comments of a category; id: cat
 * 		2: Comments of a post; id:post
 * @param $mode What to do
 * 		0: Add; 
 * 		1: Delete all and add; 
 * 		2: remove the objs; - to be implemented
 * @return void
 * 
 * @todo add missing functions
 */
function post_notification_fe_update_subscriptions($mid, $objs, $type, $mode = 1){
	global $wpdb;
	$t_subs = $wpdb->prefix . 'post_notification_subs';
	//var_dump($objs);
	//Delete all entries
	if($mode == 1){
		$wpdb->query("DELETE FROM $t_subs WHERE email_id = $mid AND obj_type = $type");
	}
	if(!is_array($objs)) $objs = array(); //Just to make sure it doesn't crash
	
	//Let's see what cats we have
	$queryObjs = '';
	if($mode == 0 || $mode == 1){ //Add the list
		foreach($objs as $obj){
			if(is_numeric($obj)) $queryObjs .= ", ($mid, $obj, $type)";//Security		
		}
		$queryObjs = substr($queryObjs, 1);
		if(strlen($queryObjs) > 0){
			$wpdb->query("INSERT INTO $t_subs (email_id, obj_id, obj_type ) VALUES $queryObjs");
		}
	}
	if($mode == 2){
		foreach($objs as $obj){
			if(is_numeric($obj)) $queryObjs .= ",$obj";//Security		
		}
		$queryObjs = substr($queryObjs, 1);
		//echo "DELETE FROM $t_subs WHERE email_id='$mid' AND obj_type=$type AND obj_id IN ($queryObjs)";
		$wpdb->query("DELETE FROM $t_subs WHERE email_id='$mid' AND obj_type=$type AND obj_id IN ($queryObjs)");		
	}
	
}





/**
 * Creates the content for the frontend
 *
 * @return array(body, header)
 */
function post_notification_page_content(){
	global $post_notification_page_content_glob, $wpdb;
	if(isset($post_notification_page_content_glob)) return $post_notification_page_content_glob;
	
	//It doesn't matter where this goes:
	
	
	$content = & $post_notification_page_content_glob;
	
	
		
	// ******************************************************** //
	//                  Init auth-variables
	// ******************************************************** //
	
	global $post_notification_addr, $post_notification_code, $post_notification_action;
	$addr = &$post_notification_addr;
	$code = &$post_notification_code;
	$action = &$post_notification_action;
	
	if(isset($_GET['addr'])){
		$addr   = $wpdb->escape($_GET['addr']);
		$code   = $wpdb->escape($_GET['code']);
		$action = $wpdb->escape($_GET['action']);
	} else if(isset($_POST['addr'])){
		$addr 	= $wpdb->escape($_POST['addr']);
		$code	= $wpdb->escape($_POST['code']);
		$action = $wpdb->escape($_POST['action']);
	} else {
		$addr = '';
		$code = '';
		$action = '';
	}

	
	
	// ******************************************************** //
	//                  DEFINE OTHER VARS NEEDED
	// ******************************************************** //
	
	
	
	$t_emails = $wpdb->prefix . 'post_notification_emails';
	$t_cats = $wpdb->prefix . 'post_notification_cats';
	
	
	
	// ******************************************************** //
	//                      Code Check
	// ******************************************************** //	
	
	if($action == 'subscribe_comment'){
		require_once(POST_NOTIFICATION_PATH  . 'frontend_subscribe_comment.php');
		$content = post_notification_fe_subscribe_comment();
	}
	//This code is not very nice in performance, but I wanted to keep it as easy to understand as possible. It's not called that often.
	else if(($code != '') && is_email($addr) && $wpdb->get_var("SELECT id FROM $t_emails WHERE email_addr = '$addr' AND act_code = '" . $code . "'")){
		require_once(POST_NOTIFICATION_PATH  . 'frontend_manage.php');
		$content = post_notification_fe_manage();		
	} else {
		require_once(POST_NOTIFICATION_PATH  . 'frontend_subscribe.php');
		$content = post_notification_fe_subscribe();
	}		
	return $content;
	
}


/**
 * This is the filter-function to replace the @@p_n_header and p_n_body stings in pages
 * @param $content
 * @return unknown_type
 */
function post_notification_filter_content($content){
	if(strpos($content, '@@post_notification_')!== false){ //Just looking for the start
		$fe = post_notification_page_content();
		$content = str_replace('@@post_notification_header', $fe['header'], $content);
		$content = str_replace('@@post_notification_body', $fe['body'], $content);
	}
	return $content;
}


/**
 * This function adds the checkbox to the comment-field.
 * @return unknown_type
 */
function post_notification_comment_subscription($post_id){
	global $wpdb;
	$t_emails = $wpdb->prefix . 'post_notification_emails';
	$t_subs = $wpdb->prefix . 'post_notification_subs';
	
	
	//Let's see whether the post has been subscribed
	$addr = post_notification_get_addr();
	//echo "|$addr|";
	$substate = 0;
	if($addr != ''){
		
		$cat_ids = implode(", ", post_notification_get_cats_of_post($post_id)); //convert to string
		
		$res = $wpdb->get_results("SELECT DISTINCT gets_mail, obj_id FROM $t_emails e LEFT JOIN $t_subs s ON (e.id = s.email_id
				AND e.email_addr = '$addr' AND (obj_type IS NULL OR (obj_type = 1 AND obj_id IN ($cat_ids)) OR (obj_type = 2 AND obj_id = $post_id)))");
		//echo "<pre>"; var_dump($res); echo "</pre>";
		if(count($res) && $res[0]->gets_mail == 1){ //Already subscribed
			$substate = 1;
			//Due to DISTINCT ther will be a max of tow results
			if($res[0]->obj_id != NULL || (count($res) > 1 && $res[1]->obj_id != NULL)){ //And already subscribed
				$substate = 2;
			}
		}
				
	}
	//Fist add a hidden box, because we never know, whether the checkbox was shown or not when
	//the checkbox istn't checked.
	/// \todo This must be cleaned up!
	echo '<input type = "hidden" name ="pn_subscribe_hint" value="1"';
	require_once(post_notification_get_profile_dir(). '/strings.php');
	
	//post_notification_vardump($substate);
	$link = post_notification_get_link() . 'action=subscribe_comment&addr=' . post_notification_get_addr() . '&';
	$link .= "PID=$post_id&POST_NOTIFICATION_FE_CHECK=1&";
	
	if($substate != 0){
		//The user is already subscribed. Let him change his settings right away.
		$checked = ($substate == 2) ? 'checked = "checked"' : '';
		$link .= ($substate == 2) ? 'unsub=1&': 'unsub=0&';
		$text .= ($substate == 2) ? $post_notification_strings['subscibe_to_comments_unsubscribe']:
						$post_notification_strings['subscibe_to_comments_subscribe'];
		
		echo '<input type = "checkbox" style = "width:auto" name="pn_subscirbe" value = "1"' . "$checked />" . $post_notification_strings['subscibe_to_comments'];
		echo "<br/><a href=\"$link\">$text</a>";
	} else {
		//The user hasn't subsciribed
		echo '<a href="' .$link . '">' .
				$post_notification_strings['subscibe_to_comments_not_registerd'].
				'</a>';
	}

}


?>
