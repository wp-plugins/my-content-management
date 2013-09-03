jQuery(document).ready(function($){
	$('.mcm-settings .tabs a[href="#'+firstItem+'"]').addClass('active');
	$('.mcm-settings .wptab').not('#'+firstItem).hide();
	$('.mcm-settings .tabs a').on('click',function(e) {
		e.preventDefault();
		$('.mcm-settings .tabs a').removeClass('active');
		$(this).addClass('active');
		var target = $(this).attr('href');
		$('.mcm-settings .wptab').not(target).hide();
		$(target).show();
	});
});