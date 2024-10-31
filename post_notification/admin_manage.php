<?php

#------------------------------------------------------
# INFO
#------------------------------------------------------
# This is part of the Post Notification Plugin for 
# Wordpress. Please see the Readme2.txt for details.
#------------------------------------------------------

function post_notification_admin_sub(){
	echo '<h3>' . __('Manage addresses', 'post_notification') . '</h3>';
	if (!$_POST['manage']){
		?>
		<p> <?php _e('The Emails may be seprated by newline, space, comma, semi colon, tabs, [, ], &lt; or &gt;.' , 'post_notification'); ?> <br />
		<b><?php _e('Watch out! There is only simple checking whether the email address is valid.', 'post_notification'); ?> </b></p>
		
		<form name="import" action="admin.php?page=post_notification/admin.php&amp;action=manage" method="post">
		  	<b>Manage:</b>
		  	<br />
		  	<textarea name="imp_emails" cols="60" rows="10" class="commentBox"></textarea>
		  	<br /><br />
		  	<input type="submit" name="manage" value="<?php _e('Manage', 'post_notification'); ?>" class="commentButton" />
		  	<input type="reset" name="Reset" value="<?php _e('Reset', 'post_notification'); ?>" class="commentButton" /><br/><br/><br/>
		  	<?php _e('What should be done?', 'post_notification'); ?><br/>
			<table width="100%"class="alternate">
				<tr><th colspan="2"><?php _e('General:', 'post_notification'); ?> </th></tr>
				<tr>
					<td colspan ="2">
						<input type="radio" name="gen" value="add" checked="checked" ><?php _e('Send registration mail to missing emails.', 'post_notification'); ?></input><br/>
						<input type="radio" name="gen" value="force"><?php _e('Add missing mails as registerd', 'post_notification'); ?></input><br/>
						<input type="radio" name="gen" value="resend"><?php _e('Unrigister all mails and send a registration mail', 'post_notification'); ?></input><br/>
						<input type="radio" name="gen" value="ignore"><?php _e('Ignore unregistered mails', 'post_notification'); ?></input><br/>
						<input type="radio" name="gen" value="del"><?php _e('Delete Emails', 'post_notification'); ?></input><br/>
					</td>
				</tr>
				<tr><th colspan="2"><?php _e('Subscribed <em>post</em> categories', 'post_notification'); ?> </th></tr>
				<tr >
					<td>
						<input type="radio" name="pAct" value="ign" checked="checked" ><?php _e('Nothing', 'post_notification'); ?></input><br />
						<input type="radio" name="pAct" value="add"><?php _e('Add selected categories', 'post_notification'); ?></input><br />
						<input type="radio" name="pAct" value="rem"><?php _e('Remove selected categories', 'post_notification'); ?></input><br />
						<input type="radio" name="pAct" value="repl"><?php _e('Replace with selected categories', 'post_notification'); ?></input><br />
					</td>
					<td>
						<?php 
							$selected_cats = explode(',', get_option('post_notification_selected_cats'));    
							echo post_notification_get_catselect('post','', $selected_cats); 
						?>
					<td>
				</tr>
				<tr><th colspan="2"><?php _e('Subscribed <em>comment</em> categories', 'post_notification'); ?> </th></tr>
				<tr >
					<td>
						<input type="radio" name="cAct" value="ign" checked="checked" ><?php _e('Nothing', 'post_notification'); ?></input><br />
						<input type="radio" name="cAct" value="add"><?php _e('Add selected categories', 'post_notification'); ?></input><br />
						<input type="radio" name="cAct" value="rem"><?php _e('Remove selected categories', 'post_notification'); ?></input><br />
						<input type="radio" name="cAct" value="repl"><?php _e('Replace with selected categories', 'post_notification'); ?></input><br />
					</td>
					<td>
						<?php 
							$selected_cats = explode(',', get_option('post_notification_selected_cats'));    
							echo post_notification_get_catselect('com','', $selected_cats); 
						?>
					<td>
				</tr>
				
			</table>
		</form>
		<?php
	} else {	
		global $wpdb;
		$t_emails = $wpdb->prefix . 'post_notification_emails';
		$t_queue = $wpdb->prefix . 'post_notification_queue';
		$t_subs = $wpdb->prefix . 'post_notification_subs';
		/// \todo: Add lock!
		
		$import_array = preg_split('/[\s\n\[\]<>\t,;]+/',$_POST['imp_emails'],-1, PREG_SPLIT_NO_EMPTY);
		/**
		 * This whole code could probably be reduced to 3 or 4 queries in total. But performance isn't an issue here.
		 */
		foreach($import_array as $addr){
			// Set Variables //
			
			$now = post_notification_date2mysql();
			
			// Basic checking
			if(!is_email($addr)){
				if(!$addr == ""){
					echo '<div class="error">' .  __('Email is not valid:', 'post_notification') . " $addr</div>";			
				}
				continue;
			}
			//*************************************/
			//*    Check database for duplicates  */
			//*************************************/
			
			$mid = $wpdb->get_var("SELECT id FROM $t_emails WHERE email_addr = '$addr'"); 
			
			if($_POST['gen'] == 'del'){
				if($mid != ''){		
					$wpdb->query("DELETE FROM $t_subs WHERE email_id = $mid");
					$wpdb->query("DELETE FROM $t_queue WHERE email_id = $mid");
					$wpdb->query("DELETE FROM $t_emails WHERE id = $mid");
					echo "<div>" . __('Removed email:', 'post_notification') . " $email_addr</div>";
				} else {
					echo '<div class="error">' .  __('Email is not in DB:', 'post_notification') . " $addr</div>";
				}	
				continue;
			}
			
			
			//Let's see what to do with unregistered mails
			$send_sub = 0;	
			if (!$mid) {
				if($_POST['gen'] == 'ignore'){
					echo '<div class="error">' .  __('Ignored Mail:', 'post_notification') . " $addr</div>";
					continue;
				}
				
				$gets_mail = 0;
				$send_sub = 1;
				if($_POST['gen'] == 'force'){
					$gets_mail = 1;
					$send_sub = 0;
				} 
				
				$wpdb->query(
						"INSERT " . $t_emails .
						" (email_addr, gets_mail, last_modified, date_subscribed) " .
						" VALUES ('$addr', '$gets_mail', '$now', '$now')");
				echo "<div>" . __('Added Email:', 'post_notification') . " $addr</div>";
				$mid = $wpdb->get_var("SELECT id FROM $t_emails WHERE email_addr = '$addr'"); 		
			} 
			
			if($mid == ''){
				echo '<div>' . __('Something went wrong with the Email:', 'post_notification') . $addr . '</div>';
				continue;
			}
			
			
			if($_POST['gen'] == 'resend'){
				$wpdb->query("UPDATE $t_emails SET gets_mail = 0 WHERE WHERE id = $mid");	
				$send_sub = 1;
			}
			
			if($send_sub == 1){
				$wpdb->query("INSERT INTO $t_queue (email_id, obj_id, state, type, date) 
							VALUES($mid, 0, 0, 2, '" . post_notification_date2mysql() . "')");
			}
			
			//Now let's do the category stuff. Let's do it in parallel
							
			
			for($loop = 0; $loop < 2; $loop++){
				if($loop == 0){
					$action = $_POST['pAct'];
					$cats = $_POST['pn_post'];
					$type = 0;
				} else {
					$action = $_POST['cAct'];
					$cats = $_POST['pn_com'];
					$type = 1;
				}
				if($action == 'repl'){
					$wpdb->query("DELETE FROM $t_subs WHERE email_id = $mid AND obj_type = $type");
				}	
				if( ($action == 'add' || $action == 'repl') && is_array($cats)){
					//todo: Is there a better way of optimization?
					for($el = 0; $el < count($cats); $el++) $cats[$el] = "($mid, {$cats[$el]} , $type)" ;
					$values = implode(', ', $cats);
					$wpdb->query("INSERT IGNORE INTO $t_subs (email_id, obj_id, obj_type) VALUES $values");	
				} 
				if($action == 'del' && is_array($cats)){
					$values = implode(', ', $cats);
					$wpdb->query("DELETE FROM $t_subs WHERE email_id = $mid AND obj_type = $type AND obj_id IN ($values)");	
				}
				
				
			}
			
			echo '<div>' . __('Updated Email:', 'post_notification') . " $addr</div>";	
			
		} //end foreach
	}
}

if(!is_array($pn_cats)) $post_subs = array(); //Just to make shure it doesn't crash