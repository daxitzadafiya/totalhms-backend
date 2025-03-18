@extends('templates.monster.main')
@push('before-styles')
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/css/select2.min.css" rel="stylesheet" type="text/css" />
@endpush

@push('after-scripts')

<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/js/select2.full.min.js" type="text/javascript"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/monster/js/jasny-bootstrap.js"></script>

<script>
    jQuery(document).ready(function() {

        $(".select2").select2();

        $('.rolle').on('change', function(e) {
            e.preventDefault();

            var role = $(this).val();
            console.log(role);
            if (role == 2) {
                $(".select2").attr('required', true);

            } else {
                $(".select2").attr('required', false);

            }


        });

    });

    function turnOnPasswordStyle() {
        $('#xpass').attr('type', "password");
    }

    function turnOncPasswordStyle() {
        $('#xpass').attr('type', "password");
    }

    function turnOnEmailStyle() {
        $('#epostadresse').attr('type', "email");
    }



    // Example starter JavaScript for disabling form submissions if there are invalid fields
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
                <h4 class="mb-0 text-white">Informasjon</h4>
            </div>
            <div class="card-body">

                <form class="needs-validation" novalidate method="POST" action="{{ url('/admin/save/user') }}">
                    <div class="form-body">
                        <h3 class="card-title">Person Info</h3>
                        <hr>
                        <div class="row pt-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Navn</label>
                                    <input type="text" name="name" value="{{ (old('name') != '' ) ? old('name') : (isset($uData->name) ? $uData->name : '') }}" required class="form-control">
                                    <small class="invalid-feedback"> Name is required </small>

                                </div>
                            </div>
                            <!--/span-->
                            <div class="col-md-6">
                                <div class="form-group ">
                                    <label class="control-label">Epost</label>
                                    <input type="text" name="email" value="{{ (old('email') != '' ) ? old('email') : (isset($uData->email) ? $uData->email : '') }}" id="epostadresse" oninput="turnOnEmailStyle()" required class="form-control">
                                    <small class="invalid-feedback"> Enter valid email </small>
                                </div>
                            </div>
                            <input type="hidden" name="id" value="{{ isset($uData->id) ? ($uData->id != 0 ) ? $uData->id : '' :''}}" />
                            <!--/span-->
                        </div>
                        {{ csrf_field() }}
                        <div class="row pt-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Passord</label>
                                    <input type="text" {{ isset($uData->password) ? '' : "required" }} name="password" oninput="turnOncPasswordStyle()" id="xpass" class="form-control">
                                    <small class="invalid-feedback"> Passord is required </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Company</label>
                                    @php
                                    $companies = \DB::table("companyprofile")->select('id','company_name')->where("del_status",0)->get();

                                    @endphp
                                    <select class="form-control select2" placeholder="das" name="companyId[]" style="width:100%;" multiple="multiple">
                                        <option value="0">Select</option>
                                        @foreach($companies as $data)
                                        <option {{ isset($uData->companyId) ? ($uData->companyId == $data->id) ? 'selected' :"" : "" }} value="{{$data->id}}">{{ $data->company_name}}</option>
                                        @endforeach
                                    </select>
                                    <small class="invalid-feedback"> Passord is required </small>
                                </div>
                            </div>
                            <!--/span-->

                            <!--/span-->
                        </div>
                        <!--/row-->


                        <!--/row-->
                        <h3 class="box-title mt-5">Adresse</h3>
                        <hr>
                        <div class="row">
                            <div class="col-md-6 ">
                                <div class="form-group">
                                    <label>Adresse</label>
                                    <input type="text" required name="address" value="{{ (old('address') != '' ) ? old('address') : (isset($uData->address) ? $uData->address : '') }}" class="form-control">
                                    <small class="invalid-feedback"> Adresse is required </small>

                                </div>
                            </div>
                            <div class="col-md-6 ">
                                <div class="form-group">
                                    <label>Telefonnummer</label>
                                    <input type="text" required value="{{ (old('phone') != '' ) ? old('phone') : (isset($uData->phone) ? $uData->phone : '') }}" name="phone" class="form-control">
                                    <small class="invalid-feedback"> Telefonnummer is required </small>


                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Poststed</label>
                                    <input type="text" required value="{{ (old('city') != '' ) ? old('city') : (isset($uData->city) ? $uData->city : '') }}" name='city' class="form-control">
                                    <small class="invalid-feedback"> Poststed is required </small>

                                </div>
                            </div>
                            <!--/span-->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Postnummer</label>
                                    <input type="text" required value="{{ (old('zipcode') != '' ) ? old('zipcode') : (isset($uData->zipcode) ? $uData->zipcode : '') }}" name="zipcode" class="form-control">
                                    <small class="invalid-feedback"> Postnummer is required </small>

                                </div>
                            </div>
                            <!--/span-->
                        </div>
                        <!--/row-->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Personnummer</label>
                                    <input type="text" required value="{{ (old('personal_no') != '' ) ? old('personal_no') : (isset($uData->personal_no) ? $uData->personal_no : '') }}" name="personal_no" class="form-control">
                                    <small class="invalid-feedback"> Personnummer is required </small>

                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Kontonr</label>
                                    <input type="text" required name="account_no" value="{{ (old('account_no') != '' ) ? old('account_no') : (isset($uData->account_no) ? $uData->account_no : '') }}" class="form-control">
                                    <small class="invalid-feedback"> Kontonr is required </small>

                                </div>
                            </div>
                            <!--/span-->

                        </div>
                        <div class="row">

                            <!--/span-->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Rolle</label>
                                    <div class="mb-2">

                                        <label class="custom-control custom-radio">
                                            <input id="radio1" name="role" value="1" type="radio" {{  (isset($uData->user_role) ?($uData->user_role == 1 ) ? 'checked' : '': '') }} class="custom-control-input">
                                            <span class="custom-control-label">Super-Admin</span>
                                        </label>
                                        <label class="custom-control custom-radio">
                                            <input id="radio2" name="role" value="2" {{  (isset($uData->user_role) ?($uData->user_role != 1 ) ? 'checked' : '': '') }} type="radio" class="custom-control-input">
                                            <span class="custom-control-label">Company-Admin</span>
                                        </label>
                                    </div>
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
        </div>
    </div>
</div>

<!-- Row -->@endsection