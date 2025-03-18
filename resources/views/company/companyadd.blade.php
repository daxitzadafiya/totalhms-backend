@extends('templates.monster.main')
@push('after-styles')
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/dropify/dist/css/dropify.min.css">

@endpush


@section('content')

@include('common.errors')
@include('common.success')

<div class="row">
    <div class="col-lg-12">
        <div class="card card-outline-info">
            <div class="card-header">
                <h4 class="mb-0 text-white">Company Profile</h4>
            </div>
            <div class="card-body">
                <form method="post" class="needs-validation" novalidate enctype="multipart/form-data" action="{{    url('admin/save/company') }}">
                    <div class="form-body">
                        {{ csrf_field() }}
                        <h3 class="card-title">Person Info</h3>
                        <hr>
                        <div class="row pt-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Company Name</label>
                                    <input type="text" required name="company_name" class="form-control" value="{{ (old('company_name') != '' ) ? old('company_name') : (isset($cData->company_name) ? $cData->company_name : '') }}">
                                    <small class="invalid-feedback"> Comapny Name is required </small>
                                    <input type="hidden" name="id" value="{{ isset($cData->id) ?  $cData->id  :  '' }}" />
                                </div>
                            </div>
                            <!--/span-->
                            <div class="col-md-6">
                                <div class="form-group ">
                                    <label class="control-label">VAT-number</label>
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
                                    <label class="control-label">Industry</label>
                                    <input type="number" name="industry" required class="form-control form-control-danger" value="{{ (old('industry') != '' ) ? old('industry') : (isset($cData->industry) ? $cData->industry : '') }}">
                                    <small class="invalid-feedback"> Industry is required </small>

                                </div>
                            </div>
                            <!--/span-->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Business sector code </label>
                                    <input type="number" required class="form-control" name="sector_code" value="{{ (old('sector_code') != '' ) ? old('sector_code') : (isset($cData->sector_code) ? $cData->sector_code : '') }}">
                                    <small class="invalid-feedback"> Sector Code is required </small>

                                </div>
                            </div>
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
                                    <input type="text" required name="zipcode" class="form-control  form-control-danger" value="{{ (old('zipcode') != '' ) ? old('zipcode') : (isset($cData->zip_code) ? $cData->zip_code : '') }}">
                                    <small class="invalid-feedback"> zipcode is required </small>

                                </div>
                            </div>

                            <!--/span-->
                        </div>
                        <!--/row-->
                        <div class="row">

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Logo</label>
                                    <input type="file" name="logo" class="dropify" data-default-file="file_uploader/documents/4/companylogo/4-f2Z71p5wEs.jpg" /> </div>
                            </div>
                            <!--/span-->
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success"> <i class="fa fa-check"></i> Save</button>
                        <button type="button" class="btn btn-inverse">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@push('after-scripts')

<!-- jQuery file upload -->
<script src="/vendor/wrappixel/monster-admin/4.2.1/monster/js/jasny-bootstrap.js"></script>

<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/dropify/dist/js/dropify.min.js"></script>
<script>
    $(document).ready(function() {
        // Basic
        $('.dropify').dropify();

    });

    (function() {
        'use strict';
        window.addEventListener('load', function() {
            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            var forms = document.getElementsByClassName('needs-validation');
            // Loop over them and prevent submission
            var validation = Array.prototype.filter.call(forms, function(form) {
                form.addEventListener('submit', function(event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();
</script>
@endpush
@endsection