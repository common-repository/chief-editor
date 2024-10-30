jQuery(document).ready(function($) {

	jQuery(function() {
		jQuery(".datepicker").datepicker({
			dateFormat : "mm/dd/yy"
		});
	});

	

	jQuery('.chief-editor-bat-submit').click(function(event) {
		event.preventDefault();
		var $this = jQuery(this);
		var $formId = jQuery(this).parents('form');

		var fields = $formId.serializeArray();
		var $postID = fields[0].value;
		var $blogID = fields[1].value;
		var $authorID = fields[2].value;

			
			swal.fire({
  title: 'Are you sure?',
  text: 'It will send an validation request to authors and editors in chief',
  type: 'warning',
  showCancelButton: true,
  confirmButtonText: 'Yes, send it!',
  cancelButtonText: 'No, do not send anything'
}).then((result) => {
  if (result.value) {
    
      data = { 
	      action : 'ce_send_author_std_validation_email_confirmed',		 
	      postID : $postID, 
	      blogID : $blogID, 
	      authorID : $authorID
		  } 
	
		 
		 $.post(ajaxurl, data, function(response) {
		      swal.fire({title:'Validation request',text:response}); 
		      });
		 
		
  // For more information about handling dismissals please visit
  // https://sweetalert2.github.io/#handling-dismissals
  } else if (result.dismiss === swal.DismissReason.cancel) {
    swal.fire(
      'Cancelled',
      'Nothing was sent',
      'error'
    )
  }
})


	});

	jQuery(".chiefed_bat_send_confirm").click(function(event){
	    event.preventDefault();
		console.log("yo chiefed_bat_send_confirm");
		// return false;
	});


});