@extends('layouts.layout')

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Category Details</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">
                    <a href="{{route("pet_types.index")}}">Pet Types</a>
                </div>
                <div class="breadcrumb-item">Details</div>
            </div>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-sm-12 col-lg-12">
                    <div class="card author-box card-primary">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-12">
                                    <label>Category Name : </label> <b>{{$category->c_name}}</b>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <label>Category Color : </label> <b>{{$category->c_color}}</b>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <label>Is Eco Category: </label> <b>@if($category->c_is_eco==1) Yes @else No @endif</b>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <label>Category Image (With Background) : </label>
                                    <img alt="img" src="{{getPhotoURL('pet_types',$category->c_id,$category->c_image)}}" class="rounded-circle mr-1" height="100" width="100">
                                </div>
                            </div>
                            <br/>
                            <div class="row">
                                <div class="col-sm-12">
                                    <label>Category Image (Transparent Image) : </label>
                                    <img alt="img" src="{{getPhotoURL('pet_types',$category->c_id,$category->c_trans_image)}}" class="rounded-circle mr-1" height="100" width="100" style="background-color:#888;border-radius: 50%;">
                                </div>
                            </div>

                            <div class="float-right mt-sm-0 mt-3">
                                <a href="{{route("pet_types.index")}}" class="btn">Back <i class="fas fa-chevron-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection