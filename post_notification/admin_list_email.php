<?php

#------------------------------------------------------
# INFO
#------------------------------------------------------
# This is part of the Post Notification Plugin for 
# Wordpress. Please see the Readme2.txt for details.
#------------------------------------------------------

class Walker_pn_CategoryDropdown extends Walker {
	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'category_parent', 'id' => 'cat_ID'); //TODO: decouple this

	function start_el($output, $category, $depth, $args) {
		$pad = str_repeat('&nbsp;', $depth * 3);

		$cat_name = apply_filters('list_cats', $category->cat_name, $category);
		$output .= "\t<option value=\"".$category->cat_ID."\"";
		if ( in_array($category->cat_ID, $args))
			$output .= ' selected="selected"';
		$output .= '>';
		$output .= $pad.$cat_name;
		$output .= "</option>\n";

		return $output;
	}
}


function post_notification_admin_sub(){
	global $wpdb;
	$t_emails = $wpdb->prefix . 'post_notification_emails';
	$t_subs = $wpdb->prefix . 'post_notification_subs';
	
	
	if($_GET['action'] == 'remove_email') $remove=true; else $remove = false;
	
	echo '<h3>' . __('List of addresses:', 'post_notification') . '</h3>';
	
	if (isset($_POST['removeEmailChecked'])) {    
	
		if ($_POST['removeEmail'] == "") {
			echo '<div class = "error">' . __('No address checked!', 'post_notification') . '</div>';
		} else {
			echo __('The following addresses were deleted:', 'post_notification') . '<br /><br />';
			
			foreach ($_POST['removeEmail'] as $removeAddress) {
				
				post_notification_remove_email($removeAddress);
				echo "$removeAddress<br />";
			}
		}
	} else {
		
		
		if(isset($_POST['email'])) 
			$email = $_POST['email'];
		else
			$email = '*';
		
		 
		$sel_post_cats = $_POST['post_cats'];	
		if (!is_array($sel_post_cats)) $sel_post_cats = array();
		
		$sel_com_cats = $_POST['com_cats'];	
		if (!is_array($sel_com_cats)) $sel_com_cats = array();
		
		
		
		if(isset($_POST['limit']))
			$limit= $_POST['limit'];
		else
			$limit = 50;
			
		if(!is_numeric($limit)) $limit= 50;
		if($limit< 1) $limit= 1;
		
		if(isset($_POST['start']))
			$start = $_POST['start'];
		else
			$start = '';	
		
		if(!is_numeric($start)) $start = 0;
		if(isset($_POST['next'])) $start += $limit;
		if(isset($_POST['perv'])) $start -= $limit;
		if($start < 0) $start = 0;
		
		$show_id = isset($_POST['show_id']);
		$show_list = isset($_POST['show_list']);
		$show_unconf = isset($_POST['show_unconf']);
		
		
		echo '<form method="post" action="admin.php?page=post_notification/admin.php&action=' . $_GET['action'] . '"> ';
		echo __('Email:', 'post_notification') . ' <input name="email" type="text" size="30" value="' . $email . '"> ';
		echo __('Posts of Cats:', 'post_notification') . ' <select name="post_cats[]" multiple="multiple"> ';
			$cats = get_categories();
			$walker = new Walker_pn_CategoryDropdown;
			echo call_user_func_array(array(&$walker, 'walk'), array($cats, 0, $sel_post_cats));
		echo '</select> ';
		echo __('Posts of Cats:', 'post_notification') . ' <select name="com_cats[]" multiple="multiple"> ';
			$cats = get_categories();
			$walker = new Walker_pn_CategoryDropdown;
			echo call_user_func_array(array(&$walker, 'walk'), array($cats, 0, $sel_com_cats));
		echo '</select> ';
		echo __('Limit:', 'post_notification') . ' <input name="limit" type="text" size="4" value="' . $limit. '" /> ';
		echo __('Start at:', 'post_notification') . ' <input name="start" type="text" size="4" value="' . $start . '" /><br  /> ';
		echo __('Show unconfirmed mails:', 'post_notification') . ' <input name="show_unconf" type="checkbox" ';
		if($show_unconf) echo ' checked = "checked" ';
		echo '/><br /> ';
		echo __('Only show cat ids:', 'post_notification') . ' <input name="show_id" type="checkbox" ';
		if($show_id) echo ' checked = "checked" ';
		echo '/><br/> ';	
		echo __('Show as list:', 'post_notification') . ' <input name="show_list" type="checkbox" ';
		if($show_list) echo ' checked = "checked" ';
		echo '/> ';
		
		?></select><br />
		<input type="submit" name="submit" value="<?php _e('Update', 'post_notification');?>" /><input type="submit" name="perv" value="<<--" /><input type="submit" name="next" value="-->>" />
		<form>
		<?php
		
		
		///Ok, now let's do some work.
		
		if($remove) echo '<form method="post" action="admin.php?page=post_notification/admin.php&action=remove_email">';
		
		//Lets assemble the query
		/// \todo Replace with group_concat in the future	
		
		
		$sqlfrom = " FROM $t_emails e ";
		$sqlwhere = ' WHERE email_addr LIKE\'' . str_replace('*', '%', $email) . '\' ';
		
		if(Count($sel_post_cats)){
			$sqlfrom .= "LEFT JOIN $t_subs pCat ON (pCat.email_id = e.id AND pCat.obj_type = 0)";
			$sqlwhere .= ' AND pCat.obj_id IN (' . implode(', ', $sel_post_cats)  . ') ';
		}
		if(Count($sel_com_cats)){
			$sqlfrom .= " LEFT JOIN $t_subs cCat ON (cCat.email_id = e.id AND cCat.obj_type = 1) ";
			$sqlwhere .= ' AND cCat.obj_id IN (' . implode(', ', $sel_com_cats)  . ') ';
		}
		
		
		$sqlwhere .= ($show_unconf)? ' AND (gets_mail IS NULL OR gets_mail = 0) ' : ' AND gets_mail = 1 ';
		
		$sql = $sqlfrom . $sqlwhere;
		
		$emails = $wpdb->get_results("SELECT e.* $sql GROUP BY e.id LIMIT $start, $limit");
		$total  = $wpdb->get_var("SELECT COUNT(DISTINCT e.id)  $sql");

		
		if (!$emails) {
			echo '<p class="error">' . __('No entries found!', 'post_notification') . '</p>';
			echo '</div>';
			return;
		}
		echo '<p>';
		echo str_replace(	array('@@start', '@@end', '@@total'),
							array($start, $start + count($emails), $total),
							__('Showing entry @@start to @@end of @@total entries.', 'post_notification'));
		echo '</p>';
		if(!$show_list){
			echo '<table><tr>';
			if($remove)
				echo '<td width="20"><b>&nbsp;</b></td>';
				
			echo '<td><b>' . __('Address', 'post_notification') . '</b></td>
				<td ><b>' . __('Accepted', 'post_notification') . '</b></td>
				<td><b>' . __('Date accepted', 'post_notification') . '</b></td>
				<td><b>' . __('Subscribed post of cat', 'post_notification') . '</b></td>
				<td><b>' . __('Subscribed comments of cat', 'post_notification') . '</b></td>
				<td><b>' . __('Settings', 'post_notification') . '</b></td>
				<td><b>' . __('IP', 'post_notification') . '</b></td> 
				</tr>';
		} else {
			echo '<br /><br />'	;
		}
			
		foreach($emails as $email) {
			$email_addr = $email->email_addr;
			$gets_mail = $email->gets_mail;
			$last_modified = $email->last_modified;
			$datestr = get_option('date_format') . ' ' . get_option('time_format');
			$date_subscribed = post_notification_date_i18n_tz($datestr, post_notification_mysql2gmdate($email->date_subscribed));
			$id = $email->id;
			$ip = long2ip($email->subscribe_ip);
			
			if ($gets_mail == "1"){
				$gets_mail = __('Yes', 'post_notification');
			} else {
				$gets_mail = __('No', 'post_notification');
			}
			
			$modlink = post_notification_get_mailurl($email->email_addr, $email->act_code);
			
			
			$subs = $wpdb->get_results("SELECT obj_type, obj_id FROM $t_subs  WHERE email_id = " . $id . " ORDER BY obj_type, obj_id ASC");
			$pCats = '';
			$cCats = '';
			if(isset($subs)){
				foreach($subs as $sub){
					if($sub->obj_type == 0){
						$aStr = &$pCats;
					} elseif($sub->obj_type == 1) {
						$aStr = &$cCats;
					} else {
						continue;
					}
					$cat = $sub->obj_id;
					
					if($cat == 0){
						if($show_id){
							$aStr .= '<abbr title="' . __('All', 'post_notification') . '">0</abbr>, ';
						} else {
							$aStr .=  __('All', 'post_notification') . ', ';
						}		
					} else {
						$cat = get_category($cat); //ID -> Object
						if($show_id){
							$aStr .= '<abbr title="' . $cat->cat_name . '">' . $cat->cat_ID . '</abbr>, ' ;
						} else {
							$aStr .= $cat->cat_name . ', ';
						}
					}
					
				}
				$pCats =  substr($pCats, 0, -2);
				$cCats =  substr($cCats, 0, -2);
				
			}
			$settings = '';
			
			if($email->send_type == 0) $settings .= __('Text', 'post_notification'). ', ';
			else $settings .= __('HTML', 'post_notification') . ', ';
			
			
			$minute = $email->send_datum;
			$day = floor($minute / (60* 24));
			$minute %= 60 * 24;
			$hour = floor($minute / (60));
			$minute %= 60;
			if($email->send_type == 0){
				$settings .= __('imedeately', 'post_notification');
			} else{
				if($email->send_type == 1){
					$settings .= __('dayly at');
				}
				if($email->send_type == 2){
					global $wp_locale;
					$settings .= __('weekly at') . $wp_locale->get_weekday($day);
				}
				$settings .= " $hour:" .sprintf('%02d',$minute). ", ";
			}
			
			if(!$show_list){
				echo "<tr>";
				if($remove)
					echo "<td><input type=\"checkbox\" name=\"removeEmail[]\" value=\"$email_addr\" /></td>";
				echo "<td><a href=\"$modlink\" target=\"_blank\">$email_addr<a></td>";
				echo "<td>$gets_mail</td>";
				echo "<td>$date_subscribed</td>";
				echo "<td>$pCats</td>";
				echo "<td>$cCats</td>";
				echo "<td>$settings</td>";
				echo "<td>$ip</td>";
				echo "</tr>";
			} else {
				echo $email_addr . '<br/>';
			}
		}
		echo "</table>";
		if($remove){
			?>
			<script type="text/javascript">
			function post_notification_checkall(value){
				boxes = document.getElementsByName("removeEmail[]");
				for(i = 0; i < boxes.length; i++){
					boxes[i].checked = value;
				}
			}
			</script>
			
			<?php
			echo '<br />'.
			'<input type="button" onclick="post_notification_checkall(true)"  value="'.  __('Check all', 'post_notification') . '" />' . 
			'<input type="button" onclick="post_notification_checkall(false)" value="'.  __('Uncheck all', 'post_notification') . '" />' .
			
			'<br /> <input type="submit" name="removeEmailChecked" value="' . __('Delete', 'post_notification') . '"></form>';
		}
		
	}
		
	

} 
?>