@extends('templates.monster.main')

@push('before-styles')
<link rel="stylesheet" type="text/css" href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/datatables/media/css/dataTables.bootstrap4.css') }}">
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/daterangepicker/daterangepicker.css" rel="stylesheet">
<style>
    .dataTables_filter {
        display: none;
    }
</style>
@endpush

@push('after-scripts')
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/datatables/datatables.js') }}"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/moment/moment.js"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/daterangepicker/daterangepicker.js"></script>
<script src="{{ asset('/filejs/Project/projectview.js') }}"></script>
@endpush

@section('content')
@include('common.errors')
@include('common.success')

<div class="row page-titles">
    <div class="col-md-6 col-8 align-self-center">
        <h3 class="text-themecolor mb-0 mt-0">Project</h3>
    </div>
    <div class="col-md-6 col-4 align-self-center">
        <a href="{{ url('/add/project') }}" class="btn float-right hidden-sm-down btn-success"><i class="mdi mdi-plus-circle"></i>
        Opprett Project</a>
    </div>
</div>

<div class="card">
    <div class="card-body">

        <div class="row">
            <div class="col-md-2">
                <div class="form-group">
                    <label>Date</label>
                    <div class='input-group mb-3'>
                        <input type='text' class="form-control date_fil" />
                        <div class="input-group-append">
                            <span class="input-group-text">
                                <span class="ti-calendar"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Project Manager</label>
                    <select name="responsible" id="response" class="form-control">
                        <option selected value=''>Alle</option> 
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>status</label>
                    <select name="status" id="statid" required class="form-control">
                        <option selected value=''>Alle</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Search</label>
                    <input type="text" class="form-control" name="srch" id="srch">
                </div>
            </div>
        </div>

    </div>
</div>

<div class="card">
    <div class="card-body">
        
        <table id="project" class="table table-hover">
            <thead>
                <tr>
                    <th>Project Number</th>
                    <th>Project Name</th>
                    <th>Address</th>
                    <th>City</th>
                    <th>Zip code</th>
                    <th>Start date</th>
                    <th>Date Of Completion</th>
                    <th>Project Manager</th>
                </tr>
            </thead>
        </table>

    </div>
</div>
@endsection