<?php
if( ! function_exists('BBACKUP_Ajax_Handle') ) {
  /**
   * @since 1.0.0
   * Ajax handle processes
   */
  function bbackup_ajax_handle() {

    # nonce verify
    if( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bears_backup_nonce' ) ) {
      wp_send_json_error( 'Invalid nonce.' );
      exit();
    }
    # end nonce verify

    /**
     * Fix issue security
     * verify only admin can access
     */
    if( ! current_user_can( 'manage_options' ) ) {
      wp_send_json_error( 'You are not authorized to access this page.' );
      exit(); 
    }
    /** End fix issue security */

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
  // add_action( 'wp_ajax_nopriv_BBACKUP_Ajax_Handle', 'BBACKUP_Ajax_Handle' );
}

if(! function_exists('BBACKUP_Upload_File_Backup')) {
  /**
   * @since 1.0.0 
   */
  function BBACKUP_Upload_File_Backup() {
    /**
      * Fix issue security
      * verify only admin can access
      */
      if( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'You are not authorized to access this page.' );
        exit();
    }
    /** End fix issue security */
    
    @ini_set( 'upload_max_size' , '512M' );
    @ini_set( 'post_max_size' , '512M' );

    /**
     * file type verify
     */
    $file = $_FILES['file'];
    $allowed_types = array('application/zip', 'application/x-zip-compressed', 'application/zip+octet-stream');
    $max_size = 150 * 1024 * 1024; // 150MB

    /** verify file type */
    if (!in_array($file['type'], $allowed_types)) {
      wp_send_json_error('Invalid file type');
      exit();
    }

    /** verify file size */
    if ($file['size'] > $max_size) {
      wp_send_json_error(sprintf('File size is too large, max size is %sMB', ($max_size / 1024 / 1024)));
      exit();
    }

    // wp_send_json_error( 'test' );
    // exit();
    
    $upload_overrides = array( 
      'test_form' => false,
      'mimes' => array(
        'zip' => array('application/zip+octet-stream'),
      ),
    );

    // wp_send_json_error( [$file, $upload_overrides] );
    // exit();

    $movefile = wp_handle_upload($file, array(
        'test_form' => false,
        'mimes' => array(
            'zip' => 'application/zip'
        )
    ));

    if ( $movefile ) {
      wp_send_json_success( $movefile );
    } else {
      wp_send_json_error( $movefile );
    }

    exit();
  }

  add_action( 'wp_ajax_BBACKUP_Upload_File_Backup', 'BBACKUP_Upload_File_Backup' );
  // add_action( 'wp_ajax_nopriv_BBACKUP_Upload_File_Backup', 'BBACKUP_Upload_File_Backup' );
}