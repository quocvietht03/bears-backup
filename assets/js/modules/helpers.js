var $  = jQuery.noConflict();

module.exports = {
  _async(_f) {
    return new Promise( function(resolve, reject){
      _f.call(this, resolve, reject);
    } );
  },
  _request (opts) {
    var _o = $.extend({
      type: 'POST',
      url: bbackup_object.ajax_url,
      data: {},
      success: function() { return; },
      error: function(e) { console.log( 'error: ' + e ); },
    }, opts);

    return $.ajax(_o);
  },
}
