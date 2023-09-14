@extends('layouts.layout')

@section('addcss')
<link rel="stylesheet" href="{{asset("public/assets/modules/select2/dist/css/select2.min.css")}}">

@endsection

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Edit Pet</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">
                    <a href="{{route("pets.index")}}">Pets</a>
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
                        <form class="" id="edit_user_form" novalidate="" action="{{route("pets.update",$pet->pet_id)}}" enctype="multipart/form-data" method="POST">
                            @csrf
                            <input type="hidden" name="_method" value="PUT">
                            <div class="card-body">
                                <div class="col-sm-12">
                                    <div class="row">
                                        <div class="col-sm-4 form-group">
                                            <img alt="img" src="{{$pet->pet_image}}" class="rounded-circle mr-1" height="100" width="100">
                                        </div>
                                        <div class="col-sm-8 form-group">
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input form-control" name="image" id="customFile" accept="image/*" alt="Image">
                                                <label class="custom-file-label" for="customFile">Choose file</label>
                                                <div class="invalid-feedback">
                                                    Only image file accepted and less then 5MB
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 form-group">
                                            <label>Pet Name</label>
                                            <input type="text" value="{{$pet->pet_name}}" placeholder="Pet name" name="pet_name" class="form-control" required="">
                                            <div class="invalid-feedback">
                                                Please enter pet name
                                            </div>
                                        </div>

                                        <div class="col-sm-6 form-group">
                                            <label for="pet_type">Select Pet Type</label>
                                            <select name="pet_type" id="pet_type" class="form-control select2" required="">
                                                <option value="">Select Pet Type</option>
                                                @foreach($pet_types as $type)
                                                @if($pet->pet_type_id==$type->pt_id)
                                                <option value="{{$type->pt_id}}" selected="selected">{{$type->pt_name}}</option>
                                                @else
                                                <option value="{{$type->pt_id}}">{{$type->pt_name}}</option>
                                                @endif
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback">
                                                Please select pet type
                                            </div>
                                        </div>

                                        <div class="col-sm-6 form-group">
                                            <label>Date Of Birth</label>
                                            <input type="date" value="{{$pet->pet_dob}}" placeholder="Date Of Birth" name="pet_dob" class="form-control">
                                        </div>

                                        <div class="col-sm-6 form-group">
                                            <label for="gender">Select Gender</label>
                                            <select name="pet_gender" id="gender" class="form-control select2">
                                                <option value="">Select Gender</option>
                                                <option value="Male" {{ $pet->pet_gender=='Male' ? 'selected' : ''}}>Male</option>
                                                <option value="Female" {{ $pet->pet_gender=='Female' ? 'selected' : ''}}>Female</option>
                                            </select>
                                        </div>

                                        <div class="col-sm-6 form-group">
                                            <label>Pet Size (in pounds)</label>
                                            <input type="text" value="{{$pet->pet_size}}" placeholder="Pet size" name="pet_size" class="form-control">
                                        </div>

                                        <div class="col-sm-6 form-group">
                                            <label for="pet_is_friendly">Friendly With Other Pet?</label>
                                            <select name="pet_is_friendly" id="pet_is_friendly" class="form-control select2">
                                                <option value="">Friendly With Other Pet?</option>
                                                <option value="Yes" {{ $pet->pet_is_friendly=='Yes' ? 'selected' : ''}}>Yes</option>
                                                <option value="No" {{ $pet->pet_is_friendly=='No' ? 'selected' : ''}}>No</option>
                                            </select>
                                        </div>

                                        <div class="col-sm-6 form-group">
                                            <label>Pet Note</label>
                                            <input type="text" value="{{$pet->pet_note}}" placeholder="Pet note" name="pet_note" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button class="btn btn-primary">Submit</button>
                                <a href="{{route("pets.index")}}" class="btn btn-light">Cancel</a>
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
    var controller_url = "{{route('pets.index')}}";
</script>
<script src="{{asset("public/assets/modules/select2/dist/js/select2.full.min.js")}}"></script>
<script src="{{asset("public/assets/pages-js/pets/add_edit.js")}}"></script>
@endsection