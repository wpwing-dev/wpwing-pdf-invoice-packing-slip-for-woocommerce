jQuery(function($){
    $('body').on('click', '.wcpi_upload_image', function(e){
        e.preventDefault();

        var button = $(this),
        wcpi_uploader = wp.media({
            title: 'Custom image',
            library : {
                uploadedTo : wp.media.view.settings.post.id,
                type : 'image'
            },
            button: {
                text: 'Use this image'
            },
            multiple: false
        })
        .on('select', function() {
            var attachment = wcpi_uploader.state().get('selection').first().toJSON();
            console.log(attachment.url);
            $('#company_logo_upload-field').val(attachment.url);
        })
        .open();
    });
});