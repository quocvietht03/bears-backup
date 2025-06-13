<?php

if( ! class_exists('BBACKUP_Backup_Panel') ) {
  class BBACKUP_Backup_Panel {

    public function __construct() {
      add_action( 'admin_menu', array( $this, 'admin_settings_menu' ) );
    }

    /**
     * @since 1.0.0
     * register admin backup panel menu
     */
    public function admin_settings_menu() {
      
      add_menu_page(
  			__('Backup Panel', 'bears-backup'),
  			__('Backup', 'bears-backup'),
  			'manage_options',
  			'bbackup-backup-panel',
  			array( $this, 'output' ),
        'dashicons-media-archive'
  		);

    }

    /**
     * @since 1.0.0
     * settings page output html
     */
    public function output() {
      ?>
      <div id="bbackup-backup-panel" class="bbackup-panel-wrap">
        <div class="panel-inner">
          <div class="panel-heading">
            <h2 class="title">
              <?php _e('Backup', 'bears-backup'); ?>
            </h2>
            <div class="bbackup-button-action">
              <el-popover
                ref="backup-advance-popover"
                placement="bottom-start"
                title="Advance Backup Data"
                width="280"
                trigger="hover">
                <div>
                  Please checked <el-checkbox v-model="package_import"></el-checkbox> (✔️ checkbox) for package is demo content!
                </div>
              </el-popover>

              <el-button v-popover:backup-advance-popover type="success" class="bbackup-button-backup-now" @click="BackupData($event)" icon="time"><?php _e('Backup Now', 'bears-backup'); ?></el-button>
              
              <el-upload
                class="bbackup-upload-file"
                ref="upload"
                :on-change="SubmitUploadBackup" 
                :before-upload="BeforUploadBackup" 
                :on-success="UploadBackupSuccess"
                :file-list="bbackup_upload_filelist"
                action="<?php echo add_query_arg( array( 'action' => 'BBACKUP_Upload_File_Backup' ), admin_url( 'admin-ajax.php' ) ); ?>">
                <el-tooltip content="<?php echo 'Maximum upload file size: ' . ini_get("upload_max_filesize"); ?>" placement="bottom">
                  <el-button >Upload Backup (.zip)</el-button>
                </el-tooltip>
              </el-upload>
            </div>
          </div>
          <div class="panel-body">
            <el-row :gutter="20">
              <el-col :span="24">
                <div class="grid-content">
                  <div class="bbackup-backup-step-bar">
                    <span class="progress-bar-action" :style="style_process_completed"></span>

                    <!-- Backup -->
                    <el-steps v-show="StepDisplay('backup')" :space="step_bar_conf.space" :active="step_bar_conf.active" finish-status="success">
                      <el-step title="<?php _e('Step 1: Database', 'bears-backup') ?>" description="<?php _e('Backup database', 'bears-backup') ?>"></el-step>
                      <el-step title="<?php _e('Step 2: File Config', 'bears-backup') ?>" description="<?php _e('Create file config', 'bears-backup') ?>"></el-step>
                      <el-step title="<?php _e('Step 3: Folder Uploads', 'bears-backup') ?>" description="<?php _e('Backup folder uploads', 'bears-backup') ?>"></el-step>
                      <el-step title="<?php _e('Finish', 'bears-backup') ?>" description="<?php _e('Backup success.', 'bears-backup') ?>"></el-step>
                    </el-steps>

                    <!-- Upload -->
                    <el-steps v-show="StepDisplay('upload')" :space="step_bar_conf.space" :active="step_bar_conf.active" finish-status="success">
                      <el-step title="<?php _e('Step 1: Upload', 'bears-backup') ?>" description="<?php _e('Upload file backup', 'bears-backup') ?>"></el-step>
                      <el-step title="<?php _e('Step 2: Extract Zip', 'bears-backup') ?>" description="<?php _e('Extract file backup (Unzip)', 'bears-backup') ?>"></el-step>
                      <el-step title="<?php _e('Finish', 'bears-backup') ?>" description="<?php _e('Upload success.', 'bears-backup') ?>"></el-step>
                    </el-steps>
                  </div>
                </div>
              </el-col>

              <el-col :span="24" :offset="0">
                <div class="grid-content">
                  <div class="bbackup-table-wrap">
                    <el-table
                      :data="backup_data"
                      :default-sort="sort_data"
                      :row-class-name="TableRowClassName" 
                      v-loading="table_bbackup_loading_st"
                      style="width: 100%"
                      class="bbackup-table">
                      <el-table-column type="index" width="50"></el-table-column>
                      <el-table-column prop="name" label="Backup Folder Name" sortable></el-table-column>
                      <el-table-column label="Operations" width="300">
                        <template scope="scope">
                          <el-button type="primary" size="small" @click="DownloadBackup(scope.row, backup_data)">Dowload</el-button>

                          <el-popover
                            placement="bottom-end">
                            <p><?php _e('Are you sure to restore this?', 'bears-backup') ?></p>
                            <div style="text-align: right; margin: 0">
                              <el-button type="primary" size="mini" @click="RestoreData(scope.row)">confirm</el-button>
                            </div>

                            <el-button slot="reference" type="primary" icon="time" size="small" v-loading.fullscreen.lock="fullscreenLoading">Restore</el-button>
                          </el-popover>

                          <el-popover
                            placement="bottom-end">
                            <p><?php _e('Are you sure to delete this?', 'bears-backup') ?></p>
                            <div style="text-align: right; margin: 0">
                              <el-button type="primary" size="mini" @click="DeleteItem(scope.row)">confirm</el-button>
                            </div>

                            <el-button slot="reference" type="text" icon="circle-close" size="small" @click="">Delete</el-button>
                          </el-popover>
                        </template>
                      </el-table-column>
                    </el-table>
                  </div>
                </div>
              </el-col>
            </el-row>
          </div>
        </div>
      </div>
      <?php
    }

  }

  return new BBACKUP_Backup_Panel;
}
