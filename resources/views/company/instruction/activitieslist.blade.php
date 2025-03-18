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
            if (i == 0 ){
                var column = this;
                var select = $('#catid')
                   // .appendTo( $(column.footer()).empty() )
                    .on( 'change', function () {
                        var valx= $(this).val();
                        if(valx ==""){
                            return column.search("").draw();

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
            else if(i == 1){
                var column = this;
                var select = $('#res')
                   // .appendTo( $(column.footer()).empty() )
                    .on( 'change', function () {
                        var valx= $(this).val();
                        if(valx ==""){
                            return column.search("").draw();

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
            else if(i == 2){
                var column = this;
                var select = $('#ins')
                   // .appendTo( $(column.footer()).empty() )
                    .on( 'change', function () {
                        var valx= $(this).val();
                        if(valx ==""){
                            return         column.search("").draw();

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

    $("#srch").on('keyup click', function() {
        table.search($(this).val()).draw();
    });

} );
</script>
@endpush
@include('common.errors')
@include('common.success')
<div class="row page-titles">
    <div class="col-md-6 col-8 align-self-center">
        <h3 class="text-themecolor mb-0 mt-0">Oversikt over aktiviteter</h3>
    </div>
    <div class="col-md-6 col-8 align-self-center">
            <a href="{{ url('foretak/innstrukser') }}" style="float:right;" class="btn waves-effect waves-light btn-outline-primary ">Back</a>
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
                                        @php
                                        $cat = \DB::table("all_category")->where("table_name","instructions")->where("companyId",\Auth::user()->companyId)->get();
                                        $cate = [];
                                        if(isset($iData->category)){
                                            $cate = explode("," , $iData->category);
                                        }
                                        @endphp
                                        @if($cat)
                                        <select name="category" id="catid" required class="form-control">
                                            <option selected value=''>Alle</option>

                                        </select>


                                        @endif
                                    </div>
                        </div>
                        <div class="col-md-4">
                                <div class="form-group">
                                        <label>Instruks</label>
                                        <select class="custom-select col-12" id="ins">
                                            <option selected value="">Alle</option>
                                        </select>
                                    </div>
                        </div>
                        <div class="col-md-2">
                                <div class="form-group">
                                        <label>Ansvarlig</label>
                                        <select class="custom-select col-12" id="res">
                                            <option value="" selected >Alle</option>
                                        </select>
                                    </div>
                        </div>
                        <div class="col-md-2">
                                <div class="form-group">
                                        <label>Søk etter aktivitet</label>
                                        <input type="text" class="form-control" name="srch" id="srch">
                                    </div>
                        </div>
                        <div class="col-md-4">
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
                                <th>Aktivitet</th>
                                <th>Ansvarlig</th>
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
                                    <td>{{ ucfirst($data->des) }}</td>
                                    <td>
                                        @php
                                            if($data->id){
                                                $inId = explode(',',$data->id);
                                                $uname = \DB::table("users")->select("name")->where("id",$data->responsible_person)->where("companyId",\Auth::user()->companyId)->first();
                                                $catname = \DB::table("all_category")->select("des")->where("id",$data->category_id)->first();

                        $insname = \DB::table("instructions")->select("name")->whereRaw("FIND_IN_SET($data->id,activities)")->where("companyId",\Auth::user()->companyId)->first();
                                      // $insname = \DB::DB:("SELECT name FROM `instructions` WHERE find_in_set ($data->id,activities)");
                                            }
                                        @endphp
                                    {{ isset($uname->name) ? ucfirst($uname->name) : ""}}
                                    </td>
                                    <td>
                                        {{ isset($insname->name) ? ucfirst($insname->name) : ""}}

                                    </td>
                                    <td>
                                        {{ isset($catname->des) ? ucfirst($catname->des) : ""}}
                                    </td>
                                    <td>
                                   {{--<a href="/view/activity/{{ $data->id }}" data-toggle="tooltip" data-id="{{ $data->id }}" data-original-title="View" class="view  editProduct"><i class="ti-eye"></i></a>--}}
                                    <a href="/edit/activity/{{ $data->id }}" data-toggle="tooltip" data-id="{{ $data->id }}" data-original-title="Rediger" class="edit  editProduct"><i class="ti-pencil-alt"></i></a>
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

