@extends('layouts.layout')

@section('content')

<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>About us Details</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">
                    <a href="{{route("aboutus.index")}}">About us</a>
                </div>
                <div class="breadcrumb-item">Details</div>
            </div>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-sm-12 col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <div class="form-group">
                                <label>Title</label> : {{ $aboutus->a_title }}
                            </div>
                            <div class="form-group">
                                <label>Description</label> : {{ $aboutus->a_description }}
                            </div>
                            <div class="form-group">
                                <label>Created Date</label> : {{ date("d/m/Y",strtotime($aboutus->a_created_at)) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>    
</div>
@endsection