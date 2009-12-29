jQuery(document).ready(function() {
	jQuery('.pre-publish-reminder-check').click(function() {
		fadeParentWhenChecked(this);
	}).each(function(i) {
		fadeParentWhenChecked(this);
	});

	
	
	if(typeof(jQuery.fn.ColorPicker) == 'function') {
		jQuery('.reminder-color-picker').ColorPicker( {
			onSubmit : function(hsb, hex, rgb, el) {
				jQuery(el).val(hex);
				jQuery(el).ColorPickerHide();
			},
			onChange : function(hsb, hex, rgb, el) {
				jQuery(el).val(hex);
			},
			onBeforeShow : function() {
				jQuery(this).ColorPickerSetColor(this.value);
			}
		}).bind('keyup', function() {
			jQuery(this).ColorPickerSetColor(this.value);
		});
	}
	
	jQuery('#reminders-sortable').sortable(
		{
			items:'tr',
			update: function(event, ui) {
				jQuery.post(
					'admin-ajax.php',
					{
						action:'sort_pre_publish_reminders',
						reminders:jQuery(this).sortable('serialize')
					},
					function(data,status) {}
				);
			}
		}
	);

});

function fadeParentWhenChecked(el) {
	if (jQuery(el).is(':checked')) {
		jQuery(el).parent().fadeTo(1000, .3);
	} else {
		jQuery(el).parent().fadeTo(1000, 1);
	}
}