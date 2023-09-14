@extends('layouts.layout')

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Add New About us</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">
                    <a href="{{route("aboutus.index")}}">About us</a>
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
                        <form class="" id="add_faq_form" novalidate="" action="{{route("aboutus.store")}}" enctype="multipart/form-data" method="POST">
                            @csrf
                            <div class="card-body">
                                <div class="col-sm-12">
                                    <div class="row">
                                        <div class="col-sm-6 form-group">
                                            <input type="text" value="{{old("title")}}" placeholder="Title" name="title" class="form-control required" required="">
                                            <div class="invalid-feedback">
                                                Please enter title
                                            </div>
                                        </div>
                                        <div class="col-sm-6 form-group">
                                            <textarea class="form-control" placeholder="description" name="description" required="">{{old("description")}}</textarea>
                                            <div class="invalid-feedback">
                                                Please enter description
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button class="btn btn-primary">Submit</button>
                                <a href="{{route("aboutus.index")}}" class="btn btn-light">Cancel</a>
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
    var controller_url = "{{route('aboutus.index')}}";
    var selected_user = "";
</script>
<script src="{{asset("public/assets/pages-js/aboutus/add_edit.js")}}"></script>
@endsection