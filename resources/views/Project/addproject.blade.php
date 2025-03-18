@extends('templates.monster.main')

@push('before-styles')
<link rel="stylesheet" href="https://demo.worksuite.biz/plugins/bower_components/bootstrap-datepicker/bootstrap-datepicker.min.css">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/wizard/steps.css') }}" rel="stylesheet">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/dropify/dist/css/dropify.min.css') }}">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css') }}" rel="stylesheet" type="text/css">
@endpush

@push('after-scripts')
<script src="https://demo.worksuite.biz/plugins/bower_components/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/js/select2.full.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/wizard/jquery.validate.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/dropify/dist/js/dropify.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/wizard/jquery.steps.min.js') }}"></script>
<script src="{{ asset('filejs/Project/addproject.js') }}"></script>
@endpush

@section('content')
@include('common.errors')
@include('common.success')


<div class="row">
    <div class="col-lg-12">
        <div class="card card-outline-info">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0 text-white">Opprett Project</h4>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ url('/project/add') }}">

                        <div class="row">
                            <div class="col-md-6">
                                <label><b>Project Number:</b></label>
                                    <div class="form-group">
                                        <input type="number" name="project_id" class="form-control" required>
                                    </div>
                            </div>
                            <div class="col-md-6">
                                <label><b>Project Name:</b></label>
                                    <div class="form-group">
                                        <input type="text" name="project_name" class="form-control" required>
                                    </div>
                            </div>
                        </div>
                        {{ csrf_field() }}
                        <div class="row">
                            <div class="col-md-6">
                                <label><b>Contracting Authority:</b></label>
                                    <div class="form-group">
                                    @if($contract_contact)
                                        <select name="contract_contact" id="contract" class="form-control" required>
                                        @foreach($contract_contact as $contact)
                                            <option value='{{ $contact->id }}'>{{ $contact->bname}}</option>
                                        @endforeach
                                            <option value='add'>Add New Contact </option>
                                        </select>
                                    @endif
                                    </div>
                            </div>
                            <div class="col-md-6">
                                <label><b>Address:</b></label>
                                    <div class="form-group">
                                        <input type="text" name="address" class="form-control" required>
                                    </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <label><b>City:</b></label>
                                    <div class="form-group">
                                        <input type="text" name="city" class="form-control" required>
                                    </div>
                            </div>
                            <div class="col-md-6">
                                <label><b>Zip code:</b></label>
                                    <div class="form-group">
                                        <input type="number" name="zipcode" class="form-control" required>
                                    </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <label><b>Start date:</b></label>
                                    <div class="form-group">
                                        <div class="input-group">
                                            <input type="text" name='startdate' id="start_date" class=" form-control form-control-danger  ">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">
                                                        <span class="ti-calendar"></span>
                                                    </span>
                                                </div>
                                        </div>
                                    </div>
                            </div>
                            <div class="col-md-6">
                                <label><b>Date of Completion:</b></label>
                                    <div class="form-group">
                                        <div class="input-group">
                                            <input type="text" name='closeddate' id="closed_date" class=" form-control form-control-danger  ">
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
                                <label><b>Project Manager:</b></label>
                                    <div class="form-group">
                                    @if($employees)
                                        <select class="select2 mb-2 select2-multiple form-control" name="responsible[]" style="width: 100%" multiple="multiple" data-placeholder="Choose">
                                        @foreach($employees as $employ)
                                            <option value='{{ $employ->id }}'>{{ $employ->name }}</option>
                                        @endforeach
                                        </select>
                                    @endif
                                    </div>
                            </div>
                            <div class="col-md-6">
                                <label><b>Estimated Time Consumption:</b></label>
                                    <div class="form-group">
                                        <input type="number" name="budget" class="form-control" placeholder="hours" required>
                                    </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <label><b>Employee Involved:</b></label>
                                    <div class="form-group">
                                    @if($employees)
                                        <select class="select2 mb-2 select2-multiple form-control" name="attendee[]" style="width: 100%" multiple="multiple" data-placeholder="Choose">
                                        @foreach($employees as $employ) 
                                            <option value='{{ $employ->id }}'>{{ $employ->name }}</option>
                                        @endforeach
                                        </select>
                                    @endif
                                    </div>
                            </div>
                            <div class="col-md-6">
                                <label><b>Involved Contractors</b></label>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-11">
                                                <div class="input-group">
                                                    <select class="select2 mb-2 select2-multiple form-control" name="other_supplier[]" style="width: 100%" multiple="multiple" data-placeholder="Choose">
                                                    @if($supplier)
                                                    @foreach($supplier as $supp)
                                                        <option value='{{ $supp->id }}'>{{ $supp->name}}</option>
                                                    @endforeach
                                                    @endif
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-1">
                                                <div class="input-group-append">
                                                    <span class="input-group-addon">
                                                        <button type="button" class="btn btn-warning add_supplier"><i class="fa fa-plus"></i></button>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-success"> <i class="fa fa-check"></i> Lagre</button>
                            </div>
                        </div>
                        

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


<!--Supplier Modal -->
<div id="supplier_modal"  class="modal fade" tabindex="-1" role="dialog" aria-labelledby="vcenter" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            
            <div class="modal-header">
                <h4>Add suppliers</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            
            <div class="modal-body">
                <form method="post" action="{{ url('/add/suppliers') }}">
                
                <div class="row">
                    <div class="col-md-6">
                        <label class="control-label">Name:</label>
                        <div class="form-group">
                            <input type="text" name="supplier_name" class="form-control" required>
                        </div>
                    </div>
                    {{ csrf_field() }}
                    <div class="col-md-6">
                        <label class="control-label">VAT-Number</label>
                        <div class="form-group">
                            <input type="number" name="vat_number" class="form-control" required>
                        </div>
                    </div>
                </div>  

                <div class="row">
                    <div class="col-md-6">
                        <label class="control-label">Address:</label>
                        <div class="form-group">
                            <input type="text" name="address" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="control-label">City:</label>
                        <div class="form-group">
                            <input type="text" name="city" class="form-control" required>
                        </div>
                    </div>
                </div>  

                <div class="row">
                    <div class="col-md-6">
                        <label class="control-label">Zipcode:</label>
                        <div class="form-group">
                            <input type="text" name="zipcode" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="control-label">Phone:</label>
                        <div class="form-group">
                            <input type="number" name="phone" class="form-control" required>
                        </div>
                    </div>
                </div> 

                <div class="row">
                    <div class="col-md-6">
                        <label class="control-label">Mail:</label>
                        <div class="form-group">
                            <input type="text" name="mail" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label>Category</label>
                        <div clss="form-group">
                        @php
                        $cat = \DB::table("all_category")->where("table_name","suppliers")->get();
                        @endphp
                        @if($cat)
                            <select name="supplier_category" class="form-control" required>
                            @foreach($cat as $data)
                                    <option value='{{ $data->id }}'>{{ $data->des }}</option>
                            @endforeach
                            </select>
                        @endif
                        </div>
                    </div>
                </div> 


            </div>
            
            <div class="modal-footer">
                <button type="submit" class="btn btn-success waves-effect text-left">Lagre</button>
            </div>
            </form>

        </div>
    </div>
</div>


        <!-- sample modal content -->
        <div id="contact_modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="vcenter" aria-hidden="true">
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
                                                            $cat = \DB::table("all_category")->where("table_name","projects")->get();
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
                                                            <input type="text"  name="cNavn" class="form-control  form-control-danger" value="{{ (old('cNavn') != '' ) ? old('cNavn') : (isset($cData->cNavn) ? $cData->cNavn : '') }}">
                                                            <!-- <small class="invalid-feedback"> Navn required </small> -->
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">Phone</label>
                                                            <input type="text"  name='phone' class="form-control  form-control-danger" value="{{ (old('phone') != '' ) ? old('cPhone') : (isset($cData->Phone) ? $cData->cPhone : '') }}">
                                                            <!-- <small class="invalid-feedback"> Phone required </small> -->
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">

                                                        <div class="form-group">
                                                            <label class="control-label">E-Post</label>
                                                            <input type="text"  name="epost" class="form-control  form-control-danger" value="{{ (old('epost') != '' ) ? old('cEpost') : (isset($cData->cEpost) ? $cData->cEpost : '') }}">
                                                            <!-- <small class="invalid-feedback"> E-post required </small> -->
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
                                                            <input type="text"  name="worktype" class="form-control" value="{{ (old('worktype') != '' ) ? old('worktype') : (isset($cData->worktype) ? $cData->worktype : '') }}">
                                                            <!-- <small class="invalid-feedback">Stilling required </small> -->
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