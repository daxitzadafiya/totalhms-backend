@extends('templates.monster.main')

@push('after-styles')

<link rel="stylesheet" type="text/css" href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/datatables/media/css/dataTables.bootstrap4.css') }}">
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css" rel="stylesheet" type="text/css">
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/bootstrap-switch/bootstrap-switch.min.css" rel="stylesheet">

@endpush

@push('after-scripts')
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/bootstrap-switch/bootstrap-switch.min.js"></script>

<!-- This is data table -->
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js"></script>

<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/datatables/datatables.min.js') }}"></script>
<!-- start - This is for export functionality only -->
<script src="https://cdn.datatables.net/buttons/1.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.flash.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
<script src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js"></script>
<script src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.print.min.js"></script>
<script type="text/javascript">
    $(".bt-switch input[type='checkbox'], .bt-switch input[type='radio']").bootstrapSwitch();
    var radioswitch = function() {
        var bt = function() {
            $(".radio-switch").on("switch-change", function() {
                $(".radio-switch").bootstrapSwitch("toggleRadioState")
            }), $(".radio-switch").on("switch-change", function() {
                $(".radio-switch").bootstrapSwitch("toggleRadioStateAllowUncheck")
            }), $(".radio-switch").on("switch-change", function() {
                $(".radio-switch").bootstrapSwitch("toggleRadioStateAllowUncheck", !1)
            })
        };
        return {
            init: function() {
                bt()
            }
        }
    }();
    $(document).ready(function() {
        radioswitch.init()
    });
</script>
<!-- end - This is for export functionality only -->
<script>
    jQuery(document).ready(function($) {

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $('#myTable').DataTable({
            processing: false,
            serverSide: false,
            ajax: "{{ url('/admin/comapnyRequestData') }}",
            columns: [{
                    data: 'id',
                    name: 'id'
                },
                {
                    data: 'company_name',
                    name: 'company_name'
                },
                {
                    data: 'vat_number',
                    name: 'vat_number'
                },
                {
                    data: 'industry',
                    name: 'industry'
                },
                {
                    data: 'address',
                    name: 'address'
                },
                {
                    data: 'created_at',
                    name: 'created_at'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                },
            ]
        });

    });
    window.del = function(event) {
        var cid = $('.deleteProduct').data("id");
        if (cid != "") {

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
                        url: '{{ url("admin/del/company") }}',
                        data: {
                            'id': cid
                        },

                    }).done(function(msg) {
                        if (msg == 0) {
                            $('.preloader').hide();
                            event.closest('tr').remove();
                            swal("Saved!", " ", "success").then(function() {});
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
    window.approve = function(event) {
        var cid = $('.app').data("id");
            var status = $('.app').val();
        console.log(status);
        if (cid != "") {

            swal({
                title: "Advarsel",
                text: "Are you sure want to Approve this company!",
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
                        url: '{{ url("admin/company/approve") }}',
                        data: {
                            'id': cid,
                            'status':status
                        },

                    }).done(function(msg) {
                        if (msg == 0) {
                            $('.preloader').hide();
                            //event.closest('tr').remove();
                            swal("Saved!", " ", "success").then(function() {});
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
</script>

@endpush

@section('content')
<div class="row page-titles">
    <div class="col-md-6 col-8 align-self-center">
        <h3 class="text-themecolor mb-0 mt-0"> Mitt foretak</h3>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="javascript:void(0)">Company</a></li>
            <li class="breadcrumb-item active">Request Company</li>
        </ol>
    </div>

</div>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive m-t-40">
                    <table id="myTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Id</th>
                                <th>Company name</th>
                                <th>Vat No.</th>
                                <th>Industry</th>
                                <th>Address</th>
                                <th>Registreringsdato</th>
                                <th>Handling</th>

                            </tr>
                        </thead>
                        <tbody>


                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        @endsection