@extends('layouts.layout')

@section('content')
@php
$module_permissions = Session::get("user_access_permission");
$module_permission = !empty($module_permissions['pets']) ? $module_permissions['pets'] : array();
@endphp
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Pet Details</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">
                    <a href="{{route("pets.index")}}">Pets</a>
                </div>
                <div class="breadcrumb-item">Details</div>
            </div>
        </div>
        <div class="section-body">
            <h2 class="section-title">Pet Details</h2>
            <p class="section-lead">Information relating to pets</p>

            <div class="row">
                <div class="col-12 col-sm-12 col-lg-12">
                    <div class="card author-box card-primary">
                        <div class="card-body">
                            <div class="author-box-left">
                                <img alt="image" src="{{$pet->pet_image}}" class="rounded-circle mr-1" width="100" height="100">
                                <div class="clearfix"></div>
                            </div>
                            <div class="author-box-details">
                                <div class="form-group mb-1">
                                    <label>Pet Name</label> : {{$pet->pet_name}} 
                                </div>
                                <div class="form-group mb-1">
                                    <label>Pet Type</label> : {{ $pet->pet_type ? $pet->pet_type->pt_name : " - - - "}}
                                </div>    
                                <div class="form-group mb-1">
                                    <label>Pet Owner</label> : {{ $pet->addedBy ? $pet->addedBy->u_first_name." ".$pet->addedBy->u_last_name : " - - - "}}
                                </div>    
                                <div class="form-group mb-1">
                                    <label>Gender</label> : {{$pet->pet_gender}} 
                                </div>
                                <div class="form-group mb-1">
                                    <label>Pet Size</label> : {{$pet->pet_size}} Pounds
                                </div>
                                <div class="form-group mb-1">
                                    <label>Friendly With Other Pet?</label> : {{$pet->pet_is_friendly ? $pet->pet_is_friendly : ""}}
                                </div>
                                <div class="form-group mb-1">
                                    <label>Date Of Birth</label> : {{ $pet->pet_dob ? date(config('constants.DATE_ONLY_NEW'),strtotime($pet->pet_dob)) : " - - - " }}
                                </div>
                                <div class="form-group mb-1">
                                    <label>Note</label> : {{ $pet->pet_note }}
                                </div>
                                <div class="form-group mb-1">
                                    <label>Created Date</label> : {{ date(config('constants.DATE_ONLY_NEW'),strtotime($pet->pet_created_at)) }}
                                </div>
                                <div class="w-100 d-sm-none"></div>
                                <div class="float-right mt-sm-0 mt-3">
                                    <a href="{{route("pets.index")}}" class="btn">Back <i class="fas fa-chevron-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-sm-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h5>Pet Images</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @if(!empty($pet->images) && $pet->images->count() > 0)
                                @foreach($pet->images as $image)
                                <div class="col-sm-4 col-md-3 col-lg-2 m-0 p-0 text-center">
                                    <img alt="img" src="{{$image->pi_image}}" class="rounded-circle text-center mb-4" height="150" width="150">
                                </div>
                                @endforeach
                                @else
                                <div class="col-sm-12 m-0 p-0 text-center">
                                    <p>pet images not found.</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-sm-12 col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h5>Pet Breeds</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Breed Name</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($pet->breed as $br)
                                        <tr>
                                            <td>{{$br->pb_name}}</td>
                                            <td>{{$br->breed_percentage}}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-sm-12 col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h5>Pet Co-Owners</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="datatable">
                                    <thead>
                                        <tr>
                                            <th>Co-Owner Name</th>
                                            <th>Delete</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($pet->co_owners) && $pet->co_owners->count() > 0)
                                        @foreach($pet->co_owners as $pet_member)
                                        <tr>
                                            <td><img alt="img" src="{{$pet_member->member->u_image}}" class="rounded-circle mr-1" height="40" width="40"> {{$pet_member->member->u_first_name}} {{$pet_member->member->u_last_name}}</td>
                                            <td>
                                                @if(in_array('destroy',$module_permission))
                                                <a href="{{route("pets.delete_member", $pet_member->pet_co_owner_id)}}" class="btn btn-danger">Delete</a>
                                                @else
                                                - - - -
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                        @else
                                        <tr>
                                            <td colspan="2">No Co-Owner found</td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection