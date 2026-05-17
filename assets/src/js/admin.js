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

  // Toggle Company Details fields
  var $companyDetailFields = $('#company_address-wrapper, #company_city-wrapper, #company_zip-wrapper, #company_country-wrapper, #company_phone-wrapper, #company_email-wrapper, #company_vat-wrapper');
  let companyDetails = $('#company_details_checkbox-field').is(':checked');
  if (companyDetails === true) {
    $companyDetailFields.show();
  } else {
    $companyDetailFields.hide();
  }
  $('body').on('click', '#company_details_checkbox-field', function(e) {
    if (companyDetails === true) {
      $companyDetailFields.hide('slow');
      companyDetails = false;
    } else {
      $companyDetailFields.show('slow');
      companyDetails = true;
    }
  });

  // Toggle Company Notes input box
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

  // Toggle Company Footer input box
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

  // Invoice preview button
  $('body').on('click', '#invoice_preview_btn-field', function(e) {
    e.preventDefault();
    var $btn = $(this);
    $btn.prop('disabled', true).text(wpwing_wcpi_object.preview_loading);

    $.ajax({
      url: wpwing_wcpi_object.ajax_url,
      type: 'POST',
      data: {
        action: 'wpwing_preview_document',
        nonce: wpwing_wcpi_object.preview_nonce,
        document_type: 'invoice',
      },
      success: function(response) {
        $btn.prop('disabled', false).text(wpwing_wcpi_object.preview_btn);
        if (response.success) {
          openPreviewModal(response.data.html);
        } else {
          alert(response.data);
        }
      },
      error: function() {
        $btn.prop('disabled', false).text(wpwing_wcpi_object.preview_btn);
        alert('Preview failed. Please try again.');
      },
    });
  });

  function openPreviewModal(html) {
    var $overlay = $('<div id="wpwing-preview-overlay"></div>');
    var $modal   = $('<div id="wpwing-preview-modal"></div>');
    var $close   = $('<button id="wpwing-preview-close" type="button">&times;</button>');
    var $title   = $('<h2></h2>').text(wpwing_wcpi_object.preview_title);
    var $iframe  = $('<iframe id="wpwing-preview-frame" frameborder="0"></iframe>');

    $modal.append($close).append($title).append($iframe);
    $overlay.append($modal);
    $('body').append($overlay);

    var iframeDoc = $iframe[0].contentDocument || $iframe[0].contentWindow.document;
    iframeDoc.open();
    iframeDoc.write(html);
    iframeDoc.close();

    $overlay.on('click', '#wpwing-preview-close', function() {
      $overlay.remove();
    });
    $overlay.on('click', function(e) {
      if ($(e.target).is($overlay)) {
        $overlay.remove();
      }
    });
  }

});
