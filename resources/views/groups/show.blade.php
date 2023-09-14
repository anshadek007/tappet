@extends('layouts.layout')

@section('content')
@php
$module_permissions = Session::get("user_access_permission");
$module_permission = !empty($module_permissions['groups']) ? $module_permissions['groups'] : array();
@endphp
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Group Details</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">
                    <a href="{{route("groups.index")}}">Groups</a>
                </div>
                <div class="breadcrumb-item">Details</div>
            </div>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-sm-12 col-md-8">
                    <div class="card author-box card-primary">
                        <div class="card-header">
                            <h5>Group Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-12">
                                    <label>Group Name : </label> <b>{{$group->group_name}}</b>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <label>Group Description : </label> <b>{{$group->group_description}}</b>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <label>Group Privacy : </label> <b>{{$group->group_privacy}}</b>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <label>Created Date : </label> <b>{{ date(config('constants.DATE_ONLY_NEW'),strtotime($group->group_created_at)) }}</b>
                                </div>
                            </div>
                            <div class="float-right mt-sm-0 mt-3">
                                <a href="{{route("groups.index")}}" class="btn">Back <i class="fas fa-chevron-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-12 col-md-4">
                    <div class="card author-box card-primary">
                        <div class="card-header">
                            <h5>Group Image</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-12 m-0 p-0 text-center">
                                    <img alt="img" src="{{$group->group_image}}" class="rounded-circle text-center" height="150" width="150" style="background-color:#888;border-radius: 50%;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-sm-12 col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h5>Group Members</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="datatable">
                                    <thead>
                                        <tr>
                                            <th>Member Name</th>
                                            <th>Role</th>
                                            <th>Delete</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($group->group_members) && $group->group_members->count() > 0)
                                        @foreach($group->group_members as $group_member)
                                        <tr>
                                            <td><img alt="img" src="{{$group_member->member->u_image}}" class="rounded-circle mr-1" height="40" width="40"> {{$group_member->member->u_first_name}} {{$group_member->member->u_last_name}}</td>
                                            <td>{{$group_member->gm_role}}</td>
                                            <td>
                                                @if(in_array('destroy',$module_permission))
                                                <a href="{{route("groups.delete_member", $group_member->gm_id)}}" class="btn btn-danger">Delete</a>
                                                @else
                                                - - - -
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                        @else
                                        <tr>
                                            <td colspan="3">No Members added in this group</td>
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