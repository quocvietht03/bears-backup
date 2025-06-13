/**
 * @package Bears Demo content
 * Backup panel script
 */

export default function () {
  var $ = jQuery.noConflict();
  var ajax_url = bbackup_object.ajax_url;
  
  return {
    el: '#bbackup-backup-panel',
    data () {
      return {
        backup_data: [],
        sort_data: {
          prop: 'name',
          order: 'descending',
        },
        step_bar_conf: {
          space: 200,
          active: 0,
          process_completed: 0,
        },
        table_bbackup_loading_st: false,
        process_type: '',
        ajax_handle: false,
        row_in_process: {},
        new_folder_backup: '',
        package_import: false,
        bbackup_upload_filelist: [],
        fullscreenLoading: false,
      };
    },
    created (el) {
      this.LoadBackupData();
    },
    watch: {
      'step_bar_conf.active' (step) {
        var self = this;
        if( self.process_type == '' ) return;

        switch(self.process_type) {
          case 'backup':
            if(step >= 4){
              setTimeout(function() { self.process_type = ''; }, 5000);
            }
            break;

          case 'upload': 
            if(step >= 3){
              setTimeout(function() { self.process_type = ''; }, 5000);
            }
            break;
        }
      }
    },
    computed: {
      style_process_completed () {
        return {
          width: this.step_bar_conf.process_completed + '%',
        };
      },
    },
    methods:{
      TableRowClassName(row) {
        var _classes = [];

        if( this.new_folder_backup == row.name ) _classes.push( 'is-new-row' );
        if( row == this.row_in_process ) _classes.push( 'is-process-row' );

        return _classes.join(' ');
      },
      DeleteItem(row) {
        var self = this;
        var backup_folder = row.name;

        this.row_in_process = row;

        window.bbackup_helpers._request({
          data: { action: 'BBACKUP_Ajax_Handle', handle: 'BBACKUP_Delete_Folder_Backup', params: { backup_folder: backup_folder  } },
          success (res) {
            console.log(res);

            if(res.st == 'OK') {
              self.$notify({ title: 'Success', message: res.message, type: 'success', offset: 100 });
              self.LoadBackupData();
            } else {
              self.$notify.error({ title: 'Error', message: res.message, offset: 100 });
            }
          }
        })
      },
      DownloadBackup (row, rows) {
        var self = this;
        this.row_in_process = row;

        window.bbackup_helpers._request({
          data: { action: 'BBACKUP_Ajax_Handle', handle: 'BBACKUP_Download_Backup', params: row },
          success (res) {
            Vue.set(self, 'row_in_process', {});

            if(res.success) {
              window.location.assign(res.data.backup_path_file);
            } else {
              console.log('fail!!!');
            }
          },
        })
      },
      LoadBackupData () {
        var self = this;
        this.table_bbackup_loading_st = true;
        window.bbackup_helpers._request({
          data: { action: 'BBACKUP_Ajax_Handle', handle: 'BBACKUP_Load_Backup_Data' },
          success (res) {
            self.table_bbackup_loading_st = false;
            self.backup_data = res;
          },
        })
      },
      StepDisplay (type) {
        return type == this.process_type;
      },
      BackupData (event) {
        var self = this;
        var Current_Processes = 0;
        var BK_Folder_Name = '';
        var Has_Error = false;

        this.process_type = 'backup';

        var Backup_Processes = [
          {
            handle: 'BBACKUP_Backup_Database',
            params: { package_import: self.package_import },
            On_Start () {
              // reset step bar
              self.step_bar_conf.active = 0;
              self.step_bar_conf.process_completed = 0;
            },
            On_After_Success (res) { 
              BK_Folder_Name = res.bk_folder_name;
              Backup_Processes[Current_Processes+1].params.bk_folder_name = BK_Folder_Name;

              console.log( 'Backup database!' );
            },
            On_Error () {
              console.log('fail!!!');
              self.$notify.error({ title: 'Error', message: 'Backup databse fail!', offset: 100 });
            },
          },
          {
            handle: 'BBACKUP_Create_File_Config',
            params: {},
            On_After_Success (res) {
              Backup_Processes[Current_Processes+1].params.bk_folder_name = BK_Folder_Name;
              console.log( 'Create file config.json!' );
            },
            On_Error () {
              console.log('fail!!!');
              self.$notify.error({ title: 'Error', message: 'Create file config.json fail!', offset: 100 });
            },
          },
          {
            handle: 'BBACKUP_Backup_Folder_Upload',
            params: {},
            On_After_Success (res) {
              console.log( 'Backup folder Uploads!' );
              // setTimeout(function() { self.process_type = ''; }, 5000);
            },
            On_Error () {
              console.log('fail!!!');
              self.$notify.error({ title: 'Error', message: 'Backup folder Uploads fail!', offset: 100 });
            },
          },
          {
            ajax: false,
            action () {
              setTimeout(function() {
                var message = 'Backup success!';
                console.log( message );

                self.step_bar_conf.active = 4;                // finish (step 4)
                self.step_bar_conf.process_completed = 100;   // 100% process (backup success)
                self.new_folder_backup = BK_Folder_Name;

                self.LoadBackupData();

                self.$notify({ title: 'Success', message: message, type: 'success', offset: 100 });
              }, 2000)
            },
          }
        ];

        var Backup_Step_By_Step = function(opts) {
          var data = $.extend({
            ajax: true,
            action: 'BBACKUP_Ajax_Handle',
            handle: '',
            On_Start () {},
            On_After_Success () {},
            On_Error () {},
          }, opts);

          var _f = {
            NextStep () {
              Current_Processes += 1;
              Backup_Step_By_Step(Backup_Processes[Current_Processes]);
            },
          };

          data.On_Start.call(null);

          if(data.ajax != true) { data.action.call(_f); return; }

          window.bbackup_helpers._request({
            data: { action: data.action, handle: data.handle, params: data.params },
            success (res) {
              if(res.st != 'OK') {
                Has_Error = true;
                if(data.On_Error) data.On_Error.call(this, res);
                return;
              }

              if(data.On_After_Success) data.On_After_Success.call(this, res);

              Current_Processes += 1;
              self.step_bar_conf.active = Current_Processes;
              self.step_bar_conf.process_completed = res.progress;
              if(Backup_Processes[Current_Processes]) Backup_Step_By_Step(Backup_Processes[Current_Processes]);
            }
          });
        }
        Backup_Step_By_Step(Backup_Processes[Current_Processes]);
      },
      BeforUploadBackup	() {
        // set process type
        this.process_type = 'upload';

        // reset step bar
        this.step_bar_conf.active = 0;
        this.step_bar_conf.process_completed = 0;
      },
      SubmitUploadBackup (event) {
        var self = this;
      },
      UploadBackupSuccess (res, file, fileList) {
        var self = this;

        if(res.success == true) {
          if(res.data.error) {
            // has error
            self.$notify({ title: 'Error', message: res.data.error, type: 'error', offset: 100, duration: 5000 });
            return;
          }

          // set step bar
          self.step_bar_conf.active = 1;
          self.step_bar_conf.process_completed = 30;

          // clear file list
          Vue.set(self, 'bbackup_upload_filelist', []);

          // upload file success
          self.MoveExtractBackupFile( res.data );
        }
      },
      MoveExtractBackupFile (data) {
        var self = this;

        window.bbackup_helpers._request({
          data: { action: 'BBACKUP_Ajax_Handle', handle: 'BBACKUP_Move_Extract_Backup_Upload_File', params: data },
          success(res) {
            console.log(res);

            if(res.success == true) {
              // set step bar
              self.step_bar_conf.active = 2;
              self.step_bar_conf.process_completed = 70;

              setTimeout(function() {
                // set step bar
                self.step_bar_conf.active = 3;
                self.step_bar_conf.process_completed = 100;
                self.LoadBackupData();

                self.$notify({ title: 'Success', message: res.data.message, type: 'success', offset: 100, duration: 5000 });
              }, 2000)
            } else {
              self.$notify({ title: 'Error', message: res.data.message, type: 'error', offset: 100, duration: 5000 });
            }
          }
        })
      },
      RestoreData(row) {
        var self = this;
        var backup_folder = row.name;
        self.fullscreenLoading = true;
        window.bbackup_helpers._request({
          data: { action: 'BBACKUP_Ajax_Handle', handle: 'BBACKUP_Restore_Data', params: row },
          success(res) {
            console.log(res);
            self.fullscreenLoading = false;
            if(res.success == true) {
              self.$notify({ title: 'Success', message: res.data.message, type: 'success', offset: 100, duration: 5000 });
            } else {
              self.$notify({ title: 'Error', message: res.data.message, type: 'error', offset: 100, duration: 5000 });
            }
          }
        })
      }
    }
  };
}
