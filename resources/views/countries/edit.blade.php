@extends('layouts.layout')
@section('addcss')
<link href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.4.3/css/flag-icon.css" type="text/css" rel="stylesheet">
@endsection
@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Edit Country</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">
                    <a href="{{route("countries.index")}}">Countries</a>
                </div>
                <div class="breadcrumb-item">Edit</div>
            </div>
        </div>
        <form class="" id="edit_a_form" novalidate="" action="{{route("countries.update",$country->c_id)}}" enctype="multipart/form-data" method="POST">
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
                                <label>Country Name</label>
                                <input type="text" value="{{$country->c_name}}" placeholder="Enter country name" name="country_name" class="form-control required" required="">
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
    var code = "{{$country->c_iso_code}}";

    $(document).ready(function () {
        if (code != "") {
            $("#show_flag").html('<i class="flag-icon-background flag-icon flag-icon-' + code.toLowerCase() + '"></i>');
        }

        $("#country_code").keyup(function () {
            code = $(this).val();
            $("#show_flag").html('<i class="fa fa-flag fa-2x"></i>');
            if (code != "") {
                $("#show_flag").html('<i class="flag-icon-background flag-icon flag-icon-' + code.toLowerCase() + '"></i>');
            }
        });
    });
</script>
<script src="{{asset("public/assets/pages-js/countries/add_edit.js")}}"></script>
<style>
    .flag-icon {
        width: 28px !important;
    }
</style>
@endsection