@extends('emails.layouts.layout')

@section('content')
<tr>
    <td>
        <table  align="left" cellpadding="10" cellspacing="0"  width="100%" style="padding: 40px 25px 50px 25px; max-width:600px;border-spacing:0px;border-width:medium;border-style:none;border-top:1px solid #efefef;border-bottom: 1px solid #efefef; color: #4f4f4f; font-size: 14px;line-height:22px;">
            <tbody>
                <tr>
                    <td style="color: #27B9D5; font-size: 22px; font-weight: bold;">Welcome to {{ config('app.name') }}.</td>
                </tr>
                <tr>
                    <td>You've received friend invitation mail from {{$invitation_from}}</td>
                </tr>

                <tr>
                    <td>{{ config('app.name') }} App is available on App Store and Play Store</td>
                </tr>
                <tr style="text-align: center">
                    <td style="text-align: center"><a href="https://www.apple.com/in/ios/app-store/?friend_invitation_token={{$invitation_token}}"><img src="{{asset('/public/assets/images/app-store.png')}}" width="100" height="100" ></a></td>
                </tr>
                <tr style="text-align: center">
                    <td style="text-align: center"><a href="https://play.google.com/store?hl=en&friend_invitation_token={{$invitation_token}}"><img src="{{asset('/public/assets/images/play-store.png')}}" width="100" height="100" ></a></td>
                </tr>
                <tr>
                    <td></td>
                </tr>
                <tr>
                    <td style="padding: 10px 10px 0 10px;">The {{ config('app.name') }} Team</td>
                </tr>
                <tr>
                    <td style="padding:0 10px;">
                        Contact details are at <a href="{{ env('APP_URL','http://127.0.0.1') }}" style="color:#27B9D5;text-decoration:none;">{{ env('APP_URL','http://127.0.0.1') }}</a>
                    </td>
                </tr>
            </tbody>
        </table>
    </td>
</tr>

@endsection