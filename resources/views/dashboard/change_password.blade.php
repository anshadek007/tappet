@extends('layouts.layout')

@section('addcss')
@endsection

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Change Password</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">Change Password</div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul style="margin-bottom: 0px">
                                @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        <form class="" id="change_password_form" novalidate="" action="{{route("update_password",$user->a_id)}}" enctype="multipart/form-data" method="POST">
                            @csrf
                            <input type="password" style="display: none">
                            <div class="card-body">
                                <div class="col-sm-12">
                                    <div class="row">
                                        <div class="col-sm-12 form-group">
                                            <input type="password" id="old_password" placeholder="Old Password" name="old_password" class="form-control" required="">
                                            <div class="invalid-feedback">
                                                Please enter old password
                                            </div>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <input type="password" id="new_password" placeholder="New Password" name="new_password" class="form-control" required="">
                                            <div class="invalid-feedback">
                                                Please enter password
                                            </div>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <input type="password" name="confirm_password" placeholder="Confirm Password" class="form-control" required="">
                                            <div class="invalid-feedback">
                                                Passwords do not match
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button class="btn btn-primary">Submit</button>
                                <a href="{{route("dashboard")}}" class="btn btn-light">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('addjs')
<script src="{{asset("public/assets/pages-js/admins/add_edit.js")}}"></script>
@endsection