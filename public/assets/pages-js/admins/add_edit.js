jQuery(document).ready(function () {
    jQuery("#add_user_form").validate({
        rules: {
            user_image: {
                accept: "image/jpg,image/jpeg,image/png,image/gif",
                filesize: 2000000,
            },
            c_password: {
                required: true,
                equalTo: "#password"
            }
        },
        messages: {
        },
        errorPlacement: function (error, element) {
            jQuery(element).addClass('is-invalid');
        },
    });

    jQuery("#edit_user_form").validate({
        rules: {
            user_image: {
                accept: "image/jpg,image/jpeg,image/png,image/gif",
                filesize: 2000000,
            },
            c_password: {
                equalTo: "#password"
            }
        },
        messages: {
        },
        errorPlacement: function (error, element) {
            jQuery(element).addClass('is-invalid');
        },
    });
    
    jQuery("#change_password_form").validate({
        rules: {
            c_password: {
                equalTo: "#password"
            }
        },
        messages: {
        },
        errorPlacement: function (error, element) {
            jQuery(element).addClass('is-invalid');
        },
    });
});