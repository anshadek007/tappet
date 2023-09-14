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
            user_image: {
                accept: "image/jpg,image/jpeg,image/png,image/gif",
                filesize: 5000000,
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
    
    
    // get country list
    jQuery(document).on('change', '#country_id', function () {
        var id = jQuery(this).val();
        $("#city_id").html("<option value=''>Select City</option>");
        if (!isNaN(id) && id != '') {
            jQuery.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                "url": get_city_list,
                type: "POST",
                data: {'id': id},
                dataType: 'json',
                cache: false,
                success: function (response) {
                    $("#city_id").html("");
                    if (response.success == true && typeof response.all_records !== 'undefined') {
                        var user_list_option = "<option value=''>Select City</option>";
                        if (response.all_records.length > 0) {
                            $.each(response.all_records, function (key, value) {
                                user_list_option += "<option value=" + value.id + ">" + value.name + "</option>";
                            });
                        }

                        $("#city_id").html(user_list_option);
                    } else {
                        swal(response.message, {
                            icon: 'error',
                        });
                    }
                }, error: function () {
                    $("#city_id").html("");
                    swal("Problem while performing your action", {
                        icon: 'info',
                    });
                }
            });
        }
    });
});