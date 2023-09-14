jQuery(document).ready(function () {
    $(".colorpickerinput").colorpicker({
        format: 'hex',
        component: '.input-group-append',
    });

    jQuery("#add_custom_form").validate({
        rules: {
            category_color: {
                required: true
            },
            category_image: {
                accept: "image/jpg,image/jpeg,image/png,image/gif,image/svg+xml",
                filesize: 5000000,
            },
            category_trans_image: {
                accept: "image/jpg,image/jpeg,image/png,image/gif,image/svg+xml",
                filesize: 5000000,
            }
        },
        messages: {
            category_color: {
                required: "Please select category color"
            },
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

    $('#category_submit').on('click', function () {
        if ($("#add_custom_form").valid()) {
            $("#add_custom_form").submit();
        }
        return false;
    });
});
