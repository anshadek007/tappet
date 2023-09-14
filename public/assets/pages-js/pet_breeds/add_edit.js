jQuery(document).ready(function () {
    jQuery("#custom_form").validate({
        rules: {
        },
        messages: {
        },
        errorPlacement: function (error, element) {
            jQuery(element).addClass('is-invalid');
        },
    });
});