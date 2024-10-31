<?php

#------------------------------------------------------
# INFO
#------------------------------------------------------
# This is part of the Post Notification Plugin for 
# Wordpress. Please see the Readme2.txt for details.
#------------------------------------------------------



function post_notification_admin_sub(){

	global $wpdb, $wp_filesystem;
	$t_posts = $wpdb->prefix . 'post_notification_posts';
	$t_subs = $wpdb->prefix . 'post_notification_subs';
	$t_emails = $wpdb->prefix . 'post_notification_emails';
	$t_queue = $wpdb->prefix . 'post_notification_queue';
	$datestr = get_option('date_format') . ' ' . get_option('time_format');
	
	//Run the install
	
	require_once(POST_NOTIFICATION_PATH . 'install.php');
	

	
	post_notification_install();

	
	if (is_object($wp_filesystem) ){
		
		echo "WP_FILESYSTEM!";
		$dir_handle=opendir(POST_NOTIFICATION_PATH);
		while (false !== ($file = readdir ($dir_handle))) {
			if(is_dir(POST_NOTIFICATION_PATH . $file) && $file[0] != '.' && $file[0] != '_') {
				if(is_wp_error(copy_dir(POST_NOTIFICATION_PATH . $file, POST_NOTIFICATION_DATA . $file))){
					echo '<div class="error">' . __('Failed to copy folder:', 'post_notification') .'<br/>';
					echo POST_NOTIFICATION_PATH . $file. ' -> ' . POST_NOTIFICATION_DATA . $file . '<br/>';
					echo __('Please ensure the Folder is writabel (777):', 'post_notification') .' ' .POST_NOTIFICATION_DATA. '</div>';
				} else {
					echo '<div class="error">' . __('Copied Folder:', 'post_notification') .'<br/>';
					echo POST_NOTIFICATION_PATH . $file. ' -> ' . POST_NOTIFICATION_DATA . $file . '</div>';
					if(delete(POST_NOTIFICATION_PATH . $file) === false){
						echo '<div class="error">' . __('Error deleting folder:', 'post_notification') . ' ' . POST_NOTIFICATION_PATH . $file . '</div>';
					}
				}
				
			}	
		}
		closedir($dir_handle); 
	}
	

	

	
	//Check for problems
	$mutex = @fopen(POST_NOTIFICATION_PATH . '_temp/post_notification.lock', 'w');
	if($mutex == false){
		echo '<div class="error">' .  __('Couldn\'t create File:', 'post_notification') . ' ' . POST_NOTIFICATION_PATH . '_temp/post_notification.lock<br />'
			. __('Please assure the the path is writeable.', 'post_notification') . ' '. __('This is an error!', 'post_notification') . '</div>'	;
	} else {
		fclose($mutex);
	}
	
	
	
	if(!function_exists('iconv')){
		echo '<div class="error">' .  __('PHP was compiled without iconv-libs. This might cause severe trouble using UTF-8 with special chars or umlauts.', 'post_notification'). ' ' 
			. __('Most hosters support this, as it is a sensible php-extension.', 'post_notification') . ' '
			. '<a href="http://php.net/manual/ref.iconv.php">' . __('More information.', 'post_notification') 
			. '</a></div>';
	}
	if(!function_exists('mb_detect_encoding')){
		echo '<div class="error">' . __('This version of PHP does not support the function mb_detect_encoding. You might experience some trouble with non-ASCII characters.', 'post_notification'). ' '
			. __('Most hosters support this, as it is a sensible php-extension.', 'post_notification') . ' '
			. '<a href="http://php.net/manual/ref.mbstring.php">' . __('More information.', 'post_notification') 
			. '</a></div>';
	}
	
	if(!function_exists('html_entity_decode')){
		echo '<div class="error">' .  __('This version of PHP does not support the function html_entity_decode. Sending out text mails will not be possible.', 'post_notification'). ' ' 
			. __('Please contact your hoster.', 'post_notification')
			. '</a></div>';		
	}

	if('' != ($dest = post_notification_installtheme())){
		echo '<div class="error">' . __('Couldn\'t create File:', 'post_notification') . $dest . ' ' 
		. __('Please assure the the path is writeable.', 'post_notification') . ' ' 
		. __('You can also copy the file from the plugin directory manually.', 'post_notification')
		. '</div>';
	}
	
	
	if(get_option('post_notification_debug') == 'yes'){
		echo '<div class="error">' . __('PN is in debugging mode. This should only be on, if something isn\'t working correctly.', 'post_notification') . '</div>';
	}
	if(get_option('post_notification_uninstall') == 'yes'){
		require_once(POST_NOTIFICATION_PATH . "install.php");
		echo '<div class="error">' . __('It seems like you want to uninstall this Plugin. Please deactivate it or change the setting the options. ', 'post_notification') . '</div>';
	} else if($wpdb->get_var("SHOW TABLES LIKE '$t_subs'") == NULL){
		echo '<div class="error">' . __('It seems like there is some trouble creating the tables in the DB. If you\'re lucky there should be some errormessages on this page.', 'post_notification') . '</div>';
	}
	
	if(ini_get('max_execution_time') != 0 && ini_get('max_execution_time') < 15){
		echo '<div class="error">' . __('The maximum executiontime is very low.', 'post_notification') . ' '
			. '<a href="http://php.net/manual/ref.info.php#ini.max-execution-time">' . __('More information.', 'post_notification') 
			. '</a></div>';;
	}
	
	if( (get_option('db_version') > 4772)  && (get_option('db_version') < 6124) && (substr( get_option('post_notification_template'), -5) == '.html')){
		echo '<div class="error">' . __('There is a bug in WP handling HTML-Mails. Please make sure a mail-plugin is installed.', 'post_notification') . 
		' <a href="http://wordpress.org/extend/plugins/wordpress-22-mailfix/">Wordpress 2.2 Mailfix</a> </div>';
		
	}
	
	
	//clean up - just in case.
	//Probably there's something with better performance, but as long there are not
	// 10 000 emails in the db this shouldn't matter. 
	/* Not well enaugh tested -> Next version
	$wpdb->query("	DELETE $t_cats, t_emails 
					FROM $t_cats LEFT JOIN t_emails
					WHERE email_addr IS NULL ");
	*/

	//Try to send.
	if(get_option('post_notification_debug') == 'yes'){
		post_notification_send_check(true);
	}
		
	//------------ Some info.....
	$nummails = $wpdb->get_var("SELECT COUNT(*) FROM $t_emails WHERE gets_mail = 1");
	echo '<p>'. __('Number of Subscribers:', 'post_notification') . "<b> $nummails </b></p>";
	//------------ Some advertising ----
	if($nummails > 500){
		echo '<p><b>' . __('Looks like you are realy using this plugin.', 'post_notification') . 
		'</b> <a href="http://pn.xn--strbe-mva.de/forum.php?req=thread&postid=8">' . __('What about a donation?.', 'post_notification') . '</a></p>';
	}
	
	
	echo '<p>' . __('The time is:', 'post_notification'); 
	echo ' <b>' . post_notification_date_i18n_tz($datestr, time()) . '</b></p>'; //Can use i18n_time as it uses date
	
	echo '<p>' .  __('Posts to be notified.', 'post_notification');
	$posts = $wpdb->get_results(
	"SELECT id, post_title, post_date_gmt, notification_sent, post_status, date_saved " 
		. post_notification_sql_posts_to_send(true) .
		" ORDER BY post_date_gmt DESC");
	echo '<table>
		<tr class="alternate"><th>'
			. __('Post-ID', 'post_notification') . '</th><th>'
			. __('Post title', 'post_notification') . '</th><th>'
			. __('Publish-date', 'post_notification') . '</th><th>'
			. __('Save-date', 'post_notification') . '</th></tr>';
	
	if (empty ($posts)) {
		echo '<tr><td colspan="5" class="alternate">' . __('None queued', 'post_notification') . '</td></tr>';
 
 	} else { 
		foreach($posts as $post){
			echo  '<tr class="alternate"><td>'
				. $post->id . '</td><td>' 
				. $post->post_title . '</td><td>'
				. post_notification_date_i18n_tz($datestr, post_notification_mysql2gmdate($post->post_date_gmt)) . '</td><td>' 
				. post_notification_date_i18n_tz($datestr, post_notification_mysql2gmdate($post->date_saved)) . '</td></tr>';
		
		}
	}
	echo '</table></p>';
	
	
	echo '<p>' . __('Mails to send:', 'post_notification');
	
	$emails = $wpdb->get_results("SELECT email_addr, obj_id, post_title
			FROM $t_queue q JOIN $t_emails e ON q.email_id = e.id
				JOIN " . $wpdb->posts . "p ON q.email_id = p.id
			WHERE q.type = 0 AND e.state = 0 ORDER BY q.obj_id");
	
	echo '<table>
		<tr class="alternate"><th>'
			. __('Email', 'post_notification') . '</th><th>'
			. __('Post-ID', 'post_notification') . '</th><th>'
			. __('Post title', 'post_notification') . '</th><th>';

	if (empty ($posts)) {
		echo '<tr><td colspan="3" class="alternate">' . __('None queued', 'post_notification') . '</td></tr>';
 
 	} else { 
		foreach($emails as $email){
			echo  '<tr class="alternate"><td>'
				. $email->email_addr . '</td><td>' 
				. $post->obj_id . '</td><td>'
				. $post->post_title . '</td></tr>';
		
		}
	}
	echo '</table></p>';
	
	echo '<p>' . __('Mails with errors:', 'post_notification');
	
	$emails = $wpdb->get_results("SELECT email_addr, obj_id, post_title
			FROM $t_queue q JOIN $t_emails e ON q.email_id = e.id
				JOIN " . $wpdb->posts . "p ON q.email_id = p.id
			WHERE q.type = 0 AND e.state = -1 ORDER BY q.obj_id");
	
	echo '<table>
		<tr class="alternate"><th>'
			. __('Email', 'post_notification') . '</th><th>'
			. __('Post-ID', 'post_notification') . '</th><th>'
			. __('Post title', 'post_notification') . '</th><th>';

	if (empty ($posts)) {
		echo '<tr><td colspan="3" class="alternate">' . __('None', 'post_notification') . '</td></tr>';
 
 	} else { 
		foreach($emails as $email){
			echo  '<tr class="alternate"><td>'
				. $email->email_addr . '</td><td>' 
				. $post->obj_id . '</td><td>'
				. $post->post_title . '</td></tr>';
		
		}
	}
	echo '</table></p>';
	
	
	echo '<p>' . __('The next mail will be sent:', 'post_notification') . ' ';
	if(get_option('post_notification_nextmove') == '-1'){
		 _e('None queued', 'post_notification');
	} else {
		echo post_notification_date_i18n_tz($datestr, get_option('post_notification_nextmove'));
	}
	echo '</p>';
	
	echo '<p>' . __('The last mail was sent:', 'post_notification') . ' ';
	echo post_notification_date_i18n_tz($datestr, get_option('post_notification_lastsend'));
	echo '</p>';
	echo '<p>' . __('The last post was saved:', 'post_notification') . ' ';
	echo post_notification_date_i18n_tz($datestr, get_option('post_notification_lastpost'));
	echo '</p>';

	
	
}
?>