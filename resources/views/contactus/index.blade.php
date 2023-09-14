@extends('layouts.layout')

@section('addcss')
<link rel="stylesheet" href="{{asset("public/assets/modules/datatables/datatables.min.css")}}">
<link rel="stylesheet" href="{{asset("public/assets/modules/datatables/DataTables-1.10.16/css/dataTables.bootstrap4.min.css")}}">
<link rel="stylesheet" href="{{asset("public/assets/modules/datatables/Select-1.2.4/css/select.bootstrap4.min.css")}}">
<link rel="stylesheet" href="{{asset("public/assets/modules/select2/dist/css/select2.min.css")}}">
@endsection

@section('content')
@php
$module_permissions = Session::get("user_access_permission");
$module_permission = !empty($module_permissions['contactus']) ? $module_permissions['contactus'] : array();
@endphp
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <div class="col-lg-12">
                @php
                $routeName = explode('.', \Request::route()->getName());
                @endphp
                <h1>{{ ucfirst($routeName[0]) }}</h1>
                
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body" style="padding: 0px">
                        <div id="accordion">
                            <div class="accordion">
                                <div class="accordion-header collapsed" role="button" data-toggle="collapse" data-target="#panel-body-1" aria-expanded="false">
                                    <h4>Filter</h4>
                                </div>
                                <div class="accordion-body collapse" id="panel-body-1" data-parent="#accordion">
                                    <div class="row">
                                        <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                            <div class="form-group">
                                                <input type="text" placeholder="Title" name="name" id="name" class="form-control">
                                            </div>
                                        </div>
                                        
                                        <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                            <div class="form-group">
                                                <input type="text" placeholder="Email" name="email" id="email" class="form-control">
                                            </div>
                                        </div>

<!--                                       <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                            <div class="form-group">
                                                <input type="text" placeholder="Mobile Number" name="mobile_number" id="mobile_number" class="form-control">
                                            </div>
                                        </div>-->
                                        
                                        <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                            <div class="form-group">
                                                <select class="form-control" id="status" name="status">
                                                    <option value="">Status (All)</option>
                                                    <option value="1">Active</option>
                                                    <option value="2">In Active</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="col-lg-12">
                                            <input type="button" name="resetfilter" value="Reset Filter" id="reset-filter" class="btn btn-light float-right reset_filter">
                                            <input type="button" name="filter" value="Filter" id="apply-filter" class="btn btn-primary float-right search_filter" style="margin-right: 15px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="datatable">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Email</th>
                                        <th>Mobileno</th>
                                        <th>Status</th>
                                        <th>Delete</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('addjs')
<script src="{{asset("public/assets/modules/datatables/datatables.min.js")}}"></script>
<script src="{{asset("public/assets/modules/datatables/DataTables-1.10.16/js/dataTables.bootstrap4.min.js")}}"></script>
<script src="{{asset("public/assets/modules/datatables/Select-1.2.4/js/dataTables.select.min.js")}}"></script>
<script src="{{asset("public/assets/modules/jquery-ui/jquery-ui.min.js")}}"></script>
<script src="{{asset("public/assets/modules/select2/dist/js/select2.full.min.js")}}"></script>

<script type="text/javascript">
var controller_url = "{{route('contactus.index')}}";
var module_permission = {!! json_encode(array_values($module_permission)) !!};
</script>

<!-- Page Specific JS File -->
<script src="{{asset("public/assets/pages-js/contactus/index.js")}}"></script>
@endsection