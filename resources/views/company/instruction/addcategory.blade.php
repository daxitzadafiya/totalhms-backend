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

        <a href="{{ url('/add/category')}}" class="btn float-right hidden-sm-down btn-success"><i class="mdi mdi-plus-circle"></i> Add Company</a>
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
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="control-label">Category Name</label>
                                <input type="text" name="name" value="{{ (old('name') != '' ) ? old('name') : (isset($cData->des) ? $cData->des : '') }}" required class="form-control name">
                                <small class="invalid-feedback name"> Name is required </small>
                                <input type="hidden" name="id" id="id" value="{{ isset($cData->id) ? $cData->id : 0}}" />
                            </div>
                        </div>
                    </div>
                </div>
                <a href="{{ url('foretak/categories')}}"  ><button  id="edit" class="btn btn-info btn-rounded"  type="button">Back</button></a>
                <button id="save" class="btn btn-success btn-rounded" onclick="save()" type="button">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- End PAge Content -->
<!-- ============================================================== -->
@endsection

@push('before-styles')
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css" rel="stylesheet" type="text/css">
@endpush

@push('after-scripts')
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js"></script>
<script>
    window.save = function() {
        var name = $('.name').val();
        var id = $('#id').val();
        if (name != "") {

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
                        url: '{{ url("/add/category") }}',
                        data: {
                            'name': name,
                            "table": "instructions",
                            "id":id
                        },

                    }).done(function(msg) {
                        if (msg == 0) {
                            $('.preloader').hide();
                            swal("Saved!", " ", "success").then(function() {

                            window.location("{{ url('foretak/categories') }}");
                            });
                        } else {
                            $('.preloader').hide();
                            swal("error !", " ", "error").then(function() {});
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

        }else{
                $(".name").show();
            }
    }
</script>

@endpush