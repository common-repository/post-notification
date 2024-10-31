<h2>Get notified of new posts:</h2>
<form id="newsletter" method="post" action="<?php echo post_notification_get_link(); ?>" style="text-align:left">
	<p>email: <input type="text" name="addr" size="25" maxlength="50" value="<?php echo post_notification_get_addr(); ?>"/> </p>
	<input type="submit" name="submit" value="Submit" /></p>

</form>