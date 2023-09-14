@extends('layouts.layout')

@section('addcss')
<link rel="stylesheet" href="{{asset("public/assets/modules/select2/dist/css/select2.min.css")}}">

@endsection

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Edit User</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">
                    <a href="{{route("users.index")}}">Users</a>
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
                        <form class="" id="edit_user_form" novalidate="" action="{{route("users.update",$user->u_id)}}" enctype="multipart/form-data" method="POST">
                            @csrf
                            <input type="hidden" name="_method" value="PUT">
                            <div class="card-body">
                                <div class="col-sm-12">
                                    <div class="row">
                                        <div class="col-sm-4 form-group">
                                            <img alt="img" src="{{$user->u_image}}" class="rounded-circle mr-1" height="100" width="100">
                                        </div>
                                        <div class="col-sm-8 form-group">
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input form-control" name="user_image" id="customFile" accept="image/*" alt="Image">
                                                <label class="custom-file-label" for="customFile">Choose file</label>
                                                <div class="invalid-feedback">
                                                    Only image file accepted and less then 5MB
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 form-group">
                                            <label>First Name</label>
                                            <input type="text" value="{{$user->u_first_name}}" placeholder="First name" name="first_name" class="form-control" required="">
                                            <div class="invalid-feedback">
                                                Please enter first name
                                            </div>
                                        </div>
                                        <div class="col-sm-6 form-group">
                                            <label>Last Name</label>
                                            <input type="text" value="{{$user->u_last_name}}" placeholder="Last name" name="last_name" class="form-control" required="">
                                            <div class="invalid-feedback">
                                                Please enter last name
                                            </div>
                                        </div>
                                        <div class="col-sm-6 form-group">
                                            <label>Email</label>
                                            <input type="email" value="{{$user->u_email}}" placeholder="Email" name="email" class="form-control" required="">
                                            <div class="invalid-feedback">
                                                Please enter valid email address
                                            </div>
                                        </div>
                                        <div class="col-sm-6 form-group">
                                            <label>Mobile Number</label>
                                            <input type="text" value="{{$user->u_mobile_number}}" placeholder="Mobile number" name="u_mobile_number" class="form-control" required="">
                                            <div class="invalid-feedback">
                                                Please enter mobile number
                                            </div>
                                        </div>
                                        
                                        <div class="col-sm-6 form-group">
                                            <label>Date Of Birth</label>
                                            <input type="date" value="{{$user->u_dob}}" placeholder="Date Of Birth" name="u_dob" class="form-control">
                                        </div>

                                        <div class="col-sm-6 form-group">
                                            <label for="gender">Select Gender</label>
                                            <select name="u_gender" id="gender" class="form-control select2">
                                                <option value="">Select Gender</option>
                                                <option value="Male" {{ $user->u_gender=='Male' ? 'selected' : ''}}>Male</option>
                                                <option value="Female" {{ $user->u_gender=='Female' ? 'selected' : ''}}>Female</option>
                                                <option value="Other" {{ $user->u_gender=='Other' ? 'selected' : ''}}>Other</option>
                                                <option value="Prefer not to say" {{ $user->u_gender=='Prefer not to say' ? 'selected' : ''}}>Prefer not to say</option>
                                            </select>
                                        </div>



                                        <div class="col-sm-6 form-group">
                                            <label>Password</label>
                                            <input type="password" id="password" placeholder="Password" name="password" class="form-control">
                                            <div class="invalid-feedback">
                                                Please enter password
                                            </div>
                                        </div>
                                        <div class="col-sm-6 form-group">
                                            <label>Confirm Password</label>
                                            <input type="password" name="c_password" placeholder="Confirm Password" class="form-control">
                                            <div class="invalid-feedback">
                                                Passwords do not match
                                            </div>
                                        </div>

                                        <div class="col-sm-6 form-group">
                                            <label>Address</label>
                                            <input type="text" value="{{$user->u_address}}" placeholder="Address" name="u_address" class="form-control">
                                        </div>

                                        <div class="col-sm-6 form-group">
                                            <label>Zip Code</label>
                                            <input type="text" value="{{$user->u_zipcode}}" placeholder="Zip code" name="u_zipcode" class="form-control">
                                        </div>

                                        <div class="col-sm-6 form-group">
                                            <label for="country_id">Select Country</label>
                                            <select name="u_country" id="country_id" class="form-control select2">
                                                <option value="">Select Country</option>
                                                @foreach($all_country as $country)
                                                @if($user->u_country==$country->c_id)
                                                <option value="{{$country->c_id}}" selected="selected">{{$country->c_name}}</option>
                                                @else
                                                <option value="{{$country->c_id}}">{{$country->c_name}}</option>
                                                @endif
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback">
                                                Please select country
                                            </div>
                                        </div>

                                        <div class="col-sm-6 form-group">
                                            <label for="city_id">Select City</label>
                                            <select name="u_city" id="city_id" class="form-control select2">
                                                <option value="">Select City</option>
                                                @foreach($all_cities as $city)
                                                @if($user->u_city==$city->id)
                                                <option value="{{$city->id}}" selected="selected">{{$city->name}}</option>
                                                @else
                                                <option value="{{$city->id}}">{{$city->name}}</option>
                                                @endif
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback">
                                                Please select city
                                            </div>
                                        </div>

                                        <!--                                        <div class="col-sm-6 form-group">
                                                                                    <label for="searchTextField">Location</label>
                                                                                    <input id="searchTextField" aria-describedby="searchTextField" class="form-control" name="advertisement_location" type="text" size="50" placeholder="Enter a location" autocomplete="on" runat="server" value="{{ $user->u_location }}"/>
                                                                                    <input type="hidden" id="cityLat" name="u_latitude"  value="{{ $user->u_latitude }}"/>
                                                                                    <input type="hidden" id="cityLng" name="u_longitude"  value="{{ $user->u_longitude }}"/>
                                        
                                                                                    <small id="searchTextField" class="form-text text-muted">
                                                                                        Add location in above field and select place from list of results.
                                                                                    </small>
                                                                                    <div class="invalid-feedback">
                                                                                        Please enter valid location
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-sm-6 form-group"></div>
                                                                                <div class="col-sm-6 form-group">
                                                                                    <label for="cityState">State</label>
                                                                                    <input type="text" id="cityState" name="u_state"  value="{{ $user->u_state }}" readonly="readonly" class="form-control"/>
                                                                                </div>
                                                                                <div class="col-sm-6 form-group">
                                                                                    <label for="city2">City</label>
                                                                                    <input type="text" id="city2" name="u_city"  value="{{ $user->u_city }}" readonly="readonly"  class="form-control"/>
                                                                                </div>-->

                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button class="btn btn-primary">Submit</button>
                                <a href="{{route("users.index")}}" class="btn btn-light">Cancel</a>
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
    var controller_url = "{{route('users.index')}}";
    var get_city_list = "{{route('get_city_list')}}";
</script>
<script src="{{asset("public/assets/modules/select2/dist/js/select2.full.min.js")}}"></script>
<script src="{{asset("public/assets/pages-js/users/add_edit.js")}}"></script>
<!--<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCXjNiBAPE85qtu9R4E1avqGT3YcvmYtmM&libraries=places"></script>-->

<script>
//    function initialize() {
//        var input = document.getElementById('searchTextField');
//        var autocomplete = new google.maps.places.Autocomplete(input);
//        google.maps.event.addListener(autocomplete, 'place_changed', function () {
//            var city = postal_code = state = '';
//            var place = autocomplete.getPlace();
//
//            //find state name
//            for (var i = 0; i < place.address_components.length; i++) {
//                for (var b = 0; b < place.address_components[i].types.length; b++) {
//                    if (place.address_components[i].types[b] == "administrative_area_level_1") {
//                        var state = place.address_components[i].long_name;
//                        break;
//                    }
//                }
//            }
//
//            //find city name
//            for (var i = 0; i < place.address_components.length; i++) {
//                for (var b = 0; b < place.address_components[i].types.length; b++) {
//                    if (place.address_components[i].types[b] == "administrative_area_level_2") {
//                        var city = place.address_components[i].long_name;
//                        break;
//                    }
//                }
//            }
//
//            //find postal_code
//            for (var i = 0; i < place.address_components.length; i++) {
//                for (var b = 0; b < place.address_components[i].types.length; b++) {
//                    if (place.address_components[i].types[b] == "postal_code") {
//                        var postal_code = place.address_components[i].long_name;
//                        break;
//                    }
//                }
//            }
//
////            var add = place.formatted_address;
////            var value = add.split(",");
////            
////            var count = value.length;
////            var country = value[count - 1];
////            var state = value[count - 2];
////            var city = value[count - 3];
//
//            document.getElementById('cityState').value = state;
//            document.getElementById('city2').value = city;
//            document.getElementById('cityLat').value = place.geometry.location.lat();
//            document.getElementById('cityLng').value = place.geometry.location.lng();
//
//        });
//    }
//    google.maps.event.addDomListener(window, 'load', initialize);
</script>
@endsection