$(document).ready(function(){
    //Register Handlers
    rzpd.activation = {
    	initialize: function(){

    		rzpd.activation.renderForm();

			rzpd.activation.fillForm();

    		$('button.next').click(rzpd.activation.saveAndNext);

    		$('button.prev').click(rzpd.activation.saveAndPrev);

    		$('button.save').click(rzpd.activation.save);

    		$('input[type=file]').change(rzpd.activation.fileUpload);
    		
    		$('#bussiness-operation-checkbox').click(rzpd.activation.updateOperationAddress);

    		$('#activateButton').click(rzpd.activation.submitForActivation);


		},
		renderForm: function(){

			//Initialise bootstrap file input for displaying better file controls
 			$('input[type=file]').bootstrapFileInput();
			$('.file-inputs').bootstrapFileInput();

    		//Add previous, next link to all fieldsets
    		var prevLink = '<button type="button" class="prev btn btn-info" ><< Prev</button>';
			var nextLink = '<button type="button" class="next btn btn-info" >Next >></button>';
			var saveLink = '<button type="button" class="save btn btn-success" >Save Details</button>';
			var navHTML = '<div class="prev-next">' +
							  	 prevLink +
								 nextLink +
					             saveLink +
					             '</div>';

			//Hide the fieldsets initially and add the buttons
		    $('#activation-form > fieldset').hide().prepend(navHTML);
		    
		    //Disable prev on first, next and save on last
			$('#activation-form > fieldset:first .prev').prop('disabled', true);
			$('#activation-form > fieldset:last .next').prop('disabled', true);
			$('#activation-form > fieldset:last .save').prop('disabled', true);

		},
		fillForm: function(){
			//Fetch already saved details
			$.ajax({
			    url: '/activation/details',
			    type: 'GET',
			    success: function(data) {
			    	//Autofill form controls
			    	$.each(data.data, function(i,e){
			    		$('#activation-form .form-control[name="'+i+'"]').val(e);
			    	});

			    	//Mark the files already uploaded
			    	$.each(data.files, function(i,e){
			    		console.log($('#activation-form input[name="'+i+'"]').parent().parent());
			    		rzpd.activation.displayMessage($('#activation-form input[name='+i+']').parent().parent(), "Uploaded earlier.", 'info');
			    	});

			    	//Mark the steps done
			    	var steps = $.parseJSON(data.steps_finished);

			    	$.each(steps, function(i, e){
			    		$('.progtrckr li:eq('+e+')').removeClass().addClass('progtrckr-done');
			    		rzpd.activation.displayErrorMessage($('#activation-form > fieldset:eq('+e+')'), 'Step saved successfully!', 'success');

			    		if(e==5){
			    			rzpd.activation.displayMessage($('#activation-form'), 'Form has been submitted for activation and is pending admin response.', 'info');
			    		}
			    	});

			    	//Check if form is submitted for activation already
			    	if(data.locked == 1){
			    		rzpd.activation.displayMessage($('#activation-form'), "Form has been locked by admin, please await admin response.", 'info');
			    	} else {
			    		$('#activation-form > fieldset:first').show();
	    				$('.progtrckr li:first').removeClass().addClass('progtrckr-current');
			    	}
			    },
			    error: function() {
				    rzpd.activation.displayMessage($('#activation-form > fieldset:first'), 'Oops! There was an error in loading the form, please refresh the page.', 'danger');
			    }
			});
		},
		saveAndNext: function(){
			$fieldset = $(this).parent().parent();
			
			//Call save
			rzpd.activation.handleSave($fieldset);

			$fieldset.hide().next().show();

			var index = $('#activation-form > fieldset').index($fieldset);

			//Change todo of next to current
			$('.progtrckr li:eq('+(index + 1)+')').removeClass().addClass('progtrckr-current');

			return false;
		},
		saveAndPrev: function(){
			$fieldset = $(this).parent().parent();

			//Call save
			rzpd.activation.handleSave($fieldset);

			$fieldset.hide().prev().show();

			var index = $('#activation-form > fieldset').index($fieldset);

			//Change done of previous to current
			$('.progtrckr li:eq('+(index-1)+')').removeClass().addClass('progtrckr-current');

			return false;
		},
		save: function(){
			var $fieldset = $(this).parent().parent();

			rzpd.activation.handleSave($fieldset);
		},
		handleSave: function($fieldset){
			//Disable fieldset before saving
			$fieldset.prop('disabled', true);

			var step = $('#activation-form > fieldset').index($fieldset);
			//Disallow step 5 saving
			if(step == 5)
			{	
				$('.progtrckr li:eq('+step+')').removeClass().addClass('progtrckr-todo');
				$fieldset.prop('disabled', false);
				return false;
			}

			//Remove old errors
			$fieldset.find('.alert').remove();

			rzpd.activation.displayErrorMessage($fieldset, 'Saving...', 'info');
			
			var data = {};
			var fields = $fieldset.find('.form-control');
			fields.each(function(){
				var $item = $(this);
				data[$item.attr('name')] = $item.val();
			});

			data._token = rzpd.activation.csrfToken();

			$.ajax({
	            url: '/activation/save/step/'+step,
	            type: 'POST',
	            data: data,
	            success: function(data) {
	            	$fieldset.find('.alert').remove();

	                if(data.success)
	                {	
	                    rzpd.activation.displayErrorMessage($fieldset, 'Step saved successfully!', 'success');
				        //Change current to done
						$('.progtrckr li:eq('+step+')').removeClass().addClass('progtrckr-done');
	                }
	                else
	                {	
	                	$.each(data.status, function(i, e){
	                		rzpd.activation.displayErrorMessage($fieldset, e, 'danger');
	                	});

	                	 //Change current to error
						$('.progtrckr li:eq('+step+')').removeClass().addClass('progtrckr-error');    
	                }
	            },
	            error: function() {
	            	$fieldset.find('.alert').remove();

	            	rzpd.activation.displayErrorMessage($fieldset, 'Oops! There was an error in handling this request.', 'danger');

	            	//Change current to error
					$('.progtrckr li:eq('+step+')').removeClass().addClass('progtrckr-error');
	            },
	            complete: function() {
	            	$fieldset.prop('disabled', false);
	            }
	        });
			return false;
		},
		submitForActivation: function(){
			var $fieldset = $(this).parent().parent().parent();

			//clear old errors
			$fieldset.find('.alert').remove();

			//Check if agreed to terms
			var agree = $fieldset.find('#agree-terms').is(':checked');
			if(agree === false){
				rzpd.activation.displayErrorMessage($fieldset, 'You must agree to the terms and conditions to use the service.', 'danger');

				return false;
			}

			//Disable all fieldsets before submission
			var $fieldsets = $fieldset.parent().find('fieldset');

			$fieldsets.prop('disabled', true);
			var data = {};

			data._token = rzpd.activation.csrfToken();

			$.ajax({
			    url: '/activation',
			    type: 'POST',
			    data: data,
			    success: function(data) {
			    	if(data.success)
			    	{	
			    		//Refresh on success
			    		location.reload();
			    	}
			    	else
			    	{	
	                	$.each(data.status, function(i, e){
	                		rzpd.activation.displayErrorMessage($fieldset, e, 'danger');
	                	});

	                	$fieldsets.prop('disabled', false);
			    	}
			    },
			    error: function() {
			    	rzpd.activation.displayErrorMessage($fieldset, 'Oops! There was an error in processing your request.', 'danger');

			    	$fieldsets.prop('disabled', false);
			    }
			});

			return false;
		},
		fileUpload: function(){
			//clear old errors
			$parent = $(this).parent().parent();
			$parent.find('.alert').remove();

			var name = $(this).attr('name');
			var files = this.files;
			var data = new FormData();
			data.append(name, files[0]);
			data.append('_token', rzpd.activation.csrfToken());

			rzpd.activation.displayMessage($parent, 'Uploading file....', 'info');

			$.ajax({
			    url: '/activation/save/file',
			    type: 'POST',
			    data: data,
			    cache: false,
			    dataType: 'json',
			    processData: false, // Don't process the files
			    contentType: false, // Set content type to false as jQuery will tell the server its a query string request
			    success: function(data) {
			    	if(data.success)
			    	{	
			    		$parent.find('.alert').remove();
			    		rzpd.activation.displayMessage($parent, 'File Uploaded successfully!', 'success');
			    	}
			    	else
			    	{	
			    		$parent.find('.alert').remove();
			    		rzpd.activation.displayMessage($parent, data.status, 'danger');
			    		$parent.find('.file-input-name').hide();
			    	}
			    },
			    error: function() {
			    	$parent.find('.alert').remove();
				    rzpd.activation.displayMessage($parent, 'Oops! There was an error in handling this request.', 'danger');
				    $parent.find('.file-input-name').hide();
			    }
			});

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
		updateOperationAddress: function(){
			if($(this).is(':checked')) {
				$('textarea[name="bussiness_operation_address"]').val($('textarea[name="bussiness_registered_address"]').val());
				$('textarea[name="bussiness_operation_address"]').prop('disabled', true);
				
				$('input[name="bussiness_operation_state"]').val($('input[name="bussiness_registered_state"]').val());
				$('input[name="bussiness_operation_state"]').prop('disabled', true);
				
				$('input[name="bussiness_operation_city"]').val($('input[name="bussiness_registered_city"]').val());
				$('input[name="bussiness_operation_city"]').prop('disabled', true);
				
				$('input[name="bussiness_operation_pin"]').val($('input[name="bussiness_registered_pin"]').val());
				$('input[name="bussiness_operation_pin"]').prop('disabled', true);
			} else {
	        	$('textarea[name="bussiness_operation_address"]').prop('disabled', false);
	        	
	        	$('input[name="bussiness_operation_state"]').prop('disabled', false);
	        	
	        	$('input[name="bussiness_operation_city"]').prop('disabled', false);
	        	
	        	$('input[name="bussiness_operation_pin"]').prop('disabled', false);
    		}
		},
		csrfToken: function(){
			return $('input[name="_token"]').val();
		}
    };

    //Initialize
    rzpd.activation.initialize();
});