@extends('templates.monster.main')

@push('before-styles')
<style>
    div#myTable_filter {
        display: none;
    }
</style>
<link rel="stylesheet" type="text/css" href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/datatables/media/css/dataTables.bootstrap4.css') }}">
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css" rel="stylesheet" type="text/css">
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/icheck/skins/all.css" rel="stylesheet">
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/daterangepicker/daterangepicker.css" rel="stylesheet">
<link rel="stylesheet" href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/dropify/dist/css/dropify.min.css">
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/wizard/steps.css" rel="stylesheet">


@endpush

@push('after-scripts')
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/icheck/icheck.min.js"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/icheck/icheck.init.js"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/dropify/dist/js/dropify.min.js"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/datatables/datatables.js') }}"></script>
<!-- start - This is for export functionality only -->
<script src="https://cdn.datatables.net/buttons/1.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.flash.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
<script src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js"></script>
<script src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.print.min.js"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/wizard/jquery.validate.min.js"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/wizard/jquery.steps.min.js"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/moment/moment.js"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/daterangepicker/daterangepicker.js"></script>
<!-- end - This is for export functionality only -->
<script>
    jQuery(document).ready(function($) {
        $('.dropify').dropify();
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        var zx;
        var startDate;
        var endDate;
        var table = $('#myTable').DataTable({
            "iDisplayLength": 50,
            processing: true,
            "bFilter": false,
            "bLengthChange": false,
            "language": {
                "infoFiltered": ""
            },
            serverSide: true,
            ajax: {
                url: "{{ url('/contactdata') }}",
                type: 'GET',
                data: function(d) {
                    d.made = zx;
                    d.formDate = startDate;
                    d.toDate = endDate;
                }
            },
            columns: [{
                    data: 'bname',
                    name: 'bname'
                }, {
                    data: 'category',
                    name: 'category'
                },
                {
                    data: 'cName',
                    name: 'cName'
                },
                {
                    data: 'cPhone',
                    name: 'cPhone'
                },
                {
                    data: 'cEpost',
                    name: 'cEpost'
                },
                {
                    data: 'attch',
                    name: 'attch'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                },
            ],

            initComplete: function() {
                this.api().columns().every(function(i) {
                    if (i == 1) {

                        var column = this;
                        var select = $('#catid')
                            // .appendTo( $(column.footer()).empty() )
                            .on('change', function() {
                                var valx = $(this).val();
                                if (valx == "") {
                                    return column.search("").draw();

                                }
                                var val = $.fn.dataTable.util.escapeRegex(
                                    $(this).val()
                                );

                                column
                                    .search(val ? '^' + val + '$' : '', true, false)
                                    .draw();
                            });

                        column.data().unique().sort().each(function(d, j) {

                            select.append('<option value="' + d + '">' + d + '</option>')
                        });

                    }
                });
            }
        });
        window.del = function(event) {
            var uid = $('.deleteProduct').data("id");
            if (uid != "") {

                swal({
                    title: "Advarsel",
                    text: "Are you sure want to delet!",
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
                            url: '{{ url("/delete/contact") }}',
                            data: {
                                'id': uid
                            },

                        }).done(function(msg) {
                            if (msg == 0) {
                                $('.preloader').hide();
                                event.closest('tr').remove();
                                swal("Deleted!", " ", "success").then(function() {});
                            } else {
                                $('.preloader').hide();
                                swal("error !", " ", "error").then(function() {
                                    // location.reload();
                                });
                            }
                        });

                    } else if (
                        // Read more about handling dismissals
                        result.dismiss === swal.DismissReason.cancel
                    ) {
                        swal("Avbrutt", "Ingen ting ble slettet!", "error");
                    }
                    $('.preloader').hide();

                    swal.closeModal();
                });

            }
        }

        $('.sMade').on('change', function(e) {
            var chk = e.target.checked;
            var val = e.target.value;
            if (chk == false) {
                zx = val;
                table.search(val).draw(true);
            } else {
                zx = '';
                table.search(val).draw(true);
            }
        });

        $("#srch").on('keyup click', function() {
            table.search($(this).val()).draw();
        });

        $('.daterange').daterangepicker();
        $('.daterange').on('apply.daterangepicker', function(ev, picker) {
            startDate = picker.startDate.format('YYYY-MM-DD');
            endDate = picker.endDate.format('YYYY-MM-DD');
            table.search('').draw();
        });
    });
</script>

@endpush

@section('content')

@include('common.errors')
@include('common.success')
<div class="row page-titles">
    <div class="col-md-6 col-8 align-self-center">
        <h3 class="text-themecolor mb-0 mt-0">Ansatte</h3>
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">Oversikt over foretakets ansatte</li>
        </ol>
    </div>
    <div class="col-md-6 col-4 align-self-center">
        {{--<button class="right-side-toggle waves-effect waves-light btn-info btn-circle btn-sm float-right ml-2"><i class="ti-settings text-white"></i></button>
       <a href="{{ url('foretak/kontakt/ny')}}" class="btn float-right hidden-sm-down btn-success"><i class="mdi mdi-plus-circle"></i>Legg til ny kontakt</a>
        --}}
        <a data-toggle="modal" data-target="#verticalcenter" class="btn float-right hidden-sm-down btn-success"><i class="mdi mdi-plus-circle"></i>Legg til ny Ansatte</a>
    </div>
</div>
{{--<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4>Innstillinger</h4>
                <hr>
                <br>
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Kategori</label>

                            <select name="category" id="catid" required class="form-control">
                                <option selected value=''>Alle</option>
                            </select>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>--}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <div class="table m-t-40">
                    <table id="myTable" class="table">
                        <thead>
                            <tr>
                                <th>Navn</th>
                                <th>Kategori</th>
                                <th>Kontaktperson</th>
                                <th>Telefonnummer</th>
                                <th>E-post</th>
                                <th>Antall vedlegg</th>
                                <th>Valg</th>

                            </tr>
                        </thead>
                        <tbody>


                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        <!-- sample modal content -->
        <div id="verticalcenter" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="vcenter" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="vcenter">Add Contact</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    </div>
                    <div class="modal-body">
                        <!----Form Data -->

                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body wizard-content ">
                                        <form action="#" class="needs-validation" novalidate enctype="multipart/form-data">
                                                <div class="row">

                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">Navn</label>
                                                            <input type="text" name="name" value="{{ (old('name') != '' ) ? old('name') : (isset($eData->name) ? $eData->name : '') }}" required class="form-control">
                                                            <input type="hidden" name="id" value="0" >
                                                            <small class="invalid-feedback"> Name is required </small>
                                                        </div>
                                                    </div>
                                                    <!--/span-->
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">Employed since</label>
                                                            <input type="date" required name='employed_since' class="form-control  form-control-danger" value="{{ (old('city') != '' ) ? old('city') : (isset($eData->city) ? $eData->city : '') }}">
                                                            <small class="invalid-feedback"> Date is required </small>
                                                        </div>
                                                    </div>
                                                    <!--/span-->
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">Telefonnummer</label>
                                                            <input type="text" required name="phone" class="form-control  form-control-danger" value="{{ (old('address') != '' ) ? old('address') : (isset($eData->address) ? $eData->address : '') }}">
                                                            <small class="invalid-feedback"> Telefonnummer required </small>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                           <label class="control-label">Rolle</label>
                                                            <select name="role" required class="form-control custom-select">
                                                                <option value="0">Select Rolle</option>
                                                                <option value="1">role-1</option>
                                                                <option value="2">role-2</option>
                                                            </select>
                                                            <small class="invalid-feedback"> Rolle required </small>
                                                        </div>
                                                    </div>
                                                </div>
                                                        <h4>Dependent Info</h4>
    <hr>
                                            <div class="row">
                                                    <div class="col-md-6">

                                                        <div class="form-group">
                                                            <label class="control-label">Navn pårørende</label>
                                                            <input type="text" required name="dependent_name" class="form-control  form-control-danger" value="{{ (old('dependent_name') != '' ) ? old('dependent_name') : (isset($eData->dependent_name) ? $eData->dependent_name : '') }}">
                                                            <small class="invalid-feedback"> Navn required </small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="control-label">Telefonnummer pårørende</label>
                                                            <input type="text" required name='dependent_phone' class="form-control  form-control-danger" value="{{ (old('dependent_phone') != '' ) ? old('dependent_phone') : (isset($eData->dependent_phone) ? $eData->dependent_phone : '') }}">
                                                            <small class="invalid-feedback"> Phone required </small>
                                                        </div>
                                                    </div>
                                                </div>

                                            <h4>Dokumenter</h4>
                                            <hr>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="form-group">
                                                            <label for="input-file-now-custom-3">Upload You document</label>
                                                            <input type="file" id="input-file-now-custom-3" class="dropify" data-height="100" />
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-actions">
                                                    <button type="submit" class="btn btn-success"> <i class="fa fa-check"></i> Save</button>
                                                    <button type="button" class="btn btn-dark">Cancel</button>
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

                <script>
                    function update() {
                        var datax = $('.needs-validation').serialize();
                        console.log(datax);
                        if (datax != "") {

                            // $('.preloader').show();
                            $.ajax({
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                type: 'POST',
                                url: '{{ url("/ansatte/add/employee") }}',
                                data: datax,
                            }).done(function(data) {
                                if (data.success == true) {
                                    $('.preloader').hide();
                                    swal(data.msg, " ", "success").then(function() {
                                        location.reload();
                                    });
                                } else {
                                    $('.preloader').hide();
                                    printErrorMsg(data.error);

                                }
                            });
                        }
                    }

                    function printErrorMsg(msg) {
                        $(".print-error-msg").find("ul").html('');
                        $(".print-error-msg").css('display', 'block');
                        $.each(msg, function(key, value) {
                            $(".print-error-msg").find("ul").append('<li>' + value + '</li>');
                        });
                    }

                    (function() {
                        'use strict';
                        window.addEventListener('load', function() {
                            // Fetch all the forms we want to apply custom Bootstrap validation styles to
                            var forms = document.getElementsByClassName('needs-validation');
                            // Loop over them and prevent submission
                            var validation = Array.prototype.filter.call(forms, function(form) {
                                form.addEventListener('submit', function(event) {
                                    if (form.checkValidity() === true) {
                                        event.preventDefault();

                                        update(form);
                                    }
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
                @endsection