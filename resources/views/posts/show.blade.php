@extends('layouts.layout')

@section('content')
@php
$module_permissions = Session::get("user_access_permission");
$module_permission = !empty($module_permissions['posts']) ? $module_permissions['posts'] : array();
@endphp
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Post Details</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active">
                    <a href="{{route("dashboard")}}">Dashboard</a>
                </div>
                <div class="breadcrumb-item">
                    <a href="{{route("posts.index")}}">posts</a>
                </div>
                <div class="breadcrumb-item">Details</div>
            </div>
        </div>
        <div class="section-body">
            <div class="row">
                <div class="col-12 col-sm-12 col-md-8">
                    <div class="card author-box card-primary">
                        <div class="card-header">
                            <h5>Post Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-12">
                                    <label>Post Name : </label> <b>{{$post->post_name ? $post->post_name : " - - - "}}</b>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <label>Post Location : </label> <b>{{$post->post_location}}</b>
                                </div>
                            </div>
                            <div class="row hidden">
                                <div class="col-sm-12">
                                    <label>Post Type : </label> <b>{{$post->post_type}}</b>
                                </div>
                            </div>
                            @if(!empty($post->event) && !empty($post->event->event_id))
                            <div class="row">
                                <div class="col-sm-12">
                                    <label>Event Name : </label> <a href='{{route("events.show", $post->event->event_id)}}' class="font-weight-600"><img src='{{$post->event->event_image}} ' alt="img" width="25" height="25" class="rounded-circle mr-1"><label class="mt-1">{{$post->event->event_name}}</label></a>
                                </div>
                            </div>
                            @endif
                            <div class="row">
                                <div class="col-sm-12">
                                    <label>Created Date : </label> <b>{{ date(config('constants.DATE_ONLY_NEW'),strtotime($post->post_created_at)) }}</b>
                                </div>
                            </div>
                            <div class="float-right mt-sm-0 mt-3">
                                <a href="{{route("posts.index")}}" class="btn">Back <i class="fas fa-chevron-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-12 col-md-4" style="display: none;">
                    <div class="card author-box card-primary">
                        <div class="card-header">
                            <h5>Post Image</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-12 m-0 p-0 text-center">
                                    <img alt="img" src="{{$post->post_image}}" class="rounded-circle text-center" height="250" width="250">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-sm-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h5>Post Media</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @if(!empty($post->post_images) && $post->post_images->count() > 0)
                                
                                @foreach($post->post_images as $image)
                                
                                @if(!empty($image->post_media_type) && $image->post_media_type =='image')
                                <div class="col-md-12 col-lg-4 mt-3 text-center">
                                    <div style="border: 1px solid #d6d6d6;padding: 10px 0">
                                        <img alt="img" src="{{$image->post_image_image}}" class="rounded-circle text-center mb-4" height="150" width="150">
                                        <hr/>
                                        <a target="_blank" href="{{$image->post_image_image}}">Download</a>
                                    </div>
                                </div>
                                @elseif(!empty($image->post_media_type) && $image->post_media_type=='video')
                                <div class="col-md-12 col-lg-4 mt-3 text-center">
                                    <div style="border: 1px solid #d6d6d6;padding: 10px 0">
                                        <video width="260" height="190" controls>
                                            <source src="{{$image->post_image_image}}" type="video/mp4">
                                            Your browser does not support the video tag.
                                        </video>
                                        <hr/>
                                        <a target="_blank" href="{{$image->post_image_image}}">Download</a>
                                    </div>
                                </div>
                                @elseif(!empty($image->post_media_type) && $image->post_media_type=='audio')
                                <div class="col-md-12 col-lg-4 mt-3 text-center">
                                    <div style="border: 1px solid #d6d6d6;padding: 10px 0;">
                                        <audio controls style="overflow: hidden;width: 100%;padding: 10px;max-height: 160px;min-height: 160px;">
                                            <source src="{{$image->post_image_image}}" type="audio/mpeg">
                                            Your browser does not support the audio element.
                                        </audio>

                                        <hr/>
                                        <a target="_blank" href="{{$image->post_image_image}}">Download</a>
                                    </div>
                                </div>
                                @endif
                                
                                @endforeach
                                
                                @else
                                <div class="col-sm-12 m-0 p-0 text-center">
                                    <p>Post media not found.</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-sm-12 col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h5>Post Likes @if(!empty($post->post_likes) && $post->post_likes->count() > 0) ({{$post->post_likes->count()}}) @endif</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="datatable">
                                    <thead>
                                        <tr>
                                            <th>Post Liked By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($post->post_likes) && $post->post_likes->count() > 0)
                                        @foreach($post->post_likes as $post_member)
                                        <tr>
                                            <td><img alt="img" src="{{$post_member->user->u_image}}" class="rounded-circle mr-1" height="40" width="40"> {{$post_member->user->u_first_name}} {{$post_member->user->u_last_name}}</td>
                                        </tr>
                                        @endforeach
                                        @else
                                        <tr>
                                            <td>No one like post</td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 col-sm-12 col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h5>Post Comments @if(!empty($post->post_comments) && $post->post_comments->count() > 0) ({{$post->post_comments->count()}}) @endif</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="datatable">
                                    <thead>
                                        <tr>
                                            <th>Commented By</th>
                                            <th>Comment Text</th>
                                            <th>Delete</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(!empty($post->post_comments) && $post->post_comments->count() > 0)
                                        @foreach($post->post_comments as $post_member)
                                        <tr>
                                            <td><img alt="img" src="{{$post_member->user->u_image}}" class="rounded-circle mr-1" height="40" width="40"> {{$post_member->user->u_first_name}} {{$post_member->user->u_last_name}}</td>

                                            <td>
                                                {{$post_member->post_comment_text}}
                                            </td>
                                            <td>
                                                @if(in_array('destroy',$module_permission))
                                                <a href="{{route("posts.delete_comment", $post_member->post_comment_id)}}" class="btn btn-danger">Delete</a>
                                                @else
                                                - - - -
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                        @else
                                        <tr>
                                            <td colspan="3">No one commented on this post</td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>
@endsection
