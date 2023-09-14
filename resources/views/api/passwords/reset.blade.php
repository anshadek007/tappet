@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Reset Password') }}</div>

                <div class="card-body">
                    @php
                        if(isset($corporate) && $corporate == 'true'){
                            $url = route('api.password.corporate.update');
                        } else {
                            $url = route('api.password.update');
                        }
                    @endphp
                    @if(!empty($password_resets))
                    <form id="password_update" method="POST" autocomplete="off"  action="{{ $url }}">
                        @csrf

                        <input type="hidden" name="token" value="{{ $token }}">
                        <input type="hidden" name="u_email" value="{{ $email }}">

                        <div class="form-group row">
                            <label for="u_password" class="col-md-4 col-form-label text-md-right">New Password</label>
                            <div class="col-md-6">
                                <input id="u_password" type="password" class="form-control{{ $errors->has('u_password') ? ' is-invalid' : '' }}" minlength="8" name="u_password" required>
                                <span class="text-danger pass_error" role="alert">
                                    <strong>Enter new password.</strong>
                                    </span>
                                @if ($errors->has('u_password'))
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('u_password') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="password_confirm" class="col-md-4 col-form-label text-md-right">{{ __('Confirm Password') }}</label>
                            <div class="col-md-6">
                                <input id="password_confirm" type="password" class="form-control" name="u_password_confirmation" required>
                                <span class="text-danger cpass_error" role="alert">
                                    <strong>Passwords do not match.</strong>
                                </span>
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="button" class="btn btn-primary" id="btnSubmit">
                                    {{ __('Reset Password') }}
                                </button>
                            </div>
                        </div>
                    </form>
                    @else
                    <div class="alert alert-danger" role="alert">
                        This reset password link is invalid / expired.
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
<script type="text/javascript">
$(function () {
    $(".cpass_error, .pass_error").hide();

    $("#u_password").keydown(function (event) {
        if (event.keyCode == 32) {
            event.preventDefault();
        }
    });

//    $('#u_password').keyup(function () {
//        this.value = this.value.replace(/\s/g, '');
//    });

    $('#u_password').on('keyup', function () {
        $(".pass_error").hide();
        var Password = $(this).val();

        if (Password.length < 6) {
            $(".pass_error").show().html('<strong>Minimum 6 character is required</strong>');
        } else {
            $(".pass_error").hide();
        }
    });


    $("#btnSubmit").click(function () {
        $(".cpass_error, .pass_error").hide();
        var password = $("#u_password").val();
        var confirmPassword = $("#password_confirm").val();

        if (password == "") {
            $(".pass_error").show().html('<strong>Enter new password.</strong>');
            return false;
        } else if (password.length == "") {
            $(".pass_error").show().html('<strong>Enter new password.</strong>');
            return false;
        } else if (password.length < 6) {
            $(".pass_error").show().html('<strong>Minimum 6 character is required.</strong>');
            return false;
        } else if (password != confirmPassword) {
            $(".cpass_error").show();
            return false;
        }

        $("#password_update").submit();
        return true;
    });
});
</script>
@endsection
