(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	 //
	 jQuery(document).on('click', '.button.selectall', function() {
	 	if (!pppt_checkall) {
	 		jQuery('input[name="archive[]"]:not(:checked)').attr('checked', 'checked');
	 		pppt_checkall = true;
	 	}
	 	else {
	 		jQuery('input[name="archive[]"]:checked').removeAttr('checked');
	 		pppt_checkall = false;
	 	}
	 });

	 //
	 jQuery(document).on('click', '.button.restore', function() {
	 	if (jQuery('input[name="archive[]"]:checked').length == 0) {
	 		alert(pppt_translate.checked_required);
	 	}
	 	else {
	 		jQuery('#type_action').val('restore');
	 		jQuery('#farchives').submit();
	 	}
	 });

	 //
	 jQuery(document).on('click', '.button.delete', function() {
	 	if (jQuery('input[name="archive[]"]:checked').length == 0) {
	 		alert(pppt_translate.checked_required);
	 	}
	 	else {
	 		jQuery('#type_action').val('delete');
	 		jQuery('#farchives').submit();
	 	}
	 });
})( jQuery );

var pppt_checkall = false;

//
function pppt_show_archives() {
	jQuery('.thickbox.pppt-thickbox-launcher').click();
	jQuery('#TB_ajaxContent').html('<p>'+pppt_translate.loading+'</p>');
	//
	var params = {};
	params.action = 'show_archives';
	params.plugin_status = jQuery('input[name="plugin_status"]').val();
	params.paged = jQuery('input[name="plugin_status"]').val();
	params.s = jQuery('input[name="s"]').val();
	params.pppt_show_archives_nonce = jQuery('#pppt_show_archives_nonce').val();
	//
    jQuery.ajax({
        type: 'POST',
        'url': ajaxurl,
        'data': params,
        'dataType': 'json',
        'success': function(data) {
			jQuery('#TB_ajaxContent').html(data.message);
		}
    });
    //
	return false;	

}