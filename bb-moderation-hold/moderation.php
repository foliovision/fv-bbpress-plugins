<?php
/*
Plugin Name: BB Moderation Hold
Plugin URI: http://www.adityanaik.com
Description: Hold posts and topics for moderation
Author: Aditya Naik
Author URI: http://www.adityanaik.com/
Version: 101.0.4.1fv
*/

$fv_moderate_hashed_email = false;

require_once( dirname( __FILE__ ) . '/fvcrypt.php' );

add_filter( 'bb_parse_query', 'fv_moderate_init', 0 );

function fv_moderate_init() {
  global $bb_current_user;
  global $topic;
  global $fv_moderate_hashed_email;
  
  
  
  /*if( isset($topic) && isset($_GET['moderated_id']) && class_exists('FVCrypt_fv_moderate') ) {
  	$decrypt = new FVCrypt_fv_moderate( $topic->topic_title );
  	$fv_moderate_hashed_email = $decrypt->Decrypt($_GET['moderated_id']);
  }
  //echo '<!--fv_moderate_init  fv_moderate_hashed_email '.var_export($fv_moderate_hashed_email,true).' for '.$topic->topic_title.' -->';*/
  if( $fv_moderate_hashed_email || $_COOKIE['fv_forum_email'] || isset( $bb_current_user->ID ) || bb_current_user_can('browse_deleted') ) {
    add_filter( 'get_posts_where', 'fv_moderate_get_posts_where' );
  }  
}

   
function fv_moderate_get_posts_where( $where ) {
  global $fv_moderate_hashed_email;

  if( bb_current_user_can('browse_deleted') ) {
    if( strpos( $where, "p.post_status = '0'" ) !== FALSE ) {
      $where = str_replace( "p.post_status = '0'", "( p.post_status = '0' OR p.post_status = '-1' )", $where );
    } 
  } else {        
    global $bbdb;
    global $bb_current_user;
    
    if( isset( $bb_current_user->ID ) ) {
      $user_id = $bb_current_user->ID;
    } else {
    	if( $fv_moderate_hashed_email ) {
    		$email = $fv_moderate_hashed_email;
    	} else if( isset( $_COOKIE['fv_forum_email'] ) ) {
      	$email = $bbdb->escape($_COOKIE['fv_forum_email']); 
      }
      $user_id = $bbdb->get_var( "SELECT ID FROM $bbdb->users WHERE user_email = '{$email}'" );
    }
    
    if( !$user_id ) {
      return $where;
    }
    
    if( strpos( $where, "p.post_status = '0'" ) !== FALSE ) {
      $where = str_replace( "p.post_status = '0'", "( p.post_status = '0' OR ( p.post_status = '-1' AND poster_id = {$user_id} ) )", $where );
    } 
  
  }
  
  add_filter( 'get_post_text', 'fv_moderate_post_text_notice', 20, 2);
  return $where;
} 


function fv_moderate_post_text_notice( $text, $id ) {
  global $bb_post;
  if( $bb_post->post_status == -1 ) {
    $text = '<em id="note-for-moderator">Your post is currently pending moderation.</em>'."\n\n".$text;
  }
	return $text;
}


add_filter( 'get_topics_where', 'fv_moderate_get_topics_where' );

function fv_moderate_get_topics_where( $where ) {
  //  latest posts
  if( !current_user_can('browse_deleted') && trim($where) == "t.topic_status = '0' AND t.topic_sticky != '2'" ){
    $where .= ' AND t.topic_posts > 0 ';
  }
  
  //  forum listing  
  else if( !current_user_can('browse_deleted') && preg_match( "~^t.forum_id = '\d+' AND t.topic_status = '0' AND t.topic_sticky !?= '0'$~", trim($where) ) ){
    $where .= ' AND t.topic_posts > 0 ';
  }
  
  //echo '<!--new where '.$where.'-->'."\n";
  return $where;
}


/**
 * Filter topics held for moderation
 *
 * @author  Aditya Naik <aditya@adityanaik.com>
 * @version v 0.02 Sun Apr 08 2007 01:31:51 GMT-0400 (Eastern Daylight Time)
 */
function bb_moderation_hold_where_moderated_topics($where){
  return str_replace("topic_status = '0'", "topic_status = '-1'", $where);
}

/**
 * Filter posts held for moderation
 *
 * @author  Aditya Naik <aditya@adityanaik.com>
 * @version v 0.02 Sun Apr 08 2007 01:32:35 GMT-0400 (Eastern Daylight Time)
 */
function bb_moderation_hold_where_moderated_posts($where){
  return str_replace("post_status = '0'", "post_status = '-1'", $where);
}

/**
 * Holds stuff for moderation
 *
 * Hold topics and posts for moderation depending on the options.
 * Also send mail options are set
 *
 * @author  Aditya Naik <aditya@adityanaik.com>
 * @version v 0.01 Tue Apr 10 2007 23:29:07 GMT-0400 (Eastern Daylight Time)
 */
function bb_moderation_hold_after_posting_do_the_magic($post_id){
  global $bbdb, $topic_id, $post_id;
  $options = bb_anonymous_default_options(bb_get_option('bb_moderation_hold'));

  $hold_topics = bb_moderation_check_options('hold_topics', $options);
  $hold_posts = bb_moderation_check_options('hold_posts', $options);
  
  if ( $hold_topics && isset($_POST['topic']) && $forum = (int) $_POST['forum_id'] ) {
    $bbdb->query("UPDATE $bbdb->topics SET topic_status = '-1' WHERE topic_id = '$topic_id'");
    if ('Y' == $options['hold_topics_send_email']) bb_moderation_hold_mail_moderation();
    $post_id = false;
  } elseif($hold_posts) {
    $post_id = false;
    if ('Y' == $options['hold_posts_send_email']) bb_moderation_hold_mail_moderation('P');
  }
  
}

/**
 * Send Moderation Notification
 *
 * @author  Aditya Naik <aditya@adityanaik.com>
 * @version v 0.02 Sun Apr 08 2007 01:37:30 GMT-0400 (Eastern Daylight Time)
 */
function bb_moderation_hold_mail_moderation($obj = 'T') {

  if ('T' == $obj) {
    $email = $options['hold_topics_email_address'];
    $obj = 'topic';
  } elseif ('P' == $obj) {
    $email = $options['hold_posts_email_address'];
    $obj = 'post';
  } else
    return;

  $message = __("You have a new $s in the moderation queue.");
  bb_mail( $email, __('Moderation Alert'), sprintf( $message, "$obj"));
}

/**
 * Change the status before post is created
 * 
 * @author  Aditya Naik <aditya@adityanaik.com>
 * @version v 0.02 Tue Apr 10 2007 23:29:38 GMT-0400 (Eastern Daylight Time)
 */
function bb_moderation_fix_status_before_post($old_status, $post_id, $topic_id) {
    $options = bb_anonymous_default_options(bb_get_option('bb_moderation_hold'));

	$hold_posts = bb_moderation_check_options('hold_posts', $options);
    if (!$post_id && $hold_posts) {
		$old_status = -1;    
    }
    
    ///
    global $bbdb;
    $topic = $bbdb->get_row("SELECT * FROM $bbdb->topics WHERE topic_id = '$topic_id'");
    if($hold_topics && intval($topic->topic_posts) === 0):
    $bbdb->query("UPDATE $bbdb->topics SET topic_status = '-1' WHERE topic_id = '$topic_id'");
    if ('Y' == $options['hold_topics_send_email']) bb_moderation_mail_moderation();
    endif;
    ///
    
    return $old_status;
}

/**
 * Check Moderation options
 * 
 * @author  Aditya Naik <aditya@adityanaik.com>
 * @version v 0.01 Tue Apr 10 2007 23:30:05 GMT-0400 (Eastern Daylight Time)
 */
function bb_moderation_check_options($check = 'hold_topics', $options = false) {

    if (!$options)
        $options = bb_anonymous_default_options(bb_get_option('bb_moderation_hold'));

    switch($options[$check]) {
        case 2:
          if (!$user = bb_current_user()) $ret = true;
          break;
        case 3:
          if (!bb_current_user_can('moderate')) $ret = true;
          break;
        default:
            $ret = false;
    }

    return $ret;
}
add_action('bb_post.php','bb_moderation_hold_after_posting_do_the_magic');
add_filter('pre_post_status','bb_moderation_fix_status_before_post',10,3);
//add_filter('post_delete_link','bb_moderation_fix_delete_link',10,3);     // we don't need this one anymore -> have the Approve button
add_filter('post_delete_link','fv_moderation_approve_link',10,3);

/**
 * Add a delete/moderate link to posts
 * 
 * @author  Aditya Naik <aditya@adityanaik.com>
 * @version v 0.01 Tue Apr 10 2007 23:30:05 GMT-0400 (Eastern Daylight Time)
 */
function bb_moderation_fix_delete_link($r, $post_status, $post_id){
    if (-1 == $post_status)
        $r = "<a class=\"post-moderate-link\" href='" . bb_get_option('uri') . 'bb-admin/admin-base.php?plugin=bb_moderation_hold_post_admin_page' . "' >". __('Moderate') ."</a>";
    return $r;
}

function fv_moderation_approve_link($r, $post_status, $post_id){
    if( !bb_current_user_can( 'moderate' ) ) {
      return $r;  
    }
    if (-1 == $post_status ) {
        $strNonce = '\'' . bb_create_nonce('approve-post-' . $post_id) . '\'';
        $r .= ' <a class="post-approve-link"  href="#post-' . $post_id . '" onclick="fv_approve_post(' . $post_id . ','.$strNonce.')" >'. __('Approve') .'</a>';
    }    
    return $r;
}

add_action('bb_ajax_approve-post','fv_approve_post_action' );

function fv_approve_post_action() {
  
  if(!bb_verify_nonce($_POST['nonce'], 'approve-post-' . $_POST['postid'])) {
    die('-2');
  }
  
  if(bb_current_user_can( 'moderate' ) ) {
    if(intval($_POST['postid']) <= 0) {      // check if there wasn't some error when sending the topic ID
      die('-1');
    }  
    bb_moderation_hold_approve_posts(array($_POST['postid']));
    die('1');
  }
  else {
    die('0');  
  }
}

add_action('bb_foot', 'fv_print_approve_comment_script');

function fv_print_approve_comment_script() {
  if( bb_current_user_can( 'moderate' ) ) :
  ?>
  <!-- From the BB Moderation Hold plugin -->
  <script>
  function fv_approve_post(id, nonce) {
        var strPostbody = jQuery("#post-"+id+" div.post").html();
        jQuery("#post-"+id+" div.post").text('Approving Comment... ');
        
        var url = '<?php echo bb_get_option('uri'); ?>bb-admin/admin-ajax.php';
        
        jQuery.post(url, { postid: id, action: "approve-post", nonce: nonce},

            function(data){

                if (data == '1') {
                  jQuery("#post-"+id+" div.post").html(strPostbody);
                  jQuery("#post-"+id+" #note-for-moderator").remove();
                  jQuery("#post-"+id+" a.post-moderate-link").remove();
                  jQuery("#post-"+id+" a.post-approve-link").remove();
                }
                else if (data == '-1' ) {
                  jQuery("#post-"+id+" div.post").html('<p style="color: red;">Comment was NOT approved ! (something went wrong...)</p><br /><p style=\"color: red;\">The topic ID is 0 or negative integer.</p>');
                }
                else if (data == '-2' ) {
                  jQuery("#post-"+id+" div.post").html('<p style="color: red;">Comment was NOT approved ! (something went wrong...)</p><br /><p style=\"color: red;\">Nonce wasn\'t verified properly.</p>');
                }
                else {
                  jQuery("#post-"+id+" div.post").html('<p style="color: red;">Comment was NOT approved ! (something went wrong...)</p>');
                }

            });
   
  }
  </script>
  <!-- end of From the BB Moderation Hold plugin -->
  <?php
  endif;
}

/**** HERE BEGINS THE CODE RELATED TO THE ADMIN SCREEN ******/

if (!BB_IS_ADMIN) {
	return;
}

/**
 * Add Admin Page
 *
 * @author  Aditya Naik <aditya@adityanaik.com>
 * @version v 0.01 Sun Apr 08 2007 02:12:09 GMT-0400 (Eastern Daylight Time)
 */
add_action( 'bb_admin_menu_generator', 'bb_moderation_hold_add_admin_page' );
function bb_moderation_hold_add_admin_page() {
  global $bb_submenu;

  ///bb_admin_add_submenu(__('Moderation Options'), 'moderate', 'bb_moderation_hold_admin_page');
  ///bb_admin_add_submenu(__('Topics for Moderation'), 'moderate', 'bb_moderation_hold_topic_admin_page', 'content.php' );
  ///bb_admin_add_submenu(__('Posts for Moderation'), 'moderate', 'bb_moderation_hold_post_admin_page', 'content.php' );
bb_admin_add_submenu(__('Moderation Options'), 'moderate', 'bb_moderation_hold_admin_page', 'options-general.php');
bb_admin_add_submenu(__('Topics for Moderation'), 'moderate', 'bb_moderation_hold_topic_admin_page', 'topics.php' );
bb_admin_add_submenu(__('Posts for Moderation'), 'moderate', 'bb_moderation_hold_post_admin_page', 'posts.php' );


}

/**
 * Administration Page for Moderation Options
 *
 * @author  Aditya Naik <aditya@adityanaik.com>
 * @version v 0.01 Sun Apr 08 2007 02:12:05 GMT-0400 (Eastern Daylight Time)
 */
function bb_moderation_hold_admin_page() {

  global $bbdb, $topic, $bb_post, $post_id, $topic_id;
  $options = bb_anonymous_default_options(bb_get_option('bb_moderation_hold'));
  ?>
  <h2><?php _e('Hold for moderation') ?></h2>
  <form method="post">
    <table class="widefat">
      <tr<?php alt_class('options'); ?>>
        <td>Hold topics for moderation</td>
        <td>Hold posts for moderation</td>
      </tr>
      <tr>
        <td>
          <input type="radio" value="1" id="hold_topics1" name="hold_topics"<?php if (1 == $options['hold_topics']) echo ' checked' ;?> /> <label for="hold_topics1">None</label>
          <?php if (function_exists('bb_anonymous_posting_fix_bb_user_can')) { ?><input type="radio" value="2" id="hold_topics2" name="hold_topics"<?php if (2 == $options['hold_topics']) echo ' checked' ;?> /> <label for="hold_topics2">Anonymous Topics</label> <?php } ?>
          <input type="radio" value="3" id="hold_topics3" name="hold_topics"<?php if (3 == $options['hold_topics']) echo ' checked' ;?> /> <label for="hold_topics3">All Topics</label>
        </td>
        <td>
          <input type="radio" value="1" id="hold_posts1" name="hold_posts"<?php if (1 == $options['hold_posts']) echo ' checked' ;?> /> <label for="hold_posts1">None</label>
          <?php if (function_exists('bb_anonymous_posting_fix_bb_user_can')) { ?><input type="radio" value="2" id="hold_posts2" name="hold_posts"<?php if (2 == $options['hold_posts']) echo ' checked' ;?> /> <label for="hold_posts2">Anonymous Topics</label> <?php } ?>
          <input type="radio" value="3" id="hold_posts3" name="hold_posts"<?php if (3 == $options['hold_posts']) echo ' checked' ;?> /> <label for="hold_posts3">All Topics</label>
        </td>
      </tr>
      <tr<?php alt_class('options'); ?>>
        <td>
          <input type="checkbox" value="Y" id="hold_topics_send_email" name="hold_topics_send_email"<?php if ('Y' == $options['hold_topics_send_email']) echo ' checked' ;?> /> <label for="hold_topics_send_email">Send email to</label>
          <input type="text" value="<?php echo $options['hold_topics_email_address']; ?>" id="hold_topics_email_address" name="hold_topics_email_address" />
        </td>
        <td>
          <input type="checkbox" value="Y" id="hold_posts_send_email" name="hold_posts_send_email"<?php if ('Y' == $options['hold_posts_send_email']) echo ' checked' ;?> /> <label for="hold_posts_send_email">Send email to</label>
          <input type="text" value="<?php echo $options['hold_posts_email_address']; ?>" id="hold_posts_email_address" name="hold_posts_email_address" />
        </td>
      </tr>
      <tr>
        <td colspan="2" class="submit"><input type="submit" name="bb_moderation_hold_update_options" value="Update" /></td>
      </tr>
    </table>
  </form>
  <?php
}

/**
 * Admin Page for Topic Moderation
 *
 * @author  Aditya Naik <aditya@adityanaik.com>
 * @version v 0.01 Sun Apr 08 2007 02:32:53 GMT-0400 (Eastern Daylight Time)
 */
function bb_moderation_hold_topic_admin_page() {

  global $bbdb, $topic, $bb_post, $post_id, $topic_id;
  $options = bb_anonymous_default_options(bb_get_option('bb_moderation_hold'));

  if ($options['hold_topics']) :
    if ( !bb_current_user_can('moderate') )
      die(__("Now how'd you get here?  And what did you think you'd being doing?")); //This should never happen.
    add_filter( 'get_latest_topics_where', 'bb_moderation_hold_where_moderated_topics' );
    add_filter( 'topic_link', 'bb_make_link_view_all' );
    $topics = get_latest_topics( 0, $page);
    ?>
    <h2><?php _e('Topics for Moderation') ?></h2>
    <?php if ( $topics ) : ?>
      <form method="post" name="topic_moderation_form" >
        <table class="widefat">
          <tr class="thead">
            <th></th>
            <th><?php _e('Topic') ?></th>
            <th><?php _e('Last Poster') ?></th>
            <th><?php _e('Freshness') ?></th>
          </tr>

          <?php foreach ( $topics as $topic ) : ?>
            <tr<?php alt_class('topic'); ?>>
              <td><input type="checkbox" name="topicids[]" value="<?php topic_id(); ?>" /></td>
              <td><a href="<?php topic_link(); ?>"><?php topic_title(); ?></a></td>
              <td class="num"><?php topic_last_poster(); ?></td>
              <td class="num"><small><?php topic_time(); ?></small></td>
            </tr>
          <?php endforeach; ?>
        </table>
        <p class="submit">
          <input type="submit" name="bb_moderation_hold_topic_delete" value="Delete" />
          <input type="submit" name="bb_moderation_hold_topic_approve" value="Approve" />
        </p>
      </form>
    <?php else: ?>
      <p>No topics for moderation</p>
    <?php endif;
  endif;

}

/**
 * Admin page for post moderation
 *
 * @author  Aditya Naik <aditya@adityanaik.com>
 * @version v 0.01 Sun Apr 08 2007 02:30:31 GMT-0400 (Eastern Daylight Time)
 */
function bb_moderation_hold_post_admin_page() {

  global $bbdb, $topic, $bb_post, $post_id, $topic_id;
  $options = bb_anonymous_default_options(bb_get_option('bb_moderation_hold'));

  if ($options['hold_posts']) :
    if ( !bb_current_user_can('moderate') )
      die(__("Now how'd you get here?  And what did you think you'd being doing?")); //This should never happen.
    add_filter( 'get_latest_posts_where', 'bb_moderation_hold_where_moderated_posts' );
    add_filter( 'post_link', 'bb_make_link_view_all' );
    $posts = get_latest_posts( 50);
    ?>
    <h2><?php _e('Posts for Moderation') ?></h2>
    <?php if ( $posts ) : ?>
      <form method="post" name="moderation_form" >
        <table id="posts-list" class="widefat">
          <tr class="thead">
            <th style="width: 20px; "></th>
            <th><?php _e('Post') ?></th>
            <th><?php _e('Topic') ?></th>
            <th><?php _e('Poster') ?></th>
            <th><?php _e('Freshness') ?></th>
          </tr>

          <?php foreach ( $posts as $bb_post ) :
            $topic = get_topic( $bb_post->topic_id);
            ?>
            <tr<?php alt_class('post'); ?>>
              <td style="width: 20px; "><input type="checkbox" name="postids[]" value="<?php post_id(); ?>" /></td>
              <td class="post">
                <?php post_text(); ?>
                <!--<div><?php //echo substr(get_post_text(),0,150); ?></div>-->
                <div>
                <span class="row-actions"><a href="<?php post_link(); ?>">Permalink</a>
<?php
  remove_filter('post_delete_link','bb_moderation_fix_delete_link',10,3);
	bb_post_admin( array(
		'before_each' => ' | ',
		'each' => array(
			'undelete' => array(
				'before' => ' '
			)
		),
		'last_each' => array(
			'before' => ' | '
		)
	) );
	add_filter('post_delete_link','bb_moderation_fix_delete_link',10,3);
?>
                </span>&nbsp;
                </div>
              </td>
              <td><a href="<?php topic_link(); ?>"><?php topic_title(); ?></a></td>
              <td class="num"><?php post_author(); ?></td>
              <td class="num"><small><?php bb_post_time(); ?></small></td>
            </tr>
          <?php endforeach; ?>
        </table>
        <p class="submit">
          <input type="submit" name="bb_moderation_hold_post_delete" value="Delete" />
          <input type="submit" name="bb_moderation_hold_post_approve" value="Approve" />
        </p>
      </form>
    <?php else: ?>
      <p>No posts for moderation</p>
    <?php
    endif;
  endif;
}

/**
 * Set Default options
 *
 * @author  Aditya Naik <aditya@adityanaik.com>
 * @version v 0.01 Sun Apr 08 2007 01:27:00 GMT-0400 (Eastern Daylight Time)
 */
function bb_anonymous_default_options($options){
  if (!$options) {
    $options = array('hold_topics' => 1, 'hold_posts' => 1);
    bb_update_option('bb_moderation_hold' , $options);
  } else {
    if (empty($options['hold_topics'])) $options['hold_topics'] = 1;
    if (empty($options['hold_posts'])) $options['hold_posts'] = 1;
    if (empty($options['hold_topics_send_email'])) $options['hold_topics_send_email'] = N;
    if (empty($options['hold_posts_send_email'])) $options['hold_posts_send_email'] = N;
    if (empty($options['hold_topics_email_address'])) {
      $options['hold_topics_send_email'] = N;
    }
    if (empty($options['hold_posts_email_address'])) {
      $options['hold_posts_send_email'] = N;
    }
    bb_update_option('bb_moderation_hold' , $options);
  }
  return $options;
}

/**
 * Process Admin Page Post
 *
 * @author  Aditya Naik <aditya@adityanaik.com>
 * @version v 0.01 Sun Apr 08 2007 02:11:58 GMT-0400 (Eastern Daylight Time)
 */
add_action( 'bb_admin-header.php','bb_moderation_hold_process_post');
function bb_moderation_hold_process_post() {

  if(isset($_POST['bb_moderation_hold_update_options'])) {
    $hold_posts = $_POST['hold_posts'];
    $hold_topics = $_POST['hold_topics'];
    $hold_topics_send_email = $_POST['hold_topics_send_email'];
    $hold_posts_send_email = $_POST['hold_posts_send_email'];
    $hold_topics_email_address = $_POST['hold_topics_email_address'];
    $hold_posts_email_address = $_POST['hold_posts_email_address'];
    $options = array(
      'hold_posts' => $hold_posts,
      'hold_topics' => $hold_topics,
      'hold_posts_send_email' => $hold_posts_send_email,
      'hold_topics_send_email' => $hold_topics_send_email,
      'hold_topics_email_address' => $hold_topics_email_address,
      'hold_posts_email_address' => $hold_posts_email_address
    );
    bb_anonymous_default_options($options);
    $link = add_query_arg( array('plugin' => $_GET['plugin'], 'bb_moderation_hold_options' => 'updated'), bb_get_option( 'uri' ) . 'bb-admin/admin-base.php' );
    wp_redirect( $link );
  }

  if(isset($_POST['bb_moderation_hold_topic_approve'])) {
    bb_moderation_hold_approve_topics($_POST['topicids']);
    $link = add_query_arg( array('plugin' => $_GET['plugin'], 'bb_moderation_hold_approved' => 'topics'), bb_get_option( 'uri' ) . 'bb-admin/admin-base.php' );
    wp_redirect( $link );
  }

  if(isset($_POST['bb_moderation_hold_topic_delete'])) {
    if ($_POST['topicids']) : foreach($_POST['topicids'] as $topic_id) :
      bb_delete_topic( $topic_id, 1 );
    endforeach; endif;
    $link = add_query_arg( array('plugin' => $_GET['plugin'], 'bb_moderation_hold_deleted' => 'topics'), bb_get_option( 'uri' ) . 'bb-admin/admin-base.php' );
    wp_redirect( $link );
  }

  if(isset($_POST['bb_moderation_hold_post_delete'])) {
    if ($_POST['postids']) : foreach($_POST['postids'] as $post_id) :
      bb_delete_post( $post_id, 1 );
    endforeach; endif;
    $link = add_query_arg( array('plugin' => $_GET['plugin'], 'bb_moderation_hold_deleted' => 'posts'), bb_get_option( 'uri' ) . 'bb-admin/admin-base.php' );
    wp_redirect( $link );
  }

  if(isset($_POST['bb_moderation_hold_post_approve'])) {
    bb_moderation_hold_approve_posts($_POST['postids']);
    $link = add_query_arg( array('plugin' => $_GET['plugin'], 'bb_moderation_hold_approved' => 'posts'), bb_get_option( 'uri' ) . 'bb-admin/admin-base.php' );
    wp_redirect( $link );
  }

  if ( isset($_GET['bb_moderation_hold_approved']) )
    $theme_notice = bb_admin_notice(ucfirst($_GET['bb_moderation_hold_approved']) . " Approved");
  elseif ( isset($_GET['bb_moderation_hold_deleted']) )
    $theme_notice = bb_admin_notice(ucfirst($_GET['bb_moderation_hold_deleted']) . " Deleted");
  elseif(isset($_GET['bb_moderation_hold_options']))
    $theme_notice = bb_admin_notice("Options Updated");
}


/**
 * Approve Topics
 *
 * @author  Aditya Naik <aditya@adityanaik.com>
 * @version v 0.01 Sun Apr 08 2007 01:33:00 GMT-0400 (Eastern Daylight Time)
 */
function bb_moderation_hold_approve_topics($topicids){
    global $bbdb;
    if ($topicids) : foreach($topicids as $topic_id) :
        $bbdb->query("UPDATE $bbdb->topics SET topic_status = '0' WHERE topic_id = '$topic_id'");
        
        $postids = $bbdb->get_col("SELECT post_id FROM $bbdb->posts where topic_id = $topic_id");
        bb_moderation_hold_approve_posts($postids);
    endforeach; endif;
}

/**
 * Approve Posts - input has to be an array()
 *
 * @author  Aditya Naik <aditya@adityanaik.com>
 * @version v 0.01 Sun Apr 08 2007 01:34:06 GMT-0400 (Eastern Daylight Time)
 */
function bb_moderation_hold_approve_posts($postids){
  global $bbdb, $thread_ids_cache;
  if ($postids) {
    foreach($postids as $post_id) {
      $bbdb->query("UPDATE $bbdb->posts SET post_status = '0' WHERE post_id = '$post_id'");
      $bb_post = bb_get_post ( $post_id );
      add_filter( 'get_topic_where', 'no_where' );
      $topic   = get_topic( $bb_post->topic_id , false);
      $topic_id = (int) $topic->topic_id;
  
      if (!$user = bb_get_user( $bb_post->poster_id )){
        $uid = 0;
        $uname = $bb_post->poster_name;
      } else {
        $uid = $bb_post->poster_id;
      }
  
      $topic_posts = $topic->topic_posts + 1;
  
      $bbdb->query("UPDATE $bbdb->forums SET posts = posts + 1 WHERE forum_id = $topic->forum_id");
      $bbdb->query("UPDATE $bbdb->topics SET topic_time = '" . $bb_post->post_time . "', topic_last_poster = '$uid', topic_last_poster_name = '$uname',
        topic_last_post_id = '$post_id', topic_posts = '$topic_posts' WHERE topic_id = '$topic_id'");
  
      bb_update_topicmeta( $topic->topic_id, 'deleted_posts', isset($topic->deleted_posts) ? $topic->deleted_posts - 1 : 0 );
  
      if ( isset($thread_ids_cache[$topic_id]) ) {
        $thread_ids_cache[$topic_id]['post'][] = $post_id;
        $thread_ids_cache[$topic_id]['poster'][] = $uid;
      }
      
      $post_ids = get_thread_post_ids( $topic_id );
      if ( $uid && !in_array($uid, array_slice($post_ids['poster'], 0, -1)) )
        bb_update_usermeta( $uid, $bb_table_prefix . 'topics_replied', $bb_current_user->data->topics_replied + 1 );
    }
  }
}



add_action( 'bb_rss.php_pre_db', 'fv_bb_moderation_hold_rss' );

function fv_bb_moderation_hold_rss() {
//die('wooot??');
	if ( isset($_GET['topic']) || bb_get_path() == 'topic' ) {		
		// Topic
		$feed = 'topic';
		$topic = get_topic(isset($_GET['topic']) ? $_GET['topic'] : bb_get_path(2));
		$feed_id = $topic->topic_id;		
	}
	$topic_status = $bbdb->get_var( "SELECT post_status FROM bb_posts WHERE topic_id = '{$topic_id}' ORDER BY post_time LIMIT 1" );
	
	if( $topic_status == -1 && !current_user_can('manage_options') ) {
		die('woot!');
	}

}

?>