jQuery( document ).on( 'click', '.feedback-function', function() {
	var post_id = jQuery(this).data('id');
	var calif = jQuery(this).data('calif');
	jQuery.ajax({
		url : setfeedback.ajaxurl,
		type : 'post',
		data : {
			action : 'inserta_feed',
			post_id : post_id,
			calif : calif
		},
		dataType : 'json',
		success : function( response ) {
			alert(response.msg);
			if(response.ok==true){
				jQuery("#porc5").html(response.val5+"%");
				jQuery("#porc4").html(response.val4+"%");
				jQuery("#porc3").html(response.val3+"%");
				jQuery("#porc2").html(response.val2+"%");
				jQuery("#porc1").html(response.val1+"%");
			}
		}
	});

	return false;
})
