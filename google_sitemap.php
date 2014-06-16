<?php
/**
 * Plugin Name: Google Sitemaps
 * Plugin Description: This sitemap Generator creates a XML Sitemap which shows those URLs marked as "index" while using the bbpress SEO tools. The plugin is based on the work from Rich Boakes (boakes.org).  
 * Author: Olaf Lederer
 * Author URI: http://www.finalwebsites.com/
 * Version: 0.1
*/

// the full path the the file that will be written - write access
// is required so set up your user /group privilages correctly or
// remember to "chmod 777 $sitemap_file" before runtime.
$sitemap_file = $_SERVER['DOCUMENT_ROOT']."/support/sitemap.xml";

$version='0.1';
$generator="http://foliovision.com";

// change the options below to match the forum post freqency
$frequency = 'daily'; // daily, weekly, or monthly
$priority = 0.6; // between 0.1 and 1.0

// function to create a sitemap "onPost""
add_hooks();

/**
 * Find all topics in the database and add them to the data that
 * will be added to the sitemap;
 */
function discover_topics() {
	global $bbdb, $frequency, $priority;
	$topic_query = "SELECT t.topic_id AS tid, p.post_time AS tim, t.topic_posts AS nposts, t.forum_id AS forum_id FROM $bbdb->posts p, $bbdb->topics t WHERE p.topic_id = t.topic_id AND post_status = 0 AND topic_status = 0 GROUP BY t.topic_id ORDER BY p.post_time DESC";
	$matches = $bbdb->get_results($topic_query);
  
  $moderated = $bbdb->get_results( "SELECT topic_id, post_id, post_time, post_status FROM bb_posts GROUP BY topic_id HAVING MIN(post_time)", OBJECT_K );

	if ($matches) {
		foreach($matches as $match) {
      
      if( isset( $moderated[$match->tid] ) && $moderated[$match->tid]->post_status == -1 ) {
        continue;
      }      
		
			$url = get_topic_link($match->tid);

			$time = strtotime($match->tim);
			$nice_time = date("Y-m-d", $time);

			if ($match->nposts > 10) {
				$prio = ($match->nposts > 20) ? 0.8 : 0.7;
				$freq = (($time+(3600*24*30)) < time()) ? 'daily' : 'weekly';
				$pages = floor($match->nposts/10);
				for ($i = 1; $i <= $pages; $i++) {
					$p_url = $url.'/page/'.($i+1);
					add_row($p_url, $nice_time, $freq, $prio);
				}
			} 
			add_row($url, $nice_time, $frequency, $priority);
		}
	}
}


/**
 * Add a row of data to the result set that will be written to the file.
 */
function add_row($url, $mod, $freq, $priority) {
	global $rows;
	$rows[] = array('url'=>$url, 'mod_time'=>$mod, 'freq'=>$freq, 'priority'=>$priority);
}

/**
 * Manage the file handling bit of writing to the sitemap file
 */
function dump_sitemap_file() {
	global $sitemap_file;
	$f = fopen($sitemap_file, "w");
	dump_sitemap_header($f);
	dump_rows($f);
	dump_sitemap_footer($f);
	fflush($f);
	fclose($f);
}

/**
 * Neatly package up and write the XML header for the file
 */
function dump_sitemap_header($handle) {
	global $version, $generator;
	fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>');
	fwrite($handle, '<!-- Generator: '.$generator.' -->');
	fwrite($handle, '<!-- Generator-Version: '.$version.' ('.substr(md5_file(__FILE__),0,4).') -->');
	fwrite($handle, '<!-- Page generated: '.date("Y-m-d H:i:s",time()).' -->');
	fwrite($handle, '<urlset xmlns="http://www.google.com/schemas/sitemap/0.84" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.google.com/schemas/sitemap/0.84 http://www.google.com/schemas/sitemap/0.84/sitemap.xsd">');
	
	fwrite($handle, '<url>');
	fwrite($handle, '	<loc>http://foliovision.com/support</loc>');
	fwrite($handle, '	<lastmod>'.date("Y-m-d").'</lastmod>');
	fwrite($handle, '	<changefreq>daily</changefreq>');
	fwrite($handle, '	<priority>1.0</priority>');
	fwrite($handle, '</url>');
}

/**
 * Neatly package up and write the XML footer for the file
 */
function dump_sitemap_footer($handle) {
	fwrite($handle, '</urlset>');
}


/**
 * Dump every row that's been discovered to the available file handle
 */
function dump_rows($handle) {
	global $rows;
	foreach($rows as $row) {
		dump_row($handle, $row);
	}
	$rows = array();
	unset($rows);
}

/**
 * Write out the individual row data
 */
function dump_row($handle, $row) {
	$u = parse_url($row['url']);
	$u2 = $u['scheme'].'://'.$u['host'].$u['path'];
	fwrite($handle, '<url>'.PHP_EOL);
	fwrite($handle, '	<loc>'.$u2.'</loc>'.PHP_EOL);
	fwrite($handle, '	<lastmod>'.$row['mod_time'].'</lastmod>'.PHP_EOL);
	fwrite($handle, '	<changefreq>'.$row['freq'].'</changefreq>'.PHP_EOL);
	fwrite($handle, '	<priority>'.$row['priority'].'</priority>'.PHP_EOL);
	fwrite($handle, '</url>'.PHP_EOL);
}


function sometimes_create_sitemap() {
	// this hook gets fired an awful lot, so to reduce
	// the server workload, there's only a 1 in 10 chance
	// that it will actually do anything.  Most of the time
	// it just returns.
	$n = mt_rand(1, 10);
	if ( ($n % 10) ) {
		create_sitemap();
	}
	
}
/**
 * This is the main function that gets linked to various bb_press
 * hooks (aka actions).
 */
function create_sitemap() {
	// discover what there is to know
	discover_topics();
	// write it out
	dump_sitemap_file();
	// finished already!
}

/**
 * BBPress Hooks which the plugin uses to ensure it knows
 * when there has been an update.
 */
function add_hooks() {
	add_filter( 'bb_new_topic', 'create_sitemap', 0); // edit FW
	add_filter( 'bb_update_topic', 'sometimes_create_sitemap', 0);
	add_filter( 'bb_delete_topic', 'create_sitemap', 0); // edit FW

	add_filter( 'bb_new_post', 'sometimes_create_sitemap', 0);
	add_filter( 'bb_update_post', 'sometimes_create_sitemap', 0);
	add_filter( 'bb_delete_post', 'sometimes_create_sitemap', 0);
}
