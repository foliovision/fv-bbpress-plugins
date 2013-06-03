<?php
/*
Plugin Name: FV Ajax Topic Title Changer
Description: Lets you edit the topic title in frontend.
Author: Folivision
Author URI: http://folivoision.com/
Version: 1.0
Plugin URI: http://foliovision.com/
*/

add_action('bb_ajax_change-topic-title','fv_change_topic_title_action' );

function fv_change_topic_title_action() {
  
  if( bb_current_user_can( 'keymaster' ) ) {
    if(strlen(trim($_POST['newtitle'])) <= 0) {    // check for the new title length if it's OK
      die('-1');
    }
    elseif(intval($_POST['topicid']) <= 0) {      // check if there wasn't some error when sending the topic ID
      die('-2');
    }  
    elseif( !bb_insert_topic( array( 'topic_title' => $_POST['newtitle'], 'topic_id' => $_POST['topicid'] ) ) ) {
      die('0');
    }
    else {
      die('1');
    }  
  }
  else {
    die('-3');  
  }
}

add_action('bb_foot', 'fv_print_change_topic_title_script');

function fv_print_change_topic_title_script() {
  if( bb_current_user_can( 'keymaster' ) && bb_is_topic() ) :
  ?>
  <!-- From the fv-title-ajax-changer plugin -->
  <script>
  jQuery(document).ready( function() {
      var strOriginalDocumentTitle = jQuery(document)[0].title;
      jQuery( "#topic-info h2.topictitle" ).click(function () {
        var strOriginalTopicTitle = jQuery(this).text();
        var strFontSize = jQuery(this).css('font-size');
        jQuery(this).hide();
        jQuery( "#topic-info" ).append(
          "<input class=\"new-title\" type=\"text\" value=\""+ this.innerHTML +"\" style=\"width: 82%; font-size: 12px; height:" + strFontSize + ";\" />"
          +"<input class=\"save-button submit-button\" type=\"button\" value=\"Save\"  style=\"margin-right: 5px; margin-left: 5px;\"/>"
          +"<input class=\"cancel-button submit-button\" type=\"button\" value=\"Cancel\"/>"
        );
        jQuery( "#topic-info .save-button" ).click( function(){
          var objSend = new Object;
          objSend.newtitle = jQuery( "#topic-info .new-title" )[0].value;
          objSend.action = 'change-topic-title';
          objSend.topicid = '<?php echo get_topic_id(); ?>';
          
          var url = '<?php echo bb_get_option('uri'); ?>bb-admin/admin-ajax.php';
          
          jQuery.post( url, objSend, 
            function( strReturn ){
              jQuery( "#topic-info .save-button" ).remove();
              jQuery( "#topic-info .cancel-button" ).remove();
              jQuery( "#topic-info .new-title" ).remove();
              
              if(strReturn == '1') {
                jQuery( "#topic-info h2.topictitle" ).show();
                jQuery( "#topic-info h2.topictitle" ).text(objSend.newtitle);
                var strNewDocumentTitle = strOriginalDocumentTitle.replace(strOriginalTopicTitle, objSend.newtitle);
                jQuery(document)[0].title = strNewDocumentTitle;
              }
              else if(strReturn == '-1') {
                jQuery( "#topic-info" ).html("<p style=\"color: red;\">There was a problem with changing the title...</p><br /><p style=\"color: red;\">The length of the new title has to be at least 1.</p>");
              }
              else if(strReturn == '-2') {
                jQuery( "#topic-info" ).html("<p style=\"color: red;\">There was a problem with changing the title...</p><br /><p style=\"color: red;\">The topic ID is 0 or negative integer.</p>");
              }
              else if(strReturn == '-3') {
                jQuery( "#topic-info" ).html("<p style=\"color: red;\">There was a problem with changing the title...</p><br /><p style=\"color: red;\">You are NOT a keymaster.</p>");
              }  
              else {
                jQuery( "#topic-info" ).text("There was a problem with changing the title...");
              }
            }
          );
          
        });
        jQuery( "#topic-info .cancel-button" ).click( function(){
          jQuery( "#topic-info .save-button" ).remove();
          jQuery( "#topic-info .cancel-button" ).remove();
          jQuery( "#topic-info .new-title" ).remove();
          jQuery( "#topic-info h2.topictitle" ).show();
          jQuery( "#topic-info h2.topictitle" ).text(strOriginalTopicTitle);  
        });
      });  
    });
  </script>
  <!-- end of From the fv-title-ajax-changer plugin -->
  <?php
  endif;
}

?>