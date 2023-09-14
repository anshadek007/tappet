@extends('layouts.layout')

@section('addcss')
<link rel="stylesheet" href="{{asset("public/assets/modules/select2/dist/css/select2.min.css")}}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.0-alpha14/css/tempusdominus-bootstrap-4.min.css" />

@endsection

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Edit Event</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">
                    <a href="{{route("events.index")}}">Events</a>
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
                        <form class="" id="add_custom_form" novalidate="" action="{{route("events.update",$event->event_id)}}" enctype="multipart/form-data" method="POST">
                            @csrf
                            <input type="hidden" name="_method" value="PUT">
                            <div class="card-body">
                                <div class="col-sm-12">
                                    <div class="row">
                                        <div class="col-sm-12 form-group">
                                            <img alt="img" src="{{$event->event_image}}" class="rounded-circle mr-1" height="100" width="100">
                                        </div>

                                        <div class="col-sm-6 form-group">
                                            <label for="event_name">Event Name</label>
                                            <input type="text" value="{{ $event->event_name }}" placeholder="Enter event Name" name="event_name" class="form-control required" required="" maxlength="100" id="event_name">
                                            <div class="invalid-feedback">
                                                Please enter event name
                                            </div>
                                        </div>

                                        <div class="col-sm-6 form-group">
                                            <label for="event_description">Event Description</label>
                                            <textarea class="form-control" placeholder="description" name="description" required="">{{ $event->event_description }}</textarea>
                                            <div class="invalid-feedback">
                                                Please enter description
                                            </div>
                                        </div>

                                        <div class="col-sm-12 form-group">
                                            <label for="event_location">Event Location</label>
                                            <input type="text" value="{{ $event->event_location }}" placeholder="Enter location name" name="event_location" class="form-control required autocomplete" required="" maxlength="100" id="autocomplete">
                                            <input type="hidden" readonly="" class="form-control" name="event_latitude" value="{{ $event->event_latitude }}"/>
                                            <input type="hidden" readonly="" class="form-control" name="event_longitude" value="{{ $event->event_longitude }}"/>
                                            <div class="invalid-feedback">
                                                Please enter location name
                                            </div>
                                            <small>Please search and select valid address from <i>Google Autocomplete</i></small>
                                        </div>

                                        <div class="col-sm-6 form-group">
                                            <label for="event_start_date">Select Event Start Date Time</label>
                                            <div class="input-group date" id="event_start_date" data-target-input="nearest">
                                                <input type="text" name="event_start_date" class="form-control datetimepicker-input" data-target="#event_start_date" required="" value="{{$event->event_startdate}}"/>
                                                <div class="input-group-append" data-target="#event_start_date" data-toggle="datetimepicker">
                                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-sm-6 form-group">
                                            <label for="event_end_date">Select Event End Date Time</label>
                                            <div class="input-group date" id="event_end_date" data-target-input="nearest">
                                                <input type="text" name="event_end_date" class="form-control datetimepicker-input" data-target="#event_end_date" required="" value="{{$event->event_enddate}}"/>
                                                <div class="input-group-append" data-target="#event_end_date" data-toggle="datetimepicker">
                                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                </div>
                                            </div>
                                        </div>


                                        <div class="col-sm-6 form-group">
                                            <label for="event_participants">Event Participants</label>
                                            <select name="event_participants" id="event_participants" class="form-control select2">
                                                <option value="">Select Event Participants</option>
                                                <option value="Public" {{ $event->event_participants=='Public' ? 'selected' : ''}}>Public</option>
                                                <option value="Friends & Groups" {{ $event->event_participants=='Friends & Groups' ? 'selected' : ''}}>Friends & Groups</option>
                                            </select>
                                        </div>

                                        <div class="col-sm-6 form-group">
                                            <label for="customFile">Event Image</label>
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
<script src="https://maps.googleapis.com/maps/api/js?key={{env('GOOGLE_MAP_API_KEY')}}&libraries=places"></script>
<script src="{{asset("public/assets/modules/select2/dist/js/select2.full.min.js")}}"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.0-alpha14/js/tempusdominus-bootstrap-4.min.js"></script>
<script type="text/javascript">
var controller_url = "{{route('events.index')}}";

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
<script src="{{asset("public/assets/pages-js/events/add_edit.js")}}"></script>
@endsection
