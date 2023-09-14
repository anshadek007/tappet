jQuery(document).ready(function () {

    $('#event_start_date').datetimepicker({
        //pickTime: false,
//        defaultDate: new Date(),
//        minDate: moment().subtract(1, 'd'),
        format: 'YYYY-MM-DD HH:mm:ss',
        icons: {
            time: "fa fa-clock",
//            date: "fa fa-calendar",
//            up: "fa fa-arrow-up",
//            down: "fa fa-arrow-down",
//            previous: "fa fa-chevron-left",
//            next: "fa fa-chevron-right",
//            today: "fa fa-clock-o",
//            clear: "fa fa-trash-o"
        }
    });
    
    $('#event_end_date').datetimepicker({
        //pickTime: false,
//        defaultDate: new Date(),
//        minDate: moment().subtract(1, 'd'),
        format: 'YYYY-MM-DD HH:mm:ss',
        icons: {
            time: "fa fa-clock",
//            date: "fa fa-calendar",
//            up: "fa fa-arrow-up",
//            down: "fa fa-arrow-down",
//            previous: "fa fa-chevron-left",
//            next: "fa fa-chevron-right",
//            today: "fa fa-clock-o",
//            clear: "fa fa-trash-o"
        }
    });

    jQuery("#add_custom_form").validate({
        rules: {
            image: {
                accept: "image/jpg,image/jpeg,image/png,image/gif,image/svg+xml",
                filesize: 5000000,
            },
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
