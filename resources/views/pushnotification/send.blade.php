@extends('layouts.layout')

@section('addcss')
<link rel="stylesheet" href="{{asset("public/assets/modules/select2/dist/css/select2.min.css")}}">
<link rel="stylesheet" href="{{asset("public/assets/modules/bootstrap-daterangepicker/daterangepicker.css")}}">
<link rel="stylesheet" href="{{asset("public/assets/modules/dual-listbox/bootstrap-duallistbox.css")}}">
@endsection

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Send Push Notification</h1>
            <div class="section-header-breadcrumb">
                
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
                        <form class="" id="add_pushnotification_form" novalidate="" action="{{route("pushnotification.send")}}" enctype="multipart/form-data" method="POST">
                            @csrf
                            <div class="card-body">
                                <div class="col-sm-12">
                                    <div class="row">
                                        <div class="col-sm-12 form-group">
                                            <label>Push Content</label>
                                            <textarea name="push_content"  id="push_content" class="form-control required"></textarea>
                                            <div class="invalid-feedback">
                                                Please enter push content
                                            </div>
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>Target</label>
                                            <select class="form-control" name="push_target" id="push_target">
                                                <option value="1">All (Android / iOS)</option>
                                                <option value="2">Android</option>
                                                <option value="3">iOS</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button class="btn btn-primary">Submit</button>
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
<script src="{{asset("public/assets/pages-js/pushnotification/add_edit.js")}}"></script>
@endsection