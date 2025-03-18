@extends('templates.monster.main')
@push('after-styles')
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/css/select2.min.css" rel="stylesheet" type="text/css" />
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/summernote/dist/summernote-bs4.css" rel="stylesheet" />
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css" rel="stylesheet" type="text/css">
<style>
.note-toolbar.card-header {
    background-color: bisque;
}
</style>

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
                        'name': name ,"table":"contacts"
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
                <h4 class="mb-0 text-white">Legg til ny kontakt</h4>
            </div>
            <div class="card-body">

            </div>
        </div>
    </div>
</div>
<!-- sample modal content -->
<!-- Row -->@endsection