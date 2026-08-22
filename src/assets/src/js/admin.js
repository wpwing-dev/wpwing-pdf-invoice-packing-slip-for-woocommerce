jQuery(function($) {

  // ── Media uploader (company logo) ──────────────────────────────────────────
  $('body').on('click', '.wcpdf_upload_image', function(e) {
    e.preventDefault();
    var uploader = wp.media({
      title:    'Custom image',
      library:  { uploadedTo: wp.media.view.settings.post.id, type: 'image' },
      button:   { text: 'Use this image' },
      multiple: false,
    }).on('select', function() {
      var attachment = uploader.state().get('selection').first().toJSON();
      $('#company_logo_upload-field').val(attachment.url);
    }).open();
  });

  // ── Checkbox toggles ────────────────────────────────────────────────────────
  // Each pair maps a checkbox to the field wrapper(s) it controls.
  var toggles = [
    { checkbox: '#company_name_checkbox-field',    targets: '#company_name_text-wrapper' },
    { checkbox: '#company_logo_checkbox-field',    targets: '#company_logo_upload-wrapper' },
    { checkbox: '#company_details_checkbox-field', targets: '#company_address-wrapper, #company_city-wrapper, #company_zip-wrapper, #company_country-wrapper, #company_phone-wrapper, #company_email-wrapper, #company_vat-wrapper' },
    { checkbox: '#company_notes_checkbox-field',   targets: '#company_notes_text-wrapper' },
    { checkbox: '#company_footer_checkbox-field',  targets: '#company_footer_text-wrapper' },
  ];

  toggles.forEach(function(pair) {
    var $checkbox = $(pair.checkbox);
    var $targets  = $(pair.targets);

    // Set initial state instantly (no animation on page load)
    $targets.toggle($checkbox.is(':checked'));

    // On change, read truth from DOM and animate
    $('body').on('change', pair.checkbox, function() {
      $targets.slideToggle(150);
    });
  });

  // ── Preview panel ────────────────────────────────────────────────────────────
  var $previewPanel = $('.wpwing-settings-right');

  function showPreviewPanel() {
    $previewPanel.show();
  }

  function hidePreviewPanel() {
    $previewPanel.hide();
  }

  // Show panel immediately if the Template tab is already active on page load
  var activeTab = $('#_last_active_tab').val() || 'wpwing_pdf_general';
  if (activeTab === 'wpwing_pdf_template') {
    showPreviewPanel();
  }

  // Show/hide panel when switching tabs
  $('body').on('click', '.wpwing-wcpdf-setting-nav-tab', function() {
    var target = $(this).data('target');
    if (target === 'wpwing_pdf_template') {
      showPreviewPanel();
    } else {
      hidePreviewPanel();
    }
  });

  // Close button: clear iframe and reset to placeholder, but keep panel open
  $('#wpwing-preview-close').on('click', function() {
    var $frame    = $('#wpwing-preview-frame');
    var iframeDoc = $frame[0].contentDocument || $frame[0].contentWindow.document;
    iframeDoc.open();
    iframeDoc.write('');
    iframeDoc.close();
    $frame.hide();
    $('#wpwing-preview-placeholder').show();
  });

  // ── Invoice preview button ───────────────────────────────────────────────────
  $('body').on('click', '#invoice_preview_btn-field', function(e) {
    e.preventDefault();
    var $btn = $(this);
    $btn.prop('disabled', true).text(wpwing_wcpdf_object.preview_loading);

    // Collect live (unsaved) form values so preview reflects current inputs
    var overrides = {};
    $('.wpwing-settings-left form').find('input, select, textarea').each(function() {
      var $el  = $(this);
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
      url:  wpwing_wcpdf_object.ajax_url,
      type: 'POST',
      data: {
        action:            'wpwing_preview_document',
        nonce:             wpwing_wcpdf_object.preview_nonce,
        document_type:     'invoice',
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
    var $frame    = $('#wpwing-preview-frame');
    var iframeDoc = $frame[0].contentDocument || $frame[0].contentWindow.document;
    iframeDoc.open();
    iframeDoc.write(html);
    iframeDoc.close();
    showPreviewPanel();
    $('#wpwing-preview-placeholder').hide();
    $frame.show();
  }

  // ── Settings search ───────────────────────────────────────────────────────
  var $settingsSearch = $('#wpwing-settings-search');

  function filterSettingsRows(query) {
    var q = $.trim(query).toLowerCase();
    $('.wpwing-wcpdf-setting-tab table tr').each(function() {
      var $row = $(this);
      $row.toggle(!q || $row.text().toLowerCase().indexOf(q) !== -1);
    });
  }

  $settingsSearch.on('input', function() {
    filterSettingsRows($(this).val());
  });

  // Re-apply the current search when switching tabs, so it stays live.
  $('body').on('click', '.wpwing-wcpdf-setting-nav-tab', function() {
    filterSettingsRows($settingsSearch.val());
  });

  // ── Copy system info (System Status tab) ────────────────────────────────────
  $('body').on('click', '#wpwing-copy-system-info', function() {
    var $btn    = $(this);
    var $status = $('#wpwing-copy-system-info-status');
    var source  = document.getElementById('wpwing-status-copy-source');

    if (!source) return;

    source.focus();
    source.select();

    var copied = false;
    try {
      copied = document.execCommand('copy');
    } catch (e) {
      copied = false;
    }

    if (window.getSelection) {
      window.getSelection().removeAllRanges();
    }
    $btn.blur();

    $status.text(copied ? wpwing_wcpdf_object.copy_success : wpwing_wcpdf_object.copy_failed);
    setTimeout(function() { $status.text(''); }, 2000);
  });

});
