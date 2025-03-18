@extends('templates.monster.main')

@section('content')


@include('common.errors')
@include('common.success')
<div class="row page-titles">
    <div class="col-md-6 col-8 align-self-center">
        <h3 class="text-themecolor mb-0 mt-0">  HMS-Erklæring</h3>
       
    </div>
    <div class="col-md-6 col-4 align-self-center">
       {{--<button class="right-side-toggle waves-effect waves-light btn-info btn-circle btn-sm float-right ml-2"><i class="ti-settings text-white"></i></button>

        <a href="{{ url('/admin/add/company')}}"  class="btn float-right hidden-sm-down btn-success"><i class="mdi mdi-plus-circle"></i> Add Company</a>
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
                <div class="click2edit mb-5">Click on Edite button and change the text then save it.</div>
                <button id="edit" class="btn btn-info btn-rounded" onclick="edit()" type="button">Edit</button>
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

<!-- summernotes CSS -->
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/summernote/dist/summernote-bs4.css" rel="stylesheet" />
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css" rel="stylesheet" type="text/css">


@endpush

@push('after-scripts')
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js"></script>

<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/summernote/dist/summernote-bs4.min.js"></script>
<script>
    jQuery(document).ready(function() {
        $('.click2edit').summernote('code', {!! isset($content->content) ? json_encode($content->content) : ''   !!});
        $(".click2edit").summernote('destroy');

        $('.summernote').summernote({
            height: 350, // set editor height
            minHeight: null, // set minimum height of editor
            maxHeight: null, // set maximum height of editor
            focus: false // set focus to editable area after initializing summernote
        });

        $('.inline-editor').summernote({
            airMode: true
        });

    });

    window.edit = function() {
        $(".click2edit").summernote()
    },
        window.save = function() {
            var textareaValue = $('.click2edit').summernote('code');
            $(".click2edit").summernote('destroy');
            if(textareaValue != ""){
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
                                url: '{{ url("/add/hse") }}',
                                data: { 'text':textareaValue } ,

                                }).done(function( msg ) {
                                    if(msg == 0 ){
                                            $('.preloader').hide();
                                            swal("Saved!"," ", "success").then(function (){
                                            });
                                    }else{
                                        $('.preloader').hide();
                                            swal("error !"," ", "error").then(function (){
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
</script>

@endpush