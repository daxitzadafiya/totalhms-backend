@extends('templates.monster.main')


@section('content')

@push('before-styles')
<style>
div#myTable_filter {
    display: none;
}
</style>
<link rel="stylesheet" type="text/css" href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/datatables/media/css/dataTables.bootstrap4.css') }}">
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css" rel="stylesheet" type="text/css">

@endpush

@push('after-scripts')
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js"></script>

<!-- This is data table -->
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/datatables/datatables.js') }}"></script>
<!-- start - This is for export functionality only -->
<script src="https://cdn.datatables.net/buttons/1.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.flash.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
<script src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/pdfmake.min.js"></script>
<script src="https://cdn.rawgit.com/bpampuch/pdfmake/0.1.18/build/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {

    var table = $('#myTable').DataTable( {
        "iDisplayLength": 50,
        "bLengthChange": false,
        "language": {                
            "infoFiltered": ""
        },
        initComplete: function () {
            this.api().columns().every( function (i) {
                if (i == 1 )
                {
                var column = this;
                var select = $('#catid')
                   // .appendTo( $(column.footer()).empty() )
                    .on( 'change', function () {
                        var valx= $(this).val();
                        if(valx ==""){
                            return   column.search("").draw();
                        }
                        var val = $.fn.dataTable.util.escapeRegex(
                            $(this).val()
                        );

                        column
                            .search( val ? '^'+val+'$' : '', true, false )
                            .draw();
                    } );

                column.data().unique().sort().each( function ( d, j ) {
                    select.append( '<option value="'+d+'">'+d+'</option>' )
                } );
            }
            } );
        }
    } );

} );
</script>
@endpush
@include('common.errors')
@include('common.success')
<div class="row page-titles">
    <div class="col-md-6 col-8 align-self-center">
        <h3 class="text-themecolor mb-0 mt-0">Tilgjenglige maler</h3>
    </div>
    <div class="col-md-6 col-4 align-self-center">
        <a href="{{ url('foretak/innstrukser') }}" style="margin-left:20px;float: right;"  class="btn waves-effect waves-light btn-outline-primary pull-left">Back</a>
            @if(\Auth::user()->role ==1)
        <a href="{{ url('/add/template')}}" class="btn float-right hidden-sm-down btn-success"><i class="mdi mdi-plus-circle"></i> Add Template</a>
        @endif

    </div>

</div>

<div class="row">
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
</div>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive m-t-40">
                    <table id="myTable" class="table">
                        <thead>
                            <tr>
                                <th>Instruks</th>
                                <th>Kategori</th>
                                <th>Valg</th>
                            </tr>
                        </thead>
                        <tbody>
                                @if(count($cData) == 0)
                            <tr> <td colspan="3" align="center">{{ "No data Found  " }}</td></tr>
                            @else

                            @foreach($cData as $data)
                                <tr>
                                    <td>{{ ucfirst($data->title) }}</td>
                                    <td>
                                            @php
                                            if($data->category_id){
                                                $inId = explode(',',$data->category_id);
                                                $catname = \DB::table("all_category")->select("des")->where("id",$data->category_id)->first();

                                            }
                                        @endphp
                                    {{ isset($catname->des) ? $catname->des : ""}}
                                    </td>
                                    <td>
                                            <a  href="/template/instruk/{{ $data->id }}" class="btn waves-effect waves-light btn-outline-secondary">Bruk Mal</button> 
                                    @if(\Auth::user()->role == 1)  
                                    <a style="padding-left: 1em;"href="/edit/template/{{ $data->id }}" data-toggle="tooltip" data-id="{{ $data->id }}" data-original-title="Edit" class="edit  editProduct"><i class="ti-pencil-alt"></i></a>
                                         <a href="javascript:void(0)" data-toggle="tooltip" data-id="{{ $data->id }}" onclick="del();" data-original-title="Delete" class="del  deleteProduct"><i class="ti-trash"></i></a>
                                    @endif
                                </td>
                                </tr>
                            @endforeach
                            @endif



                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        @endsection

@push('before-styles')
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css" rel="stylesheet" type="text/css">
@endpush

@push('after-scripts')
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js"></script>
<script>
   jQuery(document).ready(function($) {

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
                        url: '{{ url("/delete/template") }}',
                        data: {
                            'id': uid
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
});
</script>

@endpush
