<?php
if( ! function_exists('BBACKUP_Ajax_Handle') ) {
  /**
   * @since 1.0.0
   * Ajax handle processes
   */
  function bbackup_ajax_handle() {
    $data = array_merge(array(
      'handle' => '',
      'params' => '',
    ), $_POST);
    extract( $data );

    if( function_exists($handle) ) {
      call_user_func($handle, $params);
    }

    exit();
  }
  add_action( 'wp_ajax_BBACKUP_Ajax_Handle', 'BBACKUP_Ajax_Handle' );
  add_action( 'wp_ajax_nopriv_BBACKUP_Ajax_Handle', 'BBACKUP_Ajax_Handle' );
}

if(! function_exists('BBACKUP_Upload_File_Backup')) {
  /**
   * @since 1.0.0 
   */
  function BBACKUP_Upload_File_Backup() {
    @ini_set( 'upload_max_size' , '512M' );
    @ini_set( 'post_max_size' , '512M' );

    $file = $_FILES['file'];

    $upload_overrides = array( 
      'test_form' => false,
      'mimes' => array(
        'zip' => array('application/zip+octet-stream'),
      ),
    );
    $movefile = wp_handle_upload( $file, $upload_overrides );

    if ( $movefile ) {
      wp_send_json_success( $movefile );
    } else {
      wp_send_json_error( $movefile );
    }

    exit();
  }

  add_action( 'wp_ajax_BBACKUP_Upload_File_Backup', 'BBACKUP_Upload_File_Backup' );
  add_action( 'wp_ajax_nopriv_BBACKUP_Upload_File_Backup', 'BBACKUP_Upload_File_Backup' );
}