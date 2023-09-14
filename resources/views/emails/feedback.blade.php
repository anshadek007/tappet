@extends('emails.layouts.layout')

@section('content')
<!-- Main content -->
<tr>
    <td>
        <table  align="left" cellpadding="10" cellspacing="0"  width="100%" style="padding: 20px 10px; max-width:600px;border-spacing:0px;border-width:medium;border-style:none;border-top:1px solid #efefef;border-bottom: 1px solid #efefef; color: #4f4f4f; font-size: 14px;line-height:22px;">
            <tbody>
                <tr>
                    <td style="color: #27c3f7; font-size: 22px; font-weight: bold;">Hello Admin,</td>
                </tr>
                <tr>
                    <td>
                        You have received new feedback mail.
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0 10px 0 10px;">
                        Name: {{ $name }}  
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0 10px 0 10px;">
                        Email: {{ $email }}  
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0 10px 0 10px;">
                        Message: {!! html_entity_decode($desc) !!}  
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
                        <a href="mailto:support@xyz.com" style="color:#27c3f7;text-decoration:none;">support@xyz.com</a>
                    </td>
                </tr>
            </tbody>
        </table>
    </td>
</tr>

@endsection