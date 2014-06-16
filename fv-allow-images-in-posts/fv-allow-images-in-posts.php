<?php
/**
 * Plugin Name: FV Allow Images In Posts
 * Plugin Description: Allows forum users to post <code>&lt;img /&gt;</code> tags in their comments.
 * Author: Foliovision
 * Author URI: http://foliovision.com   
 * Version: 1.0
 */ 
 
add_filter( 'bb_allowed_tags', 'fv_allow_images_filter' );

function fv_allow_images_filter( $tags ) {

  //if (bb_current_user_can('moderate')) {
  $tags['img'] = array( 'src' => array(),
                        'title' => array(),
                        'alt' => array() 
                      );
  return $tags;
}
?>