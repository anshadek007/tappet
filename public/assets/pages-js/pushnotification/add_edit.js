jQuery(document).ready(function () {
    jQuery("#add_pushnotification_form").validate({
        rules: {
            
        },
        messages: {
        },
        errorPlacement: function (error, element) {
            jQuery(element).addClass('is-invalid');
        },
    });
});