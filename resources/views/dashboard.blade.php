@extends('layouts.layout')

@section('addcss')
<link rel="stylesheet" href="{{asset("public/assets/modules/datatables/datatables.min.css")}}">
<link rel="stylesheet" href="{{asset("public/assets/modules/datatables/DataTables-1.10.16/css/dataTables.bootstrap4.min.css")}}">
<link rel="stylesheet" href="{{asset("public/assets/modules/datatables/Select-1.2.4/css/select.bootstrap4.min.css")}}">
@endsection


@section('content')

<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1>Dashboard</h1>
        </div>
        <div class="row">
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-primary">
                        <i class="fas fa-user-lock"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Admins</h4>
                        </div>
                        <div class="card-body">
                            {{ $total_admins }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-danger">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Users</h4>
                        </div>
                        <div class="card-body">
                            {{ $total_users }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-warning">
                        <i class="fas fa-cubes"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Breed</h4>
                        </div>
                        <div class="card-body">
                            {{ $total_breed }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-success">
                        <i class="fas fa-snowflake"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Pet Types</h4>
                        </div>
                        <div class="card-body">
                            {{ $total_types }}
                        </div>
                    </div>
                </div>
            </div>   
            <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                <div class="card card-statistic-1">
                    <div class="card-icon bg-success">
                        <i class="fas fa-paw"></i>
                    </div>
                    <div class="card-wrap">
                        <div class="card-header">
                            <h4>Pets</h4>
                        </div>
                        <div class="card-body">
                            {{ $total_pets }}
                        </div>
                    </div>
                </div>
            </div>   
            
<!--            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <h4>Advertisement Reports</h4>
                </div>
                <div class="card-body">
                  <div class="table-responsive table-invoice">
                    <table class="table table-striped" id="datatable">
                        <thead>
                            <tr>
                                <th>Advertisement Name</th>
                                <th>Advertisement Name Arabic</th>
                                <th>Sender</th>
                                <th>Message</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                  </div>
                </div>
              </div>
            </div>-->
        </div>
    </section>
</div>

@endsection

@section('addjs')
<script src="{{asset("public/assets/modules/datatables/datatables.min.js")}}"></script>
<script src="{{asset("public/assets/modules/datatables/DataTables-1.10.16/js/dataTables.bootstrap4.min.js")}}"></script>
<script src="{{asset("public/assets/modules/datatables/Select-1.2.4/js/dataTables.select.min.js")}}"></script>
<script src="{{asset("public/assets/modules/jquery-ui/jquery-ui.min.js")}}"></script>

<script type="text/javascript">
var controller_url = "";
</script>

<script src="{{asset("public/assets/pages-js/dashboard/index.js")}}"></script>   
@endsection
