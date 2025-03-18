@extends('templates.monster.main')
@push('after-styles')
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/summernote/dist/summernote-bs4.css') }}" rel="stylesheet" />
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css') }}" rel="stylesheet" type="text/css">
<link rel="stylesheet" href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/dropify/dist/css/dropify.min.css') }}">

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

    button.btn.btn-rounded.btn-block.btn-primary {
        width: 65%;
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
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/dropify/dist/js/dropify.min.js') }}"></script>

<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/moment/moment.js') }}"></script>
{{--<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js') }}"></script>
--}}
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/jquery-ui.min.js"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/icheck/icheck.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/icheck/icheck.init.js') }}"></script>
<script src="{{ asset('/filejs/document/documentedit.js') }}"></script>
<script>
    jQuery(document).ready(function() {
        $(".s").summernote({
            height: 200,
            toolbar: [
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['font', ['strikethrough', 'superscript', 'subscript']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['view', ['codeview']],
            ],
        });
        $('.s').summernote('code', '{!! isset($dData->discription) ? htmlspecialchars_decode($dData->discription) : (isset($tData->des) ? htmlspecialchars_decode($tData->des) : '')  !!}');
    });
</script>

@endpush
@section('content')

@include('common.errors')
@include('common.success')

<!-- ============================================================== -->
<!-- Start Page Content -->
<!-- ============================================================== -->
<!-- Row -->
<?php $url =  request()->segment(2); ?>
<div class="row">
    <div class="col-lg-12">
        <div class="card card-outline-info">
            <div class="card-header">
                <h4 class="mb-0 text-white">Opprett ny Document</h4>
            </div>
            <div class="card-body">

                <form class="needs-validation" novalidate method="POST" enctype="multipart/form-data" action="{{ url('/document/edit/save') }}">
                    <div class="form-body">
                        <div class="row ">
                            <!--/span-->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Dokumenttittel</label>
                                    <input type="text" name="name" value="{{ (old('name') != '' ) ? old('name') : (isset($dData->name) ? $dData->name : '') }}" required class="form-control">
                                    <small class="invalid-feedback"> Dokumenttittel is required </small>

                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group ">
                                    <label class="control-label">Kategori</label>

                                    @php
                                    $cat = \DB::table("all_category")->where("companyId",\Auth::user()->companyId)->where("table_name","documents")->get();
                                    $cate = [];

                                    if(isset($dData->category)){
                                    $cate = explode("," , $dData->category);
                                    }
                                    if(isset($tData->category_id)){
                                    $cate = explode("," , $tData->category_id);
                                    }
                                    @endphp
                                    @if($cat)
                                    <select name="category" id="cat" required class="form-control">
                                        <option value=''>Velg kategori</option>
                                        @foreach($cat as $data)
                                        <option {{ (isset($data->id) && in_array($data->id,$cate) ? 'selected' : '') }} {{ (isset($dData->category_id) && ($dData->category_id == $data->id )) ? "selected" : '' }} value="{{ $data->id }}">{{ $data->des}}</option>
                                        @endforeach
                                    </select>
                                    <small class="invalid-feedback"> Kategori required </small>

                                    @endif
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group ">
                                    <label class="control-label">Legg til kategori</label>
                                    <button type="button" data-toggle="modal" data-target="#tooltipmodals" class="btn btn-rounded btn-block btn-primary"><small>Legg til kategori </small></button>

                                </div>
                            </div>
                            <input type="hidden" name="id" value="{{ isset($dData->id) ? ($dData->id != 0 ) ? $dData->id : '' :''}}" />

                        </div>

                        {{ csrf_field() }}
                        <input type="hidden" name="type" value="1" />
                        <input type="hidden" name="template_id" value="{{ isset($dData->template_id) ? ($dData->template_id != 0 ) ? $dData->template_id : '' : ''}}" id="template_id" />

                        <div class="row pt-3" id="des">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label">Beskrivelse</label>
                                    <div class="s mb-5"></div>
                                    <input type="hidden" name="discription" id="disc">
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--/row-->

                    <div class="form-actions">
                        <button type="submit" class="btn btn-success"> <i class="fa fa-check"></i> Lagre</button>
                        <button type="button" class="btn btn-inverse">Avbryt</button>
                        @if(isset($dData->id) && $dData->id !=0)
                        <button type="button" data-toggle="tooltip" data-id="{{ isset($dData->id) ? $dData->id : '' }}" data-original-title="Delete" onclick="del(this)" class="deleteProduct btn btn-warning">Delete</button>
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
<script>
  
</script>

<!-- Row -->@endsection