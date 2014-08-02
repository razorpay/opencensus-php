function activate(){
    //Register Handlers

    rzpd.activation = {
    	initialize: function(){

    		rzpd.activation.renderForm();

			rzpd.activation.fillForm();

    		$('button.next').click(rzpd.activation.gonext);

    		$('button.prev').click(rzpd.activation.goprev);

    		$('button.validate').click(rzpd.activation.validate);

		},
		renderForm: function(){

    		//Add previous, next link to all fieldsets
    		var prevLink = '<button type="button" class="prev btn btn-info" ><< Prev</button>';
			var nextLink = '<button type="button" class="next btn btn-info" >Next >></button>';
			var navHTML = '<div class="prev-next">' +
							  	 prevLink +
								 nextLink +
					             '</div>';

			//Hide the fieldsets initially and add the buttons
		    $('#activation-form > fieldset').hide().prepend(navHTML);
		    
		    //Disable prev on first, next and save on last
			$('#activation-form > fieldset:first .prev').prop('disabled', true);
			$('#activation-form > fieldset:last .next').prop('disabled', true);

			$('#activation-form .form-control').prop('disabled', true);

			$('#activation-form > fieldset:first').show();

		},
		fillForm: function(){
			var data = rzpd.activationdata; 

			data = $.parseJSON(data);
	    	//Autofill form controls
	    	$.each(data.data, function(i,e){
	    		$('#activation-form .form-control[name="'+i+'"]').val(e);
	    	});

	    	//Mark the files already uploaded
	    	$.each(data.files, function(i,e){
	    		rzpd.activation.displayMessage($('#activation-form #i').parent().parent(), "Uploaded earlier.", 'info');
	    		$('#'+i).html('<a target="_blank" href="'+e+'">Click Here to see uploaded document</a>');
	    	});

	    	 //Mark the steps done
	    	 var steps_finished = $.parseJSON(data.steps_finished);

	    	$.each(steps_finished, function(i, e){
	    		$('.progtrckr li:eq('+e+')').removeClass().addClass('progtrckr-done');
	    	});

	    	//Check if form is submitted for activation already
	    	if($.inArray(5, steps_finished) == -1){
	    		rzpd.activation.displayMessage($('#activation-form'), "Form has been not submitted for activation yet.", 'info')
	    	} else {
	    		rzpd.activation.displayMessage($('#activation-form'), "Form has been submitted for activation. You may activate the merchant after verifying all the steps.", 'info');    	
	    	}

	    	if(data.locked == 1){
	    		rzpd.activation.displayMessage($('#activation-form'), "Admin has locked the form for editing by merchant.", 'info');    	
	    	}
		},
		gonext: function(){
			$fieldset = $(this).parent().parent();
			
			$fieldset.hide().next().show();

			return false;
		},
		goprev: function(){
			$fieldset = $(this).parent().parent();

			$fieldset.hide().prev().show();

			return false;
		},
		displayErrorMessage: function($fieldset, message, alerttype){
	        var alert = $('<div></div>');
	        var alertClass = 'alert-' + alerttype;

	        alert.addClass('alert alert-dismissable ' + alertClass);
	        alert.append('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
	        alert.append(message);

	       	$fieldset.find('.prev-next').after(alert);
    	},
    	displayMessage: function($element, message, alerttype){
	        var alert = $('<div></div>');
	        var alertClass = 'alert-' + alerttype;
	        
	        alert.addClass('alert alert-dismissable ' + alertClass);
	        alert.append('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
	        alert.append(message);

	        $element.prepend(alert);
    	},
		csrfToken: function(){
			return $('input[name="_token"]').val();
		}

    };

    //Initialize
    rzpd.activation.initialize();
}