@extends('layouts.layout')


@section('addcss')
<link rel="stylesheet" href="{{asset("public/assets/modules/select2/dist/css/select2.min.css")}}">
@endsection
@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Add New City</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">
                    <a href="{{route("cities.index")}}">Cities</a>
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
                        <form class="" id="add_faq_form" novalidate="" action="{{route("cities.store")}}" enctype="multipart/form-data" method="POST">
                            @csrf
                            <div class="card-body">
                                <div class="col-sm-12">
                                    <div class="row">
                                        <div class="col-sm-6 form-group">
                                            <select name="city_country_id" id="city_country_id" class="form-control select2 required" required="">
                                                <option value="">Select Country</option>
                                                @foreach($all_country as $country)
                                                <option value="{{$country->c_id}}">{{$country->c_name}}</option>
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback">
                                                Please select country
                                            </div>
                                        </div>

                                        <div class="col-sm-6 form-group">
                                            <input type="text" value="{{old("title")}}" placeholder="Enter city name" name="title" class="form-control required" required="">
                                            <div class="invalid-feedback">
                                                Please enter city name
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button class="btn btn-primary">Submit</button>
                                <a href="{{route("cities.index")}}" class="btn btn-light">Cancel</a>
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
    var controller_url = "{{route('cities.index')}}";
    var selected_user = "";
</script>
<script src="{{asset("public/assets/modules/select2/dist/js/select2.full.min.js")}}"></script>
<script src="{{asset("public/assets/pages-js/cities/add_edit.js")}}"></script>
@endsection