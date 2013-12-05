<?php
/**
 * @package Nicer Permalinks
 */


// Add plugin filters
add_filter( 'get_forum_link', 'get_forum_nicer_link', 10, 3 );
add_filter( 'get_topic_link', 'get_topic_nicer_link', 10, 3 );
add_filter( 'get_post_link',  'get_post_nicer_link', 10, 2 );


/**
 * Functions
 */
 

/// 
function get_forum_slug_recursive( $id ) {
  $forum = bb_get_forum( get_forum_id( $id ) );
  $slug = $forum->forum_slug;
  if( $forum->forum_parent != 0 ) {
    $slug = get_forum_slug_recursive( $forum->forum_parent ).'/'.$slug;
  }
  
  return $slug;  
}

/**
 * Return forum nicer link
 *
 * @param string $link Forum link
 *
 * @global $bb
 *
 * @return string
 */
function get_forum_nicer_link( $link ) {
  global $bb;

	// Remove redundant "forum" word from forum link and append '/'. Mandatory! Props: Mohta	
	///return str_replace( $bb->uri . 'forum/', $bb->uri, $link ) . '/';
	
	$args = func_get_args(); 
	$forum_slug_recursive = get_forum_slug_recursive( $args[1] );
	//echo '<!--forum_slug_recursive'.var_export( $forum_slug_recursive, true ).'-->';

	return preg_replace( '~'.$bb->uri.'forum/[^/]*~', $bb->uri.$forum_slug_recursive, $link ).'/';
}

/**
 * Return topic nicer link
 *
 * @param string $link Topic link
 *
 * @global $bb
 * @global $topic
 *
 * @uses bb_get_topic_from_uri()
 * @uses wp_get_referer()
 * @uses add_query_arg()
 * @uses bb_get_forum()
 *
 * @return string
 */
function get_topic_nicer_link( $link ) {
	global $bb;

	if ( function_exists( 'is_pm' ) // bbPM plugin is activated
		&& false !== stripos( $_SERVER['REQUEST_URI'], $bb->path . 'pm/' ) // A bbPM page is requested
	)
		return $_SERVER['REQUEST_URI'] . '/'; // Append '/' to dodge .htaccess' rules

	global $topic;

	if ( empty( $topic ) || is_string( $topic ) ) { // bb-post.php names $topic a trimmed version of the post title, but here we are looking for the topic object
		$topic = bb_get_topic_from_uri( $link );

		if ( empty( $topic ) ) // Fix for bbPress 1.0.2 deleted topic redirection link
			return add_query_arg( 'view', 'all', wp_get_referer() );
	}

	// Replace "topic" word with parent forum slug to emphasize hierarchy
	///return str_replace( $bb->uri . 'topic', $bb->uri . bb_get_forum( $topic->forum_id )->forum_slug, $link );
	 
	$forum_slug_recursive = get_forum_slug_recursive( $topic->forum_id );	
	return str_replace( $bb->uri . 'topic', $bb->uri . $forum_slug_recursive, $link );
}

/**
 * Return post nicer link
 *
 * @param string $link    Post link
 * @param int    $post_id Post id
 *
 * @global $bb_post
 *
 * @uses bb_get_first_post()
 * @uses get_post_link()
 *
 * @return string
 */
function get_post_nicer_link( $link, $post_id = 0 ) {
	if ( empty( $post_id ) ) { // Fix for bbPress 1.0.2 Relevant "posts" links
		global $bb_post; // $bb_post actually is a topic object
		remove_filter( 'get_post_link',  'get_post_nicer_link', 10, 2 );
		$link = get_post_link( bb_get_first_post( $bb_post )->post_id );
		add_filter( 'get_post_link',  'get_post_nicer_link', 10, 2 );
	}

	return $link;
}

///
add_filter( 'wp_parse_str', 'fv_nicer_permalink_wp_parse_str' );
function fv_nicer_permalink_wp_parse_str( $array ) {
  unset( $array['parent_id'] );
  return $array;
}

add_filter( 'bb_repermalink', 'fv_nicer_permalink_bb_repermalink' );
function fv_nicer_permalink_bb_repermalink( $id ) {
  if( isset( $_GET['parent_id'] ) ) {
    //echo '<!--_GET'.var_export( $_GET, true ).'-->'."\n"; 
    
    global $bbdb;
    $parent_id = $bbdb->get_var( $bbdb->prepare( "SELECT forum_id FROM $bbdb->forums WHERE forum_slug = '%s'", $_GET['parent_id'] ) );
    //echo '<!--last_query'.var_export( $bbdb->last_query, true ).'-->'."\n";    
    $id = $bbdb->get_var( $bbdb->prepare( "SELECT forum_id FROM $bbdb->forums WHERE forum_slug = %s AND forum_parent = %d", $id, $parent_id ) );
    //echo '<!--last_query'.var_export( $bbdb->last_query, true ).'-->'."\n";     
    //echo '<!--parent_id'.var_export( $parent_id, true ).'-->'."\n";   
    //echo '<!--id'.var_export( $id, true ).'-->'."\n";  
  }

  return $id;
}

//add_filter( 'bb_slug_increment', 'fv_nicer_permalink_bb_slug_increment', 4 );
function fv_nicer_permalink_bb_slug_increment( $slug ) {
  $args = func_get_args();
  echo '<!--args'.var_export( $args, true ).'-->';die();
  
  return $slug;
}


/*
Non-existing forums were redirected to ?view=all and that was creating an endless loop. I'm not sure why yet - 2011/08/18
Ugly hack, TODO
*/
function fv_nicer_permalink_get_topic_link( $link ) {
  if( $link == '?view=all' ) {
    $link = bb_get_option( 'uri' );
  }
  
  return $link;
}
add_filter( 'get_topic_link', 'fv_nicer_permalink_get_topic_link' );
