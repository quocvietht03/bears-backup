<?php

if( ! function_exists('BBACKUP_Placeholder_Escape') ) {
  /**
   * @since 1.0.0
   *
   */
  function BBACKUP_Placeholder_Escape() {
    return '{bbackup_94402fb9ba80f392e0976ece85f92b2b876bfcaed8d83250cc06083f7139c7d9}';
  }
}

if(! function_exists('BBACKUP_Add_Placeholder_Escape')) {
  /**
   * @since 1.0.0
   */
  function BBACKUP_Add_Placeholder_Escape( $query ) {
    /*
     * To prevent returning anything that even vaguely resembles a placeholder,
     * we clobber every % we can find.
     */
    return str_replace( '%', BBACKUP_Placeholder_Escape(), $query );
  }
}

if( ! function_exists('BBACKUP_Backup_Setting_Data_Info') ) {
  /**
   * @since 1.0.0
   * Backup setting file info data
   */
  function BBACKUP_Backup_Setting_Data_Info() {
    global $wpdb;

    return array(
      'wpdb_prefix'         => $wpdb->prefix,
      'siteurl'             => get_option('siteurl'),
      'blogname'            => get_option( 'blogname' ),
      'blogdescription'     => get_option( 'blogdescription' ),
      'admin_email'         => get_option( 'admin_email' ),
      'placeholder_escape'  => BBACKUP_Placeholder_Escape(),
    );
  }
}

if( ! function_exists('BBACKUP_Scandir') ) {
  /**
   * @since 1.0.0
   * Scandir
   */
  function BBACKUP_Scandir($directory, $except = array()) {
    $result = scandir($directory);
    return array_diff($result, $except);
  }
}

if(! function_exists('BBACKUP_Recurse_Copy')) {
  /**
   * @since 1.0.0
   */
  function BBACKUP_Recurse_Copy($src, $dst) {
    // increase memory 256MB
    ini_set('memory_limit', '512M');

    $dir = opendir($src);
    @mkdir($dst);
    while(false !== ( $file = readdir($dir)) ) {
      if ( ! in_array( $file, array( '.', '..', 'bears-backup' ) ) ) {
        if ( is_dir($src . '/' . $file) ) {
          BBACKUP_Recurse_Copy($src . '/' . $file,$dst . '/' . $file);
        }
        else {
          copy($src . '/' . $file,$dst . '/' . $file);
        }
      }
    }
    closedir($dir);
  }
}

if( ! function_exists('BBACKUP_Get_Link_Download') ) {
  /**
   * @since 1.0.0
   * Get link download backup
   */
  function BBACKUP_Get_Link_Download($backup_folder) {
    return BBACKUP_UPLOAD_PATH . '/' . $backup_folder;
  }
}

if(! function_exists('BBACKUP_Get_Upload_Dir_Var')) {
  /**
   * Get the upload URL/path in right way (works with SSL).
   *
   * @param $param string "basedir" or "baseurl"
   * @return string
   */
  function BBACKUP_Get_Upload_Dir_Var( $param, $subfolder = '' ) {
    $upload_dir = wp_upload_dir();
    $url = $upload_dir[ $param ];

    if ( $param === 'baseurl' && is_ssl() ) {
      $url = str_replace( 'http://', 'https://', $url );
    }

    return implode('/', array($url, $subfolder));
  }
}

if( ! function_exists('BBACKUP_Delete_Folder_Backup') ) {
  /**
   * @since 1.0.0
   * Delete folder backup by folder name
   */
  function BBACKUP_Delete_Folder_Backup($data = array( 'backup_folder' => 'NAME_FOLDER_BACKUP_HERE' )) {
    global $wp_filesystem;

    if (empty($wp_filesystem)) {
      require_once (ABSPATH . '/wp-admin/includes/file.php');
      WP_Filesystem();
    }

    $backup_folder = $data['backup_folder'];

    if($wp_filesystem->is_dir( BBACKUP_UPLOAD_PATH . '/' . $backup_folder )) {

      // delete zip file
      if(file_exists(BBACKUP_UPLOAD_PATH . '/' . $backup_folder . '.zip')) {
        $wp_filesystem->rmdir( BBACKUP_UPLOAD_PATH . '/' . $backup_folder . '.zip', true );
      }

      // delete backup folder
    	$result = ($wp_filesystem->rmdir( BBACKUP_UPLOAD_PATH . '/' . $backup_folder, true )) ? 'OK' : 'KO';

      $message = array(
        'OK' => __('Delete success.', 'bears-backup'),
        'KO' => __('Delete fail.', 'bears-backup'),
      );

      wp_send_json( array(
        'st' => $result,
        'message' => $message[$result],
      ) );
    } else {
      wp_send_json( array(
        'st' => 'KO',
        'message' => __('Folder not exist!' , 'bears-backup'),
      ) );
    }

  }
}

if( ! function_exists('BBACKUP_Load_Backup_Data') ) {
  /**
   * @since 1.0.0
   * get all folder backup 
   */
  function BBACKUP_Load_Backup_Data() {
    if( ! is_dir( BBACKUP_UPLOAD_PATH ) ) {
      wp_send_json( array() ); return;
    }

    $files = BBACKUP_Scandir(BBACKUP_UPLOAD_PATH, array( '.', '..' ));
    $data = array();

    foreach($files as $file) {
      if( ! is_dir( BBACKUP_UPLOAD_PATH . '/' . $file ) ) continue;
      array_push($data, array(
        'name' => $file,
        'backup_path_file' => BBACKUP_Get_Link_Download( $file ),
      ));
    }

    wp_send_json( $data );
    exit(); return;
  }
}

if( ! function_exists('BBACKUP_Backup_Database') ) {
  /**
   * @since 1.0.0
   * Backup database func
   *
   * @param {array} $params
   */
  function BBACKUP_Backup_Database($params = array(), $return_type = 'json') {
    global $wpdb;
    // $wpdb->flush(); // Kill cached query results.

    $current_user = wp_get_current_user();
    $upload_dir   = wp_upload_dir();
    $package_import = (isset($params['package_import']) && $params['package_import'] == 'true') ? true : false;

    if ( isset( $current_user->user_login ) && ! empty( $upload_dir['basedir'] ) ) {

      if ( ! file_exists( BBACKUP_UPLOAD_PATH ) ) {
        wp_mkdir_p( BBACKUP_UPLOAD_PATH );
      }

      $backup_folder_name = wp_sprintf( '%s%s %s','b', ($package_import == true) ? 'demo' : 'backup', date('Y-m-d-h-i-s') );
      $backup_folder_name = str_replace( ' ', '_',  $backup_folder_name);

      /* create folder backup */
      if ( ! file_exists( BBACKUP_UPLOAD_PATH . '/' . $backup_folder_name ) ) {
        wp_mkdir_p( BBACKUP_UPLOAD_PATH . '/' . $backup_folder_name );
      }

      /* backup sql name */
      $backup_file_name = 'database.sql';

      // $db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
      // $dump = new MySQLDump($db);

      // $dump = new BBACKUP_MySQLDump($db);
      $dump = new BBACKUP_MySQLDump($wpdb);
      if( $package_import == true ) {
        $dump->table_exclude = array( $wpdb->prefix . 'users', $wpdb->prefix . 'usermeta' );
      }

      $dump->save( implode( '/', array( BBACKUP_UPLOAD_PATH, $backup_folder_name, $backup_file_name ) ) );
      $result = 'OK';

      $message = array(
        'OK' => __( 'Backup database success.', 'bears-backup' ),
        'KO' => __( 'Backup database error.', 'bears-backup' ),
      );

      $return_data = array(
        'success' => true,
        'st' => $result,
        'handle_name' => 'bbackup_backup_database',
        'message' => $message[$result],
        'progress' => 30,
        'bk_folder_name' => $backup_folder_name,
      );

      if( $return_type == 'json' ) { wp_send_json( $return_data ); }
      else { return $return_data; }

    } else {
      $return_data = array(
        'success' => false,
        'st' => 'KO',
        'handle_name' => 'bbackup_backup_database',
        'message' => __('Error: Backup database fail!!!', 'bears-backup'),
      );

      if( $return_type == 'json' ) { wp_send_json( $return_data ); }
      else { return $return_data; }
    }
  }
}

if(! function_exists('BBACKUP_Create_File_Config')) {
  /**
   * @since 1.0.0
   * Create file conf
   */
  function BBACKUP_Create_File_Config($data, $return_type = 'json') {
    global $wp_filesystem;

    // protect if the the global filesystem isn't setup yet
    if( is_null( $wp_filesystem ) ) WP_Filesystem();

    $backup_folder_name = $data['bk_folder_name'];
    $conf = BBACKUP_Backup_Setting_Data_Info();
    $conf_file_name = 'conf.json';

    $result = $wp_filesystem->put_contents(
      BBACKUP_UPLOAD_PATH . '/' . $backup_folder_name . '/' . $conf_file_name,
      json_encode( $conf ),
      FS_CHMOD_FILE
    );

    if( $result ) {
      $return_data = array(
        'success' => true,
        'st' => 'OK',
        'handle_name' => 'bbackup_create_file_config',
        'progress' => 60,
        'message' => __('Create file config success.', 'bears-backup'),
        'bk_folder_name' => $backup_folder_name,
      );

      if( $return_type == 'json' ) { wp_send_json( $return_data ); }
      else { return $return_data; }

    } else {
      $return_data = array(
        'success' => true,
        'st' => 'KO',
        'handle_name' => 'bbackup_create_file_config',
        'message' => __('Error: Create file conf fail!!!', 'bears-backup'),
      );

      if( $return_type == 'json' ) { wp_send_json( $return_data ); }
      else { return $return_data; }
    }
  }
}

if( ! function_exists('BBACKUP_Backup_Folder_Upload') ) {
  /**
   * @since 1.0.0
   * Backup folder upload
   */
  function BBACKUP_Backup_Folder_Upload($data, $return_type = 'json') {
    $backup_folder_name = $data['bk_folder_name'];
    $uploads_path = WP_CONTENT_DIR . '/' . 'uploads';
    $files_bk_name = '_f';

    $path_files_bk = BBACKUP_UPLOAD_PATH . '/' . $backup_folder_name . '/' . $files_bk_name;
    if ( ! file_exists( $path_files_bk ) ) {
      wp_mkdir_p( $path_files_bk );
    }

    BBACKUP_Recurse_Copy( $uploads_path, $path_files_bk );

    $return_data = array(
      'success' => true,
      'st' => 'OK',
      'handle_name' => 'bbackup_backup_folder_upload',
      'progress' => 90,
      'folder' => '', //$folders,
      'message' => __('Backup folder upload success.', 'bears-backup'),
    );

    if( $return_type == 'json' ) { wp_send_json( $return_data ); }
    else { return $return_data; }
  }
}

if(! function_exists('BBACKUP_Download_Backup')) {
  /**
   * @since 1.0.0
   * Download backup file
   *
   * @param {array} $params
   */
  function BBACKUP_Download_Backup($params = array()) {

    // increase memory 256MB
    ini_set('memory_limit', '512M');

    // Zip handle
    $zipFile = new \PhpZip\ZipFile();
    $zipFile
    ->addDirRecursive( $params['backup_path_file'] )
    ->saveAsFile( $params['backup_path_file']. '.zip' )
    ->close();

    if( file_exists( $params['backup_path_file']. '.zip' ) ) {
      $download_uri =  BBACKUP_Get_Upload_Dir_Var('baseurl', basename(BBACKUP_UPLOAD_PATH)) . '/' . basename( $params['backup_path_file']. '.zip' );
      wp_send_json_success(array('backup_path_file' => $download_uri));
    } else {
      wp_send_json_error();
    }
  }
}

if(! function_exists('BBACKUP_Restore_Data')) {
  /**
   * @since 1.0.0
   * Restore data
   *
   * @param {array} $data
   */
  function BBACKUP_Restore_Data($data, $return_type = 'json') {
    extract($data);

    if ( ! is_dir( $backup_path_file ) ) return;

    // increase memory 256MB
    ini_set('memory_limit', '512M');

    $upload_dir = wp_upload_dir();
    $sourcePath = implode('/', array($backup_path_file, '_f'));
    $targetPath = $upload_dir['basedir'];

    // clear folder uploads
    BBACKUP_Clear_Folder_Uploads();

    // restore folder uploads backup
    BBACKUP_Recurse_Copy($sourcePath, $targetPath);

    // restore databse backup
    $result_restore_database = BBACKUP_Restore_Database($data);

    $result_data = array(
      'success' => true,
      'data' => array(
        'message' => __('Restore success!'),
        'extra_data' => $result_restore_database,
      )
    );

    if( $return_type == 'json' ) {
      wp_send_json_success( $result_data );
    } else {
      return $result_data;
    }

  }
}

if(! function_exists('BBACKUP_Clear_Folder_Uploads')) {
  /**
   * @since 1.0.0
   */
  function BBACKUP_Clear_Folder_Uploads() {

    $upload_dir = wp_upload_dir();
    $uploadsPath = $upload_dir['basedir'];
    $exclude_file = array('.', '..', 'bears-backup');

    $folderInner = BBACKUP_Scandir($uploadsPath);

    global $wp_filesystem;
    if (empty($wp_filesystem)) {
      require_once (ABSPATH . '/wp-admin/includes/file.php');
      WP_Filesystem();
    }

    // increase memory 256MB
    ini_set('memory_limit', '512M');

    if( empty($folderInner) ) return;

    // each folder and delete
    foreach($folderInner as $folder) {
      if(in_array($folder, $exclude_file)) continue;

      // delete backup folder
    	$wp_filesystem->rmdir( $uploadsPath . '/' . $folder, true );
    }

    return array(
      'success' => true,
      'data' => array(
        'message' => __('Clear folder uploads success!'),
      )
    );
  }
}

if(! function_exists('BBACKUP_Restore_Database')) {
  /**
   * @since 1.0.0
   */
  function BBACKUP_Restore_Database($data) {
    extract($data);
    if ( ! is_dir( $backup_path_file ) ) return;

    global $wp_filesystem, $wpdb;
    $wpdb->flush(); // Kill cached query results.

    if (empty($wp_filesystem)) {
      require_once (ABSPATH . '/wp-admin/includes/file.php');
      WP_Filesystem();
    }

    $config = json_decode($wp_filesystem->get_contents(implode('/', array($backup_path_file, 'conf.json'))), true);
    $database = $wp_filesystem->get_contents( implode('/', array($backup_path_file, 'database.sql')) );

    $replace_variables = array(
      "DROP TABLE IF EXISTS `{$config['wpdb_prefix']}" => "DROP TABLE IF EXISTS `{$wpdb->prefix}",
      "CREATE TABLE `{$config['wpdb_prefix']}" => "CREATE TABLE `{$wpdb->prefix}",
      "INSERT INTO `{$config['wpdb_prefix']}" => "INSERT INTO `{$wpdb->prefix}",
      "{$config['wpdb_prefix']}user_roles" => "{$wpdb->prefix}user_roles",
      $config['siteurl']            => get_option('siteurl'),
      $config['blogname']           => get_option( 'blogname' ),
      $config['blogdescription']    => get_option( 'blogdescription' ),
      $config['admin_email']        => get_option( 'admin_email' ),
      $config['placeholder_escape'] => '%',
    );

    // increase memory 512MB
    ini_set('memory_limit', '512M');

    // edit database data & clone file
    $new_database = str_replace( array_keys($replace_variables), array_values($replace_variables), $database );

    $clone_databse_name = '__restore-databse.sql';
    $wp_filesystem->put_contents( implode('/', array($backup_path_file, $clone_databse_name)), $new_database );

    $import = new BBACKUP_MySQLImport($wpdb);
    $import->load( implode('/', array( $backup_path_file, $clone_databse_name ) ) );

    // Fix Serialization
    $fix_serialization_count = BBACKUP_Serialization_Fix_Run_Script();

    // remove file clone
    wp_delete_file( implode('/', array( $backup_path_file, $clone_databse_name ) ) );

    return array(
      'success' => true,
      'data' => array(
        'message' => __('Restore database success!'),
        'fix_serialization_count' => $fix_serialization_count,
      )
    );
  }
}

if(! function_exists('BBACKUP_Fix_Str_Length')) {
  function BBACKUP_Fix_Str_Length($m) {
    $len = strlen($m[2]);
    $data = $m[2];
    return "s:$len:\"$data\";";
  }
}

if(! function_exists('BBACKUP_Fix_String_Serialized') ) {
  /**
   * @since
   */
  function BBACKUP_Fix_String_Serialized($string) {
    return preg_replace_callback( '/s:(\d+):"(.*?)";/', 'BBACKUP_Fix_Str_Length', $string );
  }
}

if(! function_exists('BBACKUP_Serialization_Fix_Run_Script')) {
  function BBACKUP_Serialization_Fix_Run_Script(){
    // increase memory 512MB
    ini_set('memory_limit', '512M');

    global $wpdb, $serializationFixedCount;
    $wpdb->flush(); // clear cache
    $count = 0;

    // OPTIONS
    $sql = "SELECT * FROM $wpdb->options WHERE option_value RLIKE 's:'";
    $options = $wpdb->get_results($sql);
    foreach( $options as $option ){

      $string = BBACKUP_Fix_String_Serialized($option->option_value);
      if( $string == $option->option_value ){ continue; }

      $count++;
      update_option( $option->option_name, unserialize($string), $option->autoload );
    }

    // POSTS META
    $sql = "SELECT * FROM $wpdb->postmeta WHERE meta_value RLIKE 's:'";
    $metas = $wpdb->get_results($sql);
    foreach( $metas as $meta ){
      $string = BBACKUP_Fix_String_Serialized($meta->meta_value);
      if( $string == $meta->meta_value ){ continue; }

      $count++;
      update_post_meta( $meta->post_id, $meta->meta_key, unserialize($string) );
    }

    // USER META
    $sql = "SELECT * FROM $wpdb->usermeta WHERE meta_value RLIKE 's:'";
    $metas = $wpdb->get_results($sql);
    foreach( $metas as $meta ){
      $string = BBACKUP_Fix_String_Serialized($meta->meta_value);
      if( $string == $meta->meta_value ){ continue; }

      $count++;
      update_user_meta( $meta->user_id, $meta->meta_key, unserialize($string) );
    }

    $serializationFixedCount = $count;
    return $count;
  }
}

if(! function_exists('BBACKUP_Serialization_Fix_String')) {
  /**
   * Fix broken serialized strings by recalculating length
   * @param string $string
   * @return string
   */
  function BBACKUP_Serialization_Fix_String($string){
    if( !is_serialized($string) ){
      return $string;
    }
    try {
      if( unserialize($string) == FALSE ){
        throw new Exception("Broken string", 1);
      }
    } catch(exception $e) {
      $string = preg_replace(
        '!s:(\d+):"(.*?)";!e',
        "'s:'.strlen('$2').':\"$2\";'",
        $string
      );
    }
    return $string;
  }
}

if(! function_exists('BBACKUP_Move_Extract_Backup_Upload_File')) {
  /**
   * @since 1.0.0
   */
  function BBACKUP_Move_Extract_Backup_Upload_File($params) {
    global $wp_filesystem;
    if (empty($wp_filesystem)) {
      require_once (ABSPATH . '/wp-admin/includes/file.php');
      WP_Filesystem();
    }

    if ( ! file_exists( BBACKUP_UPLOAD_PATH ) ) {
      wp_mkdir_p( BBACKUP_UPLOAD_PATH );
    }

    // move backup file
    $destination = implode('/', array( BBACKUP_UPLOAD_PATH, '/', basename($params['file']) ));
    $result = $wp_filesystem->move($params['file'], $destination, true);
    if( $result ) {
      // extract zip
      $unzipfile = unzip_file( $destination, implode('', array(BBACKUP_UPLOAD_PATH, '/', basename($params['file'], '.zip'))));

      if ( is_wp_error( $unzipfile ) ) {
        wp_send_json_error( array(
          'message' => __('Extract fail!', 'bears-backup'),
        ) );
      } else {
        // remove zip file
        $wp_filesystem->delete($destination);

        wp_send_json_success( array(
          'message' => __('Upload backup complete!', 'bears-backup'),
        ) );
      }
    } else {
      wp_send_json_error( array(
        'message' => __('Extract fail!', 'bears-backup'),
      ) );
    }
  }
}

if(! function_exists('BBACKUP_Helper_Function_File_Appent_Content')) {
  /**
   * @since 1.0.0
   */
  function BBACKUP_Helper_Function_File_Appent_Content($path_file, $content) {
    return file_put_contents($path_file, $content, FILE_APPEND);
  }
}
