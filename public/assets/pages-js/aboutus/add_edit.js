jQuery(document).ready(function () {
    jQuery("#add_faq_form").validate({
        rules: {
        },
        messages: {
        },
        errorPlacement: function (error, element) {
            jQuery(element).addClass('is-invalid');
        },
    });

    jQuery("#edit_faq_form").validate({
        rules: {
        },
        messages: {
        },
        errorPlacement: function (error, element) {
            jQuery(element).addClass('is-invalid');
        },
    });
});