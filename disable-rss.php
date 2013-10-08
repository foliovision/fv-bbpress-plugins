<?php

/*
Tweak name: FV bbPress feed disabler
*/

/*
rename this file to rss.php and put it into the bbPress directory to disable feeds if you want to
*/

include( 'bb-load.php' );

if( isset($_GET['topic']) ) {
	header( "Location: ".trailingslashit(bb_get_option('uri'))."topic.php?id=".$_GET['topic'] );
	die();
}

if( isset($_GET['forum']) ) {
	header( "Location: ".trailingslashit(bb_get_option('uri'))."forum.php?id=".$_GET['topic'] );
	die();	
}

header( "Location: ".bb_get_option('uri') );
die();
