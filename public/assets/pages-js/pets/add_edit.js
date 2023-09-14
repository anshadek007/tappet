jQuery(document).ready(function () {
    jQuery("#add_user_form").validate({
        rules: {
            user_image: {
                accept: "image/jpg,image/jpeg,image/png,image/gif",
                filesize: 5000000,
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
            image: {
                accept: "image/jpg,image/jpeg,image/png,image/gif",
                filesize: 5000000,
            },
        },
        messages: {
        },
        errorPlacement: function (error, element) {
            jQuery(element).addClass('is-invalid');
        },
    });
});