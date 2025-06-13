<?php
/*
 * Plugin Name: Bears Backup
 * Plugin URI: http://bearsthemes/
 * Description: Great plugin helpfull for backup (database, media), create and import dummy data.
 * Version: 2.0.0
 * Author: Bearsthemes
 * Author URI: http://bearsthemes.com/
 * Text Domain: bears-backup
 *
 * Copyright: © 2015-2018 Bearsthemes.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * PHP Composer
 */
require 'vendor/autoload.php';
require 'vendor/fix-import.php';

if(! class_exists('Bears_Backup') ) {

  class Bears_Backup
  {

    public $BBACKUP_MAIN = array();

    function __construct ()
    {
      /* defined */
      $this->defined();

      /* include */
      $this->inc();

      /* Hooks */
      $this->hooks();
    }

    public function defined()
    {
      define( "BBACKUP_VERSION", '2.0.0' );
      define( "BBACKUP_DIR_PATH", plugin_dir_path( __FILE__ ) );
      define( "BBACKUP_DIR_URL", plugin_dir_url( __FILE__ ) );
      define( "BBACKUP_UPLOAD_PATH", $this->upload_path() );
    }

    /**
     * @since 1.0.0
     */
    public function upload_path ()
    {
      $upload_dir = wp_upload_dir();
      return $upload_dir['basedir'] . '/' . 'bears-backup';
    }

    /**
     * @since 1.0.0
     * include PHP file
     */
    public function inc()
    {
      if ( is_admin() ) {
        /* MySQL Dump */
        require_once plugin_dir_path( __FILE__ ) . '/inc/class.WPMySQLDump.php';
        require_once plugin_dir_path( __FILE__ ) . '/inc/class.WPMySQLImport.php';

        /* functions */
        require_once plugin_dir_path( __FILE__ ) . '/inc/functions.php';

        /* Ajax func */
        require_once plugin_dir_path( __FILE__ ) . '/inc/ajax.php';

        /* backup panel menu page */
        $this->BBACKUP_MAIN['BBACKUP_Backup_Panel'] = require( plugin_dir_path( __FILE__ ) . '/inc/backup-panel.php' );
      }
    }

    /**
     * @since 1.0.0
     * Hooks
     */
    public function hooks()
    {
      /* hook 'admin_enqueue_scripts' */
      add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
    }

    /**
     * @since 1.0.0
     * load script
     * use jQuery, Vue, Element Ui
     */
    public function scripts()
    {
      $page_load_scripts = apply_filters('bears_backup_page_load_scripts', array('bbackup-backup-panel', 'dbc-demo-content-panel'));
      if(empty($_GET['page']) || ! in_array( $_GET['page'], $page_load_scripts ) ) return;

      wp_enqueue_script( 'vue', plugins_url( '/assets/vendor/vue/vue.min.js', __FILE__ ), array(), '2.4.4', true );

      wp_enqueue_style( 'element-ui', plugins_url( '/assets/vendor/element-ui/element-ui.css', __FILE__ ), array(), '1.4.6', 'all' );
      wp_enqueue_script( 'element-ui', plugins_url( '/assets/vendor/element-ui/element-ui.js', __FILE__ ), array('vue'), '1.4.6', true );
      wp_enqueue_script( 'element-ui-en', plugins_url( '/assets/vendor/element-ui/element-ui-en.js', __FILE__ ), array('element-ui'), '1.4.6', true );
      // wp_add_inline_script( 'element-ui-en', 'ELEMENT.locale(ELEMENT.lang.en);' );

      wp_enqueue_style( 'bears_backup_style', plugins_url( '/assets/css/bears-backup.backend.css', __FILE__ ), array(), BBACKUP_VERSION, 'all' );
      wp_enqueue_script( 'bears_backup_script', plugins_url( '/assets/js/bears-backup.bundle.js', __FILE__ ), array('jquery'), BBACKUP_VERSION, true );

      wp_localize_script( 'bears_backup_script', 'bbackup_object', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'language' => array(

        )
      ) );
    }

  }

  $GLOBALS['Bears_Backup'] = new Bears_Backup();
}
