<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">

        <!-- CSRF Token -->
        <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>

        <link rel="shortcut icon" href="{{asset('public/favicon.ico')}}" type="image/x-icon">
        <link rel="icon" href="{{asset('public/favicon.ico')}}" type="image/x-icon">


        <link rel="stylesheet" href="{{asset('public/assets/modules/bootstrap/css/bootstrap.min.css')}}">
        <link rel="stylesheet" href="{{asset('public/assets/modules/fontawesome/css/all.min.css')}}">
        <link rel="stylesheet" href="{{asset('public/assets/modules/ionicons/css/ionicons.min.css')}}">
        <link rel="stylesheet" href="{{asset('public/assets/modules/izitoast/css/iziToast.min.css')}}">

        <!--pages css file-->
        @yield('addcss')

        <!-- Template CSS -->
        <link rel="stylesheet" href="{{asset("public/assets/css/style.css")}}">
        <link rel="stylesheet" href="{{asset("public/assets/css/components.css")}}">
        <style>
            .pull-left{
                float: left;
            }
            .pull-right{
                float: right;
            }
            .clearfix{
                clear: both;
            }
            .main-footer{
                margin-top: 0;
            }
            .navbar{
                left: 50px;
            }
            .main-content{
                padding-left: 0;
            }
            
            @media (max-width: 1024px) {
                .sidebar-gone-hide {
                    display: block !important;
                }
                .navbar {
                    left: 0;
                }
            }
        </style>
        
        
    </head>
    <body>
        
        
        <div id="app">
            <div class="main-wrapper main-wrapper-1">
                <div class="navbar-bg" style="height:75px"></div>
                <nav class="navbar navbar-expand-lg main-navbar">
                     <a href="{{ url("")}}" class="navbar-brand sidebar-gone-hide"><img src="{{asset("public/assets/images/logo.png")}}" alt="logo" class="img-fluid" style="height: 41px"></a>
                </nav>    

                <div class="main-content">
                    @yield('content')
                </div>

                <!--<footer class="main-footer" style="position: absolute;bottom: 0">-->
                <footer class="main-footer text-center">
                    <div class="footer-center">
                        Copyright &copy; {{date("Y")}} 
                        <div class="bullet"></div> Powered By {{ config('app.name') }}
                    </div>
                    <div class="footer-right">

                    </div>
                </footer>
            </div>
        </div>

        <!-- General JS Scripts -->
        <script type="text/javascript" src="{{asset("public/assets/modules/jquery.min.js")}}"></script>
        <script type="text/javascript" src="{{asset("public/assets/modules/jquery.validate.min.js")}}"></script>
        <script src="{{asset("public/assets/modules/popper.js")}}"></script>
        <script src="{{asset("public/assets/modules/tooltip.js")}}"></script>
        <script src="{{asset("public/assets/modules/bootstrap/js/bootstrap.min.js")}}"></script>
        <script src="{{asset("public/assets/modules/nicescroll/jquery.nicescroll.min.js")}}"></script>
        <script src="{{asset("public/assets/modules/moment.min.js")}}"></script>
        <script src="{{asset("public/assets/js/stisla.js")}}"></script>
        <script src="{{asset("public/assets/modules/izitoast/js/iziToast.min.js")}}"></script>
        <script src="{{asset("public/assets/modules/sweetalert/sweetalert.min.js")}}"></script>

        <script src="{{asset("public/assets/modules/jquery-validation/jquery.validate.min.js")}}"></script>
        <script src="{{asset("public/assets/modules/jquery-validation/additional-methods.min.js")}}"></script>

        @if (session('success'))
        <script type="text/javascript">
iziToast.success({
    message: '{{ session("success") }}',
    position: 'topRight'
});
        </script>
        @endif

        @if (session('error'))
        <script type="text/javascript">
            iziToast.error({
                message: '{{ session("error") }}',
                position: 'topRight'
            });
        </script>
        @endif


        <!-- Page Specific JS File -->
        @yield('addjs')

        <!-- Template JS File -->
        <script src="{{asset("public/assets/js/scripts.js")}}"></script>
        <script src="{{asset("public/assets/js/custom.js")}}"></script>

        <script type="text/javascript">
            var reminder_url = "";

            function strip(html) {
                var tmp = document.createElement("DIV");
                tmp.innerHTML = html;
                var str = tmp.textContent || tmp.innerText || "";
                return str.trim();
            }

            jQuery(document).ready(function () {
                jQuery.ajaxSetup({
                    beforeSend: function (xhr, data) {
                        data.data += '&_token=' + $('meta[name="csrf-token"]').attr('content');
//                        $('#loading').show();
                    },
                    complete: function ()
                    {
//                        $('#loading').hide();
                    }
                });
            });
        </script> 
    </body>
</html>
