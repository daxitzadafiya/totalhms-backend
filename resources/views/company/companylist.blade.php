@extends('templates.monster.main')

@push('before-styles')

<link rel="stylesheet" type="text/css" href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/datatables/media/css/dataTables.bootstrap4.css') }}">
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css" rel="stylesheet" type="text/css">

@endpush

@push('after-scripts')

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
            ajax: "{{ url('/admin/comapnyData') }}",
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
                    data: 'sector_code',
                    name: 'sector_code'
                },
                {
                    data: 'ceo',
                    name: 'ceo'
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
</script>

@endpush

@section('content')
<div class="row page-titles">
    <div class="col-md-6 col-8 align-self-center">
        <h3 class="text-themecolor mb-0 mt-0"> Mitt foretak</h3>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="javascript:void(0)">Company</a></li>
            <li class="breadcrumb-item active">Mitt foretak</li>
        </ol>
    </div>
    <div class="col-md-6 col-4 align-self-center">
        {{--<button class="right-side-toggle waves-effect waves-light btn-info btn-circle btn-sm float-right ml-2"><i class="ti-settings text-white"></i></button>
        --}}
        <a href="{{ url('/admin/add/company')}}" class="btn float-right hidden-sm-down btn-success"><i class="mdi mdi-plus-circle"></i> Add Company</a>

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
                                <th>Sector Code</th>
                                <th>Ceo</th>
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