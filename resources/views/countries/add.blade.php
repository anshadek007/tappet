@extends('layouts.layout')

@section('addcss')
<link href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.4.3/css/flag-icon.css" type="text/css" rel="stylesheet">
@endsection

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Add New Country</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">
                    <a href="{{route("countries.index")}}">Countries</a>
                </div>
                <div class="breadcrumb-item">Add New</div>
            </div>
        </div>
        <form class="" id="add_faq_form" novalidate="" action="{{route("countries.store")}}" enctype="multipart/form-data" method="POST" autocomplete="off">
            @csrf
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
                                <label>Country Name</label>
                                <input type="text" value="{{old("country_name")}}" placeholder="Enter country name" name="country_name" class="form-control required" required="">
                                <div class="invalid-feedback">
                                    Please enter country name
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <button class="btn btn-primary">Submit</button>
                            <a href="{{route("countries.index")}}" class="btn btn-light">Cancel</a>
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
    var controller_url = "{{route('countries.index')}}";
    var selected_user = "";
    $("#country_code").keyup(function () {
        var code = $(this).val();
        $("#show_flag").html('<i class="fa fa-flag fa-2x"></i>');
        if (code != "") {
            $("#show_flag").html('<i class="flag-icon-background flag-icon flag-icon-' + code.toLowerCase() + '"></i>');
        }
    });
</script>

<script src="{{asset("public/assets/pages-js/countries/add_edit.js")}}"></script>
<style>
    .flag-icon {
        width: 28px !important;
    }
</style>
@endsection