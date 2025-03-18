@extends('templates.monster.main')
@push('after-styles')
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/summernote/dist/summernote-bs4.css') }}" rel="stylesheet" />
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css') }}" rel="stylesheet" type="text/css">
<link rel="stylesheet" href="https://bootstrap-tagsinput.github.io/bootstrap-tagsinput/dist/bootstrap-tagsinput.css">
{{--<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/daterangepicker/daterangepicker.css') }}" rel="stylesheet">
--}}
<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/themes/smoothness/jquery-ui.css" />
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/icheck/skins/all.css') }}" rel="stylesheet">

<style>
    .ui-datepicker .ui-datepicker-buttonpane button.ui-datepicker-current {
        display: none;
        float: left;
    }
    button.ui-datepicker-close.ui-state-default.ui-priority-primary.ui-corner-all {
        display: none;
    }
    .note-toolbar.card-header {
        background-color: bisque;
    }
    .dropdown-toggle {
        width: 30% !important;
    }
    .note-button {
        width: 20% !important;
    }
    .dropdown-menu.dropdown-style {
        width: 100% !important;
    }
    .note-button .btn-group {
        width: 100% !important;
    }
    #activities {
        width: 90% !important;
    }
    div#ui-id-1 {
        display: none;
    }
</style>

@endpush

@push('after-scripts')

<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/js/select2.full.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/monster/js/jasny-bootstrap.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/summernote/dist/summernote-bs4.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js') }}"></script>
<script src="https://bootstrap-tagsinput.github.io/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.js"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/moment/moment.js') }}"></script>
{{--<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js') }}"></script>
--}}
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/jquery-ui.min.js"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/icheck/icheck.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/icheck/icheck.init.js') }}"></script>
<script src="{{ asset('/filejs/company/routine/addroutine.js') }}"></script>

<script>
    $('.s').summernote('code', '{!! isset($rData->discription) ? htmlspecialchars_decode($rData->discription) : (isset($tData->des) ? htmlspecialchars_decode($tData->des) : '')  !!}');
</script>

@endpush
@section('content')

@include('common.errors')
@include('common.success')
<!-- ============================================================== -->
<!-- Start Page Content -->
<!-- ============================================================== -->
<!-- Row -->
<div class="row">
    <div class="col-lg-12">
        <div class="card card-outline-info">
            <div class="card-header">
                <h4 class="mb-0 text-white">Opprett ny rutine</h4>
            </div>
            <div class="card-body">

                <form class="needs-validation" novalidate method="POST" action="{{ url('/save/rutine') }}">
                    <div class="form-body">
                        <div class="row pt-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Navn</label>
                                    <input type="text" name="name" value="{{ (old('name') != '' ) ? old('name') : (isset($rData->name) ? $rData->name : '') }}" required class="form-control">
                                    <small class="invalid-feedback"> Rutine is required </small>
                                </div>
                            </div>
                            <!--/span-->
                            <!--/span-->
                            <div class="col-md-6">
                                <div class="form-group ">
                                    <label class="control-label">Kategori</label>
                                    @php
                                    $cat = \DB::table("all_category")->where("companyId",\Auth::user()->companyId)->where("table_name","routine")->get();
                                    $cate = [];

                                    if(isset($rData->category)){
                                    $cate = explode("," , $rData->category);
                                    }
                                    @endphp
                                    @if($cat)
                                    <select name="category" id="cat" required class="form-control">
                                        <option value=''>Velg kategori</option>
                                        @foreach($cat as $data)
                                        <option {{ (isset($data->id) && in_array($data->id,$cate) ? 'selected' : '') }} {{ (isset($rData->category_id) && ($rData->category_id == $data->id )) ? "selected" : '' }} value="{{ $data->id }}">{{ $data->des}}</option>
                                        @endforeach
                                        <option value='ad'>Ny kategori</option>
                                    </select>
                                    <small class="invalid-feedback"> Kategori required </small>
                                    @endif
                                </div>
                            </div>
                            <!-- <div class="col-md-1">
                                <div class="form-group ">
                                    <label class="control-label">Legg til kategori</label>
                                    <button type="button" data-toggle="modal" data-target="#tooltipmodals" class="btn btn-rounded btn-block btn-primary"><small>Click </small></button>
                                </div>
                            </div> -->
                            <input type="hidden" name="id" value="{{ isset($rData->id) ? ($rData->id != 0 ) ? $rData->id : '' :''}}" />
                            <input type="hidden" name="template_id" value="{{ isset($rData->template_id) ? ($rData->template_id != 0 ) ? $rData->template_id : '' :''}}" id="template_id" />
                            <!--/span-->
                        </div>

                        {{ csrf_field() }}
                        <div class="row pt-3">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label">Beskrivelse</label>
                                    <div class="s mb-5"></div>
                                    <input type="hidden" name="discription" id="des">
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--/row-->

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">


                                    <div class="row">
                                        <div class="col-sm-6 ">
                                            <div class="form-group">
                                                <label>Legg til deltakere</label>
                                                @php
                                                $user = \DB::table("users")->where("user_role","!=",1)->where('id',"!=",\Auth::user()->id)->get();
                                                $contact = \DB::table("contactperson")->join("contacts","contacts.id",'=',"contactperson.contact_id")
                                                ->where('contacts.companyId',"=",\Auth::user()->companyId)->get()->toArray();
                                                $res_cnt = [];
                                                $res_emp = [];
                                                $att_emp = [];
                                                $att_cnt = [];

                                                if(isset($rData->attending_employee)){
                                                $att_emp = explode("," , $rData->attending_employee);
                                                }
                                                if(isset($rData->attending_contact)){
                                                $att_cnt = explode("," , $rData->attending_contact);
                                                }
                                                if(isset($rData->responsible_employee)){
                                                $res_emp = explode("," , $rData->responsible_employee);
                                                }
                                                if(isset($rData->responsible_contact)){
                                                $res_cnt = explode("," , $rData->responsible_contact);
                                                }
                                                @endphp
                                                @if($user)
                                                <div class="input-group att_emp_row">

                                                    <select name="attending_employee[]" id="ae" required disabled multiple class="form-control per att_emp_sel">
                                                        <option value=''>Velg</option>
                                                        @foreach($user as $data)
                                                        <option {{ (isset($data->id) && in_array($data->id,$att_emp) ? 'selected' : '') }} value="{{ "u-".$data->id }}">{{ $data->name.'(User)'}}</option>
                                                        @endforeach
                                                        @foreach($contact as $data)
                                                        <option {{ (isset($data->c_id) && in_array($data->c_id,$att_cnt) ? 'selected' : '') }} value="{{ "c-".$data->c_id }}">{{ $data->cName.'(Contact)'}}</option>
                                                        @endforeach
                                                    </select>
                                                    <div class="input-group-append">
                                                        <button class="btn btn-success att_emp_btn" type="button"><i class="fa fa-plus"></i></button>
                                                    </div>
                                                    <small class="invalid-feedback"> Legg til deltakere </small>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">

                                        <div class="col-sm-6 ">
                                            <div class="form-group">
                                                <label>Ansvarlig</label>
                                                <div class="input-group att_emp_row">
                                                    @if($user)
                                                    <select name="responsible_person[]" required disabled id="ar" multiple class="form-control pers att_emp_sel">
                                                        <option value=''>Velg</option>
                                                        @foreach($user as $data)
                                                        <option {{ (isset($data->id) && in_array($data->id,$res_emp) ? 'selected' : '') }} value="{{ "u-".$data->id }}">{{ $data->name.'(User)'}}</option>
                                                        @endforeach
                                                        @foreach($contact as $data)
                                                        <option {{ (isset($data->c_id) && in_array($data->c_id,$res_cnt) ? 'selected' : '') }} value="{{ "c-".$data->c_id }}">{{ $data->cName.'(Contact)'}}</option>
                                                        @endforeach
                                                    </select>
                                                    <div class="input-group-append att_emp_btn">
                                                        <button class="btn btn-success" type="button" id="resEmp"><i class="fa fa-plus"></i></button>
                                                    </div>
                                                    <small class="invalid-feedback"> Select Ansvarlig ansatt </small>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row ">
                                        <div class="col-md-6">
                                        <div class="form-group">

                                            <label class="control-label">Frist</label>
                                            <div class="input-group att_emp_row">
                                                <input type="text"  disabled autocomplete="off"  name="lastDate" value="{{ (old('lastDate') != '' ) ? old('lastDate') : (isset($rData->end_date) ? $rData->end_date : '') }}" required
                                                class="form-control daterange att_emp_sel">
                                                <div class="input-group-append att_emp_btn">
                                                    <button class="btn btn-success" type="button" ><i class="fa fa-plus"></i></button>
                                                </div>
                                                <small class="invalid-feedback"> Frist is required </small>
                                            </div>
                                       </div>
                                    </div>
                                    </div>

                                    <input type="hidden" name="activity_interval" id="ai" value=0 class="form-control ">


                                </div>
                            </div>
                        </div>
                    </div>



            <div class="form-actions">
                <button type="submit" class="btn btn-success"> <i class="fa fa-check"></i> Lagre</button>
                <button type="button" class="btn btn-inverse">Avbryt</button>
                @if(isset($rData->id) && $rData->id !=0)
                <button type="button" data-toggle="tooltip" data-id="{{ isset($rData->id) ? $rData->id : '' }}" data-original-title="Delete" onclick="del(this)" class="deleteProduct btn btn-warning">Delete</button>
                @endif
            </div>
            </form>
        </div>
    </div>
</div>
</div>
<!-- sample modal content -->
<div id="tooltipmodals" class="modal fade " tabindex="-1" role="dialog" aria-labelledby="tooltipmodel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tooltipmodel">Legg til kategori</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="form-group">
                        <label for="recipient-name" class="control-label">kategori navn:</label>
                        <input type="text" class="form-control" required id="catname">
                        <span class="help"></span>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn  sv btn-danger waves-effect waves-light">Lagre endringer</button>
                <button type="button" class="btn btn-info danger-effect" data-dismiss="modal">Lukk</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->

<!-- Row -->@endsection