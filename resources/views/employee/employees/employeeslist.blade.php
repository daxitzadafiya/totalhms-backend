@extends('templates.monster.main')

@push('before-styles')
<style>
    .clickbody {
        position: absolute;
    }

    a.clickview {
        position: absolute;
        /* background: red; */
        width: 100%;
        height: 100%;
        z-index: 111;
    }

    div#myTable_filter {
        display: none;
    }

    .view-group {
    display: -ms-flexbox;
    display: flex;
    -ms-flex-direction: row;
    flex-direction: row;
    padding-left: 0;
    margin-bottom: 0;
}

td.sorting_1 {
    width: 300px;
}
</style>
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css') }}" rel="stylesheet" type="text/css">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/icheck/skins/all.css') }}" rel="stylesheet">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/daterangepicker/daterangepicker.css') }}" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/dropify/dist/css/dropify.min.css') }}">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/wizard/steps.css') }}" rel="stylesheet">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/ion-rangeslider/css/ion.rangeSlider.css') }}" rel="stylesheet">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/ion-rangeslider/css/ion.rangeSlider.skinModern.css') }}" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/datatables/media/css/dataTables.bootstrap4.css') }}">
@endpush

@push('after-scripts')
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/ion-rangeslider/js/ion-rangeSlider/ion.rangeSlider.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/icheck/icheck.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/icheck/icheck.init.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/dropify/dist/js/dropify.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/wizard/jquery.validate.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/wizard/jquery.steps.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/moment/moment.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/daterangepicker/daterangepicker.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/datatables/datatables.js') }}"></script>
<script src="{{ asset('/filejs/employee/employeelist.js') }}"></script>
<!-- end - This is for export functionality only -->
@endpush

@section('content')

@include('common.errors')
@include('common.success')
<div class="row page-titles">
    <div class="col-md-6 col-8 align-self-center">
        <h3 class="text-themecolor mb-0 mt-0">Ansatte</h3>
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">Oversikt over foretakets ansatte</li>
        </ol>
    </div>
    <div class="col-md-6 col-4 align-self-center">
        {{--<button class="right-side-toggle waves-effect waves-light btn-info btn-circle btn-sm float-right ml-2"><i class="ti-settings text-white"></i></button>
       <a href="{{ url('foretak/kontakt/ny')}}" class="btn float-right hidden-sm-down btn-success btn-lg"><i class="mdi mdi-plus-circle"></i>Legg til ny kontakt</a>
        --}}
        <a data-toggle="modal" data-target="#verticalcenter" class="btn float-right hidden-sm-down btn-success "><i class="mdi mdi-plus-circle "></i>Legg til ny Ansatte</a>
                
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Jobbtittel</label>
                            <select name="jobtitle" id="jobid" required class="form-control">
                                <option selected value=''>Alle</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Avdeling</label>
                            <select name="depart" id="depart" required class="form-control">
                                <option selected value=''>Alle</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Søk</label>
                            <input type="text" class="form-control" name="srch" id="employsrch">
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="btn-group float-right">
            <button class="btn btn-info " id="list"><i class="fas fa-list-ul"></i>
                List View
            </button>
            <button class="btn btn-danger " id="grid"><i class="fas fa-th-large"></i>
                Grid View
            </button>
        </div>
    </div>
</div>
<div class="row view-group" id="gridview">
    <!-- .col -->

    @if($eData)
    @foreach($eData as $data)
    <div class="col-md-6 col-lg-6 col-xlg-4">
        <div class="card card-body clickbody">
            <a href="{{ url('/ansatte/employee/'.$data->id) }}" class="clickview"></a>
            <div class="row">
                @php
                if ($data->profile_image) {
                $path = '/file_uploader/company/'.$data->companyId.'/employee/profile/'. $data->id .'/'. $data->profile_image ;
                }else{
                $path = "/vendor/wrappixel/monster-admin/4.2.1/assets/images/users/1.jpg";
                }
                @endphp
                <div class="col-md-4 col-lg-3 text-center">
                    <a href="app-contact-detail.html"><img src=" {{ asset($path) }}" alt="user" class="img-circle img-responsive"></a>
                </div>

                <div class="col-md-8 col-lg-9">
                    <h4 class="mb-0">{{ ucfirst($data->name )}}</h4>
                    <small> {{ \Helper::get_job_title($data->user_role) }}</small>
                    <address>
                        {{ $data->address }}
                        {{ $data->city }} , {{ $data->zipcode }}<br>
                        <abbr title="Telefonenummer">*</abbr>{{ $data->phone }}
                    </address>
                </div>
                <div class="col-md-6">
                    <small>Ansatt siden: {{ $data->employed_since }}</small><br>
                    <small>Antall vedlegg: 9</small>
                </div>
                @php
                $depen = \DB::table("dependants")->where("employee_id",$data->id)->first();
                @endphp
                @if($depen)
                <div class="col-md-6">
                    <small> Pårørende : {{ $depen->dName }}</small><br>
                    <small>{{ $depen->dPhone }}</small>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
    @endif
</div>
 
<div class="card" id="listview" style="display:none;">
    <div class="card-body">
        <table id="employee" class="table table-hover" >
            <thead>
                <tr>
                    <th>Navn</th>
                    <th>Telefonnummer</th>
                    <th>Jobbtittel</th>
                    <th>Avdeling</th>
                    <th>Ansatt dato</th>
                    <th>Antall vedlegg</th>
                </tr>
            </thead>
            <tbody>
            @if($eData)
                    @foreach($eData as $data)
                <tr>
                    <td><a href="{{ url('/ansatte/employee/'.$data->id) }}"><img src=" {{ asset($path) }}" alt="user" class="img-circle img-responsive" style="width:75px; height:75px;"></a><a href="{{ url('/ansatte/employee/'.$data->id) }}" class="clickview">{{ ucfirst($data->name )}}</a></td>
                    <td>{{ $data->phone }}</td>
                    <td>{{ \Helper::get_job_title($data->user_role) }}</td>
                    <td>{{ \Helper::get_department($data->departments) }}</td>
                    <td>{{ $data->employed_since }}</td>
                    <td>{{ $data->attachment_count }}</td>
                </tr>
                    @endforeach
            @endif
            </tbody>
        </table>
    </div>
</div>


<!-- sample modal content -->
<div id="verticalcenter" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="vcenter" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="vcenter">Add Contact</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <!----Form Data -->

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body wizard-content ">
                                <form action='{{ url("/ansatte/add/employee") }}' method="post" enctype="multipart/form-data" class="tab-wizard vertical wizard-circle">
                                    <!-- Step 1 -->
                                    <h6>Personal Info</h6>
                                    <section>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Navn</label>
                                                    <input type="text" name="name" value="{{ (old('name') != '' ) ? old('name') : (isset($uData->name) ? $uData->name : '') }}" required class="form-control">
                                                    <small class="invalid-feedback"> Name is required </small>
                                                </div>
                                            </div>
                                            <!--/span-->
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">E-post</label>
                                                    <input type="email" required name='epost' class="form-control  form-control-danger" value="{{ (old('epost') != '' ) ? old('epost') : (isset($uData->epost) ? $uData->epost : '') }}">
                                                    <small class="invalid-feedback"> E-post is required </small>
                                                </div>
                                            </div>
                                            <!--/span-->
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Adresse</label>
                                                    <input type="text" required name="address" class="form-control  form-control-danger" value="{{ (old('address') != '' ) ? old('address') : (isset($uData->address) ? $uData->address : '') }}">
                                                    <small class="invalid-feedback"> Adresse required </small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Poststed</label>
                                                    <input type="text" required name='city' class="form-control  form-control-danger" value="{{ (old('city') != '' ) ? old('city') : (isset($uData->city) ? $uData->city : '') }}">
                                                    <small class="invalid-feedback"> Poststed required </small>
                                                    <input type="hidden" name="id" value="{{ isset($iData->id) ? ($iData->id != 0 ) ? $iData->id : '' :''}}" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Telefonnummer</label>
                                                    <input type="number" required name="phone" class="form-control  form-control-danger" value="{{ (old('phone') != '' ) ? old('phone') : (isset($uData->phone) ? $uData->phone : '') }}">
                                                    <small class="invalid-feedback"> Telefonnummer is required </small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Postnummer</label>
                                                    <input type="number" required name="zipcode" class="form-control  form-control-danger" value="{{ (old('zipcode') != '' ) ? old('zipcode') : (isset($uData->zip_code) ? $uData->zip_code : '') }}">
                                                    <small class="invalid-feedback"> Postnummer is required </small>
                                                </div>
                                            </div>
                                        </div>
                                        @php
                                        $cid = \Auth::User()->companyId;
                                        $departments = \DB::table('companydepartment')->where('companyId', $cid)->get();
                                        $companyroles = \DB::table('companyroles')->where('companyId', $cid)->get();
                                        @endphp
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Avdeling</label>
                                                    @if($departments)
                                                    <select name="department" class="form-control custom-select depart" >
                                                        <option value="">Velg kategori</option>
                                                        @foreach($departments as $data)
                                                        <option value="{{ $data->id }}">{{ $data->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <small class="invalid-feedback"> Kategori required </small>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Job Title</label>
                                                    <select name="role" required  disabled class="form-control custom-select roles">
                                                    <option value="0" >Select Rolle</option>
                                                    </select>
                                                    <small class="invalid-feedback"> Rolle required </small>
                                                </div>
                                            </div>
                                        </div>
                                    </section>

                                    <h6>Additional Info</h6>

                                    <section>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Personnummer</label>
                                                    <input type="number" name="personal_no" class="form-control  form-control-danger" value="{{ (old('personal_no') != '' ) ? old('personal_no') :  '' }}">
                                                    <small class="invalid-feedback"> Navn required </small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    @if($eData)
                                                    <label class="control-label">Nearest Manager</label>
                                                    <select name="near_manager" class="form-control custom-sel-ect" >
                                                        <option value="">Velg kategori</option>
                                                        @foreach($eData as $data)
                                                        <option value="{{ $data->id }}">{{ $data->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <small class="invalid-feedback"> Near manager required </small>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Employment Date</label>
                                                    <input type="text" name='emp_date' id="emp_date" class=" form-control  form-control-danger" value="{{ (old('emp_date') != '' ) ? old('emp_date') : '' }}">
                                                    <small class="invalid-feedback"> Phone required </small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Employment Percentage</label>
                                                    <input type="number" name="emp_percentage" class="form-control  form-control-danger" value="{{ (old('emp_percentage') != '' ) ? old('emp_percentage') :  '' }}">
                                                    <small class="invalid-feedback"> this required </small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Salary</label>
                                                    <input type="number" id="salary" name='salary' class="form-control  form-control-danger" value="{{ (old('Salary') != '' ) ? old('salary') : '' }}">
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
                                                    <input type="number" id="nightshift" name='nighshift' class="form-control  form-control-danger" value="{{ (old('nightshift') != '' ) ? old('nightshift') : '' }}">
                                                    <small class="invalid-feedback"> Salary required </small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Tax area</label>
                                                    <input type="number" name="tax_area" class="form-control  form-control-danger" value="{{ (old('over_time') != '' ) ? old('over_time') :  '' }}">
                                                    <small class="invalid-feedback"> this required </small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Avvountnumber</label>
                                                    <input type="number" name='acount_no' class="form-control  form-control-danger" value="{{ (old('Salary') != '' ) ? old('salary') : '' }}">
                                                    <small class="invalid-feedback"> Avvountnumber required </small>
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
                                    </section>


                                    <h6>Dependants</h6>
                                    <section>
                                        <div class="row">
                                            <div class="col-md-6">

                                                <div class="form-group">
                                                    <label class="control-label">Navn</label>
                                                    <input type="text" name="dNavn" class="form-control  form-control-danger" value="{{ (old('dNavn') != '' ) ? old('cNavn') : (isset($uData->cNavn) ? $uData->cNavn : '') }}">
                                                    <small class="invalid-feedback"> Navn required </small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="control-label">Phone</label>
                                                    <input type="number" name='dphone' class="form-control  form-control-danger" value="{{ (old('dphone') != '' ) ? old('cPhone') : (isset($uData->Phone) ? $uData->cPhone : '') }}">
                                                    <small class="invalid-feedback"> Phone required </small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                {{ csrf_field() }}
                                                <div class="form-group">
                                                    @if($relation)
                                                    <label class="control-label">Relation</label>
                                                        <select name="relation" class="form-control custom-select">
                                                         @foreach($relation as $rel)
                                                        <option value="{{ $rel->id }}">{{ $rel->name }}</option>
                                                        @endforeach
                                                        </select>
                                                    @endif
                                                    <small class="invalid-feedback"> E-post required </small>
                                                </div>
                                            </div>

                                        </div>

                                    </section>
                                    <!-- Step 4 -->
                                    <h6>Dokumenter</h6>
                                    <section>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="input-file-now-custom-3">Upload You document</label>
                                                    <input type="file" name="profile_pic" id="input-file-now-custom-3" class="dropify" data-height="200" />
                                                </div>
                                            </div>
                                        </div>
                                    </section>
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
        @endsection
