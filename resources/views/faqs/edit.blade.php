@extends('layouts.layout')

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Edit Faq</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">
                    <a href="{{route("faqs.index")}}">Faqs</a>
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
                        <form class="" id="edit_faq_form" novalidate="" action="{{route("faqs.update",$faq->faq_id)}}" enctype="multipart/form-data" method="POST">
                            @csrf
                            <input type="hidden" name="_method" value="PUT">
                            
                            <div class="card-body">
                                <div class="col-sm-12">
                                    <div class="row">
                                        <div class="col-sm-6 form-group">
                                            <input type="text" value="{{$faq->faq_title}}" placeholder="Title" name="title" class="form-control required" required="">
                                            <div class="invalid-feedback">
                                                Please enter title
                                            </div>
                                        </div>
                                        <div class="col-sm-6 form-group">
                                            <textarea class="form-control" placeholder="description" name="description" required="">{{$faq->faq_description}}</textarea>
                                            <div class="invalid-feedback">
                                                Please enter description
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button class="btn btn-primary">Submit</button>
                                <a href="{{route("faqs.index")}}" class="btn btn-light">Cancel</a>
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
    var controller_url = "{{route('faqs.index')}}";
</script>
<script src="{{asset("public/assets/pages-js/faqs/add_edit.js")}}"></script>

@endsection