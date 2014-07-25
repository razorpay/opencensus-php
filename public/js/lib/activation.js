$(document).ready(function(){
    

    var prevLink = '<button type="button" class="prev btn btn-info" ><< Prev</button>';
	var nextLink = '<button type="button" class="next btn btn-info" >Next >></button>';
	var saveLink = '<button type="button" class="save btn btn-success" >Save Details</button>';
	var navHTML = '<div class="prev-next">' +
					  	 prevLink +
						 nextLink +
			             saveLink +
			             '</div>';
	// remember our divs are direct childs of our form
    $('#activation-form > fieldset').hide().prepend(navHTML);

    // don't forget to show the first step when page is loaded!
    $('#activation-form > fieldset:first').show();

	$('#activation-form > fieldset:first .prev').prop('disabled', 'true');
	$('#activation-form > fieldset:last .next').prop('disabled','true');

	$('button.next').click(function(){
		$(this).parent().parent().hide().next().show();
		var index = $('#activation-form > fieldset').index($(this).parent().parent());
		console.log($('.progtrckr li')[index])
		$('.progtrckr li:eq('+index+')').removeClass('progtrckr-todo');
		$('.progtrckr li:eq('+index+')').addClass('progtrckr-done');
		return false;
	});

	$('button.prev').click(function(){
		$(this).parent().parent().hide().prev().show();
		var index = $('#activation-form > fieldset').index($(this).parent().parent()) - 1;
		$('.progtrckr li:eq('+index+')').removeClass('progtrckr-done');
		$('.progtrckr li:eq('+index+')').addClass('progtrckr-todo');
		return false;
	});
});