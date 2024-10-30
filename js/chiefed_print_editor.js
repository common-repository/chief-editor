jQuery(document).ready(function($) {

	jQuery('#chiefed_custom_attachment_extract_as_gallery').click(function(event) {
		event.preventDefault();
		var current_item = $(this);
		//var formId = jQuery(this).parents('form');

		var postID = current_item.attr('post_id');
		var postTitle = current_item.attr('post_title');
		var filePath = current_item.attr('images_source_document');
		var fileName = current_item.attr('images_source_document_name');
		
		
			
			swal({
  title: 'Extract images and add gallery?',
  text: 'It will extract all images (from '+fileName+') and insert as gallery at insertion point in current post '+postTitle+'. It may take some time.',
  type: 'warning',
  showCancelButton: true,
  confirmButtonText: 'Yes, go!',
  cancelButtonText: 'Cancel'
}).then((result) => {
  if (result.value) {
    
      data = { 
	      action : 'chiefed_extract_images_to_gallery',		 
	      postID : postID,
	      filePath : filePath,
		  } 
	
		 
		 $.post(ajaxurl, data, function(response) {
		      swal({title:'Images extraction',text:response}); 
		      });
		 
		
  // For more information about handling dismissals please visit
  // https://sweetalert2.github.io/#handling-dismissals
  } /*else if (result.dismiss === swal.DismissReason.cancel) {
    swal(
      'Cancelled',
      '',
      'error'
    )
  }
  */
})


	});
	


});
