<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
        <title>404 &mdash; {{ config('app.name') }}</title>

        <!-- General CSS Files -->
        <link rel="stylesheet" href="{{asset("public/assets/modules/bootstrap/css/bootstrap.min.css")}}">
        <link rel="stylesheet" href="{{asset("public/assets/modules/fontawesome/css/all.min.css")}}">

        <link rel="stylesheet" href="{{asset("public/assets/css/style.css")}}">
        <link rel="stylesheet" href="{{asset("public/assets/css/components.css")}}">
    </head>

    <body>
        <div id="app">
            <section class="section">
                <div class="container mt-5">
                    <div class="page-error">
                        <div class="page-inner">
                            <h1>404</h1>
                            <div class="page-description">
                                The page you were looking for could not be found.
                            </div>
                            <div class="page-search">
                                <div class="mt-3">
                                    <a href="{{url("/")}}">Back to Home</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="simple-footer mt-5">
                        Copyright &copy; {{date("Y")}} 
                        <div class="bullet"></div> Powered By <a href="{{ url('/') }}">{{ config('app.name') }}</a>
                    </div>
                </div>
            </section>
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

        <!-- Page Specific JS File -->

        <!-- Template JS File -->
        <script src="{{asset("public/assets/js/scripts.js")}}"></script>
        <script src="{{asset("public/assets/js/custom.js")}}"></script>
    </body>
</html>