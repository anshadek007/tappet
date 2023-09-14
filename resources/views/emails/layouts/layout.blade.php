<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <title>{{ config('app.name') }}</title>
    </head>
    <style>
        html, body {
            height: 100%;
        }
        @media screen and (max-width:600px) {
            img[class="logo"] {
                max-width: 200px;
            }
            td[class="view-email"] {
                float: left!important;
                padding: 2px 35px 15px 41px!important;
            }
        }
    </style>
    <body style="padding: 10px 0 20px 0;">
        <div align="center">
            <div style="width:100%;max-width:600px;font-family:Helvetica,Arial,sans-serif;margin-top: 20px; padding:25px 0;border-radius: 5px;">
                <table cellpadding="0" cellspacing="0" style="width:100%;font-family:Helvetica,Arial,sans-serif;font-weight:100;max-width:600px;border-spacing:0px;border-width:medium;border-style:none">
                    <tbody>
                        @include('emails.partials.header')

                        @yield('content')

                        @include('emails.partials.footer')
                    </tbody>
                </table>
            </div>
        </div>
    </body>
</html>