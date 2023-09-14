@extends('emails.layouts.layout')

@section('content')
<!-- Main content -->
<tr>
    <td>
        <table  align="left" cellpadding="10" cellspacing="0"  width="100%" style="padding: 20px 10px; max-width:600px;border-spacing:0px;border-width:medium;border-style:none;border-top:1px solid #efefef;border-bottom: 1px solid #efefef; color: #4f4f4f; font-size: 14px;line-height:22px;">
            <tbody>
                <tr>
                    <td style="color: #27c3f7; font-size: 22px; font-weight: bold;">Hello {{$name}},</td>
                </tr>
                <tr>
                    <td>
                        You have recently requested a new password. Please use below OTP for create new password.
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0 10px 0 10px;">
                        Your OTP is: {{ $otp }}  
                    </td>
                </tr>
                
                <tr>
                    <td>
                        You can always contact us and if we don’t respond immediately we’ll be back to you within 24 hours.
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 10px 0 10px;">The {{ config('app.name') }} Support Team</td>
                </tr>
                <tr>
                    <td style="padding:0 10px;">
                        <a href="mailto:{{env('MAIL_SUPPORT_USER')}}" style="color:#27c3f7;text-decoration:none;">{{env('MAIL_SUPPORT_USER')}}</a>
                    </td>
                </tr>
            </tbody>
        </table>
    </td>
</tr>

@endsection