@extends('templates.monster.main')

@push('before-styles')
<style>
    div#rutine_filter {
        display: none;
    }
    div#template_filter {
        display: none;
    }
    div#category_filter {
        display: none;
    }
</style>
<link rel="stylesheet" type="text/css" href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/datatables/media/css/dataTables.bootstrap4.css') }}">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css') }}" rel="stylesheet" type="text/css">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/daterangepicker/daterangepicker.css') }}" rel="stylesheet">

@endpush

@push('after-scripts')
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js') }}"></script>

<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/moment/moment.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/daterangepicker/daterangepicker.js') }}"></script>

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
<!-- end - This is for export functionality only -->
<script src="{{ asset('/filejs/company/routine/routinelist.js') }}"></script>

@endpush

@section('content')
<div class="row page-titles">
    <div class="col-md-6 col-8 align-self-center">
        <h3 class="text-themecolor mb-0 mt-0">Rutiner</h3>
    </div>
    <div class="col-md-6 col-4 align-self-center">
        <a href="{{ url('foretak/rutine/ny')}}" class="btn float-right hidden-sm-down btn-success"><i class="mdi mdi-plus-circle"></i>
        Opprett rutine</a>

    </div>
</div>
<ul class="nav  customtab" role="tablist">


    <li class="">
        <a class="nav-link   active  " data-toggle="tab" href="#tab4" role="tab">
            <span class="hidden-sm-up"> <i class="ti-home"></i></span>
            <span class="hidden-xs-down">Rutine</span>
        </a>
    </li>


    <li class="">
        <a class="nav-link   " data-toggle="tab" href="#tab5" role="tab">
            <span class="hidden-sm-up"> <i class="ti-home"></i></span>
            <span class="hidden-xs-down">Kategori</span>
        </a>
    </li>


    <li class="">
        <a class="nav-link   " data-toggle="tab" href="#tab6" role="tab">
            <span class="hidden-sm-up"> <i class="ti-home"></i></span>
            <span class="hidden-xs-down">Ressurser</span>
        </a>
    </li>


</ul>
<div class="tab-content  ">


    <div class="tab-pane  active  " id="tab4" role="tabpanel">

        <div class="card">
            <div class="card-body">
                <h4>Innstillinger</h4>
                <hr>
                <br>
                <div class="row">

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Kategori</label>

                            <select name="category" id="rcatid" required class="form-control">
                                <option selected value=''>Alle</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2.5">
                        <div class="form-group">
                            <label>Periode</label>
                            <div class='input-group mb-3'>
                                <input type='text' class="form-control daterange" />
                                <div class="input-group-append">
                                    <span class="input-group-text">
                                        <span class="ti-calendar"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Lagt til av</label>

                            <select id="sUser" required class="form-control">
                                <option selected value=''>Alle</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Søk i rutine</label>
                            <input type="text" class="form-control" name="srch" id="srch">
                        </div>
                    </div>
                    <div class="col-md-3.5">
                        <div class="form-group">
                            <label>Opprinnelse</label><br>
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-primary active">
                                    <input type="radio" name="options" value="" class="sMade" id="option1" autocomplete="off" checked> Alle
                                </label>
                                <label class="btn btn-primary">
                                    <input type="radio" name="options" value="2" class="sMade" id="option2" autocomplete="off">Intern</label>
                                <label class="btn btn-primary">
                                    <input type="radio" name="options" value="1" class="sMade" id="option3" autocomplete="off">System
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                    <table id="rutine" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Rutine</th>
                                <th>Kategori</th>
                                <th>Ansvarlige oppgaver</th>
                                <th>Sist endret</th>
                                {{--<th>Handling</th>--}}

                            </tr>
                        </thead>
                    </table>
            </div>
        </div>
    </div>
        <div class="tab-pane  " id="tab5" role="tabpanel">

            <div class="card">
                <div class="card-body">
                    <table id="category" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th>Antall Rutine</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(count($cData) == 0)
                            <tr>
                                <td colspan="3" align="center">{{ "No data Found  " }}</td>
                            </tr>
                            @else

                            @foreach($cData as $data)
                            <tr>
                                <td>
                                <a href="#" data-toggle="tooltip" data-id="{{ $data->id }}" data-name="{{ $data->des }}" data-original-title="Rediger" class="edit  editProduct">
                                {{ ucfirst($data->des) }}
                                </a>

                                </td>
                                <td> @php
                                    if($data->id){
                                    $insCount = \DB::table("routine")->where("category",$data->id)->where("companyId",\Auth::user()->companyId)->count();
                                    if($insCount){
                                    echo $insCount;
                                    }
                                    }
                                    @endphp</td>

                            </tr>
                            @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


<div class="tab-pane  " id="tab6" role="tabpanel">
    @if(count($tData) != 0)
        <div class="card">
            <div class="card-body">
                <h4>Innstillinger</h4>
                <hr>
                <br>
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Kategori</label>

                            <select name="category" id="tcatid" required class="form-control">
                                <option selected value=''>Alle</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Søk</label>
                            <input type="text" class="form-control" name="srch" id="malsrch">
                        </div>
                    </div>

                </div>
            </div>
        </div>
        @endif
        <div class="card">
            <div class="card-body">
                <div class="table-responsive m-t-40">
                    <table id="template" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Instruks</th>
                                <th>Kategori</th>
                                <!-- <th>Valg</th> -->
                            </tr>
                        </thead>
                        <tbody>
                            @if(count($tData) == 0)
                            <tr>
                                <td colspan="3" align="center">{{ "No data Found  " }}</td>
                            </tr>
                            @else

                            @foreach($tData as $data)
                            <tr>
                                <td><a href="/template/rutine/{{ $data->id }}" class="waves-effect waves-light ">{{ ucfirst($data->title) }}</a></td>
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
                                    <!-- <a href="/template/rutine/{{ $data->id }}" class="btn waves-effect waves-light btn-outline-secondary">Bruk Mal</button> -->
                                        @if(\Auth::user()->role == 1)
                                        <a style="padding-left: 1em;" href="/edit/template/{{ $data->id }}" data-toggle="tooltip" data-id="{{ $data->id }}" data-original-title="Edit" class="edit  editProduct"><i class="ti-pencil-alt"></i></a>
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

    </div>



</div>
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
                <button type="button" class="btn btn-info danger-warning" data-dismiss="modal">Lukk</button>
                <button type="button" class="btn btn-info danger-success del  deleteProduct"
                data-toggle="tooltip"  onclick="del();" data-original-title="Slett" >Slett</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->

@endsection

