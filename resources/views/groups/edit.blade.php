@extends('layouts.layout')

@section('addcss')
<link rel="stylesheet" href="{{asset("public/assets/modules/select2/dist/css/select2.min.css")}}">
<link rel="stylesheet" href="{{asset("public/assets/css/bootstrap-tagsinput.css")}}">
<link rel="stylesheet" href="{{asset("public/assets/modules/bootstrap-colorpicker/dist/css/bootstrap-colorpicker.min.css")}}">
@endsection

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Edit Group</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">
                    <a href="{{route("groups.index")}}">Groups</a>
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
                        <form class="" id="add_custom_form" novalidate="" action="{{route("groups.update",$group->group_id)}}" enctype="multipart/form-data" method="POST">
                            @csrf
                            <input type="hidden" name="_method" value="PUT">
                            <div class="card-body">
                                <div class="col-sm-12">
                                    <div class="row">
                                        <div class="col-sm-12 form-group">
                                            <img alt="img" src="{{$group->group_image}}" class="rounded-circle mr-1" height="100" width="100">
                                        </div>

                                        <div class="col-sm-6 form-group">
                                            <label for="group_name">Group Name</label>
                                            <input type="text" value="{{ $group->group_name }}" placeholder="Enter pet type Name" name="group_name" class="form-control required" required="" maxlength="100" id="group_name">
                                            <div class="invalid-feedback">
                                                Please enter group name
                                            </div>
                                        </div>

                                        <div class="col-sm-6 form-group">
                                            <label for="group_description">Group Description</label>
                                            <textarea class="form-control" placeholder="description" name="description" required="">{{ $group->group_description }}</textarea>
                                            <div class="invalid-feedback">
                                                Please enter description
                                            </div>
                                        </div>

                                        <div class="col-sm-6 form-group">
                                            <label for="group_privacy">Group Privacy</label>
                                            <select name="group_privacy" id="group_privacy" class="form-control select2">
                                                <option value="">Select Group Privacy</option>
                                                <option value="Private" {{ $group->group_privacy=='Private' ? 'selected' : ''}}>Private</option>
                                                <option value="Public" {{ $group->group_privacy=='Public' ? 'selected' : ''}}>Public</option>
                                            </select>
                                        </div>

                                        <div class="col-sm-6 form-group">
                                            <label for="customFile">Group Image</label>
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input form-control" name="image" id="customFile" accept="image/*" alt="Image">
                                                <label class="custom-file-label" for="customFile">Choose file</label>
                                                <div class="invalid-feedback">
                                                    Only image file accepted and less then 5MB
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button class="btn btn-primary" type="button" id="custom_form_submit">Submit</button>
                                <a href="{{route("groups.index")}}" class="btn btn-light">Cancel</a>
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
    var controller_url = "{{route('groups.index')}}";
</script>
<script src="{{asset("public/assets/modules/bootstrap-tagsinput.js")}}"></script>
<script src="{{asset("public/assets/modules/select2/dist/js/select2.full.min.js")}}"></script>
<script src="{{asset("public/assets/modules/bootstrap-colorpicker/dist/js/bootstrap-colorpicker.min.js")}}"></script>
<script src="{{asset("public/assets/pages-js/groups/add_edit.js")}}"></script>
@endsection