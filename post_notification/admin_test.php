<?php

#------------------------------------------------------
# INFO
#------------------------------------------------------
# This is part of the Post Notification Plugin for 
# Wordpress. Please see the Readme2.txt for details.
#------------------------------------------------------


function post_notification_admin_sub(){ 
	global $wpdb;
	require_once (POST_NOTIFICATION_PATH  . 'sendmail.php');
	require_once (POST_NOTIFICATION_PATH  . 'admin_functions.php');
	
	echo '<h3>' . __('Test', 'post_notification') . '</h3>';
?>
<form id="test" method="post" action="admin.php?page=post_notification/admin.php&amp;action=test">
<table width="100%">
	

<?php
	if(($email = $_POST['email']) == '') $email = get_option('post_notification_from_email');
?>
	<tr class="alternate">
		<th style="text-align:right;padding-right:10px;"><?php _e('Email:', 'post_notification') ?></th>
		<td>
			<input name="email" type="text" size="35" value="<?php  echo $email ?>" />
		</td>
	</tr>
	
<?php
	$profile_list = post_notification_get_profiles();
	$en_profiles = '';
	foreach($profile_list as $profile){
		$sel = ($profile == $_POST[template]) ? 'selected = "selected"' : '';
		$en_profiles .= "<option value = \"$profile\" $sel >$profile</option>"; 
	}

?>
	<tr class="alternate">
		<th style="text-align:right;padding-right:10px;"><?php _e('Profile:', 'post_notification'); ?></th>
		<td>
	        <select name="template" >
				<?php  echo $en_profiles; ?>
	        </select>	
		</td>
	</tr>

	<tr class="alternate">
		<th style="text-align:right;padding-right:10px;"><?php _e('Type:', 'post_notification') ?></th>
		<td>
			<?php 
			$ckPost = ($_POST['type'] == 'post'  || !isset($_POST['type']) ) ? 'checked = "chekced" >' : ' >';
			$ckCom = ($_POST['type'] == 'com') ? 'checked = "chekced" >' : ' >';
			$ckDig = ($_POST['type'] == 'digest') ? 'checked = "chekced" >' : ' >';  
			 ?>
			<input name="type" type="radio" value="post" <?php echo $ckPost; _e('Post', 'post_notification') ?></input> &middot;
			<input name="type" type="radio" value="com" <?php echo $ckCom; _e('Comment', 'post_notification') ?></input> &middot;
			<input name="type" type="radio" value="digest" <?php echo $ckDig; _e('Digest', 'post_notification') ?></input> 
		</td>
	</tr>	
		<tr class="alternate">
		<th style="text-align:right;padding-right:10px;"><?php _e('Format:', 'post_notification') ?></th>
		<td>
			<?php 
			$ckHtml = ($_POST['format'] == 'html' || !isset($_POST['format']) ) ? 'checked = "chekced" >' : ' >';
			$ckText = ($_POST['format'] == 'txt') ? 'checked = "chekced" >' : ' >';
			 ?>
			<input name="format" type="radio" value="html" <?php echo $ckHtml; _e('HTML', 'post_notification') ?></input> &middot;
			<input name="format" type="radio" value="txt" <?php echo $ckText; _e('Text', 'post_notification') ?></input>
		</td>
	</tr>	
	
	
	<tr class="alternate">
		<th style="text-align:right;padding-right:10px;"><?php _e('Posts:', 'post_notification') ?></th>
		<td>
			IDs: <input name="pId" type="text" size="35" value="<?php  echo $_POST['pId'] ?>" /><br />
			Last: <input type="text" class="text" name="numPost" value="<?php  echo $_POST['numPost'] ?>" size="10" maxlength="10" />
		</td>
	</tr>
	<tr class="alternate">
		<td />
		<td>
			<?php _e('You can either insert a comma seperated list of post ids or the number of most recent posts to be sent.', 'post_notification') ?>
			<?php _e('The id of a post can be found under Manage->Posts.', 'post_notification') ?>
		</td>		
	</tr>
	<tr class="alternate">
		<th style="text-align:right;padding-right:10px;"><?php _e('Comments:', 'post_notification') ?></th>
		<td>
			IDs: <input name="cId" type="text" size="35" value="<?php  echo $_POST['cId'] ?>" /><br />
			Last: <input type="text" class="text" name="numCom" value="<?php  echo $_POST['numCom'] ?>" size="10" maxlength="10" />
		</td>
	</tr>
	<tr class="alternate">
		<td />
		<td>
			<?php _e('You can either insert a comma seperated list of comment ids or the number of most recent comments to be sent.', 'post_notification') ?>
			<?php _e('The id of a comment can be found under Comments->Edit.', 'post_notification') ?>
		</td>		
	</tr>	
	<tr class="alternate">
	<th style="text-align:right;padding-right:10px;"><?php _e('Send mail:', 'post_notification'); ?></th>
		<td>
	        <input type="checkbox" name="send" value="true"
	        	<?php 
	        		if($_POST['send'] == 'true') echo ' checked="checked" '; 
	        	?> 
	        />	
		</td>
	</tr>
	
	<tr class="alternate">
		<td>&nbsp;</td>
		<td><input type="submit" name="updateSettings" value="<?php _e('Send test mail.', 'post_notification'); ?>" /></td>
	</tr>

</table>
</form>
<?php
	//Now lets get to the output.
	//$id, $html bestimmen!
	$postIds = array();
	$comIds = array();
	
	if($_POST['format'] == 'html'){
		$html = true;
	} else {
		$html = false;
	}
	
	
	if(is_numeric($_POST['numPost'])){
		$posts = get_posts('numberposts=' . $_POST['numPost'] );
		foreach($posts as $post){
			$postIds[] = $post->ID;
		}
	}
	if(is_numeric($_POST['numCom'])){
		if(function_exists('get_comments')) $coms = get_comments('numberposts=' . $_POST['numCom'] );
		else $coms = $wpdb->get_results( "SELECT comment_ID FROM $wpdb->comments " .
					"WHERE comment_approved = '1' ORDER BY comment_date_gmt DESC LIMIT {$_POST['numCom']}" );
		

		foreach($coms as $com){
			$comIds[] = $com->comment_ID;
		}
	}
	
	//Set dir
	if(isset($_POST[template])){
		global $post_notification_profile_override;
		$post_notification_profile_override = $_POST[template];
	}

	
	if($_POST['type'] == 'post'){
		$maildata = post_notification_prepare_post($postIds[0],$html);
	} else if($_POST['type'] == 'com'){
		$maildata = post_notification_prepare_comment($comIds[0], $html); 
	} else if($_POST['type'] == 'digest'){
		foreach($postIds as $id) $objs[] = post_notification_get_post($id, $html);
		$numPosts = count($postIds);
		foreach($comIds  as $id) $objs[] = post_notification_get_comment($id, $html);
		$numComs = count($comIds);
		$maildata = post_notification_assemble_digest($objs, $numPosts, $numComs, $html);
	} else {
		//Error
		$maildata = NULL;
	} 
	if($maildata == NULL){
		_e('Error creating mail', 'post_notification'); 
	} else {
		$body = str_replace('\\', '\\\\', $maildata['body']);
		$body = str_replace('"', '\\"', $body);
		$body = str_replace("\n", '\\n', $body);
		$body = str_replace("\r", '\\r', $body);
		if(!$html) $body = '<pre>' . $body . '</pre>';
		
		echo "\n" . '<br/><br/><b>' . __('Subject: ', 'post_notification') . $maildata['subject'];
		echo "\n" . '<iframe id="mailbody" name="mailbody" width="90%" frameborder="1"  height = "500"></iframe> ';
		echo "\n" . '<script language="JavaScript" type="text/javascript"> str="' . $body . '";';
		echo "\n" . 'top.frames.mailbody.document.write(str);';
		echo "\n" . 'top.frames.mailbody.document.close();'; 
		echo "\n" . '</script>';  
	}

	//Reset override	
	$post_notification_profile_override = '';

}
?>
