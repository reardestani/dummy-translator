jQuery(document).ready(function($) {

  function wpdt_action(e) {
    e.preventDefault();

    var el         = $( e.target );
    var action     = 'wpdt_' + el.data('action');
    var textdomain = el.parents( 'td' ).siblings('.textdomain').text();
    var dummytext  = el.parents( 'td' ).siblings('.dummytext').text();
    var type  = el.data('type');
    var plugin  = el.data('plugin');
    var generated  = el.data('generated');

    var nonce = el.data('nonce');

    var data = {
  		'action': action,
  		'textdomain': textdomain,
  		'dummytext': dummytext,
      'type': type,
      'plugin': plugin,
      'generated': generated,
  		'nonce': nonce
  	};
  	// We can also pass the url value separately from ajaxurl for front end AJAX implementations
  	$.post(ajax_object.ajax_url, data, function( response ) {
  		if ( response == 'success' ) {
        location.reload();
      } else {
        alert( response );
      }
  	});
  }

  $('.wpdt-action').on('click', wpdt_action);



});
