@extends('templates.monster.main')
@push('after-styles')
<style>
    .vtabs {
        width: 100%;
    }
</style>
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/dropify/dist/css/dropify.min.css') }}">
<link rel="stylesheet" href="https://demo.worksuite.biz/plugins/bower_components/bootstrap-datepicker/bootstrap-datepicker.min.css">
<!-- summernotes CSS -->
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/summernote/dist/summernote-bs4.css') }}" rel="stylesheet" />
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css') }}" rel="stylesheet" type="text/css">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/wizard/steps.css') }}" rel="stylesheet">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/ion-rangeslider/css/ion.rangeSlider.css') }}" rel="stylesheet">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/ion-rangeslider/css/ion.rangeSlider.skinModern.css') }}" rel="stylesheet">
@endpush

@section('content')

<div class="row page-titles">
    <div class="col-md-6 col-8 align-self-center">
        <h3 class="text-themecolor mb-0 mt-0">Mitt foretak</h3>
    </div>
</div>

@include('common.errors')
@include('common.success')
<ul class="nav  customtab" role="tablist">
    <li class="">
        <a class="nav-link" data-toggle="tab" href="#tab4" role="tab">
            <span class="hidden-sm-up"> <i class="ti-home"></i></span>
            <span class="hidden-xs-down">Mitt foretak</span>
        </a>
    </li>
    <li class="">
        <a class="nav-link   " data-toggle="tab" href="#tab6" role="tab">
            <span class="hidden-sm-up"> <i class="ti-home"></i></span>
            <span class="hidden-xs-down">HMS-Erklæring</span>
        </a>
    </li>
    <li class="">
        <a class="nav-link  active " data-toggle="tab" href="#settings" role="tab">
            <span class="hidden-sm-up"> <i class="ti-home"></i></span>
            <span class="hidden-xs-down">Innstillinger</span>
        </a>
    </li>
</ul>
<div class="tab-content  ">
    <div class="tab-pane" id="tab4" role="tabpanel" style="padding: 50px;">
        <div class="row">
            <div class="col-md-6">
                <div class="card card-outline-warning">
                    <div class="card-header">
                        <h4 class="mb-0 text-white">Foretaksinformasjon</h4>
                    </div>
                    <div class="card-body">
                        <center>
                            <div class="row text-center justify-content-md-center" style="margin-top:10%;">
                                <img src="{{ asset(isset($cData->logo) ? '/file_uploader/documents/'.\Auth::user()->companyId .'/companylogo/'.$cData->logo : '') }}" class="img-reactangle" width="150">
                            </div>
                            <div style="margin-top:8%;">
                                <h4 class="card-title" style="align:center;">{{ isset($cData->company_name) ? ucfirst($cData->company_name) : ''}}</h4>
                            </div>
                            <div style="margin-top:6%;">
                                <table class=" justify-content-md-center">
                                    <tr>
                                        <th>Organisasjonsnummer : </th>
                                        <td> <span class="card-text">{{ isset($cData->vat_number) ? $cData->vat_number: ''}}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Adresse :</th>
                                        <td><span class="card-text">{{ isset($cData->address) ? $cData->address : ''}}</span></td>
                                    </tr>
                                    <tr>
                                        <th>Poststed : </th>
                                        <td><span class="card-text">{{ isset($cData->city) ? $cData->city : ''}}</span></td>
                                    </tr>
                                    <tr>
                                        <th>Postnummer : </th>
                                        <td> <span class="card-text">{{ isset($cData->zip_code) ? $cData->zip_code : ''}}</span></td>
                                    </tr>
                                </table>
                            </div>
                            <hr class="mt-5">
                        </center>
                        <a href="javascript:void(0);" data-toggle="modal" data-target="#verticalcenter" class="btn btn-inverse editContact">Endre</a>

                    </div>
                </div>
            </div>

            <div class="col-lg-5 col-md-5">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card card-outline-info">
                            <div class="card-header">
                                <h4 class="mb-0 text-white">Oversikt</h4>
                            </div>
                            <div class="card-body">
                                <center>
                                    <table>
                                        <tr>
                                            <th> Job Title :</th>
                                            <td><span class="card-text">{{ isset($cData->ceo) ? $cData->ceo : ''}}</span></td>
                                        </tr>
                                        <tr>
                                            <th> Full Time Emp :</th>
                                            <td><span class="card-text">{{ isset($cData->full_time_emp ) ? $cData->full_time_emp : ''}}</span></td>
                                        </tr>
                                        <tr>
                                            <th> Half Time Emp : </th>
                                            <td><span class="card-text">{{ isset($cData->half_time_emp ) ? $cData->half_time_emp : ''}}</span></td>
                                        </tr>
                                        <tr>
                                            <th> HSE-Cards :</th>
                                            <td> <span class="card-text">{{ isset($cData->hse_cards ) ? $cData->hse_cards : ''}}</span></td>
                                        </tr>
                                        <tr>
                                            <th> Safety Reps :</th>
                                            <td> <span class="card-text">{{ isset($cData->safety_representatives ) ? $cData->safety_representatives : ''}}</span></td>
                                        </tr>
                                    </table>
                                    <hr class="mt-2">
                                </center>
                                <a href="javascript:void(0);" data-target="#kontact" data-toggle="modal" class="btn btn-inverse">Endre Details</a>
                            </div>

                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="card card-outline-info">
                            <div class="card-header">
                                <h4 class="mb-0 text-white">Dokumenter</h4>
                            </div>
                            <div class="card-body">
                                <hr class="mt-5">
                                <a href="javascript:void(0);" class="btn btn-inverse" data-target="#document" data-toggle="modal">Opprett dokument</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane  " id="tab6" role="tabpanel">

        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="click2edit mb-5">Click on Edite button and change the text then save it.</div>
                                <button id="edit" class="btn btn-info btn-rounded" onclick="edit()" type="button">Endre</button>
                                <button id="save" class="btn btn-success btn-rounded" onclick="save()" type="button">Lagre</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="tab-pane active" id="settings" role="tabpanel">

        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="vtabs ">
                        <ul class="nav nav-tabs tabs-vertical" role="tablist">
                            <li class="nav-item"> <a class="nav-link active" data-toggle="tab" href="#departments" role="tab"><span class="hidden-sm-up"><i class="ti-home"></i></span> <span class="hidden-xs-down">Avdeling</span> </a> </li>
                            <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#jobtitle" role="tab"><span class="hidden-sm-up"><i class="ti-user"></i></span> <span class="hidden-xs-down">Jobbtittel</span></a> </li>
                            <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#reltitle" role="tab"><span class="hidden-sm-up"><i class="ti-user"></i></span> <span class="hidden-xs-down">Relasjon</span></a> </li>
                            <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#reason" role="tab"><span class="hidden-sm-up"><i class="ti-user"></i></span> <span class="hidden-xs-down">Fraværsgrunner</span></a> </li>
                            <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#dev_place" role="tab"><span class="hidden-sm-up"><i class="ti-user"></i></span> <span class="hidden-xs-down">Områder(avvik)</span></a> </li>
                            <li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#projects" role="tab"><span class="hidden-sm-up"><i class="ti-user"></i></span> <span class="hidden-xs-down">Prosjekter</span></a> </li>

                        </ul>
                        <!-- Tab panes -->
                        <div class="tab-content">
                            <div class="tab-pane active" id="departments" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <a href="JavaScript:void(0);" class="btn btn-outline btn-success btn-sm add_dep">Opprett avdeling<i class="fa fa-plus" aria-hidden="true"></i></a>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="table-responsive">
                                            <table class="table  table-hover toggle-circle default footable-loaded footable" id="users-table">
                                                <thead>
                                                    <tr>
                                                        <th>Avdeling</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                      @if($departments)
                                                        @foreach($departments as $key=>$data)
                                                        <tr>
                                                            <td><a href="JavaScript:void(0);" class="deptitle" data-id="{{ $data->id }}" data-name="{{ $data->name }}" >
                                                             {{ $data->name }}</a></td>
                                                         </tr>
                                                        @endforeach
                                                      @else
                                                    <tr>
                                                        <td colspan="3">No record found.</td>
                                                    </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!---Jpobitit-->
                            <div class="tab-pane " id="jobtitle" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <a href="JavaScript:void(0);" class="btn btn-outline btn-success btn-sm add_job">Opprett Jobbtittel <i class="fa fa-plus" aria-hidden="true"></i></a>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="table-responsive">
                                            <table class="table  table-hover toggle-circle default footable-loaded footable" id="users-table">
                                                <thead>
                                                    <tr>
                                                        <th>Jobbtittel</th>
                                                        <th>Avdeling</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                @if($companyroles)
                                                        @foreach($companyroles as $key=>$data)
                                                        <tr>
                                                            <td><a href="JavaScript:void(0);" class="jbtitle" data-id="{{ $data->id }}" data-name="{{ $data->jobtitle }}" >
                                                             {{ $data->jobtitle }}</a></td>
                                                            <td>
                                                                 @php
                                                                 $dname = \DB::table("companydepartment")->select("name")->where("id",$data->department)->first();
                                                                 @endphp
                                                            {{ $dname->name }}</td>
                                                         </tr>
                                                        @endforeach
                                                      @else
                                                    <tr>
                                                        <td colspan="3">No record found.</td>
                                                    </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!---Relation Tab-->
 <div class="tab-pane " id="reltitle" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <a href="JavaScript:void(0);" class="btn btn-outline btn-success btn-sm add_rel">Opprett relasjon<i class="fa fa-plus" aria-hidden="true"></i></a>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="table-responsive">
                                            <table class="table  table-hover toggle-circle default footable-loaded footable" id="users-table">
                                                <thead>
                                                    <tr>
                                                        <th>Relasjon Navn</th>
                                                       </tr>
                                                </thead>
                                                <tbody>
                                                @php
                                                    $users= \DB::table('dependentrelations')->get();
                                                @endphp
                                                @if($users)
                                                        @foreach($users as $key=>$data)
                                                        <tr>
                                                            <td><a href="JavaScript:void(0);" class="edit_rel" data-id="{{ $data->id }}" data-name="{{ $data->name }}" >
                                                             {{ $data->name }}</a></td>
                                                         </tr>
                                                        @endforeach
                                                      @else
                                                    <tr>
                                                        <td colspan="3">No record found.</td>
                                                    </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                               <!---Reason Tab-->
 <div class="tab-pane " id="reason" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <!-- <a href="JavaScript:void(0);" class="btn btn-outline btn-success  add_reason btn-sm">Opprett fravær <i class="fa fa-plus" aria-hidden="true"></i></a>  -->
                                                </div>
                                            </div>

                                        </div>

                                        <div class="table-responsive">
                                            <table class="table  table-hover toggle-circle default footable-loaded footable" id="users-table">
                                                <thead>
                                                    <tr>
                                                        <th>Fraværsgrunner</th>
                                                       </tr>
                                                </thead>
                                                <tbody>
                                                @php
                                                    $users= \DB::table('absence_reason')->get();
                                                @endphp
                                                @if($users)
                                                        @foreach($users as $key=>$data)
                                                        <tr>
                                                            <td><a href="JavaScript:void(0);" class="edit_reason" data-id="{{ $data->id }}" data-name="{{ $data->name }}" data-intv="{{ $data->intv}}" data-day="{{ $data->day}}" >
                                                             {{ $data->name }}</a></td>
                                                         </tr>
                                                        @endforeach
                                                      @else
                                                    <tr>
                                                        <td colspan="3">No record found.</td>
                                                    </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>


                               <!---Place Tab-->
                               <div class="tab-pane " id="dev_place" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <!-- <a href="JavaScript:void(0);" class="btn btn-outline btn-success  add_place btn-sm">Opprett fravær <i class="fa fa-plus" aria-hidden="true"></i></a>  -->
                                                </div>
                                            </div>

                                        </div>

                                        <div class="table-responsive">
                                            <table class="table  table-hover toggle-circle default footable-loaded footable" id="users-table">
                                                <thead>
                                                    <tr>
                                                        <th>Avvik</th>
                                                       </tr>
                                                </thead>
                                                <tbody>
                                                @php
                                                    $users= \DB::table('dev_place')->get();
                                                @endphp
                                                @if($users)
                                                        @foreach($users as $key=>$data)
                                                        <tr>
                                                            <td><a href="JavaScript:void(0);" class="edit_place" data-id="{{ $data->id }}" data-place_name="{{ $data->place_name }}">
                                                             {{ $data->place_name }}</a></td>
                                                         </tr>
                                                        @endforeach
                                                      @else
                                                    <tr>
                                                        <td colspan="3">No record found.</td>
                                                    </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                             <!---Project Tab-->
                             <div class="tab-pane " id="projects" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <!-- <a href="JavaScript:void(0);" class="btn btn-outline btn-success  add_place btn-sm">Opprett fravær <i class="fa fa-plus" aria-hidden="true"></i></a>  -->
                                                </div>
                                            </div>

                                        </div>

                                        <div class="table-responsive">
                                            <table class="table  table-hover toggle-circle default footable-loaded footable" id="users-table">
                                                <thead>
                                                    <tr>
                                                        <th>Prosjekter</th>
                                                       </tr>
                                                </thead>
                                                <tbody>
                                                        <tr>
                                                        </tr>
                                                    <tr>
                                                        <td colspan="3">No record found.</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>




                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
</div>


<!-- Modal to show the add place -->
<div id="place_model" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="vcenter" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="vcenter">Opprett Place</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <!----Form Data -->
                <form action="javascript:void(0);" class="place" novalidate>
                    <div class="form-group row">
                        <label class="control-label col-lg-3">Place Navn</label>
                        <div class="col-lg-9">
                            <input type="text" name="pName" id="pName" required class="form-control" value="{{ (old('pName') != '' ) ? old('pName') : '' }}">
                            <small class="invalid-feedback">Place Navn is required </small>
                        </div>
                    </div>
                    <input type="hidden" id="divid" value="">
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success waves-effect text-left" >Lagre</button>
                <button type="button" data-id="" data-table="companydepartment" class="btn btn-danger waves-effect text-left del_place">Delete</button>
            </div>
            </form>

        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>


<!-- Absence Reason modal starts -->

<div id="Absence_modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="vcenter" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="vcenter">Create Absence Reason</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <!----Form Data -->
                <form action="javascript:void(0);"  class="rsn" novalidate>
                    <div class="form-group row">
                        <label class="control-label col-lg-6">Absence Reason</label>
                        <div class="col-lg-9">
                            <input type="text" required name='rsname' id="rsname" class="form-control" value="{{ (old('rsname') != '' ) ? old('rsname') : '' }}">
                            <input type="hidden" id="rsid" value="">
                            <small class="invalid-feedback dp">Reason Required</small></small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="control-label col-lg-6">Interval</label>
                        <div class="col-lg-9">
                            <input type="number" name='intv' id="intv" class="form-control" value="{{ (old('intv') != '' ) ? old('intv') : '' }}" >
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="control-label col-lg-6">Days</label>
                        <div class="col-lg-9">
                            <input type="number" name='day' id="day" class="form-control" value="{{ (old('day') != '' ) ? old('day') : '' }}" >
                        </div>
                    </div>

                <button type="submit" class="btn btn-xs btn-success waves-effect text-left save_dep">Lagre</button>
                {{--<button type="button" data-id="" data-table="companydepartment" class="btn btn-xs btn-danger waves-effect text-left del_dep">Delete</button>--}}
            </div>
            </form>

        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>




<!--Kontatct Modal -->
<div id="verticalcenter" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="vcenter" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="vcenter">Foretaksinformasjon </h4>

                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <!----Form Data -->
                <form action="javascript:void(0);" class="needs-validation" novalidate>
                    {{ csrf_field() }}
                    <div class="row pt-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Foretaksnavn</label>
                                <input type="text" required name="company_name" class="form-control" value="{{ (old('company_name') != '' ) ? old('company_name') : (isset($cData->company_name) ? $cData->company_name : '') }}">
                                <small class="invalid-feedback"> Comapny Name is required </small>
                                <input type="hidden" name="id" value="{{ isset($cData->id) ?  $cData->id  :  '' }}" />
                            </div>
                        </div>
                        <!--/span-->
                        <div class="col-md-6">
                            <div class="form-group ">
                                <label class="control-label">Organisasjonsummer</label>
                                <input type="number" value="{{ (old('vat_no') != '' ) ? old('vat_no') : (isset($cData->vat_number) ? $cData->vat_number : '') }}" name="vat_no" required class="form-control form-control-danger">
                                <small class="invalid-feedback"> Vat-number is required </small>

                            </div>
                        </div>
                        <!--/span-->
                    </div>
                    <!--/row-->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group ">
                                <label class="control-label">Bransje</label>
                                <input type="number" name="industry" required class="form-control form-control-danger" value="{{ (old('industry') != '' ) ? old('industry') : (isset($cData->industry) ? $cData->industry : '') }}">
                                <small class="invalid-feedback"> Industry is required </small>

                            </div>
                        </div>
                        <!--/span-->
                        <!-- <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Business sector code </label>
                                <input type="number" required class="form-control" name="sector_code" value="{{ (old('sector_code') != '' ) ? old('sector_code') : (isset($cData->sector_code) ? $cData->sector_code : '') }}">
                                <small class="invalid-feedback"> Sector Code is required </small>

                            </div>
                        </div> -->
                        <!--/span-->
                    </div>
                    <!--/row-->
                    <h3 class="box-title mt-5">Adresse</h3>
                    <hr>
                    <div class="row">
                        <div class="col-md-6 ">
                            <div class="form-group">
                                <label>Adresse</label>
                                <input type="text" required name="address" class="form-control  form-control-danger" value="{{ (old('address') != '' ) ? old('address') : (isset($cData->address) ? $cData->address : '') }}">
                                <small class="invalid-feedback"> Address is required </small>

                            </div>
                        </div>
                        <div class="col-md-6 ">
                            <div class="form-group">
                                <label>Poststed</label>
                                <input type="text" required name='city' class="form-control  form-control-danger" value="{{ (old('city') != '' ) ? old('city') : (isset($cData->city) ? $cData->city : '') }}">
                                <small class="invalid-feedback"> Poststed is required </small>

                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Postnummer</label>
                                <input type="number" required name="zipcode" class="form-control  form-control-danger" value="{{ (old('zipcode') != '' ) ? old('zipcode') : (isset($cData->zip_code) ? $cData->zip_code : '') }}">
                                <small class="invalid-feedback"> zipcode is required </small>

                            </div>
                        </div>

                        <!--/span-->
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-success"> <i class="fa fa-check"></i> Save</button>
                        <button type="button" class="btn btn-inverse">Cancel</button>
                    </div>
                </form>
            </div>
            <!-- /.modal-content -->
        </div>
    </div>
</div>
<!-- Contak person modal -->
<div id="kontact" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="vcenter" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="vcenter">Additional Info</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <!----Form Data -->
                <form action="javascript:void(0);" class="cvalid" novalidate>
                    <div class="form-group row">
                        <label class="control-label col-lg-3">Job Title</label>
                        <div class="col-lg-9">
                            <input type="text" required name='ceo' class="form-control " value="{{ (old('ceo') != '' ) ? old('ceo') : (isset($cData->ceo) ? $cData->ceo : '') }}">
                            <small class="invalid-feedback">Job Title </small>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="control-label col-lg-3">Full Time Employee</label>
                        <div class="col-lg-9">
                            <input type="text" required name="full_time_emp" class="form-control " value="{{ (old('full_time_emp') != '' ) ? old('full_time_emp') : (isset($cData->full_time_emp) ? $cData->full_time_emp : '') }}">
                            <input type="hidden" name="type" value="2">
                            <small class="invalid-feedback"> Full Time Employee</small>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="control-label col-lg-3">Half Time Employee</label>
                        <div class="col-lg-9">
                            <input type="text" required name='half_time_emp' class="form-control " value="{{ (old('half_time_emp') != '' ) ? old('half_time_emp') : (isset($cData->half_time_emp) ? $cData->half_time_emp : '') }}">
                            <small class="invalid-feedback">Half Time Employee </small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="control-label col-lg-3">HSE-Cards</label>
                        <div class="col-lg-9">
                            <input type="text" required name="hse_cards" class="form-control  form-control-danger" value="{{ (old('hse_cards') != '' ) ? old('hse_cards') : (isset($cData->hse_cards) ? $cData->hse_cards : '') }}">
                            <small class="invalid-feedback">Hse-cards required </small>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="control-label col-lg-3">Safety Representatives</label>
                        <div class="col-lg-9">
                            <input type="text" required name="safety_representatives" class="form-control  form-control-danger" value="{{ (old('safety_representatives') != '' ) ? old('safety_representatives') : (isset($cData->safety_representatives) ? $cData->safety_representatives : '') }}">
                            <small class="invalid-feedback">Safety representatives is required </small>
                        </div>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success waves-effect text-left">Lagre</button>
                {{--<button type="button" class="btn btn-danger waves-effect text-left">Sellte</button>--}}
            </div>
            </form>

        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<div id="document" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="vcenter" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered " style="max-width:800px;">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="vcenter">Dokumenter</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <!----Form Data -->
                <form action="{{url('/company/logo')}}" class="clogo" method="post" enctype="multipart/form-data">
                    {{csrf_field()}}

                    <!-- New added field -->

                        <div class="row ">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label" id="navn">Filnavn</label>
                                    <input type="text" id="name" name="name" value="{{ (old('name') != '' ) ? old('name') : (isset($rData->name) ? $rData->name : '') }}" required class="form-control">
                                    <small class="invalid-feedback"> Navn is required </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group ">
                                    <label class="control-label">Kategori</label>
                                    @php
                                    $cat = \DB::table("all_category")->where("companyId",\Auth::user()->companyId)->where("table_name","documents")->get();
                                    $cate = [];

                                    if(isset($rData->category)){
                                    $cate = explode("," , $rData->category);
                                    }
                                    if(isset($tData->category_id)){
                                    $cate = explode("," , $tData->category_id);
                                    }
                                    @endphp
                                    @if($cat)
                                    <select name="category" id="cat" required class="form-control">
                                        <option value=''>Velg kategori</option>
                                        @foreach($cat as $data)
                                        <option {{ (isset($data->id) && in_array($data->id,$cate) ? 'selected' : '') }} {{ (isset($rData->category_id) && ($rData->category_id == $data->id )) ? "selected" : '' }} value="{{ $data->id }}">{{ $data->des}}</option>
                                        @endforeach
                                    </select>
                                    <small class="invalid-feedback"> Kategori required </small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Ansvarlig for fornyelse</label>
                                    <input type="text" name="responsible" class="form-control">
                                    <small class="invalid-feedback">Ansvarlig for fornyelse is required</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label"> Dato:</label>
                                    <div class="input-group">
                                        <input type="text" name='date' id="date" class=" form-control form-control-danger  ">
                                            <div class="input-group-append">
                                                <span class="input-group-text">
                                                    <span class="ti-calendar"></span>
                                                </span>
                                            </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- New added field ended -->

                    <div class="row pt-3">
                        <div class="col-md-12">
                            <div class="form-group">

                                <input type="file" name="logo" class="dropify" />
                                <input type="hidden" name="type" value="3">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success waves-effect text-left">Lagre</button>
                        {{--<button type="button" class="btn btn-danger waves-effect text-left">Sellte</button>--}}
                    </div>
                </form>

            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->

    </div>
</div>


<div id="department_modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="vcenter" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="vcenter">Create Department</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <!----Form Data -->
                <form action="javascript:void(0);"  class="deprt" novalidate>
                    <div class="form-group row">
                        <label class="control-label col-lg-6">Department Name</label>
                        <div class="col-lg-9">
                            <input type="text" required name='dprtname' id="dprtname" class="form-control " value="{{ (old('dprtname') != '' ) ? old('dprtname') : '' }}">
                            <input type="hidden" id="dprtid" value="">
                            <small class="invalid-feedback dp">Name Required</small> </small>
                        </div>
                    </div>
                <button type="submit" class="btn btn-xs btn-success waves-effect text-left save_dep">Lagre</button>
                <button type="button" data-id="" data-table="companydepartment" class="btn btn-xs btn-danger waves-effect text-left del_dep">Delete</button>
                {{--<button type="button" class="btn btn-danger waves-effect text-left">Sellte</button>--}}
            </div>
            </form>

        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>

<div id="relation_modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="vcenter" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="vcenter">Create Relation</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <!----Form Data -->
                <form action="javascript:void(0);"  class="relate" novalidate>
                    <div class="form-group row">
                        <label class="control-label col-lg-6">Relation Name</label>
                        <div class="col-lg-9">
                            <input type="text" required name='relname' id="relname" class="form-control " value="{{ (old('relname') != '' ) ? old('relname') : '' }}">
                            <input type="hidden" id="relid" value="">
                            <small class="invalid-feedback dp">Name Required</small> </small>
                        </div>
                    </div>
                <button type="submit" class="btn btn-xs btn-success waves-effect text-left save_rel">Lagre</button>
                <button type="button" data-id="" data-table="companydepartment" class="btn btn-xs btn-danger waves-effect text-left del_rel">Delete</button>
                {{--<button type="button" class="btn btn-danger waves-effect text-left">Sellte</button>--}}
            </div>
            </form>

        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>

<!-- sample modal content -->
<div id="job_modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="vcenter" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="vcenter">Create Job Title</h4>
                <button type="button" class="close" id="cls" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <!----Form Data -->

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body wizard-content ">
                                <form action='{{ url("/add/job") }}' method="post" enctype="multipart/form-data" class="jobform vertical wizard-circle">

                                        <div class="row">
                                        <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Job Title</label>
                                                    <input type="text"  required name="jobtitle" class="form-control  form-control-danger jobtitle" value="">
                                                    <small class="invalid-feedback"> Job Title required </small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Avdeling</label>

                                                    @if($departments)
                                                    <select name="department"  required class="form-control custom-select dep">
                                                        <option value="">Velg Avdeling</option>
                                                        @foreach($departments as $data)
                                                        <option value="{{ $data->id }}">{{ $data->name}}</option>
                                                        @endforeach
                                                    </select>
                                                    <small class="invalid-feedback"> Kategori a category </small>
                                                    @endif
                                                </div>
                                            </div>
                                            <input type="hidden" id="xid" name="id" value="" />
                                                {{ csrf_field() }}
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Salary</label>
                                                    <input type="number" name='salary' class="form-control  form-control-danger salary" value="{{ (old('Salary') != '' ) ? old('salary') : '' }}">
                                                    <small class="invalid-feedback"> Salary required </small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Over time</label>
                                                    <input type="text" id="range_01" name="over_time" class="form-control  form-control-danger" value="{{ (old('over_time') != '' ) ? old('over_time') :  '' }}">
                                                    <small class="invalid-feedback"> this required </small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Night shift allowance</label>
                                                    <input type="number" name='nighshift' class="form-control  form-control-danger nighshift" value="{{ (old('Salary') != '' ) ? old('salary') : '' }}">
                                                    <small class="invalid-feedback"> Salary required </small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Holiday</label>
                                                    <input type="text" id="holiday" name="holiday" class="form-control  form-control-danger" value="{{ (old('over_time') != '' ) ? old('over_time') :  '' }}">
                                                    <small class="invalid-feedback"> Holiday required </small>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-x btn-success waves-effect text-left save_dep">Lagre</button>
                <button type="button" data-id="" data-table="companydepartment" class="btn btn-x btn-danger waves-effect text-left del_roles">Delete</button>
                                    </form>
                            </div>
                        </div>  
                    </div>                  
                </div>          

                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
        <!-- /.modal -->
@push('after-scripts')

<!-- jQuery file upload -->
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/monster/js/jasny-bootstrap.js') }}"></script>

<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/dropify/dist/js/dropify.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js') }}"></script>

<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/ion-rangeslider/js/ion-rangeSlider/ion.rangeSlider.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/wizard/jquery.validate.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/wizard/jquery.steps.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/summernote/dist/summernote-bs4.min.js') }}"></script>
<script src="https://demo.worksuite.biz/plugins/bower_components/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
<script src="{{ asset('/filejs/company/docs/companyinfo.js') }}"></script>

@endpush
@endsection