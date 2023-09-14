@extends('layouts.layout')

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Edit Breed</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">
                    <a href="{{route("pet_breeds.index")}}">Breeds</a>
                </div>
                <div class="breadcrumb-item">Edit</div>
            </div>
        </div>
        <form class="" id="custom_form" novalidate="" action="{{route("pet_breeds.update",$pet_breed->pb_id)}}" enctype="multipart/form-data" method="POST">
            @csrf
            <input type="hidden" name="_method" value="PUT">
            <div class="row">
                <div class="col-lg-6 col-md-12 col-sm-12">
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

                            <div class="form-group">
                                <label>Breed Name</label>
                                <input type="text" value="{{$pet_breed->pb_name}}" placeholder="Enter breed name" name="breed_name" class="form-control required" required="">
                                <div class="invalid-feedback">
                                    Please enter breed name
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <button class="btn btn-primary">Submit</button>
                            <a href="{{route("pet_breeds.index")}}" class="btn btn-light">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</div>
@endsection

@section('addjs')
<script type="text/javascript">
    var controller_url = "{{route('pet_breeds.index')}}";
</script>
<script src="{{asset("public/assets/pages-js/pet_breeds/add_edit.js")}}"></script>
@endsection