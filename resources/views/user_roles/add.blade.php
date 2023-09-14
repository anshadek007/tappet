@extends('layouts.layout')

@section('addcss')

@endsection

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Add Role</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">
                    <a href="{{route("user-roles.index")}}">Roles</a>
                </div>
                <div class="breadcrumb-item">Add New</div>
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
                        <form class="needs-validation" novalidate="" action="{{route("user-roles.store")}}" method="POST">
                            @csrf
                            <div class="card-body">
                                <div class="form-group">
                                    <input type="text" value="{{old("role_name")}}" placeholder="Role Name" name="role_name" class="form-control" required="">
                                    <div class="invalid-feedback">
                                        Please enter role name
                                    </div>
                                </div>
                                {{--<div class="col-sm-12">
                                    <div class="row">
                                        <div class="col-sm-6" style="padding-left: 0">
                                            <div class="form-group">
                                                <select name="role_type" value="{{old("role_type")}}" id="role_type" class="form-control" required="">
                                                    <option value="">Role Type</option>
                                                    <option value="1">Super Admin</option>
                                                    <option value="2">Sub Admin</option>
                                                </select>
                                                <div class="invalid-feedback">
                                                    Please select role type
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6" style="padding-right: 0">
                                            <div class="form-group" id="parent_role_select">
                                                <select name="parent_role" value="{{old("parent_role")}}" id="parent_role" class="form-control">
                                                    <option value="">Parent Role (None)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>--}}
                                <div class="form-group">
                                    <label>Role Permissions</label>
                                    <div id="accordion">
                                        @php ($first_group = true)
                                        @foreach($all_avilable_role_permissions as $role_group)
                                        <div class="accordion">
                                            <div class="accordion-header collapsed" role="button" data-toggle="collapse" data-target="#panel-body-{{$role_group->urpg_id}}" aria-expanded="{{$first_group == true ? "true" : "false"}}">
                                                <h4>{{$role_group->urpg_name}}</h4>
                                            </div>
                                            <div class="accordion-body collapse {{$first_group == true ? "show" : ""}}" id="panel-body-{{$role_group->urpg_id}}" data-parent="#accordion">
                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        @php($permission_modules = $role_group->roleTypes)
                                                        <div class="row">
                                                            <div class="col-4">
                                                                <div class="list-group" id="list-tab" role="tablist">
                                                                    @php($first_permission = true)
                                                                    @foreach($permission_modules as $permission)
                                                                    <a class="list-group-item list-group-item-action {{$first_permission==true ? "active show":""}}" id="tab-{{$permission->urpt_id}}-list" data-toggle="list" href="#list-{{$permission->urpt_id}}" role="tab" {{$first_permission=true ? 'aria-selected=="true"':""}}>
                                                                        {{$permission->urpt_name}}
                                                                    </a>
                                                                    @php($first_permission = false)
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                            <div class="col-8">
                                                                <div class="tab-content" id="nav-tabContent">
                                                                    @php($first_permission = true)
                                                                    @foreach($permission_modules as $permission)
                                                                    <div class="tab-pane fade {{$first_permission==true ? "active show":""}}" id="list-{{$permission->urpt_id}}" role="tabpanel" aria-labelledby="tab-{{$permission->urpt_id}}-list">
                                                                        <div class="selectgroup selectgroup-pills">
                                                                            @foreach($types_of_permission as $key => $sub_permission)
                                                                            <label class="selectgroup-item">
                                                                                <input type="checkbox" name="role_permissions[{{$permission->urpt_id}}][{{$key}}]" value="{{$sub_permission['key']}}" class="selectgroup-input" id="permission_{{$permission->urpt_id}}_{{$key}}">
                                                                                <span class="selectgroup-button">{{$sub_permission['value']}}</span>
                                                                            </label>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                    @php($first_permission = false)
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @php ($first_group = false)
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button class="btn btn-primary">Submit</button>
                                <a href="{{route("user-roles.index")}}" class="btn btn-light">Cancel</a>
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

@endsection