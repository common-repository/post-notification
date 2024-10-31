<?php

#------------------------------------------------------
# INFO
#------------------------------------------------------
# This is part of the Post Notification Plugin for 
# Wordpress. Please see the Readme2.txt for details.
#------------------------------------------------------

function post_notification_is_file($path, $file){
	if(!is_file($path . '/' . $file)){
		echo '<div class="error">'. __('File missing in profile folder.', 'post_notification') . '<br />';
		echo __('Folder', 'post_notification') . ': <b>' . $path . '</b><br />';
		echo __('File', 'post_notification') . ': <b>' . $file. '</b></div>';
		return false;
	}
	return true;
}

function post_notification_check_string($path, $string){
	include($path . '/strings.php');
	if(!array_key_exists($string, $post_notification_strings)){
		echo '<div class="error">'. __('Missing string in string file.', 'post_notification') .'<br />';
		echo __('File', 'post_notification') . ': <b>' . $path . '/strings.php </b><br />';
		echo __('String', 'post_notification') . ': <b>' . $string . '</b></div>';
		return false;
	}
	return true;
}

function post_notification_is_profile($path){
	
	return true; /// \todo Reactivate
	
	if(!(
		post_notification_is_file($path , 'confirm.tmpl') &&
		post_notification_is_file($path , 'reg_success.tmpl') &&
		post_notification_is_file($path , 'select.tmpl') &&
		post_notification_is_file($path , 'subscribe.tmpl') &&
		post_notification_is_file($path , 'unsubscribe.tmpl') &&
		post_notification_is_file($path , 'strings.php'))) return false;
	
	if(!(
		post_notification_check_string($path, 'already_subscribed') &&
		post_notification_check_string($path, 'activation_faild') &&
		post_notification_check_string($path, 'address_not_in_database') &&
		post_notification_check_string($path, 'sign_up_again') &&
		post_notification_check_string($path, 'deaktivated') &&
		post_notification_check_string($path, 'no_longer_activated') &&
		post_notification_check_string($path, 'check_email') &&
		post_notification_check_string($path, 'wrong_captcha') &&
		post_notification_check_string($path, 'all') &&
		post_notification_check_string($path, 'saved')
		)) return false;
	
	return true;

}




/**
 * Searches for Profiles. Returns an array of porfiles.
 */
function post_notification_get_profiles(){
	$profile_list = array();
	if(file_exists(POST_NOTIFICATION_DATA)){
		$dir_handle=opendir(POST_NOTIFICATION_DATA);
		while (false !== ($file = readdir ($dir_handle))) {
			if(is_dir(POST_NOTIFICATION_DATA . $file) && $file[0] != '.' && $file[0] != '_') {
				if(post_notification_is_profile(POST_NOTIFICATION_DATA . $file)){
					$profile_list[] = $file;
				}
			}
		}
		closedir($dir_handle);
	} 
	
		
	$dir_handle=opendir(POST_NOTIFICATION_PATH);
	while (false !== ($file = readdir ($dir_handle))) {
		if(is_dir(POST_NOTIFICATION_PATH . $file) && $file[0] != '.' && $file[0] != '_') {
			if(post_notification_is_profile(POST_NOTIFICATION_PATH . $file)){
				if(!in_array($file, $profile_list)) $profile_list[] = $file;
			}
		}
	}
	closedir($dir_handle); 
	return $profile_list;	
}



?>