jQuery(function($) {
  // Open wp image upload popup
  $('body').on('click', '.wcpi_upload_image', function(e) {
    e.preventDefault();

    var button = $(this),
      wcpi_uploader = wp
        .media({
          title: 'Custom image',
          library: {
            uploadedTo: wp.media.view.settings.post.id,
            type: 'image',
          },
          button: {
            text: 'Use this image',
          },
          multiple: false,
        })
        .on('select', function() {
          var attachment = wcpi_uploader
            .state()
            .get('selection')
            .first()
            .toJSON();
          console.log(attachment.url);
          $('#company_logo_upload-field').val(attachment.url);
        })
        .open();
  });

  // Toggle Company Name text box
  let company_name = $('#company_name_checkbox-field').is(':checked');
  if (company_name === true) {
    $('#company_name_text-wrapper').show();
  } else {
    $('#company_name_text-wrapper').hide();
  }
  $('body').on('click', '#company_name_checkbox-field', function(e) {
    console.log(company_name);
    if (company_name === true) {
      $('#company_name_text-wrapper').hide('slow');
      company_name = false;
    } else {
      $('#company_name_text-wrapper').show('slow');
      company_name = true;
    }
  });

  console.log($('#company_name_checkbox-field').is(':checked'));
});
