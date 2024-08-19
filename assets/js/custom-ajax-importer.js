jQuery(document).ready(function($) {
    $('#your-form-id').on('submit', function(e) {
        e.preventDefault();

        var formData = $(this).serializeArray(); // Serialize form data to an array
        var dataObject = {};

        // Convert serialized data to an object
        $.each(formData, function(i, field) {
            dataObject[field.name] = field.value;
        });

        $.ajax({
            url: customAjaxImporter.ajaxurl, // admin-ajax.php URL for AJAX
            type: 'POST',
            data: {
                action: 'custom_ajax_importer', // Custom action name
                security: customAjaxImporter.nonce, // Security nonce
                data: dataObject // Form data as an object
            },
            success: function(response) {
                if(response.success) {
                    console.log('Data imported successfully:', response.data);
                } else {
                    console.log('Error:', response.data);
                }
            }
        });

        // If you want to use the REST API endpoint instead:
        /*
        $.ajax({
            url: '/wp-json/custom-ajax-importer/v1/import',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ data: dataObject }),
            success: function(response) {
                console.log('Data imported via REST API:', response);
            },
            error: function(response) {
                console.log('Error:', response.responseText);
            }
        });
        */
    });
});
