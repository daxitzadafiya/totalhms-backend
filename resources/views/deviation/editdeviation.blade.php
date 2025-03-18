@extends('templates.monster.main')

@push('before-styles')
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/daterangepicker/daterangepicker.css" rel="stylesheet">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/css/select2.min.css') }}"
    rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/dropify/dist/css/dropify.min.css">
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css" rel="stylesheet"
    type="text/css">
<link rel="stylesheet"
    href="https://demo.worksuite.biz/plugins/bower_components/bootstrap-datepicker/bootstrap-datepicker.min.css">
@endpush

@push('after-scripts')
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/moment/moment.js"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/daterangepicker/daterangepicker.js"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/dropify/dist/js/dropify.min.js"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/js/select2.full.min.js') }}"
    type="text/javascript"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js"></script>
<script src="https://demo.worksuite.biz/plugins/bower_components/bootstrap-datepicker/bootstrap-datepicker.min.js">
</script>
<script src="{{ asset('/filejs/deviation/editdeviation.js') }}"></script>
@endpush

@section('content')
<div class="row page-titles">
    <div class="col-md-6 col-8 align-self-center">
        <h3 class="text-themecolor mb-0 mt-0">Behandle avvik</h3>
    </div>
</div>
<div class="row">
    <div class="col-lg-12">
        <div class="card card-outline-info">
            <div class="card-header">
                <h4 class="mb-0 text-white">Avvik</h4>
            </div>
            <div class="card-body">
                <form action="{{ url('/avvik/add') }}" method="post" enctype="multipart/form-data">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Rapportert av:</label>
                                @if($employ)
                                <select name="employees" required class="form-control" disabled>
                                    <option value=''>Anonymous</option>
                                    @foreach($employ as $data)
                                    <option
                                        {{ isset($dData->report_by) && $dData->report_by == $data->id  && $dData->anonymous != 1 ? "selected" : "" }}
                                        value="{{ $data->id }}">{{ $data->name}}</option>
                                    @endforeach
                                </select>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Dato:</label>
                                <div class="input-group">
                                    <input type="text" name='emp_date' id="devrange"
                                        value="{{ (old('date') != '' ) ? old('date') : (isset($dData->date) ? $dData->date : '' )  }}"
                                        class=" form-control  form-control-danger  " disabled>
                                    <div class="input-group-append">
                                        <span class="input-group-text">
                                            <span class="ti-calendar"></span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{ csrf_field()}}

                    <div class="row">
                        <div class="col-md-6">
                            <label class="control-label">Avvik:</label>
                            <div class="form-group">
                                <input type="text"
                                    value="{{ (old('subject') != '') ? old('subject') : (isset($dData->subject) ? $dData->subject : '') }}"
                                    name="subject" required class="form-control" disabled>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="control-label">Sted Oppdaget:</label>
                            <div class="form-group">
                                @if($place)
                                <select class="select2 mb-2 select2-multiple" id="placo" name="place" disabled
                                    style="width: 100%" multiple="multiple" data-placeholder="Choose">
                                    @foreach($place as $data)
                                    <option {{ isset($dData->place) && $dData->place == $data->id ? "selected" : "" }}
                                        value="{{ $data->id }}">{{ $data->place_name}}</option>
                                    @endforeach
                                </select>
                                @endif
                                <!-- <a href="JavaScript:void(0);" class="btn btn-outline btn-success add_place text-light float-right">Opprett sted <i class="fa fa-plus" aria-hidden="true"></i></a>  -->
                            </div>
                        </div>
                        <input type="hidden" name="id" value="{{ $dData->id }}" id="xid">
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <label>Beskrivelse</label>
                            <div class="form-group">
                                <textarea name="description" rows="6" disabled
                                    style="width:100%;"> {{ (old('description') != '' ) ? old('description') : (isset($dData->description) ? $dData->description : '') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label class="control-label">Konsekvens for:</label>
                            <div class="form-group">
                                <select name="consequence" class="form-control" required disabled>
                                    <option value=''>Velg Consequence</option>
                                    <option {{ ($dData->consequence=='0') ? 'selected':'' }} value='0'>Foretaket
                                    </option>
                                    <option {{ ($dData->consequence=='1') ? 'selected':'' }} value='1'>Kunden</option>
                                    <option {{ ($dData->consequence=='2') ? 'selected':'' }} value='2'>Andre</option>
                                    <select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="control-label">forslag til forbedring:</label>
                            <div class="form-group">
                                <input type="text"
                                    value="{{ (old('proposal') != '' ) ? old('proposal') : (isset($dData->proposal) ? $dData->proposal : '' ) }}"
                                    name="proposal" required class="form-control" disabled>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <label class="control-label">Add Bilde/Vedlegg</label>
                            <div class="form-group drop_file">
                                <!-- <input type="file" name="doc" class="dropify"/> -->
                                <a href="#" data-toggle="modal" data-id="{{ $dData->id }}"
                                    data-target="#pre{{ $dData->id }}" class="editProduct">
                                    <img width="30%" height="30%"
                                        src="{{ asset('file_uploader/company/'.\Auth::user()->companyId. '/document/'.$dData->doc) }}"></a>

                                <div id="pre{{ $dData->id }}" class="modal fade bs-example-modal-lg" tabindex="-1"
                                    role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true"
                                    style="display: none;">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content" style="height:800px;">
                                            <div class="modal-header">
                                                <button type="button" class="close" data-dismiss="modal"
                                                    aria-hidden="true">×</button>
                                            </div>
                                            <div class="modal-body">
                                                <img width="100%" height="100%"
                                                    src="{{ asset('file_uploader/company/'.\Auth::user()->companyId. '/document/'.$dData->doc) }}">
                                                <div>
                                                </div>
                                                <!-- /.modal-content -->
                                            </div>
                                            <!-- /.modal-dialog -->
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <!-- <div class="row">
                    <div class="col-md-6">
                        <div class="checkbox checkbox-info checkbox-circle">
                            <input id="checkbox8" name="anonymous" type="checkbox" value="1" {{ ($dData->anonymous=='1')?'checked':'' }}>
                                <label for="checkbox8">Ønsker du å være anonym? </label>
                        </div>
                    </div>
                </div> -->

                        </div>
                    </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card card-outline-info">
                    <div class="card-header">
                        <h4 class="mb-0 text-white"> Behandle innrapportert avvik</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="control-label">Har avviket skjedd tidligere?</label>
                                <div class="form-group">
                                    <select name="happen" class="form-control" required>
                                        <!-- <option value=''>Velg Consequence</option> -->
                                        <option {{ ($dData->happen_before =='1') ? 'selected' : '' }} value='1'>Nei
                                        </option>
                                        <option {{($dData->happen_before =='0') ? 'selected' : '' }} value='0'>Ja
                                        </option>
                                        <option {{ ($dData->happen_before =='2') ? 'selected' : ''}} value='2'>Usikker
                                        </option>
                                        <select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="control-label">Status</label>
                                <div class="form-group">
                                    <select name="status" class="form-control" required>
                                        <!-- <option value=''>Velg Consequence</option> -->
                                        <option {{ ($dData->status =='0') ? 'selected' : '' }} value='0'>Ny</option>
                                        <option {{ ($dData->status =='1') ? 'selected' : '' }} value='1'>Under
                                            behandling</option>
                                        <option {{ ($dData->status =='2') ? 'selected' : '' }} value='2'>Flyttet til
                                            handlingsplan</option>
                                        <option {{ ($dData->status =='3') ? 'selected' : '' }} value='3'>Fullført
                                        </option>
                                        <select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <label class="control-label">Korrigerende tiltak</label>
                                <div class="form-group">
                                    <!-- <input type="text" name="corrective" value="{{ (old('corr_action') != '' ) ? old('corr_action') : (isset($dData->corr_action ) ? $dData->corr_action : '' ) }}" required class="form-control"> -->
                                    <textarea name="corrective" class="form-control" rows="5"
                                        required>{{ (old('corr_action') != '' ) ? old('corr_action') : (isset($dData->corr_action ) ? $dData->corr_action : '' ) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <label class="control-label">Krav/spesifikasjoner</label>
                                <div class="form-group">
                                    <input type="text" name="requirement"
                                        value="{{ (old('legal_req') != '' ) ? old('legal_req') : (isset($dData->legal_req ) ? $dData->legal_req : '' ) }}"
                                        required class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Ansvarlig for utbedring:</label>
                                    @if($employ)
                                    <select name="employee" required class="form-control">
                                        <option value=''>Velg ansatt</option>
                                        @foreach($employ as $data)
                                        <option
                                            {{ isset($dData->responsible) && $dData->responsible == $data->id ? "selected" : "" }}
                                            value="{{ $data->id }}">{{ $data->name}}</option>
                                        @endforeach
                                    </select>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Frist for utbedring:</label>
                                    <div class="input-group">
                                        <input type="text" name='deadline' id="dthline"
                                            value="{{ (old('deadline') != '' ) ? old('deadline') : (isset($dData->deadline) ? $dData->deadline : '' ) }}"
                                            class=" form-control  form-control-danger  ">
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <span class="ti-calendar"></span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <div class="row">
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-danger delete_dev" data-id="{{ $dData->id }}"><i
                                        class="fa fa-trash"></i> Slett </button>
                                <button type="submit" class="btn btn-success"> <i class="fa fa-check"></i>
                                    Lagre</button>
                                <button type="button" class="btn btn-inverse">Flytt til handlingsplan</button>
                            </div>
                        </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>



        @endsection