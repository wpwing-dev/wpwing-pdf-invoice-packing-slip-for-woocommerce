jQuery(function($) {
  // Open wp image upload popup
  $('body').on('click', '.wcpdf_upload_image', function(e) {
    e.preventDefault();

    var button = $(this),
      wcpdf_uploader = wp
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
          var attachment = wcpdf_uploader
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

  // Hide/restore preview panel when switching tabs
  var previewLoaded = false;
  $('body').on('click', '.wpwing-wcpdf-setting-nav-tab', function() {
    var target = $(this).data('target');
    if (target === 'wpwing_pdf_template' && previewLoaded) {
      $('.wpwing-settings-right').show();
    } else {
      $('.wpwing-settings-right').hide();
    }
  });

  // Invoice preview button
  $('body').on('click', '#invoice_preview_btn-field', function(e) {
    e.preventDefault();
    var $btn = $(this);
    $btn.prop('disabled', true).text(wpwing_wcpdf_object.preview_loading);

    // Collect live (unsaved) form values to preview them without saving
    var overrides = {};
    $('.wpwing-settings-left form').find('input, select, textarea').each(function() {
      var $el = $(this);
      var name = $el.attr('name');
      if (!name) return;
      var match = name.match(/\[([^\]]+)\]$/);
      if (!match) return;
      var key = match[1];
      if ($el.is(':checkbox')) {
        overrides[key] = $el.is(':checked') ? ($el.val() || '1') : '';
      } else if ($el.is(':radio')) {
        if ($el.is(':checked')) overrides[key] = $el.val();
      } else {
        overrides[key] = $el.val();
      }
    });

    $.ajax({
      url: wpwing_wcpdf_object.ajax_url,
      type: 'POST',
      data: {
        action: 'wpwing_preview_document',
        nonce: wpwing_wcpdf_object.preview_nonce,
        document_type: 'invoice',
        preview_overrides: overrides,
      },
      success: function(response) {
        $btn.prop('disabled', false).text(wpwing_wcpdf_object.preview_btn);
        if (response.success) {
          showInlinePreview(response.data.html);
        } else {
          alert(response.data);
        }
      },
      error: function() {
        $btn.prop('disabled', false).text(wpwing_wcpdf_object.preview_btn);
        alert('Preview failed. Please try again.');
      },
    });
  });

  function showInlinePreview(html) {
    var $panel = $('.wpwing-settings-right');
    var $frame = $('#wpwing-preview-frame');
    var iframeDoc = $frame[0].contentDocument || $frame[0].contentWindow.document;
    iframeDoc.open();
    iframeDoc.write(html);
    iframeDoc.close();
    $panel.show();
    $('#wpwing-preview-placeholder').hide();
    $frame.show();
    previewLoaded = true;
  }

  $('#wpwing-preview-close').on('click', function() {
    var $frame = $('#wpwing-preview-frame');
    $frame.hide();
    var iframeDoc = $frame[0].contentDocument || $frame[0].contentWindow.document;
    iframeDoc.open();
    iframeDoc.write('');
    iframeDoc.close();
    $('#wpwing-preview-placeholder').show();
    $('.wpwing-settings-right').hide();
    previewLoaded = false;
  });

});
