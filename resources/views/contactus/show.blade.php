@extends('layouts.layout')

@section('content')

<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Contact Us Details</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">
                    <a href="{{route("contactus.index")}}">Contact Us</a>
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
                                <label>Title</label> : {{ $contactus->con_title }}
                            </div>
                            <div class="form-group">
                                <label>Email</label> : {{ $contactus->con_email }}
                            </div>
                            {{--<div class="form-group">
                                <label>Mobile Number</label> : {{ $contactus->con_mobile_number }}
                            </div>--}}
                            <div class="form-group">
                                <label>Message</label> : {{ $contactus->con_msg }}
                            </div>
                            <div class="form-group">
                                <label>Created Date</label> : {{ date("d/m/Y",strtotime($contactus->con_created_at)) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
</div>
@endsection