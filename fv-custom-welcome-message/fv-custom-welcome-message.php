<?php
/*
Plugin Name: FV Custom Welcome Message
Plugin URI: http://foliovision.com/
Description: Hooks into bb_send_pass_message filter
Version: 1.0
Author: Foliovision
Author URI: http://foliovision.com
*/

add_filter( 'bb_send_pass_message', 'fv_bb_send_pass_message', 10, 3 );

function fv_bb_send_pass_message() {
	
	$args = func_get_args();

	$message = $args[0];	
	$user = $args[1];
	$pass = $args[2];

	$message = sprintf(
		__( "Dear %1\$s, <br />
<br />
Welcome to Foliovision!<br /> 
<br />
You can now participate fully on <a href='%3\$s'>our support forums</a>: <a href='%3\$s'>http://foliovision.com/support/</a>. We are delighted to provide free how to advice and bug fixes. <br />
<br />
Your username is:  %1\$s <br />
Your password is: %2\$s <br />
<br />
If you have basic issues with installation, we are happy to offer pro installs and pro configuration on a one-to-one basis. <a href='http://foliovision.com/seo-tools/wordpress/pro-install'>Click here</a> to order professional support for our plugins.<br />
<br />
If you are a user of <a href='http://foliovision.com/seo-tools/wordpress/plugins/fv-testimonials'>FV Testimonials</a>, you might want to consider upgrading to the Pro version which allows multiple images and offers categories. A great testimonials section is the best way to supercharge sales. Here’s <a href='http://foliovision.com/about/testimonials'>our own testimonials section</a> and <a href='http://foliovision.com/about/clients'>our portfolio section</a> both built with FV Testimonials Pro.<br />
<br />
We look forward to speaking with you on our forums.<br />
<br />
Thanks for being part of Foliovision!<br />
<br />
Making the web work for you,<br />
<br />
Alec, Martin, Peter, Zdenka, Michala, Viktor and all the crew at Foliovision" ),
		$user->user_login,
		$pass,
		bb_get_uri( null, null, BB_URI_CONTEXT_TEXT )
	);

	return $message;	
}

?>