@extends('layouts.layout')

@section('addcss')
<link rel="stylesheet" href="{{asset("public/assets/modules/select2/dist/css/select2.min.css")}}">
@endsection

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Edit Admin</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">
                    <a href="{{route("admins.index")}}">Admins</a>
                </div>
                <div class="breadcrumb-item">Edit</div>
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
                        <form class="" id="edit_user_form" novalidate="" action="{{route("admins.update",$user->a_id)}}" enctype="multipart/form-data" method="POST">
                            @csrf
                            <input type="hidden" name="_method" value="PUT">
                            <input type="password" style="display: none">
                            <div class="card-body">
                                <div class="col-sm-12">
                                    <div class="row">
                                        <div class="col-sm-4 form-group">
                                            <img alt="img" src="{{getPhotoURL('admins',$user->a_id,$user->a_image)}}" class="rounded-circle mr-1" height="100" width="100">
                                        </div>
                                        <div class="col-sm-8 form-group">
                                        </div>
                                        <div class="col-sm-6 form-group">
                                            <input type="file" accept="image/*" alt="Admin Image" placeholder="Admin Image" name="user_image" class="form-control">
                                            <div class="invalid-feedback">
                                                Only image file accepted and less then 5MB
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
                                        <div class="col-sm-6 form-group">
                                            <select name="user_role" id="user_role" class="form-control select2" required="">
                                                <option value="" data-type="">Select Admin Role</option>
                                                @foreach($all_avilable_user_roles as $user_role)
                                                @if($user_role->role_id == $user->a_role_id)
                                                <option value="{{$user_role->role_id}}" data-type="{{$user_role->role_type}}" selected="">{{$user_role->role_name}}</option>
                                                @else
                                                <option value="{{$user_role->role_id}}" data-type="{{$user_role->role_type}}">{{$user_role->role_name}}</option>
                                                @endif
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback">
                                                Please select role
                                            </div>
                                        </div>
                                        <div class="col-sm-6 form-group">
                                            <input type="password" id="password" placeholder="Password" name="password" class="form-control">
                                            <div class="invalid-feedback">
                                                Please enter password
                                            </div>
                                        </div>
                                        <div class="col-sm-6 form-group">
                                            <input type="password" name="c_password" placeholder="Confirm Password" class="form-control">
                                            <div class="invalid-feedback">
                                                Passwords do not match
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button class="btn btn-primary">Submit</button>
                                <a href="{{route("admins.index")}}" class="btn btn-light">Cancel</a>
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
<script type="text/javascript">
    var controller_url = "{{route('admins.index')}}";
</script>
<script src="{{asset("public/assets/modules/select2/dist/js/select2.full.min.js")}}"></script>
<script src="{{asset("public/assets/pages-js/admins/add_edit.js")}}"></script>
@endsection