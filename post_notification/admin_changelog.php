<?php

#------------------------------------------------------
# INFO
#------------------------------------------------------
# This is part of the Post Notification Plugin for 
# Wordpress. Please see the Readme2.txt for details.
#------------------------------------------------------

function post_notification_admin_sub(){

	echo '<h3>' .  __('Change log', 'post_notification') . '</h3><pre>';
	echo file_get_contents(POST_NOTIFICATION_PATH . 'changelog.txt');
	echo '</pre>';
}
?>
