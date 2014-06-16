<?php
/*
Plugin Name: FV Custom Welcome Message
Plugin URI: http://foliovision.com/
Description: Hooks into bb_send_pass_message filter
Version: 0.1
Author: Foliovision
Author URI: http://foliovision.com
*/

add_filter( 'bb_send_pass_message', 'fv_bb_send_pass_message', 10, 3 );

function fv_bb_send_pass_message() {
	
	$args = func_get_args();

	$message = $args[0];	
	$user = $args[1];
	$pass = $args[2];

// 	if( '188.167.15.220' == $_SERVER['REMOTE_ADDR'] ) {
//    $strRefid = $args[3];
/*
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
We look forward to speaking with you on our forums.<br />
<br />
Also note that by registering, you have the possibility to also be a member of our affiliate program:
<ul>
<li>You will have an affiliate link of the format <code>http://foliovision.com/#affid</code> where <code>#affid</code> is your unique affiliate id you get when applying. You can use this link in any way you'd like: as long as a user comes to our site from your link and they buy one of our products, you'll automatically get a commission from what they pay.</li>
<li>You can also promote our products on your web site or blog: if you chose to do so, it's then enough to put the link to our website on your site (without the <code>/#affid</code> part) and you'll get recognized as the referrer of any new buyer coming from there.</li>
</ul>
<br />
To know more, please go to your profile and navigate to \"FV Affiliate program\" in the menu on the left.
<br />
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
//    return $message;
*/
//    }
	
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
