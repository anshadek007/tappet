@extends('layouts.layout')

@section('addcss')
<link rel="stylesheet" href="{{asset("public/assets/modules/select2/dist/css/select2.min.css")}}">
@endsection

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Settings</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">
                    <a href="{{route("settings.index")}}">Settings</a>
                </div>
                <div class="breadcrumb-item">Manage</div>
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

                        <form class="" id="add_custom_form" novalidate="" action="{{route("settings.store")}}" enctype="multipart/form-data" method="POST">
                            @csrf
                            <div class="card-body">
                                <div class="col-sm-12">
                                    @foreach ($settings as $setting)
                                    <div class="row">
                                        <div class="col-sm-6 form-group">
                                            <label for="{{$setting->s_name}}">{{$setting->s_key}}</label>
                                            @if($setting->s_type==1)
                                            <input type="text" value="{{$setting->s_value}}" placeholder="{{$setting->s_key}}" name="{{$setting->s_name."_".$setting->s_type}}" class="form-control required" id="{{$setting->s_name}}">
                                            @elseif($setting->s_type==2)
                                            <textarea placeholder="{{$setting->s_key}}" name="{{$setting->s_name."_".$setting->s_type}}" class="form-control required" id="{{$setting->s_name}}">{{$setting->s_value}}</textarea>
                                            @elseif($setting->s_type==3)
                                            <select name="{{$setting->s_name."_".$setting->s_type}}" id="{{$setting->s_name}}" class="form-control select2 required">
                                                <option value="">{{$setting->s_key}}</option>
                                                @php
                                                $get_options = json_decode($setting->s_extra,true);
                                                @endphp
                                                @foreach($get_options as $get_key=>$get_opt)
                                                <option value="{{$get_key}}" @if($get_key==$setting->s_value) selected @endif>{{$get_opt}}</option>
                                                @endforeach
                                            </select>
                                            @elseif($setting->s_type==4)
                                            @php
                                            $get_options = json_decode($setting->s_extra,true);
                                            $selected_vals = explode(',',$setting->s_value);
                                            @endphp
                                            @foreach($get_options as $get_key=>$get_opt)
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" value="{{$get_key}}" @if(in_array($get_key,$selected_vals)) checked @endif name="{{$setting->s_name."_".$setting->s_type}}[]" class="custom-control-input" id="{{$setting->s_name.$get_key}}">
                                                       <label class="custom-control-label" for="{{$setting->s_name.$get_key}}">{{$get_opt}}</label>
                                            </div>
                                            @endforeach
                                            @elseif($setting->s_type==5)
                                            @php
                                            $get_options = json_decode($setting->s_extra,true);

                                            @endphp
                                            @foreach($get_options as $get_key=>$get_opt)
                                            <div class="custom-control custom-radio">
                                                <input type="radio" value="{{$get_key}}" @if($get_key==$setting->s_value) checked @endif name="{{$setting->s_name."_".$setting->s_type}}" class="custom-control-input" id="{{$setting->s_name.$get_key}}">
                                                       <label class="custom-control-label" for="{{$setting->s_name.$get_key}}">{{$get_opt}}</label>
                                            </div>
                                            @endforeach
                                            @elseif($setting->s_type==6)
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input form-control" name="{{$setting->s_name."_".$setting->s_type}}" id="{{$setting->s_name}}" accept="image/*" alt="Image">
                                                <label class="custom-file-label" for="{{$setting->s_name}}">{{$setting->s_key}}</label>
                                            </div>
                                            @elseif($setting->s_type==7)
                                            <input type="number" value="{{$setting->s_value}}" placeholder="{{$setting->s_key}}" name="{{$setting->s_name."_".$setting->s_type}}" class="form-control required" id="{{$setting->s_name}}">
                                            @elseif($setting->s_type==8)
                                            
                                            @else
                                            <input type="text" value="{{$setting->s_value}}" placeholder="{{$setting->s_key}}" name="{{$setting->s_name."_".$setting->s_type}}" class="form-control required" id="{{$setting->s_name}}">
                                            @endif
                                        </div>
                                    </div>

                                    @endforeach
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button class="btn btn-primary" type="button" id="custom_form_submit">Submit</button>
                                <a href="{{route("dashboard")}}" class="btn btn-light">Cancel</a>
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
    var controller_url = "{{route('settings.index')}}";
</script>
<script src="{{asset("public/assets/modules/select2/dist/js/select2.full.min.js")}}"></script>
<script src="{{asset("public/assets/pages-js/settings/add_edit.js")}}"></script>
@endsection