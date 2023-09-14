@extends('emails.layouts.layout')

@section('content')
<tr>
    <td>
        <table  align="left" cellpadding="10" cellspacing="0"  width="100%" style="padding: 40px 25px 50px 25px; max-width:600px;border-spacing:0px;border-width:medium;border-style:none;border-top:1px solid #efefef;border-bottom: 1px solid #efefef; color: #4f4f4f; font-size: 14px;line-height:22px;">
            <tbody>
                <tr>
                    <td style="color: #ea0a6d; font-size: 22px; font-weight: bold;">Welcome to {{ config('app.name') }}.</td>
                </tr>
                <tr>
                    <td>
                        If you let us, you'll have a lot of fun in the parks near to you with {{ config('app.name') }} and you'll learn the many stories about the park's history and ecology.
                    </td>
                </tr>
                <tr>
                    <td>
                        We could even change your world by getting you into the parks and being active more often. 
                    </td>
                </tr>
                <tr>
                    <td>
                        The system will also bring you information about events and activities taking place in the park as well. These might be classes run in the park or talks being held there. 
                    </td>
                </tr>
                <tr>
                    <td>
                        We’ll be in touch soon with more information but in the meantime stay {{ config('app.name') }}!
                    </td>
                </tr>
                <tr>
                    <td></td>
                </tr>
                <tr>
                    <td style="padding: 10px 10px 0 10px;">The {{ config('app.name') }} Team</td>
                </tr>
                <tr>
                    <td style="padding:0 10px;">
                        Contact details are at <a href="{{ env('APP_URL','http://127.0.0.1') }}" style="color:#ea0a6d;text-decoration:none;">{{ env('APP_URL','http://127.0.0.1') }}</a>
                    </td>
                </tr>
            </tbody>
        </table>
    </td>
</tr>

@endsection