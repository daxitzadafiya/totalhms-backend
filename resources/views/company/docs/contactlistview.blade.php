@extends('templates.monster.main')

@push('before-styles')
<style>
    div#myTable_filter {
        display: none;
    }
</style>
<link rel="stylesheet" type="text/css" href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/datatables/media/css/dataTables.bootstrap4.css') }}">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css') }}" rel="stylesheet" type="text/css">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/icheck/skins/all.css') }}" rel="stylesheet">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/daterangepicker/daterangepicker.css') }}" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/dropify/dist/css/dropify.min.css') }}">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/wizard/steps.css') }}" rel="stylesheet">
@endpush

@push('after-scripts')
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/icheck/icheck.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/icheck/icheck.init.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/dropify/dist/js/dropify.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/datatables/datatables.js') }}"></script>
<!-- start - This is for export functionality only -->
<script src="https://cdn.datatables.net/buttons/1.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.flash.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
<script src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js"></script>
<script src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.print.min.js"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/wizard/jquery.validate.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/wizard/jquery.steps.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/moment/moment.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/daterangepicker/daterangepicker.js') }}"></script>
<!-- end - This is for export functionality only -->
<script src="{{asset ('/filejs/company/docs/contactlist.js') }}"></script>
@endpush
@section('content')

@include('common.errors')
@include('common.success')
<div class="row page-titles">
    <div class="col-md-6 col-8 align-self-center">
        <h3 class="text-themecolor mb-0 mt-0">Kontrakter og avtaler</h3>
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">Her finner du en oversikt over bedriftens samarbeidspartnere, leverandører og kontakter. Legg til nye kontakter etterhvert som ditt nettverk blir større.</li>
        </ol>
    </div>
    <div class="col-md-6 col-4 align-self-center">
        {{--<button class="right-side-toggle waves-effect waves-light btn-info btn-circle btn-sm float-right ml-2"><i class="ti-settings text-white"></i></button>
       <a href="{{ url('foretak/kontakt/ny')}}" class="btn float-right hidden-sm-down btn-success"><i class="mdi mdi-plus-circle"></i>Legg til ny kontakt</a>
        --}}
        <a data-toggle="modal" data-target="#verticalcenter" class="btn float-right hidden-sm-down btn-success"><i class="mdi mdi-plus-circle"></i>Opprett kontakt</a>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4>Innstillinger</h4>
                <hr>
                <br>
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Kategori</label>

                            <select name="category" id="catid" required class="form-control">
                                <option selected value=''>Alle</option>
                            </select>
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

                <div class="table m-t-40">
                    <table id="myTable" class="table">
                        <thead>
                            <tr>
                                <th>Foretaksnavn</th>
                                <th>Kategori</th>
                                <th>Kontaktperson</th>
                                <th>Telefonnummer</th>
                                <th>E-post</th>
                                <th>Antall vedlegg</th>
                            </tr>
                        </thead>
                        <tbody>


                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        <!-- sample modal content -->
        <div id="verticalcenter" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="vcenter" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="vcenter">Opprett kontakt</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    </div>
                    <div class="modal-body">
                        <!----Form Data -->

                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body wizard-content ">
                                        <form action="#" class="tab-wizard vertical wizard-circle">
                                            <!-- Step 1 -->
                                            <h6>Informasjon</h6>
                                            <section>
                                                <div class="row">

                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">Foretaksnavn</label>
                                                            <input type="text" name="name" value="{{ (old('name') != '' ) ? old('name') : (isset($cData->name) ? $cData->name : '') }}" required class="form-control">
                                                            <small class="invalid-feedback"> Name is required </small>
                                                        </div>
                                                    </div>
                                                    <!--/span-->
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">Organisasjonsnummer</label>
                                                            <input type="number" required name='vat_no' class="form-control  form-control-danger" value="{{ (old('city') != '' ) ? old('city') : (isset($cData->city) ? $cData->city : '') }}">
                                                            <small class="invalid-feedback"> Organisasjonsnummer is required </small>
                                                        </div>
                                                    </div>
                                                    <!--/span-->
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">Adresse</label>
                                                            <input type="text" required name="address" class="form-control  form-control-danger" value="{{ (old('address') != '' ) ? old('address') : (isset($cData->address) ? $cData->address : '') }}">
                                                            <small class="invalid-feedback"> Adresse required </small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">Poststed</label>
                                                            <input type="text" required name='city' class="form-control  form-control-danger" value="{{ (old('city') != '' ) ? old('city') : (isset($cData->city) ? $cData->city : '') }}">
                                                            <small class="invalid-feedback"> Poststed required </small>
                                                            <input type="hidden" name="id" value="{{ isset($iData->id) ? ($iData->id != 0 ) ? $iData->id : '' :''}}" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">Postnummer</label>
                                                            <input type="number" required name="zipcode" class="form-control  form-control-danger" value="{{ (old('zipcode') != '' ) ? old('zipcode') : (isset($cData->zip_code) ? $cData->zip_code : '') }}">
                                                            <small class="invalid-feedback"> Postnummer is required </small>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">Kategori</label>
                                                            @php
                                                            $cat = \DB::table("all_category")->where("table_name","contacts")->get();
                                                            @endphp
                                                            @if($cat)
                                                            <select name="category" required class="form-control custom-select">
                                                                <option value="">Velg kategori</option>
                                                                    @foreach($cat as $data)
                                                                <option value="{{ $data->id }}">{{ $data->des}}</option>
                                                                @endforeach
                                                            </select>
                                                            <small class="invalid-feedback"> Kategori a category </small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </section>

                                            <h6>Kontaktperson</h6>
                                            <section>
                                                <div class="row">
                                                    <div class="col-md-6">

                                                        <div class="form-group">
                                                            <label class="control-label">Navn</label>
                                                            <input type="text" required name="cNavn" class="form-control  form-control-danger" value="{{ (old('cNavn') != '' ) ? old('cNavn') : (isset($cData->cNavn) ? $cData->cNavn : '') }}">
                                                            <small class="invalid-feedback"> Navn required </small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">Phone</label>
                                                            <input type="text" required name='phone' class="form-control  form-control-danger" value="{{ (old('phone') != '' ) ? old('cPhone') : (isset($cData->Phone) ? $cData->cPhone : '') }}">
                                                            <small class="invalid-feedback"> Phone required </small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">

                                                        <div class="form-group">
                                                            <label class="control-label">E-Post</label>
                                                            <input type="text" required name="epost" class="form-control  form-control-danger" value="{{ (old('epost') != '' ) ? old('cEpost') : (isset($cData->cEpost) ? $cData->cEpost : '') }}">
                                                            <small class="invalid-feedback"> E-post required </small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label"></label>
                                                            <div class="input-group ">
                                                                <div id="check">

                                                                </div>

                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">Stilling</label>
                                                            <input type="text" required name="worktype" class="form-control" value="{{ (old('worktype') != '' ) ? old('worktype') : (isset($cData->worktype) ? $cData->worktype : '') }}">
                                                            <small class="invalid-feedback">Stilling required </small>
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
                                                            <input type="file" id="input-file-now-custom-3" class="dropify" data-height="200" />
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