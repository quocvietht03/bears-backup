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

    var nonce_name = opts.data.handle ? 'nonce__' + opts.data.handle : 'nonce';
    var __nonce = nonce_name ? bbackup_object[nonce_name] : bbackup_object.nonce;
    _o.data = { ..._o.data, nonce: __nonce };

    return $.ajax(_o); 
  },
}

