@extends('templates.monster.main')
@push('after-styles')
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/summernote/dist/summernote-bs4.css') }}" rel="stylesheet" />
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css') }}" rel="stylesheet" type="text/css">
<style>
.note-toolbar.card-header {
    background-color: bisque;
}
</style>

@endpush

@push('after-scripts')
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/js/select2.full.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/monster/js/jasny-bootstrap.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/summernote/dist/summernote-bs4.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js') }}"></script>
<script src="{{ asset('/filejs/company/docs/addcontact.js') }}"></script>
@endpush
@section('content')

@include('common.errors')
@include('common.success')
<!-- ============================================================== -->
<!-- Start Page Content -->
<!-- ============================================================== -->

<div class="row">
        <div class="col-md-6">
                <div class="card card-outline-warning">
                    <div class="card-header">
                        <h4 class="mb-0 text-white">Kontakt</h4>
                    </div>
                    <div class="card-body" style="margin:10%;">
                    <h3 class="card-title">{{ isset($cData->bname) ? ucfirst($cData->bname) : ''}}</h3>
                           <small>Organisasjonsnummer : </small> <span class="card-text">{{ isset($cData->vat_no) ? $cData->vat_no : ''}}</span>
                           <br>
                           <small>Adresse : </small> <span class="card-text">{{ isset($cData->address) ? $cData->address : ''}}</span><br>
                           <small>Poststed : </small><span class="card-text">{{ isset($cData->city) ? $cData->city : ''}}</span><br>
                           <small>Postnummer : </small><span class="card-text">{{ isset($cData->zipcode) ? $cData->zipcode : ''}}</span><br>
                        <hr class="mt-5">
                           <a href="javascript:void(0);" data-toggle="modal" data-target="#verticalcenter" class="btn btn-inverse editContact">Endre kontakt</a>
                    </div>
                </div>
            </div>
    <div class="col-lg-6 col-md-6">
        <div class="row">
            <div class="col-lg-12">

        <div class="card card-outline-info">
            <div class="card-header">
                <h4 class="mb-0 text-white">Kontaktperson</h4>
            </div>
                <div class="card-body" >
                    <table class="table">
                        <thead>
                            <th>Navn</th>
                            <th>E-post</th>
                            <th>Telefonnummer</th>
                            <th>Stilling</th>
                            <th>Valg</th>
                        </thead>
                            @php
                                if($cData->id){
                                    $cPerson = \DB::table("contactperson")->where("contact_id",$cData->id)->get();
                                }
                            @endphp
                        <tbody>
                            @if($cPerson)
                                @foreach ($cPerson as $item)
                                <tr>
                                <td>{{ $item->cName }} {!! ($item->primrary == 1) ? '<span class="label label-info">Primrary</span>' : '' !!}</td>
                                    <td>{{ $item->cEpost }}</td>
                                    <td>{{ $item->cPhone }}</td>
                                    <td>{{ $item->worktype }}</td>
                                    <td>
                                    <a href="javascript:void(0);" data-id="{{$item->c_id}}" data-type="{{$item->worktype}}" data-name="{{$item->cName}}" data-email="{{$item->cEpost}}" data-phone="{{$item->cPhone}}" data-target="#kontact" data-toggle="modal" class="edt" ><i class="icon-pencil"></i></a>
                                    <a href="javascript:void(0);" data-id="{{$item->c_id}}" class="del"  ><i class="icon-trash"></i></a>
                                        </td>
                                </tr>
                                @endforeach
                            @endif
                        </tbody>

                    </table>

                             <hr class="mt-5">
                               <a href="javascript:void(0);" data-target="#kontact" data-toggle="modal" class="btn btn-inverse">Ny kontaktperson</a>
                        </div>

            </div>
            <div class="card-body">

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
                <a href="javascript:void(0);" class="btn btn-inverse">Nytt dokument</a>
            </div>
        </div>
    </div>
</div>
</div>


<!--Kontatct Modal -->
<div id="verticalcenter"  class="modal fade" tabindex="-1" role="dialog" aria-labelledby="vcenter" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
            <!----Form Data -->
            <form action="javascript:void(0);" class="needs-validation" novalidate>
    <div class="row">

        <div class="col-md-6">
                <div class="form-group">
                    <label class="control-label">Foretaksnavn</label>
                            <input type="text" name="name" value="{{ (old('name') != '' ) ? old('name') : (isset($cData->bname) ? $cData->bname : '') }}" required class="form-control">
                            <small class="invalid-feedback"> Name is required </small>
                </div>
            </div>
            <!--/span-->
            <div class="col-md-6">
                <div class="form-group">
                    <label class="control-label">Organisasjonsnummer</label>
                       <input type="number" required name='vat_no' class="form-control  form-control-danger" value="{{ (old('vat_no') != '' ) ? old('vat_no') : (isset($cData->vat_no) ? $cData->vat_no : '') }}">
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
                            <input type="hidden" name="id" value="{{ isset($cData->id) ? ($cData->id != 0 ) ? $cData->id : '' :''}}" />
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="control-label">Postnummer</label>
                            <input type="text" required name="zipcode" class="form-control  form-control-danger" value="{{ (old('zipcode') != '' ) ? old('zipcode') : (isset($cData->zipcode) ? $cData->zipcode : '') }}">
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
                                <option value="">Select category<option>
                                @foreach($cat as $data)
                                <option {{ (isset($cData->category) && ($cData->category == $data->id) ) ? "selected" : '' }} value="{{ $data->id }}">{{ $data->des}}</option>
                                @endforeach
                            </select>
                            <small class="invalid-feedback"> Kategori a category </small>
                        @endif
                </div>
            </div>
        </div>
        </div>
        <div class="modal-footer">
                <button type="submit" class="btn btn-success waves-effect text-left">Lagre</button>
              {{-- <button type="button" class="btn btn-danger waves-effect text-left">Sellte</button>--}}
            </div>
        </form>

        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
  <!-- Contak person modal -->
  <div id="kontact"  class="modal fade" tabindex="-1" role="dialog" aria-labelledby="vcenter" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered ">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="vcenter">Ny Kontaktperson</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                </div>
                <div class="modal-body">
                <!----Form Data -->
                <form action="javascript:void(0);" class="cvalid" novalidate>
                        <div class="form-group row">
                                <label class="control-label col-lg-3">Navn</label>
                                <div class="col-lg-9">
                                        <input type="text" name="cNavn" id="cName" value="{{ (old('cNavn') != '' ) ? old('name') : '' }}" required class="form-control">
                                        <small class="invalid-feedback"> Navn is required </small>
                            </div>
                        </div>
                        <div class="form-group row">
                                <label class="control-label col-lg-3">Epost</label>
                                <div class="col-lg-9">
                                        <input type="text" name="cEpost" id="cEpost" value="{{ (old('cEpost') != '' ) ? old('cEpost') : ''}}" required class="form-control">
                                        <small class="invalid-feedback"> Epost is required </small>
                                        <input type="hidden" value="{{ isset($cData->id) ? $cData->id : ''}}" name="contact_id" >
                                        <input type="hidden" id="c_id" value="0" name="id" >
                            </div>
                        </div>
                        <div class="form-group row">
                                <label class="control-label col-lg-3">Telefonnummer</label>
                                <div class="col-lg-9">
                                        <input type="number" id="cPhone" name="cPhone" value="{{ (old('cPhone') != '' ) ? old('cPhone') :  '' }}" required class="form-control">
                                        <small class="invalid-feedback">Telefonnummer is required </small>
                            </div>
                        </div>
                        <div class="form-group row">
                                <label class="control-label col-lg-3">Stilling</label>
                                <div class="col-lg-9">
                                <input type="text" required name="worktype" class="form-control" id="type" value="{{ (old('worktype') != '' ) ? old('worktype') : (isset($cData->worktype) ? $cData->worktype : '') }}">
                                <small class="invalid-feedback">Stilling required </small>
                            </div>
                        </div>
                        <div class="form-group row">
                                <div class="col-lg-12">
                                    <input type="checkbox" name="primrary" class="check" id="square-checkbox-1" data-checkbox="icheckbox_square-red">
                                    <label for="square-checkbox-1" style="margin-right:10px;">Primærkontakt</label>

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
    @endsection
