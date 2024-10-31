<?php

#------------------------------------------------------
# INFO
#------------------------------------------------------
# This is part of the Post Notification Plugin for 
# Wordpress. Please see the Readme2.txt for details.
#------------------------------------------------------



function post_notification_get_post($id, $html){
	
	//Get the post
			
	$post = get_post($id);
	$rv['type'] = 0;
	$rv['id'] = $id;
	$rv['url'] = get_permalink($post->ID);
	$auth = get_userdata($post->post_author);
	$rv['author'] = $auth->display_name;
	$rv['title'] = strip_tags($post->post_title);
	$post_date = $post_time = 0;
	post_notification_conv_date($post->post_date_gmt, $post_date, $post_time, $html);
	$rv['gmt'] = $post->post_date_gmt;
	$rv['date'] = $post_date;
	$rv['time'] = $post_time;
	

	if(get_option('post_notification_show_content') == 'yes'){
		$post_content = stripslashes($post->post_content);
	}else if(get_option('post_notification_show_content') == 'more'){
		$post_content = stripslashes($post->post_content);
		list($post_content, $more_content) = split('<!--more', $post_content);
		if($more_content){
			$post_content .= '<a href="@@permalink" >'. get_option('post_notification_read_more') . '</a>' ;
		}
	}else if(get_option('post_notification_show_content') == 'excerpt'){
		$post_content = stripslashes($post->post_excerpt);
		$post_content .= '<br /><a href="@@permalink" >'. get_option('post_notification_read_more') . '</a>' ;
	}
	
	// Run filters over the post
	if($post_content){
		
		//Remove unwanted Filters
		$rem_filters = unserialize(get_option('post_notification_the_content_exclude'));
		
		foreach($rem_filters as $rem_filter){
			remove_filter('the_content', $rem_filter );
		}
		
		if(!$html){
			remove_filter('the_content', 'convert_smilies' ); //We defenetly don't want smilie - Imgs in Text-Mails.
		}
		$post_content = apply_filters('the_content', $post_content);
	}
	
	

	//Convert from HTML to text.
	if (!$html && isset($post_content)){
		require_once(POST_NOTIFICATION_PATH  . 'class.html2text.php');
		$h2t =& new html2text($post_content);
		$post_content = $h2t->get_text();
	}
	$rv['content'] = $post_content;
	return $rv; 
	
}


function post_notification_get_comment($id, $html){
		//Get the post
	$com = get_comment($id);
	$rv['type'] = 1;
	
	$rv['id'] = $id;
	/// \todo Make link directly to comment	
	$rv['url'] = get_permalink($com->comment_post_ID) . "#comment-" . $id;

	
	$rv['author'] = $com->comment_author;
	$rv['content'] = $com->comment_content;
	
	//Get the post, to get the title
	$post = get_post($com->comment_post_ID);
	$rv['post_title'] = strip_tags($post->post_title);
	$auth = get_userdata($post->post_author);
	$rv['post_author'] = $auth->display_name;
	$rv['post_url'] = get_permalink($post->ID);
	$rv['post_id'] = $post->ID;

	//Get date
	$date =  $time = 0;
	post_notification_conv_date($com->comment_date_gmt,  $date,  $time, $html);
	$rv['gmt'] = $com->comment_date_gmt;
	$rv['date'] = $date;
	$rv['time'] = $time;
	


	//Convert from HTML to text.
	if ($html){
		require_once(POST_NOTIFICATION_PATH  . 'class.html2text.php');
		$h2t =& new html2text($rv['content']); /// \todo make global to avoid recreation
		$rv['content'] = $h2t->get_text();
	}
	
	return $rv;
}


function post_notification_conv_date($tdate,  &$date,  &$time, $html){
	$date = mysql2date(get_option('date_format'), $tdate);
	$time = mysql2date(get_option('time_format'), $tdate);		

	//mysql2date returns a string with HTML-Entities. These must be removed.
	if(!$html){
		if(get_option('post_notification_debug') == 'yes') echo 'Date1: ' . htmlspecialchars($date) . '<br />';
		//html_entity_decode does not support UTF-8 in php < 5
		//We therefore have to convert it to ISO8859-1 first.
		if(function_exists('iconv') && (strpos(phpversion(), '4') == 0)){ 
			$time = (($temp = iconv(get_option('blog_charset'), 'ISO8859-1', $time)) != "") ? $temp : $time;
			$date = (($temp = iconv(get_option('blog_charset'), 'ISO8859-1', $date)) != "") ? $temp : $date;
			
			
		}
		if(get_option('post_notification_debug') == 'yes') echo 'Date2: ' . htmlspecialchars($date) . '<br />';	
		
		
		$time = @html_entity_decode($time,ENT_QUOTES,get_option('blog_charset'));
		$date = @html_entity_decode($date,ENT_QUOTES,get_option('blog_charset'));
		if(get_option('post_notification_debug') == 'yes') echo 'Date3: ' . htmlspecialchars($date) . '<br />';
		
		//Now it can be converted back to the correct charset.
		if(function_exists('iconv') && (strpos(phpversion(), '4') == 0)){ 
			$time =(($temp = iconv('ISO8859-1', get_option('blog_charset'), $time)) != "")? $temp : $time;
			$date =(($temp = iconv('ISO8859-1', get_option('blog_charset'), $date)) != "")? $temp : $date;
		}		
		if(get_option('post_notification_debug') == 'yes') echo 'Date4: ' . htmlspecialchars($date) . '<br />';
	}
}


function post_notification_prepare_post($id, $html){
	
	$post =  post_notification_get_post($id, $html);
	
	// Load template
	$rv = post_notification_ldfile('mail_post' . (($html)?'.html':'.txt'));
	$body    = &$rv['body'];
	$subject = &$rv['subject'];
	
	if(get_option('post_notification_debug') == 'yes'){
		
		echo "Email variables: <br /><table>";
		echo '<tr><td>Emailtype</td><td>' . (($html) ? 'HTML' : 'TEXT')  . '</td>'; 
		echo '<tr><td>@@title</td><td>' .				$post['title'] . '</td></tr>';
		echo '<tr><td>@@permalink</td><td>' . 	$post['url'] . '</td></tr>';
		echo '<tr><td>@@author</td><td>' . 			$post['author'] . '</td></tr>';
		echo '<tr><td>@@time</td><td>' . 			$post['time'] . '</td></tr>';
		echo '<tr><td>@@date</td><td>' .			$post['date'] .'</td></tr>';
		echo "</table>";
	}
	
	// Replace variables	
	$body = str_replace('@@content',$post['content'],$body); //Insert the posting first. -> for Replacements
	$body = str_replace('@@title',$post['title'],$body);
	$body = str_replace('@@permalink',$post['url'],$body);
	$body = str_replace('@@author',$post['author'],$body);
	$body = str_replace('@@time',$post['time'],$body);
	$body = str_replace('@@date',$post['date'],$body);
	
	// User replacements
	if(function_exists('post_notification_uf_perPost')){
		$body = post_notification_arrayreplace($body, post_notification_uf_perPost($id));
	}	
		
	
	// SUBJECT 
	$subject = str_replace('@@title', $post['title'] , $subject);
	$subject = str_replace('@@author',$post['author'],$subject);

	$subject = post_notification_encode($subject, get_option('blog_charset') );		
	

	$rv['id'] = $id;
	$rv['header'] = post_notification_header($html);
	return $rv;

}


function post_notification_prepare_comment($id, $html){
	
	$com = post_notification_get_comment($id, $html);
	
	// Load template

	$rv = post_notification_ldfile('mail_comment' . (($html)?'.html':'.txt'));
	$body    = &$rv['body'];
	$subject = &$rv['subject'];
	

	
	if(get_option('post_notification_debug') == 'yes'){
		
		echo "Email variables: <br /><table>";
		echo '<tr><td>Emailtype</td><td>' . (($html) ? 'HTML' : 'TEXT')  . '</td>'; 
		echo '<tr><td>@@author</td><td>' .          $com['author'] . '</td></tr>';
		echo '<tr><td>@@time</td><td>' . 			$com['time'] . '</td></tr>';
		echo '<tr><td>@@date</td><td>' .			$com['date'] .'</td></tr>';
		echo '<tr><td>@@title</td><td>' .			$com['post_title'] .'</td></tr>';
		echo '<tr><td>@@post_id</td><td>' .			$com['post_id'] .'</td></tr>';
		echo '<tr><td>@@post_author</td><td>' .		$com['post_author'] .'</td></tr>';
		echo '<tr><td>@@post_url</td><td>' .		$com['post_url'] .'</td></tr>';
		echo "</table>";
	}
	
	// Replace variables						
	$body = str_replace('@@content',$com['content'],$body); //Insert the posting first. -> for Replacements
	$body = str_replace('@@permalink',$com['url'],$body);
	$body = str_replace('@@author',$com['author'],$body);
	$body = str_replace('@@time',$com['time'],$body);
	$body = str_replace('@@date',$com['date'],$body);
	$body = str_replace('@@post_title', $com['post_title'], $body);
	$body = str_replace('@@post_id', $com['post_id'], $body);
	$body = str_replace('@@post_author', $com['post_author'], $body);
	$body = str_replace('@@post_url', $com['post_url'], $body);
	
	// User replacements
	if(function_exists('post_notification_uf_perComment')){
		$body = post_notification_arrayreplace($body, post_notification_uf_perComment($id));
	}	
		
	
	// SUBJECT 
	$subject = str_replace('@@author', $com['author'], $subject);
	$subject = str_replace('@@title', $com['post_title'], $subject);
	$subject = str_replace('@@time',$com['time'],$subject);
	$subject = str_replace('@@date',$com['date'],$subject);
	$subject = str_replace('@@post_title', $com['post_title'], $subject);
	$subject = str_replace('@@post_author', $com['post_author'], $subject);
	$subject = str_replace('@@post_url', $com['post_url'], $subject);
	
	
	$rv['id'] = $id;
	$rv['header'] = post_notification_header($html);
	return $rv;

}

function post_notification_sort_post($a, $b){
	$a_pid = ($a['type'] == 0) ? $a['id'] : $a['post_id'];
	$b_pid = ($b['type'] == 0) ? $b['id'] : $b['post_id'];
	if($a_pid == $b_pid) return 0;
	return ($a_pid < $b_pid) ? -1 : 1;
	
}


/**
 * This is a containerfunction to keep things clean
 */
function post_notification_assemble_digest($objs, $numposts, $numcomments, $format){
	$sort_post = 'post_notification_sort_post';
	/*echo "<pre>";
	var_dump($objs);
	echo "</pre>";*/
	ob_start();
	//Ausführen
	$blogname = get_option('blogname');
	$subject = $blogname;
	
	if($format == 0) include(post_notification_get_profile_dir(). '/digest_text.php');
	else include(post_notification_get_profile_dir(). '/digest_html.php');
	
	$maildata['body'] = ob_get_clean();
	$maildata['subject'] = $subject;
	// No ob_end is needed after ob_get_clean!
	return $maildata;
	
}




function post_notification_prepare_login($id, $html){
	$blogname = get_option('blogname');
		
	// Load template
	$rv = post_notification_ldfile('mail_login' . (($html)?'.html':'.txt'));
	
	$rv['id'] = $id;
	$rv['header'] = post_notification_header($html);
	return $rv;

}



function post_notification_sendmail($maildata, $addr, $code = '', $send = true){
		$maildata['body'] = str_replace('@@addr',$email->email_addr,$maildata['body']);
		
		$conf_url = post_notification_get_mailurl($addr, $code);

		
		$maildata['body'] = str_replace('@@unsub', $conf_url, $maildata['body']);
		$maildata['body'] = str_replace('@@conf_url', $conf_url, $maildata['body']);
		//User replacements
		if(function_exists('post_notification_uf_perEmail')){
			$maildata['body'] = post_notification_arrayreplace($maildata['body'], post_notification_uf_perEmail($maildata['id'], $addr));
		}	
		
		if($send){ //for debugging
			$success = wp_mail($addr, $maildata['subject'], $maildata['body'], $maildata['header']);
		}
		if(!$success){
			return false;
		} else {
			return $maildata;
		}
}

/**
 * Add Mail to queue
 *
 * @param int $id The ID
 * @param int $type 0 = a post; 1 = a comment
 */
function post_notification_add_to_queue($id, $type){
	global $wpdb;
	
	
	$t_emails = $wpdb->prefix . 'post_notification_emails';
	$t_posts = $wpdb->prefix . 'post_notification_posts';
	$t_queue = $wpdb->prefix . 'post_notification_queue';
	$t_subs = $wpdb->prefix . 'post_notification_subs';
	
	
	if($type == 0){
		$post_id = $id;
	} else {
		$com = get_comment($id);
		$post_id = $com->comment_post_ID;
	}
	
	$cat_ids = implode(", ", post_notification_get_cats_of_post($post_id)); //convert to string
	/*if(get_option('post_notification_debug') == 'yes'){
		echo 'The cat-Ids are: ' . $cat_ids. '<br />';
	}*/

	
	/* Add by subscribed cats
	 *
	 * This query is quite tricky. The main trick is the left join:
	 * First s.objid must be $id and not e.objid. This wouldn't work for categories as theses
	 * are not the same as the actual object.
	 * Secondly q.state must be 0. If a match is found this means there is already an email queued
	 * but not sent yet. If this is the case the q.state IS NULL check will fail.
	 * 
	 * -> Important: It _must_ be grouped by q.email_id and NOT by e.id. Otherwise there will be
	 * duplicate entries in the db due to inserting data while reading it.
	 * Note: Strictly it's not allowed to query a table which is written.
	 */
	 
	$wpdb->query(
		"INSERT INTO $t_queue (email_id, obj_id, state, type, date)
			SELECT e.id, " . $id . ", 0, $type ,'" . post_notification_date2mysql() ."'
			FROM $t_emails e JOIN $t_subs s ON (e.id = s.email_id)
				LEFT JOIN $t_queue q ON(e.id = q.email_id AND q.obj_id = $id AND s.obj_type = q.type AND q.state = 0)
			WHERE s.obj_id IN ($cat_ids) AND e.gets_mail = 1 AND s.obj_type = $type  AND q.state IS NULL
			GROUP BY q.email_id ");
	

	//Add commets for direct subscription of posts
	if($type == 1){
		// Almost the same query as before.
		$wpdb->query(
		"INSERT INTO $t_queue (email_id, obj_id, state, type, date)
			SELECT e.id, " . $id . ", 0, $type ,'" . post_notification_date2mysql() ."'
			FROM $t_emails e JOIN $t_subs s ON (e.id = s.email_id)
				LEFT JOIN $t_queue q ON(e.id = q.email_id AND q.obj_id = s.obj_id  AND q.state = 0)
			WHERE s.obj_id = $post_id AND e.gets_mail = 1 AND s.obj_type = 2  AND q.type = 1 AND q.state IS NULL 
			GROUP BY q.email_id ");
	}

}



/**
 * Moves Emails from the post's table to the queue table.
 * Make sure that everything is locked before doing so.
 *
 */
function post_notification_move_post_to_queue(){
	global $wpdb;
	$t_emails = $wpdb->prefix . 'post_notification_emails';
	$t_posts = $wpdb->prefix . 'post_notification_posts';
	$t_queue = $wpdb->prefix . 'post_notification_queue';
	
	//See if there is anythig to do;
	//if(get_option('post_notification_nextmove') == -1) return;
	//if(get_option('post_notification_nextmove') > time()) return;
	// \todo: Get this working.
	
	$posts = $wpdb->get_results("SELECT id " . post_notification_sql_posts_to_send());
	//var_dump($posts);
	if(!$posts){ //There's nothing to move.
		post_notification_set_next_move();
		return; 
	}
	/*
	 * Unfortunately recurson isn't supported my mySQL. Therefore some things must be a little
	 * more complicated.
	 * The next's steps are
	 * Get the posts wich are to be sent
	 * Get the categories from taxonomies
	 * Get the emails which subsribed these categories
	 * Add the emails to the queue; 
	 */
	
	//Get Posts to send
	$queued_posts = array();
	foreach($posts as $post) {
		echo "Adding post to queue: {$post->id}<br/>";
		post_notification_add_to_queue($post->id, 0);
		$queued_posts[] = $post->id;
	
	}
	$wpdb->query("UPDATE $t_posts SET notification_sent = 1 WHERE post_id IN (" . implode(", ",$queued_posts) .')' );
	
	update_option('post_notification_emptyqueue', 0); //queue isn't empty aqnymore...
	post_notification_set_next_move();
}


/**
 * Look which gets mails from the database and sends them off.
 *
 * @param datetime $endtime When to stop sending
 * @param int $maxsend Number of mails to send
 * @param int $type Which type of mails to send.
 * @return unknown
 */
function post_notification_sendloop($endtime, &$maxsend, $type){
	global $wpdb;
	
	if(get_option('post_notification_debug') == 'yes'){
		if($type == 0) echo "Looking for posts<br/>";
		if($type == 1) echo "Looking for comments<br/>";
		if($type == 2) echo "Looking for login-msgs<br/>";
	}
	
	$t_emails = $wpdb->prefix . 'post_notification_emails';
	$t_queue = $wpdb->prefix . 'post_notification_queue';
	
	if($type == 0){
		$type_name = "post";
		$emails = $wpdb->get_results("SELECT idx, email_addr, obj_id, act_code, email_id, format
				FROM $t_queue q JOIN $t_emails e ON q.email_id = e.id
				WHERE q.type = 0 AND state = 0 AND e.send_type = 0
				ORDER BY q.obj_id, e.format");
	}
	
	if($type == 1){
		$type_name = "comment";
		$emails = $wpdb->get_results("SELECT idx, email_addr, obj_id, act_code, email_id, format
				FROM $t_queue q JOIN $t_emails e ON q.email_id = e.id
				WHERE q.type = 1 AND state = 0 AND e.send_type = 0
				ORDER BY q.obj_id, e.format");
	}
	
	if($type == 2){
		$type_name = "login";
		$emails = $wpdb->get_results("SELECT idx, email_addr, obj_id, act_code, email_id, format
				FROM $t_queue q JOIN $t_emails e ON q.email_id = e.id
				WHERE q.type = 2 AND state = 0
				ORDER BY q.obj_id");
	}
	
	
	$prepared_obj = 0;
	$prepared_obj_type = -1; 
	foreach($emails as $email) {

		if($endtime != 0 ){ //if this is 0 we have as much time as we want.			
			$time_remain = $endtime - time();
		}
		
		$maxsend--;
		if($maxsend < 1 || $time_remain < 0){ //Are we allowed to send any more mails?
			break;
		}
		
		
		//Prepare email if needed
		if($prepared_obj != $email->obj_id|| $prepared_obj_type != $email->eformat){ //We need to generate the post
			if(get_option('post_notification_debug') == 'yes'){
				echo "Preparing $type_name: " . $email->obj_id . '<br />';
			}
			$html = ($email->eformat = 0)?false:true; 
			$maildata = call_user_func("post_notification_prepare_" . $type_name , $email->obj_id, $html);
			$prepared_obj = $email->obj_id;
			$prepared_obj_type = $email->eformat;
		}

		if(get_option('post_notification_debug') == 'yes'){
			echo " Sending $type_name  to: " . $email->email_addr . '<br />';
		}
		
		if(post_notification_sendmail($maildata, $email->email_addr, $email->act_code) == false){
			$sendstate = -1;
		} else {
			$sendstate = 1;
		}
		
		
		$wpdb->query(	
			" UPDATE $t_queue " .
			" SET state = $sendstate, date = '" . post_notification_date2mysql() ."'" .
			" WHERE idx = {$email->idx}");
				

	}	
	if(!each($emails)){ //Each returns false in case we are at the end of the queue;
		return 1;
	} else {
		return 0;
	}
}

function post_notification_digest_loop($endtime, &$maxsend){
	global $wpdb ;
	$t_emails = $wpdb->prefix . 'post_notification_emails';
	$t_queue = $wpdb->prefix . 'post_notification_queue';
	if(get_option('post_notification_debug') == 'yes'){
		echo "Checking for digests <br/>";
	}
	 
	//Calculate intervall
	$last_digest = (int)get_option('post_notification_last_digest');
	$time = getdate();
	$this_digest = $time['wday'] * 24 * 60 + $time['hours'] * 60 + $time['minutes'];
	
	$last_time = $last_digest % (24*60);
	$this_time = $this_digest % (24* 60);
	if($last_time < $this_time){
		$q_time = "(e.send_type = 1 AND  e.send_datum > $last_time AND e.send_datum < $this_time )";
	} else {
		$q_time = "(e.send_type = 1 AND  (e.send_datum < $last_time OR e.send_datum > $this_time) )";
	}
	
	if($last_digest < $this_digest){
		$q_day= "(e.send_type = 2 AND  e.send_datum > $last_digest AND e.send_datum < $this_digest )";
	} else {
		$q_day= "(e.send_type = 2 AND  (e.send_datum < $last_digest OR e.send_datum > $this_digest) )";
	}
	
	
	$emails = $wpdb->get_results("SELECT idx, email_addr, obj_id, act_code, email_id, format, type" .
			" FROM $t_queue q JOIN $t_emails e ON q.email_id = e.id" .
			" WHERE state = 0 AND" .
			"($q_time OR $q_day ) ".
			" ORDER BY e.id, obj_id, type");

	$numposts = 0;
	$numcomments = 0;
	$objs = array();
	$sent_idx = array();
	
	for($index = 0; $index < count($emails); $index++) {
		$email = &$emails[$index];
		if($email->type == 0){
			//It's a post
			echo "Adding post {$email->obj_id}<br/>";
			$objs[] = post_notification_get_post($email->obj_id, $email->format);
			$sent_idx[] = $email->idx;
			$numposts ++;
			
		} else if($email->type == 1){
			echo "Adding comment  {$email->obj_id}<br/>";
			$objs[]  = post_notification_get_comment($email->obj_id, $email->format);
			$sent_idx[] = $email->idx;
			$numcomments++;
		} else {
			if(get_option('post_notification_debug') == 'yes'){
				echo "Adding nothing<br/>";
			}
		}
		
		
		//If this is the last element or the next element is to someone else: Send mail
		if($index + 1 == count($emails) || $email->email_id != $emails[$index+1]->email_id){		
			if(get_option('post_notification_debug') == 'yes'){
				echo "Assembling mail to {$email->email_addr}<br/>";
			}

			$maildata = post_notification_assemble_digest_text($objs, $numposts, $numcomments, $email->format);
			
			if(post_notification_sendmail($maildata, $email->email_addr, $email->act_code) == false){
				$sendstate = -1;
			} else {
				$sendstate = 1;
			}
			//if mail is sent!
			if($endtime != 0 ){ //if this is 0 we have as much time as we want.			
				$time_remain = $endtime - time();
			}
			
			$maxsend--;
			if($maxsend < 1 || $time_remain < 0){ //Are we allowed to send any more mails?
				break;
			}
			$idxlist = implode(', ', $sent_idx);

			echo " UPDATE $t_queue " .
				" SET state = $sendstate, date = '" . post_notification_date2mysql() ."'" .
				" WHERE idx IN ($idxlist)";
			$wpdb->query(" UPDATE $t_queue " .
				" SET state = $sendstate, date = '" . post_notification_date2mysql() ."'" .
				" WHERE idx IN ($idxlist)");	
				
			$objs = array();	
			$numposts = 0;
			$numcomments = 0;				
				
		}
			
	}		
}

/// Send mails.
function post_notification_send() {
	global $wpdb, $timestart;
	
	
	//Lock to make sure that we don't run into two instances.
	if(get_option('post_notification_lock') == 'db'){
		if(!$wpdb->get_var("SELECT GET_LOCK('" . $wpdb->prefix . 'post_notification_lock' . "', 0)")) return;
	} else {
		$mutex = @fopen(POST_NOTIFICATION_PATH . '_temp/post_notification.lock', 'w');
		
		$eWouldBlock = 0;
		if ( !$mutex || !flock($mutex, LOCK_EX|LOCK_NB, $eWouldBlock) || $eWouldBlock ) {
			// There is already someone mailing. 
			@fclose($mutex);
			return;
		}
	}
	
	//Make sure plugins don't think we're a page or something....
	
	$GLOBALS['wp_query']->init_query_flags();
	
	ignore_user_abort(true); //Let's get this done....
	
	//Move Mails to queue
	post_notification_move_post_to_queue();


	
	$t_emails = $wpdb->prefix . 'post_notification_emails';
	$t_posts = $wpdb->prefix . 'post_notification_posts';
	$t_cats = $wpdb->prefix . 'post_notification_cats';
	$t_queue = $wpdb->prefix . 'post_notification_queue';

	//Include user functions, if they exist.
	//We don't want an error if the file does not exist.
	if(file_exists(POST_NOTIFICATION_PATH . 'userfunctions.php'))
		include_once(POST_NOTIFICATION_PATH . 'userfunctions.php'); 

	
	// Prepare sending out mails
	$maxsend = get_option('post_notification_maxsend');
	$mailssent = -1;
	
	//Make sure we will have a least 5 sek left.
	$endtime = ini_get('max_execution_time');
	if($endtime != 0){
		$endtime +=  floor($timestart) -5; 
	}
	$time_remain = 1;
	
	
	
	$emptyqueue = 0;
	if(
		   post_notification_sendloop($endtime, &$maxsend, 2)//send out subscription first
		&& post_notification_sendloop($endtime, &$maxsend, 0)//posts
		&& post_notification_sendloop($endtime, &$maxsend, 1)//comments
		&& post_notification_digest_loop($endtime, &$maxsend)//digest
		)$emptyqueue = 1;
	
	
	
	update_option('post_notification_emptyqueue', $emptyqueue);
	
	update_option('post_notification_lastsend', time());
	
	//Free locks for another session.
	if(get_option('post_notification_lock') == 'db'){
		$wpdb->query("SELECT RELEASE_LOCK('" . $wpdb->prefix . 'post_notification_lock' . "')");
	} else {
		flock($mutex, LOCK_UN);
		fclose($mutex);
	}
}


?>