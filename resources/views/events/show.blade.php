@extends('layouts.layout')

@section('content')
@php
$module_permissions = Session::get("user_access_permission");
$module_permission = !empty($module_permissions['events']) ? $module_permissions['events'] : array();
@endphp
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Event Details</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">
                    <a href="{{route("events.index")}}">Events</a>
                </div>
                <div class="breadcrumb-item">Details</div>
            </div>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-sm-12 col-md-8">
                    <div class="card author-box card-primary">
                        <div class="card-header">
                            <h5>Event Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-12">
                                    <label>Event Name : </label> <b>{{$event->event_name}}</b>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <label>Event Description : </label> <b>{{$event->event_description}}</b>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <label>Event Location : </label> <b>{{$event->event_location}}</b>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <label>Event Participants : </label> <b>{{$event->event_participants}}</b>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <label>Event Start Date : </label> <b>{{ date(config('constants.DATE_ONLY_NEW'),strtotime($event->event_start_date)) }}</b>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <label>Event Start Time : </label> <b>{{ $event->event_start_time }}</b>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <label>Event End Date : </label> <b>{{ date(config('constants.DATE_ONLY_NEW'),strtotime($event->event_end_date)) }}</b>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <label>Event End Time : </label> <b> {{ $event->event_end_time }}</b>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <label>Created Date : </label> <b>{{ date(config('constants.DATE_ONLY_NEW'),strtotime($event->event_created_at)) }}</b>
                                </div>
                            </div>
                            <div class="float-right mt-sm-0 mt-3">
                                <a href="{{route("events.index")}}" class="btn">Back <i class="fas fa-chevron-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-12 col-md-4">
                    <div class="card author-box card-primary">
                        <div class="card-header">
                            <h5>Event Image</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-12 m-0 p-0 text-center">
                                    <img alt="img" src="{{$event->event_image}}" class="rounded-circle text-center" height="250" width="250">
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
                            <h5>Event Image Gallery</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @if(!empty($event->images) && $event->images->count() > 0)
                                @foreach($event->images as $image)
                                <div class="col-sm-4 col-md-3 col-lg-2 m-0 p-0 text-center">
                                    <img alt="img" src="{{$image->event_image_image}}" class="rounded-circle text-center mb-4" height="150" width="150">
                                </div>
                                @endforeach
                                @else
                                <div class="col-sm-12 m-0 p-0 text-center">
                                    <p>Event gallery images not found.</p>
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
                            <h5>Event Members</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="datatable">
                                    <thead>
                                        <tr>
                                            <th>Member Name</th>
                                            <th>Invitation Status</th>
                                            <th>Delete</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($event->event_members) && $event->event_members->count() > 0)
                                        @foreach($event->event_members as $event_member)
                                        <tr>
                                            <td><img alt="img" src="{{$event_member->member->u_image}}" class="rounded-circle mr-1" height="40" width="40"> {{$event_member->member->u_first_name}} {{$event_member->member->u_last_name}}</td>

                                            <td>
                                                @if($event_member->em_status==1)
                                                Going
                                                @elseif($event_member->em_status==2)
                                                Pending Action
                                                @elseif($event_member->em_status==3)
                                                Interested
                                                @elseif($event_member->em_status==4)
                                                Not Going
                                                @elseif($event_member->em_status==5)
                                                Unfollow Event for no notification
                                                @endif
                                            </td>
                                            <td>
                                                @if(in_array('destroy',$module_permission))
                                                <a href="{{route("events.delete_member", $event_member->em_id)}}" class="btn btn-danger">Delete</a>
                                                @else
                                                - - - -
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                        @else
                                        <tr>
                                            <td colspan="3">No Members added in this event</td>
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
                            <h5>Event Groups</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="datatable">
                                    <thead>
                                        <tr>
                                            <th>Group Name</th>
                                            <th>Delete</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($event->event_groups) && $event->event_groups->count() > 0)
                                        @foreach($event->event_groups as $event_member)
                                        <tr>
                                            <td><img alt="img" src="{{$event_member->group->group_image}}" class="rounded-circle mr-1" height="40" width="40"> {{$event_member->group->group_name}}</td>
                                            <td>
                                                @if(in_array('destroy',$module_permission))
                                                <a href="{{route("events.delete_group", $event_member->eg_id)}}" class="btn btn-danger">Delete</a>
                                                @else
                                                - - - -
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                        @else
                                        <tr>
                                            <td colspan="2">No Groups added in this event</td>
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