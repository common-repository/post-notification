<?php
		// Get cats listing
		$cats_str = post_notification_get_catselect($post_notification_strings['all'], $subcats);
		
		$vars = '<input type="hidden" name="code" value="' . $code . '" /><input type="hidden" name="addr" value="' . $addr . '" />';
		
		
		$msg .= post_notification_ldfile('select.tmpl');
		$msg = str_replace('@@action',post_notification_get_link(),$msg);
		$msg = str_replace('@@addr',$addr,$msg);
		$msg = str_replace('@@cats',$cats_str,$msg);
		$msg = str_replace('@@vars',$vars,$msg);
?>