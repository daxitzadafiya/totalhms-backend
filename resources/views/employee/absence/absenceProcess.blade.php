@extends('templates.monster.main')

@push('before-styles')
<link rel="stylesheet" href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/dropify/dist/css/dropify.min.css">
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css" rel="stylesheet" type="text/css">
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/icheck/skins/all.css" rel="stylesheet">
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/css/select2.min.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="https://demo.worksuite.biz/plugins/bower_components/bootstrap-datepicker/bootstrap-datepicker.min.css">
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/daterangepicker/daterangepicker.css" rel="stylesheet">
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/wizard/steps.css" rel="stylesheet">
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/clockpicker/dist/jquery-clockpicker.min.css" rel="stylesheet">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/ion-rangeslider/css/ion.rangeSlider.css') }}" rel="stylesheet">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/ion-rangeslider/css/ion.rangeSlider.skinModern.css') }}" rel="stylesheet">
@endpush

@push('after-scripts')
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/dropify/dist/js/dropify.min.js"></script>-
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/clockpicker/dist/jquery-clockpicker.min.js"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/icheck/icheck.min.js"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/js/select2.full.min.js" type="text/javascript"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/icheck/icheck.init.js"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/moment/moment.js"></script>
<script src="https://demo.worksuite.biz/plugins/bower_components/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/ion-rangeslider/js/ion-rangeSlider/ion.rangeSlider.min.js') }}"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/daterangepicker/daterangepicker.js"></script>
<script src="/filejs/absence/processabsence.js"></script>

@endpush

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card card-outline-info">
            <div class="card-header">
                <h4 class="mb-0 text-white">Edit Frever</h4>
            </div>
            <div class="card-body">
        <form method="post" enctype="multipart/form-data" action="{{ url('/ansatte/absencelist') }}" >

            <div class="row">
                
            <div class="col-md-6">
                <div class="form-group">
                    <label>Ansatt:</label>
                    
                    <div class="row">
                        <div class="col-md-12 " >
                <!-- for the holidays -->
                            <div class="leave float-right" style="display:none;" >
                                <span><p class="holli"></p></span>
                            </div>
                        </div>
                    </div>
                   
                    @if($employ)
                    <select name="employee" required class="form-control employ emp_cat">
                        <option value=''>Velg Employee</option>
                    @foreach($employ as $data)
                    <option {{ isset( $dData->emp_id) && $dData->emp_id == $data->id ? "selected" : "" }}  value="{{ $data->id }}">{{ $data->name }}</option>
                    @endforeach
                    </select>
                    @endif
                </div>
            </div>

              
            
                <div class="col-md-6 ">
                    <!-- displaying the leaves
                     -->
                    <div class="row">
                        <div class="col-md-12">
                                <div class="leave_section float-right" id="section-1" style="display:none;">
                                    <span><p class="intval"></p></span>
                                </div>
                        </div>
                    </div>
          
                    <label>Fraværsgrunn:</label>
                        <div class="form-group">
                            @if($cat)
                                <select name="absence" id="drop2" required class="form-control leave_left">
                                    <option value=''>Velg Reasons</option>
                                    @foreach($cat as $data)
                                    <option {{ isset( $dData->abs_reason) && $dData->abs_reason == $data->id ? "selected" : "" }} value="{{ $data->id }}" data-id="section-{{ $data->id }}" >{{ $data->name}}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                </div>

                <div class="col-md-6 ml-auto ">
                    <div id="reason_section">
                        <!-- Radio buttons for the Sickness -->
                            <div  id="section-1" class="reason_section" disabled  required style="display:{{ $dData->abs_reason == 1 ? 'block':'none'  }};">
                                <div class= "sick_rad">
                                    <input tabindex="7" type="radio" {{ ($cData->sick_cat=='0')?'checked':'' }} class="own_rad" id="minimal-radio-2"  name="sickness" value="0">
                                    <label for="minimal-radio-2">Egen sykdom</label>
                                    <input tabindex="7" type="radio" {{ ($cData->sick_cat=='1')?'checked':'' }}  class="kid_rad" id="minimal-radio-3" name="sickness" value="1">
                                    <label for="minimal-radio-3">Sykt barn</label>
                                </div>

                                    <!-- dropdown for kids -->
                                    <div id="qbutton" style="display:{{ $cData->sick_cat ==1 ? 'block':'none' }};" disabled >
                                        <div class="form-group">
                                        @if($kids)
                                            <select name="kids" class="form-control w-25 kid">
                                                <option value=''>Velg Barn</option>
                                                @foreach($kids as $data)
                                                <option {{ isset( $cData->kid_name) && $cData->kid_name == $data->id ? "selected" : "" }}  value="{{ $data->id }}">{{ $data->dName }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                            <a href="JavaScript:void(0);" class="btn btn-outline btn-success addBarn w-25 text-light ">Opprett Barn <i class="fa fa-plus" aria-hidden="true"></i></a>
                                        </div>
                                    </div>
                                    <br>
                                </div>

                                <!-- if no show -->
                                <div id="section-5" class="reason_section row" data-toggle="buttons" disabled  style="display:{{ $dData->abs_reason == 5 ? 'block' : 'none' }};" >
                                    <div class="input-group clk col-md-6 float-left" data-placement="bottom" data-align="top" data-autoclose="true">
                                        <input type="text" class="form-control"  name="from_time" placeholder="fromtime" value="{{ (old('from_time') != '' ) ? old('from_time') : (isset($cData->from_time) ? $cData->from_time : '') }}" >
                                            <div class="input-group-append">
                                                <span class="input-group-text"><i class="far fa-clock"></i></span>
                                            </div>
                                    </div>
                                    <div class="input-group clk col-md-6 -" data-placement="bottom" data-align="top" data-autoclose="true">
                                        <input type="text" class="form-control" name="to_time" placeholder="to time" value="{{ (old('to_time') != '' ) ? old('to_time') : (isset($cData->to_time) ? $cData->to_time : '') }}" >
                                            <div class="input-group-append">
                                                <span class="input-group-text"><i class="far fa-clock"></i></span>
                                            </div>
                                    </div>
                                    <br><br>
                                </div>

                                <div id="section-2" class="reason_section " disabled style="display:{{ $dData->abs_reason == 2 ? 'block' : 'none' }};" >
                                    <div>
                                        <input type="text" name="sickrange" id="sickness_range" name="sickrange" class="form-control" value="{{ (old('sickrange') != '' ) ? old('sickrange') : (isset($cData->sickrange) ? $cData->sickrange : '' ) }}" >
                                    </div>
                                    <br>
                                </div>


                                <!-- late for work -->
                                <!-- <div id="section-6" class="reason_section" data-toggle="buttons" disabled  style="display:{{  $dData->abs_reason == 6 ? 'block' : 'none' }};" >
                                    <div class="input-group clockpicker " data-placement="bottom" data-align="top" data-autoclose="true">
                                        <input type="text" class="form-control" name="latetime" value="{{ (old('time') != '' ) ? old('time') : (isset($cData->time) ? $cData->time : '') }}">
                                            <div class="input-group-append">
                                                <span class="input-group-text"><i class="far fa-clock"></i></span>
                                            </div>
                                    </div>
                                    <br>
                                </div> -->


                                <!-- for the vacation -->
                                <div id="section-7" class="reason_section" style="display:{{ $dData->abs_reason == 7 ? 'block' : 'none' }};">
                                    <div>
                                            <input tabindex="7" type="radio" {{   ($cData->ferie != 1 ) ? 'checked' :'' }}  id="minimal-radio-9" name="ferie" value="0">
                                            <label for="minimal-radio-9">Søknad </label>
                                            <input tabindex="7" type="radio"  {{  ($cData->ferie ==1 ) ? 'checked' :'' }} id="minimal-radio-10" name="ferie" value="1">
                                            <label for="minimal-radio-10">Avtalt</label>
                                    </div>
                                    <br>
                                </div>

                                <!-- for the others -->
                                <div id="section-9" class="reason_section" disabled  style="display:{{ $dData->abs_reason == 9 ? 'block' : 'none' }};">
                                <input type="text" name="others" class="form-control" value="{{ (old('des') != '' ) ? old('des') : (isset($cData->des) ? $cData->des : '') }}">
                                </div>
                    </div>
                </div>
            </div>
                                    {{ csrf_field()}}
                    <div class="row">
                        <div class="col-md-12">
                            <label class="control-label">Select Duration</label>
                                <div class="form-group">
                                    
                                    <input type="radio" name="duration" {{ ($dData->duration=='single')?'checked':'' }} id="duration_single" checked value="single">
                                    <label for="duration_single">Single</label>
                                    
                                    <input type="radio" name="duration" {{ ($dData->duration=='multiple')?'checked':'' }} id="duration_multiple" value="multiple">
                                    <label for="duration_multiple">Multiple</label>

                                    <input type="radio" name="duration" {{ ($dData->duration=='half day')?'checked':'' }} id="duration_half_day" value="half day">
                                    <label for="duration_half_day">Half Day</label>

                                </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                                <label class="control-label">Dato:</label>
                                <div class="form-group" id="multi" style="display:{{ $dData->duration =='multiple' ? 'block':'none' }};">
                                    <div class="input-group" >
                                        <input type="text" value="{{ (old('count') != '' ) ? old('count') : (isset($dData->count) ? $dData->count : '') }}" name='emp_date' id="daterange" class=" form-control  form-control-danger  ">
                                            <div class="input-group-append">
                                                <span class="input-group-text">
                                                    <span class="ti-calendar"></span>
                                                </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group" id="single" style="display:{{ $dData->duration == 'single' ? 'block':'none' }};">
                                    <div class="input-group" >
                                        <input type="text" name='single_date' value="{{ (old('count') != '' ) ? old('count') : (isset($dData->count) ? $dData->count : '') }}" id="rangedate" class=" form-control form-control-danger  ">
                                            <div class="input-group-append">
                                                <span class="input-group-text">
                                                    <span class="ti-calendar"></span>
                                                </span>
                                            </div>
                                    </div>
                                </div>

                                <!--  -->
                    <div id="half_day" style="display:{{ $dData->duration == 'half day' ? 'block':'none' }};">
                       <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="input-group"  >
                                    <input type="text" name='half_day' value="{{ (old('count') != '' ) ? old('count') : (isset($dData->count) ? $dData->count : '') }}" id="halfdate" class=" form-control form-control-danger">
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                            <span class="ti-calendar"></span>
                                            </span>
                                        </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group col-md-4">
                        <div class="input-group clk " data-placement="bottom" data-align="top" data-autoclose="true">
                            <input type="text" class="form-control" name="from_time" value="{{ (old('from_time') != '' ) ? old('from_time') : (isset($cData->from_time) ? $cData->from_time : '') }}" placeholder="from time">
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="far fa-clock"></i></span>
                                </div>
                        </div>
                        </div>
                        <div class="form-group col-md-4">
                        <div class="input-group clk " data-placement="bottom" data-align="top" data-autoclose="true">
                            <input type="text" class="form-control" name="to_time" value="{{ (old('to_time') != '' ) ? old('to_time') : (isset($cData->to_time) ? $cData->to_time : '') }}" placeholder="to time">
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="far fa-clock"></i></span>
                                </div>
                        </div>
                        </div>
                    </div>
                    </div>  
                        </div>

                        
                        
                        <!-- status -->
                        <div class="col-md-6">
                            <div class="form-group">
                            <label>Status:</label>
                                <select name="status" id="drop3" required class="form-control">
                                    <option {{ ($dData->status=='0')?'selected':'' }} value='0'>Ny</option>
                                    <option {{ ($dData->status=='1')?'selected':'' }} value="1">Under behandling</option>
                                    <option {{ ($dData->status=='2')?'selected':'' }} value="2">Mangler Sykemelding</option>
                                    <option {{ ($dData->status=='3')?'selected':'' }} value="3" data-id="section-4">Avslått</option>
                                    <option {{ ($dData->status=='4')?'selected':'' }} value="4" >Registrert</option>
                                </select>
                            </div>
                        </div>
                    </div>


                    <div class="row">
                        <div class="col-md-12 ">
                            <label>Velg godgjørelse:</label>
                                <div class="form-group">
                                    <input tabindex="7" type="radio" {{ ($dData->type=='0')?'checked':'' }} id="minimal-radio-4"  name="type" value="0">
                                        <label for="minimal-radio-4">Betalt fravær</label>
                                    <input tabindex="7" type="radio" {{ ($dData->type=='1')?'checked':'' }} id="minimal-radio-1" name="type" value="1">
                                        <label for="minimal-radio-1">Ubetalt fravær</label>
                                </div>
                        </div>
                    </div>

                    <!-- <input type="hidden" name="status" value="0" > -->
                    <input type="hidden" name="id" value=" {{ $dData->id }}">

                  

                    <div class="row">
                        <div class="col-md-12" id="decline_reason">
                            <div class="form-group">
                                <div id="section-4" class="form-group decline_reason"  style="display:{{ $dData->status == 3 ? 'block' : 'none' }};" >
                                    <textarea name="decline_reason" class=" form-control" rows="8" placeholder="Decline reason" >{{ (old('decline_reason') != '' ) ? old('decline_reason') : (isset($cData->decline_reason) ? $cData->decline_reason : '') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
<!-- 
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <button type="button" class="btn waves-effect waves-light btn-secondary" id="med_doc">Add Document </button>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group drop_file" style="display:none;">
                                <input type="file" name="doc" class="dropify" />
                            </div>
                        </div>
                    </div> -->

                    
                    <div class="row">
                    <div class="col-md-12 ">
                        <label>Beskrivelse av sykdom:</label>
                        <div class="form-group">
                            <textarea  name="description" cols="137" class=" form-control" rows="8" >{{ (old('description') != '' ) ? old('description') : (isset($dData->description) ? $dData->description : '') }}</textarea>
                        </div>
                    </div>
                    </div>
                        <div class="row">
                        <div class="col-md-12 ">
                            <button type="submit" class="btn btn-success -"> <i class="fa fa-check"></i> Lagre</button>
                            <button type="submit" class="btn btn-danger delete_absence" data-id="{{ $dData->id }}"><i class="fa fa-trash"></i> Delete </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

<!-- Modals started -->

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
                            <input type="text" required name='rsname' id="rsname" class="form-control " value="{{ (old('rsname') != '' ) ? old('rsname') : '' }}">
                            <input type="hidden" id="rsid" value="">
                            <small class="invalid-feedback dp">Reason Required</small> </small>
                        </div>
                    </div>
                <button type="submit" class="btn btn-xs btn-success waves-effect text-left save_dep">Lagre</button>
                <button type="button" data-id="" data-table="companydepartment" class="btn btn-xs btn-danger waves-effect text-left del_dep">Delete</button>
            </div>
            </form>

        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>

<!-- Modal to show the add dependents -->
<div id="dependt" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="vcenter" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="vcenter">Opprett Barn</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <!----Form Data -->
                <form action="javascript:void(0);" class="depvalid" novalidate>
                    <div class="form-group row">
                        <label class="control-label col-lg-3">Navn</label>

                        <div class="col-lg-9">
                            <input type="text" name="dName" id="depName" value="{{ (old('dName') != '' ) ? old('dName') : '' }}" required class="form-control">
                            <small class="invalid-feedback"> Navn is required </small>
                        </div>
                    </div>
                    @if($depend)
                                <div class="form-group row">
                                  <label class="control-label col-lg-3">Relasjon</label>
                                    <div class="col-lg-9">
                                        <select name="category" id="categ" required class="form-control">
                                            @foreach($depend as $data)
                                             <option value="{{ $data->id }}">{{ $data->name}}</option>
                                            @endforeach
                                    </select>
                                    <small class="invalid-feedback"> Kategori required </small>
                                    <input type="hidden" id="dpid" value="" name="id">
                                    </div>
                                    </div>
                    @endif

                    {{ csrf_field() }}
                    <div class="form-group row">
                        <label class="control-label col-lg-3">Date Of Birth</label>
                        <div class="col-lg-9">
                            <input type="text" id="depend_date" name="dob" value="{{ (old('dob') != '' ) ? old('dob') :  '' }}" required class="form-control">
                            <small class="invalid-feedback">dob is required </small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="control-label col-lg-3">Telefonnummer</label>
                        <div class="col-lg-9">
                            <input type="number" id="depPhone" name="dPhone" value="{{ (old('dPhone') != '' ) ? old('dPhone') :  '' }}" required class="form-control">
                            <small class="invalid-feedback">Telefonnummer is required </small>
                        </div>
                    </div>
                    <input type="hidden" id="did" name="employee_id" value="{{ isset($eData->id) ? ucfirst($eData->id) : ''}}" >

            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success waves-effect text-left" data-id="{{ isset($eData->id) ? ucfirst($eData->id) : ''}}">Lagre</button>
               {{-- <button type="button" data-id="{{ isset($eData->id) ? ucfirst($eData->id) : ''}}" class="btn btn-danger waves-effect text-left delete_depends">Sellte</button>  --}}
            </div>
            </form>

        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>




@endsection
