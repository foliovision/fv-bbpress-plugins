<?php
/*
Plugin Name: Nicer Permalinks FV
Plugin URI: http://bbpress.org/plugins/topic/nicer-permalinks/
Description: FV version puts names of forums into the topic url: /forum1/subforum1/topic. It had some issues with not existing topics because of that, so there is a hack on get_topic_link in it. Also .htaccess is heavily modified.
Version: 100.5.0.7fv.2
Author: mr_pelle
Author URI: http://scr.im/mrpelle
*/

/**
 * @license CC-GNU-GPL http://creativecommons.org/licenses/GPL/2.0/
 */


/**
 * Define constants
 */
define( 'NICER_PERMALINKS_ID',   'nicer-permalinks' );
define( 'NICER_PERMALINKS_NAME', 'Nicer Permalinks' );


// Create text domain for translations
bb_load_plugin_textdomain( NICER_PERMALINKS_ID, dirname( __FILE__ ) . '/languages' );


if ( bb_is_admin() ) // Load admin.php if on admin area
	require_once( 'includes/admin.php' );

if ( nicer_permalinks_enabled() ) // Load plugin core if plugin is enabled
	require_once( 'includes/nicer-filters.php' );

// Call uninstaller on plugin deactivation
bb_register_plugin_deactivation_hook( __FILE__, 'nicer_permalinks_uninstall' );


/**
 * Functions
 */

/**
 * Whether or not plugin is enabled
 *
 * @uses bb_get_option()
 *
 * @return boolean
 */
function nicer_permalinks_enabled() {
	return (bool) bb_get_option( 'nicer_permalinks_enabled' );
}
