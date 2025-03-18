@extends('templates.monster.main')

@section('content')


@include('common.errors')
@include('common.success')
<div class="row page-titles">
    <div class="col-md-6 col-8 align-self-center">
        <h3 class="text-themecolor mb-0 mt-0">Template</h3>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="javascript:void(0)">Company</a></li>
            <li class="breadcrumb-item active">Instruction Template</li>
        </ol>
    </div>
    <div class="col-md-6 col-4 align-self-center">
        {{--<button class="right-side-toggle waves-effect waves-light btn-info btn-circle btn-sm float-right ml-2"><i class="ti-settings text-white"></i></button>

        <a href="{{ url('/add/template')}}" class="btn float-right hidden-sm-down btn-success"><i class="mdi mdi-plus-circle"></i> Add Company</a>
        --}}
    </div>
</div>
<!-- ============================================================== -->
<!-- Start Page Content -->
<!-- ============================================================== -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body"> 
                    <div class="form-body">
                            <div class="row pt-3">
                                <div class="col-md-6">
                                        <div class="form-group">
                                                <label class="control-label">Title</label>
                                                <small class="invalid-feedback"> Name is required </small>
                                                <input type="text" name="title" value="{{ (old('title') != '' ) ? old('title') : (isset($tData->title) ? $tData->title : '') }}" required class="form-control title">
                                                <small class="invalid-feedback"> Name is required </small>
                                                <input type="hidden" name="id" id="id" value="{{ isset($tData->id) ? $tData->id : 0}}" />
                                            </div>
                                </div>
                                <!--/span-->
    
    
                                <!--/span-->
                                <div class="col-md-5">
                                    <div class="form-group ">
                                        <label class="control-label">Category</label>
    
                                        @php
                                        $cat = \DB::table("all_category")->where("table_name","instructions")->where("companyId",\Auth::user()->companyId)->get();
                                        $cate = [];
                                        if(isset($iData->category)){
                                            $cate = explode("," , $iData->category);
                                        }
                                        @endphp
                                        @if($cat)
                                        <select name="category" id="catid" required class="form-control">
                                            <option disabled selected value='0'>Select Category</option>
                                            @foreach($cat as $data)
                                            <option {{ (isset($data->id) && in_array($data->id,$cate) ? 'selected' : '') }} value="{{ $data->id }}">{{ $data->des}}</option>
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
                            </div>
              

                <div class="summernote mb-5">Click on Edite button and change the text then save it.</div>
                <button id="edit" class="btn btn-info btn-rounded" onclick="edit()" type="button">Edit</button>
                <button id="save" class="btn btn-success btn-rounded" onclick="save()" type="button">Save</button>
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
<!-- ============================================================== -->
<!-- End PAge Content -->
<!-- ============================================================== -->
@endsection

@push('before-styles')

<!-- summernotes CSS -->
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/summernote/dist/summernote-bs4.css" rel="stylesheet" />
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css" rel="stylesheet" type="text/css">


@endpush

@push('after-scripts')
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js"></script>

<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/summernote/dist/summernote-bs4.min.js"></script>
<script>
    jQuery(document).ready(function() {
        $('.summernote').summernote({
            minHeight: 350, // set minimum height of editor
            maxHeight: 500, // set maximum height of editor
            focus: false ,
            });
        $('.summernote').summernote('code', '{!! isset($tData->des) ? htmlspecialchars_decode($tData->des) : '' !!}');

        $('.inline-editor').summernote({
            airMode: true
        });

    });


    window.save = function() {
        var textareaValue = $('.summernote').summernote('code');
        var title = $('.title').val();
        var id = $('#id').val();
        var catid = $('#catid').val();
        if (textareaValue != "") {

            swal({
                title: "Advarsel",
                text: "Vil du Save Changes?!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Ja, Save!",
                cancelButtonText: "Avbryt",
            }).then(result => {
                // swal("Slettet!", "Sletting utført.", "success");
                 $('.preloader').show();

                if (result.value) {
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: 'POST',
                        url: '{{ url("/add/template") }}',
                        data: {
                            'text': textareaValue,
                            'title': title,
                            'id':id,
                            "catid":catid
                        },

                    }).done(function(msg) {
                        if (msg == 0) {
                            $('.preloader').hide();
                            swal("Saved!", " ", "success").then(function() {
                                window.location.href ='{{ url("foretak/templates")}}'
                            });
                        } else {
                            $('.preloader').hide();
                            swal("error !", " ", "error").then(function() {
                                location.reload();
                            });
                        }
                    });

                } else if (
                    // Read more about handling dismissals
                    result.dismiss === swal.DismissReason.cancel
                ) {
                    swal("Avbrutt", "Ingen ting ble slettet!", "error");
                }
                swal.closeModal();
            });

        }
    }
    
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
                        'name': name,
                        "table": "instructions"
                    },

                }).done(function(msg) {
                    if (msg == 0) {
                        $('.preloader').hide();
                        $("#tooltipmodals").modal('hide');

                        swal("Saved!", " ", "success").then(function() {});
                        location.reload();
                    } else {
                        $('.preloader').hide();
                        swal("error !", " ", "error").then(function() {});
                    }
                });


            } else {
                $('.help').html("Please add valid category")
                $('.help').css("color", "red")
            }
        });
</script>

@endpush