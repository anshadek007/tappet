<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
        <title>Login &mdash; {{ config('app.name') }}</title>

        <!-- General CSS Files -->
        <link rel="stylesheet" href="{{asset("public/assets/modules/bootstrap/css/bootstrap.min.css")}}">
        <link rel="stylesheet" href="{{asset("public/assets/modules/fontawesome/css/all.min.css")}}">

        <!-- CSS Libraries -->
        <link rel="stylesheet" href="{{asset("public/assets/modules/bootstrap-social/bootstrap-social.css")}}">
        <link rel="stylesheet" href="{{asset("public/assets/modules/select2/dist/css/select2.min.css")}}">
        <link rel="stylesheet" href="{{asset("public/assets/modules/bootstrap-daterangepicker/daterangepicker.css")}}">
        <link rel="stylesheet" href="{{asset("public/assets/modules/dropzonejs/dropzone.css")}}">

        <!-- Template CSS -->
        <link rel="stylesheet" href="{{asset("public/assets/css/style.css")}}">
        <link rel="stylesheet" href="{{asset("public/assets/css/components.css")}}">



        <style>
            .form-float {
                width: 100%;
                position: relative;
                border-bottom: 1px solid #ddd;
            }
            .form-float .form-control {
                width: 100%;
                border: none;
                box-shadow: none;
                -webkit-border-radius: 0;
                -moz-border-radius: 0;
                -ms-border-radius: 0;
                border-radius: 0;
                padding-left: 0;
            }
            .form-float input,.form-float textarea {
                outline: none !important;
                padding: 0px !important;
            }
            .form-float.focused .form-label {
                top: -10px;
                left: 0;
                font-size: 12px;
            }
            .form-float .form-label {
                font-weight: normal;
                position: absolute;
                top: 10px;
                left: 0;
                cursor: text;
                -moz-transition: 0.2s;
                -o-transition: 0.2s;
                -webkit-transition: 0.2s;
                transition: 0.2s;
            }
        </style>
    </head>
    <body>
        <div id="app">
            <div class="main-wrapper main-wrapper-1">
                <!-- Main Content -->
                <!--<div class="main-content">-->
                <section class="section">
                    <div class="section-body">
                        <div class="row">
                            <div class="col-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h4>All Form Components</h4>
                                    </div>
                                    <div class="card-body">

                                        <div class="form-group form-float">
                                            <input type="text" class="form-control">
                                            <label class="form-label">Input</label>
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label">Radio</label>
                                            <div class="selectgroup selectgroup-pills">
                                                <label class="selectgroup-item">
                                                    <input type="radio" name="value" value="50" class="selectgroup-input" checked="">
                                                    <span class="selectgroup-button">Male</span>
                                                </label>
                                                <label class="selectgroup-item">
                                                    <input type="radio" name="value" value="50" class="selectgroup-input">
                                                    <span class="selectgroup-button">Female</span>
                                                </label>
                                                <label class="selectgroup-item">
                                                    <input type="radio" name="value" value="50" class="selectgroup-input">
                                                    <span class="selectgroup-button">Other</span>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label">Checkbox</label>
                                            <div class="selectgroup selectgroup-pills">
                                                <label class="selectgroup-item">
                                                    <input type="checkbox" name="value" value="HTML" class="selectgroup-input">
                                                    <span class="selectgroup-button">Checkbox 1</span>
                                                </label>
                                                <label class="selectgroup-item">
                                                    <input type="checkbox" name="value" value="HTML" class="selectgroup-input">
                                                    <span class="selectgroup-button">Checkbox 2</span>
                                                </label>
                                                <label class="selectgroup-item">
                                                    <input type="checkbox" name="value" value="HTML" class="selectgroup-input">
                                                    <span class="selectgroup-button">Checkbox 3</span>
                                                </label>
                                                <label class="selectgroup-item">
                                                    <input type="checkbox" name="value" value="HTML" class="selectgroup-input">
                                                    <span class="selectgroup-button">Checkbox 4</span>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Dropdown</label>
                                            <select class="form-control select2">
                                                <option>Option 1</option>
                                                <option>Option 2</option>
                                                <option>Option 3</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Doropdown Multiple Select</label>
                                            <select class="form-control select2" multiple="">
                                                <option>Option 1</option>
                                                <option>Option 2</option>
                                                <option>Option 3</option>
                                                <option>Option 4</option>
                                                <option>Option 5</option>
                                                <option>Option 6</option>
                                            </select>
                                        </div>
                                        <div class="form-group form-float">
                                            <textarea class="form-control" placeholder="Textarea"></textarea>
                                        </div>

                                        <div class="form-group">
                                            <label>Date Picker</label>
                                            <input type="text" class="form-control datepicker">
                                        </div>

                                        <div class="form-group">
                                            <label>File</label>
                                            <input type="file" class="form-control">
                                        </div>

                                        <div class="form-group">
                                            <label class="custom-switch mt-2">
                                                <input type="checkbox" name="custom-switch-checkbox" class="custom-switch-input">
                                                <span class="custom-switch-indicator"></span>
                                                <span class="custom-switch-description">Toggle switch</span>
                                            </label>
                                        </div>

                                        <div class="form-group">
                                            <label>Range</label>
                                            <input type="range" id="range_slider" step="0.5" value="0" min="0" max="24" class="col-sm-11 form-control">
                                            <input type="text" id="rang_value" name="" class="col-sm-1" value="0" readonly="">
                                        </div>

                                    </div>
                                    <div class="card-footer text-right">
                                        <a href="#" class="btn btn-icon icon-left btn-primary">
                                            <i class="fas fa-plus"></i> Add
                                        </a>
                                        <a href="#" class="btn btn-icon icon-left btn-primary">
                                            <i class="far fa-edit"></i> Edit
                                        </a>
                                        <a href="#" class="btn btn-icon icon-left btn-danger">
                                            <i class="fas fa-times"></i> Delete
                                        </a>
                                        <a href="#" class="btn btn-icon icon-left btn-primary">
                                            <i class="fas fa-hdd"></i> Save
                                        </a>
                                        <a href="#" class="btn btn-icon icon-left btn-primary">
                                            <i class="fas fa-edit"></i> Update
                                        </a>
                                        <a href="#" class="btn btn-icon icon-left btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

            </div>
        </div>


        <!-- General JS Scripts -->
        <script src="{{asset("public/assets/modules/jquery.min.js")}}"></script>
        <script src="{{asset("public/assets/modules/popper.js")}}"></script>
        <script src="{{asset("public/assets/modules/tooltip.js")}}"></script>
        <script src="{{asset("public/assets/modules/bootstrap/js/bootstrap.min.js")}}"></script>
        <script src="{{asset("public/assets/modules/nicescroll/jquery.nicescroll.min.js")}}"></script>
        <script src="{{asset("public/assets/modules/moment.min.js")}}"></script>
        <script src="{{asset("public/assets/js/stisla.js")}}"></script>

        <!-- JS Libraies -->
        <script src="{{asset("public/assets/modules/select2/dist/js/select2.full.min.js")}}"></script>
        <script src="{{asset("public/assets/modules/bootstrap-daterangepicker/daterangepicker.js")}}"></script>
        <script src="{{asset("public/assets/modules/dropzonejs/min/dropzone.min.js")}}"></script>
        <!-- Page Specific JS File -->

        <!-- Template JS File -->
        <script src="{{asset("public/assets/modules/dropzonejs/min/dropzone.min.js")}}"></script>


        <script src="{{asset("public/assets/js/scripts.js")}}"></script>
        <script src="{{asset("public/assets/js/custom.js")}}"></script>



        <script type="text/javascript">
jQuery(document).ready(function () {
    jQuery(document).on("focus", "input", function () {
        jQuery(this).parent().addClass("focused");
    });
    jQuery(document).on("blur", "input", function () {
        if (jQuery(this).val() == "") {
            jQuery(this).parent().removeClass("focused");
        }
    });

    $('.datepicker').daterangepicker({
        locale: {format: 'YYYY-MM-DD'},
        singleDatePicker: true,
    });

    jQuery("#range_slider").change(function () {
        jQuery("#rang_value").val(jQuery(this).val());
    });
});
        </script>
    </body>
</html>