jQuery(document).ready(function () {
    jQuery("#add_custom_form").validate({
        rules: {
        },
        messages: {
        },
        errorPlacement: function (error, element) {
            var next = element;
            if (element.hasClass('file')) {
                jQuery(error).insertAfter(jQuery(element).parents(".file-input"));
            } else if (element.hasClass('chosen-select')) {
                jQuery(error).insertAfter(jQuery(element).siblings(".chosen-container"));
            } else if (element.hasClass('c_color')) {
                jQuery(error).insertAfter(jQuery(element).parents(".input-group"));
            } else {
//                jQuery(error).insertAfter(jQuery(element).parent());
                jQuery(element).addClass('is-invalid');
            }
        },

    });

    $('#custom_form_submit').on('click', function () {
        if ($("#add_custom_form").valid()) {
            $("#add_custom_form").submit();
        }
        return false;
    });
});
