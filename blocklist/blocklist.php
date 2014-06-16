<?php
/*
Plugin Name: Blocklist
Plugin URI:  http://bbpress.org/plugins/topic/blocklist
Description:  blocks posts based on a list of words or IP addresses (like WordPress) by immediately marking them as spam
Version: 100.0.4 modified by FV
Author: _ck_
Author URI: http://bbshowcase.org
*/


if ( bb_is_admin() ) {
	add_action('bb_admin_menu_generator', 'bb_blocklist_configuration_page_add');
	add_action('bb_admin-header.php', 'bb_blocklist_configuration_page_process');
}


function bb_blocklist_configuration_page_add() {
	bb_admin_add_submenu(__('Blocklist','blocklist'), 'keymaster', 'bb_blocklist_configuration_page', 'options-general.php');
}


function bb_blocklist_configuration_page() {
	$blocklist = bb_get_option('blocklist');
	?>	
		<h2>Blocklist</h2>
		<form class="settings" method="post" name="blocklist_form" id="blocklist_form">
		<input type=hidden name="blocklist" value="1">
			<table class="widefat">
				<tbody>
				<tr>
					<td valign="top">
					<div style="width:380px">
					<label for="data"><b>Blocklist</b></label>
					<br /><br />
					enter only one item per line
					<br /><br /> 
					username, email, IP address, subject, and post text are&nbsp;tested and&nbsp;if matched, post is sent to spam
					<br /><br /> 
					you can use partial words and partial ip addresses, which&nbsp;match&nbsp;to the left (ie. "starting with")
					<br /><br /> 
					lines that begin with <b>#</b><br />or <i>less than 4 characters</i> are <b>ignored</b>
					</div>
					</td>
					<td align="left">
					<div style="max-width:500px;">
					<textarea rows="15" cols="40" style="padding:3px;font-size:1.5em;" name="data"><?php echo $blocklist['data']; ?></textarea>											
					</div>					
					</td>
					<td></td>
				</tr>
				<tr>
					<td valign="top">
					<label for="email"><b>Email notification</b></label>
					<br /><br />
					these emails will be notified when a post is blocked<br />					
					only one email per line, leave blank for none					
					</td>
					<td align="left">
					<div style="max-width:500px;">
					<textarea rows="3" cols="40" style="padding:3px;font-size:1.5em;" name="email"><?php echo $blocklist['email']; ?></textarea>
					</div>					
					</td>
					<td></td>
				</tr>
				<tr style="border:0">
					<td></td>
					<td align="left">
					<div style="max-width:500px;">					
						<p class="submit" style="border:0"><input class="submit" type="submit" name="submit" value="Save Blocklist Settings"></p>					
					</div>					
					</td>
					<td></td>
				
				</tr>
				</tbody>
			</table>		
		
		</form>
		<?php
}


function bb_blocklist_configuration_page_process() {
	if (isset($_POST['submit']) && isset($_POST['blocklist'])) {
	$options=array('data','email');
	foreach ($options as $option) {
		if (!empty($_POST[$option])) {
			(array) $data=explode("\n",trim($_POST[$option]));
			array_walk($data,create_function('&$arr','$arr=trim($arr);'));		
			$blocklist[$option]=implode("\r\n",$data)."\r\n";
		} else {$blocklist[$option]="";}
	}
	bb_update_option('blocklist',$blocklist);	
   bb_admin_notice( __( '<strong>Settings saved.</strong>','xnpn') );	
	}
}



add_filter( 'bb_insert_post', 'blocklist_check', 8);
/*
if ((defined('BB_IS_ADMIN') && BB_IS_ADMIN) || !(strpos($_SERVER['REQUEST_URI'],"/bb-admin/")===false)) { // "stub" only load functions if in admin 
	if (isset($_GET['plugin']) && ($_GET['plugin']=="blocklist_admin" || strpos($_GET['plugin'],"blocklist.php"))) {require_once("blocklist-admin.php");} 
	elseif (defined('BACKPRESS_PATH') && strpos($_SERVER['REQUEST_URI'],'bb-admin/content.php')) {header('Location: '.str_replace('content.php','',$_SERVER['REQUEST_URI'])); exit;}
	add_action('bb_admin_menu_generator', 'blocklist_add_admin_page');	
	function blocklist_add_admin_page() {
		global $bb_menu;  if (defined('BACKPRESS_PATH') && empty($bb_menu[165]))  {$bb_menu[165] = array( __('Manage'),'moderate','content.php', 'bb-menu-manage' );}
		bb_admin_add_submenu('Blocklist', 'administrate', 'blocklist_admin','content.php');
	} 
} 
*/
function blocklist_initialize() { 
	global $blocklist; 
	if (!isset($blocklist)) {$blocklist = bb_get_option('blocklist'); if (empty($blocklist)) {$blocklist['data']="";$blocklist['email']="";}}
}	
	
function blocklist_check($post_id=0,$wall=false) { 	
	if (bb_current_user_can('moderate') || bb_current_user_can('throttle')) {return;}	
	if ($wall) {$bb_post = user_wall_get_post( $post_id);} else {$bb_post = bb_get_post( $post_id );}
	if (empty($post_id) || empty($bb_post) || !empty($bb_post->post_status)) {return;}
	
	global $blocklist,$bbdb; blocklist_initialize(); 
	if (empty($blocklist['data'])) {return;}
	(array) $data=explode("\r\n",$blocklist['data']);
	$user=bb_get_user($bb_post->poster_id);
	
	foreach ($data as $item) {				
		if (empty($item) || strlen($item)<4 || ord($item)==35)  {continue;}
		if (preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}/',$item)) {	// is IP		
		if (strpos($bb_post->poster_ip,$item)===0) {$found="IP address"; $bad=$item; break;}
		} else {	 	// is word
			$qitem=preg_quote($item);
			if (preg_match('/\b'.$qitem.'/simU',$user->user_email)) {$found="email"; $bad=$item; break;}
			if (preg_match('/\b'.$qitem.'/simU',$user->user_login)) {$found="username"; $bad=$item; break;}
			if (preg_match('/\b'.$qitem.'/simU',$bb_post->post_text)) {$found="post text"; $bad=$item; break;}
			elseif (!$wall && $bb_post->post_position==1) {
				if (empty($topic)) {$topic = get_topic( $bb_post->topic_id );}				
				if (!empty($topic->topic_title) && preg_match('/\b'.$qitem.'/simU',$topic->topic_title)) {$found="topic title"; $bad=$item; break;}
			}
		}
		if (!empty($bad)) {break;}
	}	
	if (!empty($bad)) {		
		
		if ($wall) {
			user_wall_delete_post( $post_id, 2);
			$uri=bb_get_option('uri') . "bb-admin/admin-base.php?post_status=2&plugin=user_wall_admin&user-wall-recent=1";
		} else {			
			bb_delete_post( $post_id, 2);
			if (empty($topic)) {$topic = get_topic( $bb_post->topic_id );}
			if ( empty($topic->topic_posts) ) {bb_delete_topic( $topic->topic_id, 2 );}	// if no posts in topic, also set topic to spam
			$uri=bb_get_option('uri').'bb-admin/'.(defined('BACKPRESS_PATH') ? '' : 'content-').'posts.php?post_status=2';
		}
		
		if (empty($blocklist['email'])) {return;}
		(array) $email=explode("\r\n",$blocklist['email']);		
		
		$message="The blocklist has been triggered... \r\n\r\n";		
		$message.="Matching entry ".'"'.$bad.'"'." found in $found.\r\n";
		$message.= "$uri\r\n\r\n";		
		$message .= sprintf(__('Username: %s'), stripslashes($user->user_login)) . "\r\n";
		$message .= sprintf(__('Profile: %s'), get_user_profile_link($user->ID)) . "\r\n";
		$message .= sprintf(__('Email: %s'), stripslashes($user->user_email)) . "\r\n";		
		$message .= sprintf(__('IP address: %s'), $_SERVER['REMOTE_ADDR']) . "\r\n";
		$message .= sprintf(__('Agent: %s'), substr(stripslashes($_SERVER["HTTP_USER_AGENT"]),0,255)) . "\r\n\r\n";			

		foreach ($email as $to) {if (empty($to) || strlen($to)<8) {continue;}
			@bb_mail($to , "[".bb_get_option('name')."] blocklist triggered", $message);
		}
	} 	
}

?>