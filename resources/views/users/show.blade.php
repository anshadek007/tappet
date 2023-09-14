@extends('layouts.layout')

@section('content')
@php
$module_permissions = Session::get("user_access_permission");
$module_permission = !empty($module_permissions['users']) ? $module_permissions['users'] : array();
@endphp
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>User Details</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">
                    <a href="{{route("users.index")}}">Users</a>
                </div>
                <div class="breadcrumb-item">Details</div>
            </div>
        </div>
        <div class="section-body">
            <h2 class="section-title">User Details</h2>
            <p class="section-lead">Information relating to users, lists of user's detail and so on.</p>

            <div class="row">
                <div class="col-12 col-sm-12 col-lg-8">
                    <div class="card author-box card-primary">
                        <div class="card-body">
                            <div class="author-box-left">
                                <img alt="image" src="{{$user->u_image}}" class="rounded-circle mr-1" width="100" height="100">
                                <div class="clearfix"></div>
                            </div>
                            <div class="author-box-details">
                                <div class="form-group mb-1">
                                    <label>First Name</label> : {{$user->u_first_name}} 
                                </div>
                                <div class="form-group mb-1">
                                    <label>Last Name</label> : {{$user->u_last_name}} 
                                </div>
                                <div class="form-group mb-1">
                                    <label>Email</label> : {{$user->u_email}} 
                                    ({!!$user->u_is_verified==1 ? "<b class='text-success'>Verified</b>" : "<b class='text-danger'>Verification Pending</b>"!!})
                                </div>
                                <div class="form-group mb-1">
                                    <label>Phone Number</label> : {{$user->u_mobile_number}} 
                                    ({!!$user->u_phone_verified==1 ? "<b class='text-success'>Verified</b>" : "<b class='text-danger'>Verification Pending</b>"!!})
                                </div>
                                <div class="form-group mb-1">
                                    <label>Gender</label> : {{$user->u_gender}} 
                                </div>
                                <div class="form-group mb-1">
                                    <label>Date Of Birth</label> : {{ $user->u_dob ? date(config('constants.DATE_ONLY_NEW'),strtotime($user->u_dob)) : " - - - " }}
                                </div>
                                <div class="form-group mb-1">
                                    <label>Address</label> : {{ $user->u_address }}
                                </div>    
                                <div class="form-group mb-1">
                                    <label>City</label> : {{ $user->city ? $user->city->city_name : " - - - "}}
                                </div>    
                                <div class="form-group mb-1">
                                    <label>Country</label> : {{ $user->country ? $user->country->c_name : " - - - "}}
                                </div>
                                <div class="form-group mb-1">
                                    <label>Zip Code</label> : {{ $user->u_zipcode }}
                                </div>
                                <div class="form-group mb-1">
                                    <label>Created Date</label> : {{ date(config('constants.DATE_ONLY_NEW'),strtotime($user->u_created_at)) }}
                                </div>
                                <div class="w-100 d-sm-none"></div>
                                <div class="float-right mt-sm-0 mt-3">
                                    <a href="{{route("users.index")}}" class="btn">Back <i class="fas fa-chevron-right"></i></a>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-sm-12 col-md-12 col-lg-6">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h5>User Pets</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="datatable">
                                    <thead>
                                        <tr>
                                            <th>Pet Name</th>
                                            <th>Gender</th>
                                            <th>Delete</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($user->user_pets) && $user->user_pets->count() > 0)
                                        @foreach($user->user_pets as $pet_member)
                                        <tr>
                                            <td><img alt="img" src="{{$pet_member->pet_image}}" class="rounded-circle mr-1" height="40" width="40"> {{$pet_member->pet_name}}</td>
                                            <td>{{$pet_member->pet_gender}}</td>
                                            <td>
                                                @if(in_array('destroy',$module_permission))
                                                <a href="{{route("pets.delete_pet", $pet_member->pet_id)}}" class="btn btn-danger">Delete</a>
                                                @else
                                                - - - -
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                        @else
                                        <tr>
                                            <td colspan="3">No Pet found</td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-12 col-md-12 col-lg-6">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h5>User Groups</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="datatable">
                                    <thead>
                                        <tr>
                                            <th>Group Name</th>
                                            <th>Group Privacy</th>
                                            <th>Total Members</th>
                                            <th>Delete</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($user->user_groups) && $user->user_groups->count() > 0)
                                        @foreach($user->user_groups as $pet_member)
                                        <tr>
                                            <td><img alt="img" src="{{$pet_member->group_image}}" class="rounded-circle mr-1" height="40" width="40"> {{$pet_member->group_name}}</td>
                                            <td>{{$pet_member->group_privacy}}</td>
                                            <td>{{!empty($pet_member->group_members) && $pet_member->group_members->count() > 0 ? $pet_member->group_members->count() : 0}}</td>
                                            <td>
                                                @if(in_array('destroy',$module_permission))
                                                <a href="{{route("groups.delete_group", $pet_member->group_id)}}" class="btn btn-danger">Delete</a>
                                                @else
                                                - - - -
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                        @else
                                        <tr>
                                            <td colspan="4">No Group found</td>
                                        </tr>
                                        @endif
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
                            <h5>User Events</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="datatable">
                                    <thead>
                                        <tr>
                                            <th>Event Name</th>
                                            <th>Location</th>
                                            <th>Start DateTime</th>
                                            <th>End DateTime</th>
                                            <th>Participants</th>
                                            <th>Total Members</th>
                                            <th>Total Groups</th>
                                            <th>Delete</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($user->user_events) && $user->user_events->count() > 0)
                                        @foreach($user->user_events as $pet_member)
                                        <tr>
                                            <td><img alt="img" src="{{$pet_member->event_image}}" class="rounded-circle mr-1" height="40" width="40"> {{$pet_member->event_name}}</td>
                                            <td>{{$pet_member->event_location}}</td>
                                            <td>{{ date(config('constants.DATE_ONLY_NEW'),strtotime($pet_member->event_start_date))}} {{$pet_member->event_start_time}}</td>
                                            <td>{{ date(config('constants.DATE_ONLY_NEW'),strtotime($pet_member->event_end_date))}} {{$pet_member->event_end_time}}</td>
                                            <td>{{$pet_member->event_participants}}</td>
                                            <td>{{!empty($pet_member->event_members) && $pet_member->event_members->count() > 0 ? $pet_member->event_members->count() : 0}}</td>
                                            <td>{{!empty($pet_member->event_groups) && $pet_member->event_groups->count() > 0 ? $pet_member->event_groups->count() : 0}}</td>
                                            <td>
                                                @if(in_array('destroy',$module_permission))
                                                <a href="{{route("events.delete_event", $pet_member->event_id)}}" class="btn btn-danger">Delete</a>
                                                @else
                                                - - - -
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                        @else
                                        <tr>
                                            <td colspan="8">No event found</td>
                                        </tr>
                                        @endif
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
                            <h5>User Posts</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="datatable">
                                    <thead>
                                        <tr>
                                            <th>Post Name</th>
                                            <th>Location</th>
                                            <th>Post Type</th>
                                            <th>Total Likes</th>
                                            <th>Total Comments</th>
                                            <th>Delete</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($user->user_posts) && $user->user_posts->count() > 0)
                                        @foreach($user->user_posts as $pet_member)
                                        <tr>
                                            <td><img alt="img" src="{{$pet_member->post_image}}" class="rounded-circle mr-1" height="40" width="40"> {{$pet_member->post_name}}</td>
                                            <td>{{$pet_member->post_location}}</td>
                                            <td>{{$pet_member->post_type}}</td>
                                            <td>{{!empty($pet_member->post_likes) && $pet_member->post_likes->count() > 0 ? $pet_member->post_likes->count() : 0}}</td>
                                            <td>{{!empty($pet_member->post_comments) && $pet_member->post_comments->count() > 0 ? $pet_member->post_comments->count() : 0}}</td>
                                            <td>
                                                @if(in_array('destroy',$module_permission))
                                                <a href="{{route("posts.delete_post", $pet_member->post_id)}}" class="btn btn-danger">Delete</a>
                                                @else
                                                - - - -
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                        @else
                                        <tr>
                                            <td colspan="6">No post found</td>
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