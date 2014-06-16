<?php
/**
 * Plugin Name: FV BB Solved
 * Plugin Description: Mark topics as solved. Requires a lot of code in topic.php
 * Author: Foliovision  
 * Version: 0.1
 */ 
 

add_action('bb_ajax_fv_bb_solved','fv_bb_solved_ajax_action' );

function fv_bb_solved_ajax_action() {
  
  if(!bb_verify_nonce($_POST['nonce'], 'fv_bb_solved-'. $_POST['topic_id'])) {
    die('-2');
  }

  if(bb_current_user_can( 'moderate' ) ) {
    if(intval($_POST['topic_id']) <= 0) {      // check if there wasn't some error when sending the topic ID
      die('-1');
    }  
    
    if( !bb_get_topicmeta( $_POST['topic_id'], 'fv_bb_solved' ) ) {
			bb_update_meta( $_POST['topic_id'], 'fv_bb_solved', true, 'bb_topic' );
			die('1');
		} else {
			bb_delete_meta( $_POST['topic_id'], 'fv_bb_solved', false, 'bb_topic' );
			die('2');
		}
    
  }
  else {
    die('0');  
  }
}


?>