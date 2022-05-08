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
  let companyName = $('#company_name_checkbox-field').is(':checked');
  if (companyName === true) {
    $('#company_name_text-wrapper').show();
  } else {
    $('#company_name_text-wrapper').hide();
  }
  $('body').on('click', '#company_name_checkbox-field', function(e) {
    if (companyName === true) {
      $('#company_name_text-wrapper').hide('slow');
      companyName = false;
    } else {
      $('#company_name_text-wrapper').show('slow');
      companyName = true;
    }
  });

  // Toggle Company Logo input box
  let companyLogo = $('#company_logo_checkbox-field').is(':checked');
  if (companyLogo === true) {
    $('#company_logo_upload-wrapper').show();
  } else {
    $('#company_logo_upload-wrapper').hide();
  }
  $('body').on('click', '#company_logo_checkbox-field', function(e) {
    if (companyLogo === true) {
      $('#company_logo_upload-wrapper').hide('slow');
      companyLogo = false;
    } else {
      $('#company_logo_upload-wrapper').show('slow');
      companyLogo = true;
    }
  });

  // Toggle Comapny Details input box
  let companyDetails = $('#company_details_checkbox-field').is(':checked');
  if (companyDetails === true) {
    $('#company_details_text-wrapper').show();
  } else {
    $('#company_details_text-wrapper').hide();
  }
  $('body').on('click', '#company_details_checkbox-field', function(e) {
    if (companyDetails === true) {
      $('#company_details_text-wrapper').hide('slow');
      companyDetails = false;
    } else {
      $('#company_details_text-wrapper').show('slow');
      companyDetails = true;
    }
  });

  // Toggle Comapny Notes input box
  let companyNotes = $('#company_notes_checkbox-field').is(':checked');
  if (companyNotes === true) {
    $('#company_notes_text-wrapper').show();
  } else {
    $('#company_notes_text-wrapper').hide();
  }
  $('body').on('click', '#company_notes_checkbox-field', function(e) {
    if (companyNotes === true) {
      $('#company_notes_text-wrapper').hide('slow');
      companyNotes = false;
    } else {
      $('#company_notes_text-wrapper').show('slow');
      companyNotes = true;
    }
  });

  // Toggle Comapny Footer input box
  let companyFooter = $('#company_footer_checkbox-field').is(':checked');
  if (companyFooter === true) {
    $('#company_footer_text-wrapper').show();
  } else {
    $('#company_footer_text-wrapper').hide();
  }
  $('body').on('click', '#company_footer_checkbox-field', function(e) {
    if (companyFooter === true) {
      $('#company_footer_text-wrapper').hide('slow');
      companyFooter = false;
    } else {
      $('#company_footer_text-wrapper').show('slow');
      companyFooter = true;
    }
  });

});
