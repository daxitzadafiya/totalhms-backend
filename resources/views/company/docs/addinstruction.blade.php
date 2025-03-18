@extends('templates.monster.main')
@push('after-styles')
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/css/select2.min.css" rel="stylesheet" type="text/css" />
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/summernote/dist/summernote-bs4.css" rel="stylesheet" />
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css" rel="stylesheet" type="text/css">


@endpush

@push('after-scripts')

<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/js/select2.full.min.js" type="text/javascript"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/monster/js/jasny-bootstrap.js"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/summernote/dist/summernote-bs4.min.js"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js"></script>


<script>
    jQuery(document).ready(function() {
        $(".s").summernote();
        $(".select2").select2();

        $('.sv').on('click', function(e) {
            e.preventDefault();
            var name = $('#catname').val();
            if (name != "") {
                $('.help').hide();
                $('.preloader').show();

                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    type: 'POST',
                    url: '{{ url("/add/category") }}',
                    data: {
                        'name': name ,"table":"instructions"
                    },

                }).done(function(msg) {
                    if (msg == 0) {
                        $('.preloader').hide();
                        $("#tooltipmodals").modal('hide');

                        swal("Saved!", " ", "success").then(function() {});
                            location.reload();
                    } else {
                        $('.preloader').hide();
                        swal("error !", " ", "error").then(function() {
                        });
                    }
                });


            } else {
                $('.help').html("Please add valid category")
                $('.help').css("color", "red")
            }
        });
    });
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
                <h4 class="mb-0 text-white">Innstrukser</h4>
            </div>
            <div class="card-body">

                <form class="needs-validation" novalidate method="POST" action="{{ url('/save/instruction') }}">
                    <div class="form-body">
                        <div class="row pt-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Navn</label>
                                    <input type="text" name="name" value="{{ (old('name') != '' ) ? old('name') : (isset($iData->name) ? $iData->name : '') }}" required class="form-control">
                                    <small class="invalid-feedback"> Name is required </small>

                                </div>
                            </div>
                            <!--/span-->


                            <!--/span-->
                            <div class="col-md-5">
                                <div class="form-group ">
                                    <label class="control-label">Kategori</label>
                                    @php
                                    $cat = \DB::table("all_category")->where("table_name","instructions")->get();
                                    @endphp
                                    @if($cat)
                                    <select name="category" required class="form-control">
                                        @foreach($cat as $data)
                                        <option value="{{ $data->id }}">{{ $data->des}}</option>
                                        @endforeach
                                    </select>


                                    @endif
                                    <small class="invalid-feedback"> Select a category </small>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group ">
                                    <label class="control-label">Add Kategori</label>
                                    <button type="button" data-toggle="modal" data-target="#tooltipmodals" class="btn btn-rounded btn-block btn-primary"><small>Click </small></button>

                                </div>
                            </div>
                            <input type="hidden" name="id" value="{{ isset($iData->id) ? ($iData->id != 0 ) ? $iData->id : '' :''}}" />
                            <!--/span-->
                        </div>
                        {{ csrf_field() }}
                        <div class="row pt-3">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label">Instruks</label>
                                    <div class="s mb-5">Click on Edite button and change the text then save it.</div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <!--/row-->


                    <!--/row-->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Activities </label>
                                <input type="text" required value="{{ (old('activities') != '' ) ? old('activities') : (isset($iData->activities) ? $iData->activities : '') }}" name="activities" class="form-control">
                                <small class="invalid-feedback"> Activities is required </small>

                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Ansvarlig ansatt</label>
                                <input type="text" required name="writtenby" value="{{ (old('writtenby') != '' ) ? old('writtenby') : (isset($iData->writtenby) ? $iData->writtenby : '') }}" class="form-control">
                                <small class="invalid-feedback"> Ansvarlig ansatt is required </small>

                            </div>
                        </div>
                        <!--/span-->

                    </div>
                    <div class="row">



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
<!-- sample modal content -->
<div id="tooltipmodals" class="modal fade " tabindex="-1" role="dialog" aria-labelledby="tooltipmodel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tooltipmodel">Add Category</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="form-group">
                        <label for="recipient-name" class="control-label">Category name:</label>
                        <input type="text" class="form-control" required id="catname">
                        <span class="help"></span>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn  sv btn-danger waves-effect waves-light">Save changes</button>
                <button type="button" class="btn btn-info danger-effect" data-dismiss="modal">Close</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->

<!-- Row -->@endsection