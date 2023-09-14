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
            <h1>Add New Category</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">
                    <a href="{{route("categories.index")}}">Categories</a>
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
                        <form class="" id="add_custom_form" novalidate="" action="{{route("categories.store")}}" enctype="multipart/form-data" method="POST">
                            @csrf
                            <div class="card-body">
                                <div class="col-sm-12">
                                    <div class="row">
                                        <div class="col-sm-6 form-group">
                                            <label for="category_name">Category Name</label>
                                            <input type="text" value="{{old("category_name")}}" placeholder="Enter category Name" name="category_name" class="form-control required" required="" maxlength="100" id="category_name">
                                            <div class="invalid-feedback">
                                                Please enter category name
                                            </div>
                                        </div>

                                        <div class="col-sm-6 form-group">
                                            <label for="category_color">Category Color</label>
                                            <div class="input-group colorpickerinput">
                                                <input type="text" class="form-control required c_color" name="category_color" placeholder="Select Color" required="" id="category_color" value="{{old("category_color")}}">
                                                <div class="input-group-append">
                                                    <div class="input-group-text">
                                                        <i class="fas fa-fill-drip"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="invalid-feedback">
                                                Select Category color
                                            </div>
                                        </div>

                                        <div class="col-sm-6 form-group">
                                            <label for="customFile">Category Image (With Background)</label>
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input form-control required" name="category_image" id="customFile" accept="image/*" alt="Image">
                                                <label class="custom-file-label" for="customFile">Choose file</label>
                                                <div class="invalid-feedback">
                                                    Only image file accepted and less then 5MB
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-6 form-group">
                                            <label for="customFileTrans">Category Image (Transparent Image)</label>
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input form-control required" name="category_trans_image" id="customFileTrans" accept="image/*" alt="Image">
                                                <label class="custom-file-label" for="customFileTrans">Choose file</label>
                                                <div class="invalid-feedback">
                                                    Only image file accepted and less then 5MB
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-12 form-group">
                                            <div class="control-label">Is Eco category?</div>
                                            <label class="custom-switch mt-2">
                                                <input type="checkbox" name="c_is_eco" class="custom-switch-input">
                                                <span class="custom-switch-indicator"></span>
                                                <span class="custom-switch-description">enable switch to make this category as eco</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button class="btn btn-primary" type="button" id="category_submit">Submit</button>
                                <a href="{{route("categories.index")}}" class="btn btn-light">Cancel</a>
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
    var controller_url = "{{route('categories.index')}}";
    var selected_user = "";
</script>
<script src="{{asset("public/assets/modules/bootstrap-tagsinput.js")}}"></script>
<script src="{{asset("public/assets/modules/select2/dist/js/select2.full.min.js")}}"></script>
<script src="{{asset("public/assets/modules/bootstrap-colorpicker/dist/js/bootstrap-colorpicker.min.js")}}"></script>
<script src="{{asset("public/assets/pages-js/categories/add_edit.js")}}"></script>
@endsection