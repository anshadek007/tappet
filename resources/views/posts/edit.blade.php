@extends('layouts.layout')

@section('addcss')
<link rel="stylesheet" href="{{asset("public/assets/modules/select2/dist/css/select2.min.css")}}">
@endsection

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Edit Post</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">
                    <a href="{{route("posts.index")}}">Posts</a>
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
                        <form class="" id="add_custom_form" novalidate="" action="{{route("posts.update",$post->post_id)}}" enctype="multipart/form-data" method="POST">
                            @csrf
                            <input type="hidden" name="_method" value="PUT">
                            <div class="card-body">
                                <div class="col-sm-12">
                                    <div class="row">

                                        <div class="col-sm-6 form-group">
                                            <label for="post_name">Post Name</label>
                                            <input type="text" value="{{ $post->post_name }}" placeholder="Enter post Name" name="post_name" class="form-control" maxlength="100" id="post_name">
                                            <div class="invalid-feedback">
                                                Please enter post name
                                            </div>
                                        </div>

                                        <div class="col-sm-12 form-group">
                                            <label for="post_location">Post Location</label>
                                            <input type="text" value="{{ $post->post_location }}" placeholder="Enter location name" name="post_location" class="form-control autocomplete" maxlength="100" id="autocomplete">
                                            <input type="hidden" readonly="" class="form-control" name="post_latitude" value="{{ $post->post_latitude }}"/>
                                            <input type="hidden" readonly="" class="form-control" name="post_longitude" value="{{ $post->post_longitude }}"/>
                                            <div class="invalid-feedback">
                                                Please enter location name
                                            </div>
                                            <small>Please search and select valid address from <i>Google Autocomplete</i></small>
                                        </div>


                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button class="btn btn-primary" type="button" id="custom_form_submit">Submit</button>
                                <a href="{{route("posts.index")}}" class="btn btn-light">Cancel</a>
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
<script src="https://maps.googleapis.com/maps/api/js?key={{env('GOOGLE_MAP_API_KEY')}}&libraries=places"></script>
<script src="{{asset("public/assets/modules/select2/dist/js/select2.full.min.js")}}"></script>
<script type="text/javascript">
var controller_url = "{{route('posts.index')}}";

function fillIn() {
    var selected_auto_id = this.inputId;
    console.log("selected_auto_id=" + selected_auto_id);
    var place = this.getPlace();
    var lat = place.geometry.location.lat(),
            lng = place.geometry.location.lng();

    if (lat != "" && lng != "") {
        $('#' + selected_auto_id).parent().find('input[name*="latitude"]:first').val(lat);
        $('#' + selected_auto_id).parent().find('input[name*="longitude"]:first').val(lng);
    }
}

function callAutoComplete() {
    var inputs = document.getElementsByClassName('autocomplete');

    var options = {
//            types: ['geocode'],
    };

    var autocompletes = [];

    for (var i = 0; i < inputs.length; i++) {
        var autocomplete = new google.maps.places.Autocomplete(inputs[i], options);
        autocomplete.inputId = inputs[i].id;
        autocomplete.setFields(['geometry']);
        autocomplete.addListener('place_changed', fillIn);
        autocompletes.push(autocomplete);
    }
}

jQuery(document).ready(function () {
    callAutoComplete();
});
</script>
<script src="{{asset("public/assets/pages-js/posts/add_edit.js")}}"></script>
@endsection
