<?php
#------------------------------------------------------
# INFO
#------------------------------------------------------
# This is part of the Post Notification Plugin for 
# Wordpress. Please see the Readme2.txt for details.
#------------------------------------------------------

function post_notification_vardump($var){
	echo '<pre>';
	var_dump($var);
	echo '</pre>';
	
}

function post_notification_arrayreplace($input, $array){
	
	foreach($array as $s => $r){
		$input = str_replace($s, $r, $input);
	}
	return $input;
}

/**
 * Returns the directory of the current profile
 * Can corrent setting can be overriden by setting the global
 * $post_notification_profile_override
 * @return The directory of the current profile
 */
function post_notification_get_profile_dir(){
	global $post_notification_profile_override;
	
	
	if(isset($post_notification_profile_override) && $post_notification_profile_override != ''){
		$profile = $post_notification_profile_override;
	} else {
		$profile = get_option('post_notification_profile');
	}
	
	
	
	
	$dir = POST_NOTIFICATION_PATH . $profile ;
	if(file_exists($dir)) return $dir;
	
	$dir = POST_NOTIFICATION_DATA . $profile ;
	if(file_exists($dir)) return $dir;
	return false; 
}

function post_notification_mysql2gmdate($mysqlstring) {
	if (empty($mysqlstring)) return false;
		
	return gmmktime(
		(int) substr($mysqlstring, 11, 2), 
		(int) substr($mysqlstring, 14, 2), 
		(int) substr( $mysqlstring, 17, 2),
		(int) substr($mysqlstring,  5, 2), 
		(int) substr($mysqlstring,  8, 2), 
		(int) substr( $mysqlstring,  0, 4) );
}

function post_notification_date2mysql($unixtimestamp = 0) {
	if($unixtimestamp == 0){
		return gmdate('Y-m-d H:i:s');
	} else {
		return gmdate('Y-m-d H:i:s', $unixtimestamp);
	}
}


/**
 * This function returns the SQL-statement to select all the posts which are to be sent.
 * @param future Also list posts published in the future
 */  
function post_notification_sql_posts_to_send($future = false){
	global $wpdb;
	if(!$future){
		$add_where = "AND GREATEST(p.post_date_gmt, el.date_saved) < '" .  post_notification_date2mysql( (time() - get_option('post_notification_nervous'))) . "' ";
	} else {
		$add_where = '';
	}
	$t_posts = $wpdb->prefix . 'post_notification_posts';
	if(get_option('db_version')< 4772){
		return " FROM $wpdb->posts p, $t_posts el " .
				"WHERE( ".
				"el.notification_sent = 0 AND " .
				"p.id = el.post_id AND " .
				"p.post_status IN('publish', 'static' , 'private') ".
				"$add_where)";
	} else {
		return " FROM $wpdb->posts p, $t_posts el " .
			"WHERE( ".
			"el.notification_sent = 0 AND " .
			"p.id = el.post_id AND " .
			"p.post_status IN('publish', 'private', 'future') ".
			"$add_where)";
	}
}

/// returns a link to the Post Notification Page.
function post_notification_get_link(){
	$url = get_option('post_notification_url');
	if(is_numeric($url)) $url = get_permalink($url);
	
	if(strpos($url, '/?') || strpos($url, 'index.php?')){
		$url .= '&';
	} else {
		$url .= '?';
	}
	return $url;
}


///Load file from profile and do standard Replacement
function post_notification_ldfile($file){
	//todo: Override PRofile
	
	$data = file_get_contents(post_notification_get_profile_dir(). '/' . $file);

	if(function_exists('iconv') && function_exists('mb_detect_encoding')){
		$data = iconv(mb_detect_encoding($data, "UTF-8, UTF-7, ISO-8859-1, ASCII, EUC-JP,SJIS, eucJP-win, SJIS-win, JIS, ISO-2022-JP"), 
			get_option('blog_charset'), $data); //"auto" doesn't work on quite a few platforms so we have to list encodings.	
	}
	$blogname = get_option('blogname');
	$data = str_replace('@@blogname',$blogname,$data);
	$data = str_replace('@@site',$blogname,$data);

	$rv = array();
	$lineend = strpos($data, "\n");
	$rv['subject'] = substr($data,0, $lineend);
	if(substr($file, 5) == 'mail_'){
		$rv['subject'] =  post_notification_encode($rv['subject']); 
	}
	
	$rv['header'] = $rv['subject'];
	$rv['body'] = substr($data, $lineend);
	return $rv;

}

function post_notification_cats_to_str($cats, $type = 0, $all = ''){
	
	if(!is_array($cats)) return 0;
	$catnames = array();
	//var_dump($cats);
	foreach($cats as $cat){
		if($cat == 0){
			if($type == 0){
				return '<abbr title="' . $all . '">' . $cat . '</abbr>' ;
			} else {
				return $all;
			}
			
		} else {
			$cat = get_category($cat); //ID -> Object
			if($type == 0){
				$catnames[] = '<abbr title="' . $cat->cat_name . '">' . $subcat->cat_id . '</abbr>' ;
			} else {
				$catnames[] = $cat->cat_name;
			}
		}
	}
	//var_dump($catnames);
	return implode(', ', $catnames);	
	
}


/// Encode umlauts for mail headers
function post_notification_encode($in_str, $charset = '') {
	//get line break
	//See RFC 2047
	if($charset = '') $charset = get_option('blog_charset');
	
	if(get_option('post_notification_hdr_nl') == 'rn')
		$hdr_nl = "\r\n";
	else
		$hdr_nl = "\n";
		
	
	
	if(!function_exists('mb_detect_encoding')) return $in_str; //Can't do anything without mb-functions.
	
	$end = '?=';
	$start = '=?' . $charset . '?B?';
	$enc_len = strlen($start) + 2; //2 = end
	$lastbreak = 0;
	for($i=0; $i < strlen($in_str); $i++){
		if(function_exists('mb_check_encoding')){
			$isascii =mb_check_encoding($in_str[$i], 'ASCII'); 
		} else {
			$isascii = (mb_detect_encoding($in_str[$i], 'UTF-8, ISO-8859-1, ASCII') == 'ASCII');
		}
		
		//some adjustments
		if(strlen($code) > 0){
			if($in_str[$i] == ' ' || $in_str[$i] == "\t") $isascii = false;  
		}
		
		//linebreaking
		$this_line_len = strlen($out_str) + strlen($code) + $enc_len - $lastbreak; //$enc_len is needed in case a non-ascii is added
		if($this_line_len > 65 && ($in_str[$i] == ' ' || $in_str[$i] == "\t")){
			if($code != ''){ //Get rid of $code
				$out_str .= $start . base64_encode($code) . $end;
			}
			//Linebrak and space -> rfc 822.
			//In case we have $code this is no problem, as a new $code will start in the next line. -> Fail safe, little overhead
			$out_str .=  $hdr_nl . $in_str[$i]; 
			
			$code = '';
			$lastbreak += $this_line_len;
		}
		
		
		if(!$isascii){
			$code .= $in_str[$i];
		} else {
			if($code){
				$out_str .= $start . base64_encode($code) . $end;
				$code = '';
			}
			$out_str .= $in_str[$i];
		}
	}
	//We have some chars in the code-buffer we have to get rid of....
	if($code) $out_str .= $start . base64_encode($code) . $end;
	return $out_str;
}

/// Generate the Mail header
function post_notification_header($html){
	if(get_option('post_notification_hdr_nl') == 'rn')
		$hdr_nl = "\r\n";
	else
		$hdr_nl = "\n";
	
	$header  = "MIME-Version: 1.0$hdr_nl";
		
	if ($html){
		$header .= 'Content-Type: text/html; charset="' . get_option('blog_charset') . '"' . $hdr_nl;
	} else {
		$header .= 'Content-Type: text/plain; charset="' . get_option('blog_charset') . '"' . $hdr_nl;
	}

	$from_name = str_replace('@@blogname',get_option('blogname'),get_option('post_notification_from_name'));
	
	$from_name = post_notification_encode($from_name);
	
	$from_email    = get_option('post_notification_from_email');
	
	$header .= "From: \"$from_name\" <$from_email>$hdr_nl";
	$header .= "Reply-To: $from_email$hdr_nl";
	$header .= "Return-Path: $from_email$hdr_nl";
	return $header;
}

/// Install a theme
function post_notification_installtheme(){
	if(get_option('post_notification_filter_include') != 'yes'){
		$src  = POST_NOTIFICATION_PATH . 'post_notification_template.php';
		$dest = ABSPATH . 'wp-content/themes/' . get_option('template') . '/post_notification_template.php';
		if(!@file_exists($dest)){
			if(!@copy($src,$dest)){
				return $dest;
			}
		}
	}
	return '';
}

/// Calculate when the next mail need to be sent.
function post_notification_set_next_move(){
	global $wpdb;
	
	$d_next = post_notification_mysql2gmdate($wpdb->get_var("SELECT MIN(GREATEST(post_date_gmt, date_saved)) " .post_notification_sql_posts_to_send(true)));
	if($d_next){ //We do have somthing to send
		$nervous = $d_next + get_option('post_notification_nervous'); //There is no other way :-(
		$nextsend = get_option('post_notification_lastsend') + get_option('post_notification_pause');
		$d_next = max($nextsend, $nervous);	
		update_option('post_notification_nextmove', $d_next);
	} else {
		//There are no post with unsent mail.
		update_option('post_notification_nextmove', -1);
	}

}

function post_notification_get_mid($addr){
	global $wpdb;
	/// \todo: support arrays and do caching
	$t_emails = $wpdb->prefix . 'post_notification_emails';
	return $wpdb->get_var("SELECT id FROM $t_emails WHERE email_addr = '" . $addr . "'");
}

function post_notification_add_email($addr, $code = 'none'){
	global $wpdb ;
	$t_emails = $wpdb->prefix . 'post_notification_emails';
	$wpdb->query(
		"INSERT INTO $t_emails (email_addr,date_subscribed, act_code, subscribe_ip) ".
		"VALUES ('" . $wpdb->escape($addr) . "','" . post_notification_date2mysql() ."', '$code', " .
			ip2long($_SERVER['REMOTE_ADDR']) . ")");
}


/**
 * Create a link to the subscription page. 
 * @param addr The adress, which is to be used
 * @param code The Code, if available. If not it will be retrieved from the db.
 * @return the Adress.
 */

function post_notification_get_mailurl($addr, $code = ''){
	GLOBAL $wpdb;
	if(strlen($code) != 32){
		$t_emails = $wpdb->prefix . 'post_notification_emails';
		$query = $wpdb->get_results("SELECT id, act_code FROM $t_emails WHERE email_addr = '" . $wpdb->escape($addr) . "'");
		$query = $query[0];
		
		//Get Activation Code
		if (($query->id == '') || (strlen($query->act_code) != 32)) { //Reuse the code
			mt_srand((double) microtime() * 1000000);
			$code = md5(mt_rand(100000, 99999999) . time());
			if($query->id == ''){
				$ip = sprintf('%u', ip2long($_SERVER['REMOTE_ADDR']));
				if($ip < 0 || $ip===false) $ip = 0; //This has changed with php 5
				$wpdb->query(
					"INSERT INTO $t_emails (email_addr,date_subscribed, act_code, subscribe_ip) ".
					"VALUES ('" . $wpdb->escape($addr) . "','" . post_notification_date2mysql() ."', '$code', $ip  )");
			} else {
				$wpdb->query(
					"UPDATE $t_emails SET act_code = '$code' WHERE email_addr = '" . $wpdb->escape($addr) . "'");
			}
		} else {
			$code = $query->act_code;
		}
	}
	//Adjust the URL
	$confurl = post_notification_get_link();
	if(strpos($confurl, '/?') || strpos($confurl, 'index.php?')) $confurl .= '&';
	else 					   $confurl .= '?';
	$confurl .= "code=$code&addr=" . urlencode($addr) . '&'; 
	return $confurl;
}

class Walker_post_notification extends Walker {
	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'category_parent', 'id' => 'cat_ID'); //TODO: decouple this
	var $id_list = array(0);
	var $last_id = 0;
	var $pref;
	
	function start_lvl(&$output, $depth, $args) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent<ul class='children'>\n";
		$this->id_list[] = $this->last_id;
		return $output;
	}

	function end_lvl(&$output, $depth, $args) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
		array_pop($this->id_list);
		return $output;
	}
	
	
	
	function start_el(&$output, $category, $depth, $args) {
		$output .= str_repeat("\t", $depth * 3);
		$output .= "<li>";
				
		$output .= "\t" . '<input type="checkbox" name="pn_' . $this->pref . '[]" value="' .$category->cat_ID . 
				'" id="' . $this->pref . '_cat.'   . implode('.', $this->id_list) . '.' . $category->cat_ID. '" ';
		if ( in_array($category->cat_ID, $args, true)) $output .= ' checked="checked"'; 
		$output .= ' onclick = "post_notification_cats_init(\'' . $this->pref . '\')" />';
		
		$output .= apply_filters('list_cats', $category->cat_name, $category);


		$output .= "</li>\n";
		$this->last_id = $category->cat_ID;
		return $output;
	}
}

/**
 * Return everything needed for selecting the cats.
 * @param all_str The string used for all categories
 * @param subcats An number-array of cats which should be selected.
 * 
 */

function post_notification_get_catselect($pref, $all_str = '', $subcats = array()){
	if(!is_array($subcats)) $subcats = array();
	if($all_str == '') $all_str = __('All', 'post_notification');
	if(get_option('post_notification_empty_cats') == 'yes'){
		$cats = get_categories(array('hide_empty' => false));
	} else {
		$cats = get_categories();
	}
	$walker = new Walker_post_notification;
	$walker->pref = $pref;
	
	$cats_str  = '<script src="'. POST_NOTIFICATION_PATH_URL . '/pncats.js" type="text/javascript" ></script>';	
	$cats_str .=  '<ul class="children"><li><input type="checkbox" name="pn_' . $pref . '[]" value="0" id="'. $pref.'_cat.0" onclick="post_notification_cats_init(\''.$pref.'\')" ';
	if ( in_array(0, $subcats)) $cats_str .= ' checked="checked"'; 
	$cats_str .= '>' . $all_str .'</li>';
	$cats_str .= '<ul class="children">' . call_user_func_array(array(&$walker, 'walk'), array($cats, 0, $subcats)) . '</ul>';
	$cats_str .= '</ul>';
	$cats_str .= '<script type="text/javascript"><!--' . "\n  post_notification_cats_init('$pref');\n //--></script>";
	return $cats_str;
}

function post_notification_get_addr(){
	$commenter = wp_get_current_commenter();
	$addr = $commenter['comment_author_email'];

	
	if($addr == ''){ //still havn't got email 
		$user = wp_get_current_user();
		$addr = $user->user_email;
	}
	return $addr;	
}

function post_notification_date_i18n_tz($dateformatstring, $unixtimestamp) {
	 
	// In case the Time Zone plugin is installed, date() is working correctly.
	// Let it do its work.
	//
	if (class_exists('TimeZone')) {
		return date_i18n($dateformatstring, $unixtimestamp);
	}
	// Else, we cannot rely on date() and must revert to gmdate(). We assume
	// that no daylight saving takes part. Else, install the plugin from
	// http://kimmo.suominen.com/sw/timezone/ using (or not) the patches from
	// http://www.philippusdo.de/technische-informationen/ .
	//
	global $wp_locale;
	$i = $unixtimestamp + get_option('gmt_offset') * 3600;

	if ((! empty($wp_locale->month)) && (! empty($wp_locale->weekday))) {

		$datemonth            = $wp_locale->get_month(         gmdate('m', $i));
		$datemonth_abbrev     = $wp_locale->get_month_abbrev(  $datemonth);
		$dateweekday          = $wp_locale->get_weekday(       gmdate('w', $i));
		$dateweekday_abbrev   = $wp_locale->get_weekday_abbrev($dateweekday);
		$datemeridiem         = $wp_locale->get_meridiem(      gmdate('a', $i));
		$datemeridiem_capital = $wp_locale->get_meridiem(      gmdate('A', $i));
		
		$dateformatstring = ' '.$dateformatstring;
		$dateformatstring = preg_replace("/([^\\\])D/", "\\1".backslashit($dateweekday_abbrev),   $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])F/", "\\1".backslashit($datemonth),            $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])l/", "\\1".backslashit($dateweekday),          $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])M/", "\\1".backslashit($datemonth_abbrev),     $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])a/", "\\1".backslashit($datemeridiem),         $dateformatstring);
		$dateformatstring = preg_replace("/([^\\\])A/", "\\1".backslashit($datemeridiem_capital), $dateformatstring);
		$dateformatstring = substr($dateformatstring, 1);
	}
	$j = @gmdate($dateformatstring, $i);
	return $j;
}

function post_notification_get_cats_of_post($post_id){
	global $wpdb;
	$t_emails = $wpdb->prefix . 'post_notification_emails';
	$t_posts = $wpdb->prefix . 'post_notification_posts';
	$t_subs = $wpdb->prefix . 'post_notification_subs';
	
	
	//Find the categories
	if(get_option('db_version') < 6124){ 
		$cats = $wpdb->get_results("SELECT category_id FROM {$wpdb->post2cat} WHERE post_id = $post_id");
	} else {
		$cats = $wpdb->get_results("SELECT term_id 
									FROM {$wpdb->term_relationships} 
									JOIN {$wpdb->term_taxonomy} USING (term_taxonomy_id)
									WHERE taxonomy = 'category' AND object_id = $post_id");
	}
	$cat_ids = array();

	foreach($cats as $cat){
		if(get_option('db_version') < 6124){ 
			$last_cat =  $cat->category_id;
		} else {
			$last_cat =  $cat->term_id;
		}
		//MySQL does not support recursion :-(
		while($last_cat != 0){
			$cat_ids[] = (string)$last_cat;
			if(get_option('db_version') < 6124){ 
				$last_cat = $wpdb->get_var("SELECT category_parent FROM {$wpdb->categories} WHERE cat_ID = $last_cat");
			} else {
				$last_cat = $wpdb->get_var("SELECT parent FROM {$wpdb->term_taxonomy} 
											WHERE term_id = $last_cat AND taxonomy = 'category'");
			}
		} 
	}
	$cat_ids[] = (string)$last_cat;
	return $cat_ids;
}

function post_notification_remove_email($removeAddress){
	global $wpdb;
	$t_emails = $wpdb->prefix . 'post_notification_emails';
	$t_subs = $wpdb->prefix . 'post_notification_subs';
	//Multiple table delete only works with mysql 4.0 or 4.1
	$mid = post_notification_get_mid($removeAddress);
	$wpdb->query("DELETE FROM $t_emails where id = $mid"); 
	$wpdb->query("DELETE FROM $t_subs where email_id = $mid");
	
	
	//Multiple table delete only works with mysql 4.0 or 4.1
	/*$wpdb->query("DELETE $t_subs s, $t_emails e 
		FROM $t_emails LEFT JOIN $t_subs ON (s.email_id = e.id) 
		WHERE email_addr = '$removeAddress'"); */
					
}

?>