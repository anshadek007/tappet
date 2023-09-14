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
            <h1>Add New Pet Type</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">
                    <a href="{{route("events.index")}}">Pet Types</a>
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
                        <form class="" id="add_custom_form" novalidate="" action="{{route("events.store")}}" enctype="multipart/form-data" method="POST">
                            @csrf
                            <div class="card-body">
                                <div class="col-sm-12">
                                    <div class="row">
                                        <div class="col-sm-6 form-group">
                                            <label for="pet_type_name">Pet Type Name</label>
                                            <input type="text" value="{{old("pet_type_name")}}" placeholder="Enter pet type Name" name="pet_type_name" class="form-control required" required="" maxlength="100" id="pet_type_name">
                                            <div class="invalid-feedback">
                                                Please enter pet type name
                                            </div>
                                        </div>

                                        <div class="col-sm-6 form-group">
                                            <label for="customFile">Pet Type Image</label>
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input form-control required" name="image" id="customFile" accept="image/*" alt="Image">
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
                                <a href="{{route("events.index")}}" class="btn btn-light">Cancel</a>
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
    var controller_url = "{{route('events.index')}}";
</script>
<script src="{{asset("public/assets/modules/bootstrap-tagsinput.js")}}"></script>
<script src="{{asset("public/assets/modules/select2/dist/js/select2.full.min.js")}}"></script>
<script src="{{asset("public/assets/modules/bootstrap-colorpicker/dist/js/bootstrap-colorpicker.min.js")}}"></script>
<script src="{{asset("public/assets/pages-js/events/add_edit.js")}}"></script>
@endsection