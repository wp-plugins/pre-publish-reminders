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

});

function fadeParentWhenChecked(el) {
	if (jQuery(el).is(':checked')) {
		jQuery(el).parent().fadeTo(1000, .3);
	} else {
		jQuery(el).parent().fadeTo(1000, 1);
	}
}