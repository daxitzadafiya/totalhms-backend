@extends('templates.monster.main')


@push('before-styles')

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
            $('#myTable').DataTable( {
                "iDisplayLength": 50,
                "bFilter" : false,
            "bLengthChange": false,
            "language": {
            "infoFiltered": ""
        },
                initComplete: function () {
                    this.api().columns().every( function () {
                        var column = this;
                        var select = $('<select><option value=""></option></select>')
                            .appendTo( $(column.footer()).empty() )
                            .on( 'change', function () {
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
                    } );
                }
            } );
        } );


        </script>
@endpush
@section('content')

@include('common.errors')
@include('common.success')
<div class="row page-titles">
    <div class="col-md-6 col-8 align-self-center">
        <h3 class="text-themecolor mb-0 mt-0"> Kategorier</h3>

    </div>
    <div class="col-md-6 col-8 align-self-center">
    <a href="{{ url('foretak/innstrukser') }}" style="margin-left:20px;float: right;" class="btn waves-effect waves-light btn-outline-primary pull-left">Back</a>
        <button  data-toggle="modal" data-target="#tooltipmodals" class="btn float-right hidden-sm-down btn-success"><i class="mdi mdi-plus-circle"></i>Legg til Kategori</button>

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
                                <th>Kategori</th>
                                <th>Antall instrukser</th>
                                <th>Valg</th>
                            </tr>
                        </thead>
                        <tbody>
                                @if(count($cData) == 0)
                            <tr> <td colspan="3" align="center">{{ "No data Found  " }}</td></tr>
                            @else

                            @foreach($cData as $data)
                                <tr>
                                    <td>{{ ucfirst($data->des) }}</td>
                        <td>            @php
                                            if($data->id){
                                                $insCount = \DB::table("instructions")->where("category",$data->id)->where("companyId",\Auth::user()->companyId)->count();
                                                if($insCount){
                                                    echo $insCount;
                                                }
                                            }
                                        @endphp</td>
                                    <td>
                                 {{--<a href="/view/category/{{ $data->id }}" data-toggle="tooltip" data-id="{{ $data->id }}" data-original-title="View" class="view  editProduct"><i class="ti-eye"></i></a>--}}
                                    <a href="#" data-toggle="tooltip" data-id="{{ $data->id }}" data-name ="{{ $data->des }}" data-original-title="Rediger" class="edit  editProduct"><i class="ti-pencil-alt"></i></a>
                                    <a style="padding-left: 1em;" href="javascript:void(0)" data-toggle="tooltip" data-id="{{ $data->id }}" onclick="del();" data-original-title="Slett" class="del  deleteProduct"><i class="ti-trash"></i></a>
                                     </td>
                                </tr>
                            @endforeach
                            @endif



                        </tbody>
                    </table>
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
                        <label for="recipient-name" class="control-label">Kategori Navn:</label>
                        <input type="text" class="form-control" required id="catname">
                        <input type="hidden" class="form-control" value=0 required id="catid">
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

        @endsection


@push('after-scripts')


@endpush
