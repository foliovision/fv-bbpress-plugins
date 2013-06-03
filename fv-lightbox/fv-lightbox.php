<?php
/*
Plugin Name: FV Lightbox
Description: Uses Slimbox2 scripts for showing images in lightboxes.
Author: Foliovision
Version: 1.0
Author URI: http://foiovision.com/
Plugin URI: http://foliovision.com/
*/

if( function_exists('fv_lightbox_scripts') ) {
  add_action('bb_head', 'fv_lightbox_scripts');
}

function fv_lightbox_scripts() {
  echo '<script type="text/javascript" src="http://foliovision.com/support/my-plugins/fv-lightbox/js/slimbox2.js"></script>'."\n";
  echo '<link rel="stylesheet" id="slimbox2-css"  href="http://foliovision.com/site/wp-content/plugins/wp-slimbox2/css/slimbox2.css?ver=1.1" type="text/css" media="screen" />'."\n";
}
?>