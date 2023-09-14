@extends('layouts.layout')

@section('addcss')
@endsection

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Edit Profile</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">Edit Profile</div>
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
                        <form class="" id="edit_user_form" novalidate="" action="{{route("update_profile",$user->a_id)}}" enctype="multipart/form-data" method="POST">
                            @csrf
                            <input type="password" style="display: none">
                            <div class="card-body">
                                <div class="col-sm-12">
                                    <div class="row">
                                        <div class="col-sm-12 form-group">
                                            <img alt="img" src="{{getPhotoURL('admins',Auth::user()->a_id,Auth::user()->a_image)}}" class="rounded-circle mr-1" height="100" width="100">
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input form-control" name="user_image" id="customFile" alt="Admin Image" placeholder="Admin Image" accept="image/*">
                                                <label class="custom-file-label" for="customFile">Choose file</label>
                                                <div class="invalid-feedback">
                                                    Only image file accepted and less then 5MB
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 form-group">
                                            <input type="text" value="{{$user->a_first_name}}" placeholder="First Name" name="first_name" class="form-control required" required="">
                                            <div class="invalid-feedback">
                                                Please enter first name
                                            </div>
                                        </div>
                                        <div class="col-sm-6 form-group">
                                            <input type="text" value="{{$user->a_last_name}}" placeholder="Last Name" name="last_name" class="form-control" required="">
                                            <div class="invalid-feedback">
                                                Please enter last name
                                            </div>
                                        </div>
                                        <div class="col-sm-6 form-group">
                                            <input type="email" value="{{$user->a_email}}" placeholder="Email" name="email" class="form-control" required="">
                                            <div class="invalid-feedback">
                                                Please enter valid email address
                                            </div>
                                        </div>
                                        <div class="col-sm-6 form-group">
                                            <input type="text" value="{{$user->a_user_name}}" placeholder="Username" name="user_name" class="form-control" required="">
                                            <div class="invalid-feedback">
                                                Please enter login username
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