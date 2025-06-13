<?php

if( ! class_exists('BBACKUP_Demo_Content_Panel') ) {
  class BBACKUP_Demo_Content_Panel {

    public function __construct() {
      add_action( 'admin_menu', array( $this, 'admin_settings_menu' ) );
    }

    /**
     * @since 1.0.0
     * register admin backup panel menu
     */
    public function admin_settings_menu() {

      add_menu_page(
  			__('Demo Content', 'bears-backup'),
  			__('Demo Content', 'bears-backup'),
  			'manage_options',
  			'bbackup-demo-content-panel',
  			array( $this, 'output' ),
        'dashicons-archive'
  		);

    }

    /**
     * @since 1.0.0
     * settings page output html
     */
    public function output() {
      ?>
      <div id="demo-content-panel" class="bbackup-panel-wrap">
        
      </div>
      <?php
    }

  }

  return new BBACKUP_Demo_Content_Panel;
}
