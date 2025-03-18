@extends('templates.monster.main')

@push('before-styles')
<style>
div#doctable_filter {
    display: none;
}

div#template_filter {
    display: none;
}

div#category_filter {
    display: none;
}
</style>
<link rel="stylesheet" type="text/css"
    href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/datatables/media/css/dataTables.bootstrap4.css') }}">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css') }}"
    rel="stylesheet" type="text/css">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/daterangepicker/daterangepicker.css') }}"
    rel="stylesheet">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/summernote/dist/summernote-bs4.css') }}"
    rel="stylesheet" />
<link rel="stylesheet"
    href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/dropify/dist/css/dropify.min.css') }}">
@endpush

@push('after-scripts')
<!-- new script
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>                 -->
<!-- old scripts -->
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/dropify/dist/js/dropify.min.js') }}">
</script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/summernote/dist/summernote-bs4.min.js') }}">
</script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/moment/moment.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/daterangepicker/daterangepicker.js') }}">
</script>

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
<script src="{{ asset('/filejs/document/documentlist.js') }}"></script>
<!-- end - This is for export functionality only -->
@endpush

@section('content')
@include('common.errors')
@include('common.success')

<div class="row page-titles">
    <div class="col-md-6 col-8 align-self-center">
        <h3 class="text-themecolor mb-0 mt-0">Dokumenter</h3>
    </div>

</div>
<ul class="nav  customtab" role="tablist">


    <li class="">
        <a class="nav-link   active  " data-toggle="tab" href="#tab4" role="tab">
            <span class="hidden-sm-up"> <i class="ti-home"></i></span>
            <span class="hidden-xs-down">Dokumenter</span>
        </a>
    </li>

    <li class="">
        <a class="nav-link   " data-toggle="tab" href="#tab6" role="tab">
            <span class="hidden-sm-up"> <i class="ti-home"></i></span>
            <span class="hidden-xs-down">Ressurser</span>
        </a>
    </li>
    <li class="">
        <a class="nav-link   " data-toggle="tab" href="#tab5" role="tab">
            <span class="hidden-sm-up"> <i class="ti-home"></i></span>
            <span class="hidden-xs-down">Kategori</span>
        </a>
    </li>
</ul>
<div class="tab-content ">
    <div class="tab-pane  active  " id="tab4" role="tabpanel">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 col-8 align-self-center">
                        <h4>Innstillinger</h4>
                    </div>
                    <div class="col-md-6 col-4 align-self-center">
                        <a href="#" class="btn float-right hidden-sm-down btn-success template_btntwo"
                            data-toggle="modal" data-id="7"><i class="mdi mdi-plus-circle"></i>
                            Opprett/last opp</a>

                    </div>
                </div>
                <hr>
                <br>
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Kategori</label>

                            <select name="category" id="dcatid" required class="form-control">
                                <option selected value=''>Alle</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Format</label>

                            <select name="category" id="dcat" required class="form-control">
                                <option selected value=''>Alle</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Opprinnelse</label><br>

                            <div class="btn-group d-block w-100" data-toggle="buttons">
                                <div class="d-flex justify-content-between">
                                    <label class="btn btn-primary active " style="font-size: 12px; width: 28%;"
                                        id="hmade3">
                                        <input type="radio" name="options" value="" class="sMade" id="option1"
                                            autocomplete="off"> Alle
                                    </label>
                                    <label class="btn btn-primary active ml-3" style="font-size: 12px; width: 28%;"
                                        id="showMade">
                                        <input type="radio" name="options" value="" readonly="readonly" class="sMade"
                                            id="option1" autocomplete="off"> Mal
                                    </label>

                                    <label class="btn btn-primary ml-3" style="font-size: 12px;" id="hmade4">
                                        <input type="radio" name="options" value="Egendefinert" class="sMade"
                                            id="option2" autocomplete="off">Egendefinert</label>
                                </div>
                                <!-- <label class="btn btn-primary" style="font-size: 12px;">
                                            <input type="radio" name="options" value="Egendefinert" class="sMade" id="option3" autocomplete="off"> Egendefinert
                                        </label> -->
                                <div class="mt-2 d-flex justify-content-between">
                                    <label class="btn btn-primary  w-50" style="font-size: 12px; display:none; "
                                        id="hmade">
                                        <input type="radio" name="options" value="SystemTemp" class="sMade" id="option2"
                                            autocomplete="off">System </label>

                                    <label class="btn btn-primary ml-3 w-50 " style="font-size: 12px; display:none; "
                                        id="hmade2">
                                        <input type="radio" name="options" value="Egendefinert" class="sMade"
                                            id="option2" autocomplete="off" display="hidden">Egendefinert</label>
                                </div>
                            </div>


                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Eier</label>

                            <select name="category" id="dcati" required class="form-control">
                                <option selected value=''>Alle</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2 ml-auto">
                        <div class="form-group">
                            <label>Søk i Dokumenter</label>
                            <input type="text" class="form-control" name="srch" id="srch">

                        </div>
                    </div>



                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <table id="doctable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Navn</th>
                            <th>Kategori</th>
                            <th>Format</th>
                            <th>Opprinnelse</th>
                            <th>Eier</th>
                            <th>Sist oppdatert</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($dData)
                        @foreach($dData as $data)
                        <tr>
                            <td>
                                @if($data->type ==2)
                                <a href="#" data-toggle="modal" data-id="{{ $data->id }}" data-name="{{ $data->name }}"
                                    data-target="#pre{{ $data->id }}" class="editProducts">
                                    {{ ucfirst($data->name) }}
                                </a>
                                <div id="pre{{ $data->id }}" class="modal fade bs-example-modal-lg" tabindex="-1"
                                    role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true"
                                    style="display: none;">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content" style="height:800px;">
                                            <div class="modal-header">
                                                <h4 class="modal-title" id="myLargeModalLabel">
                                                    {{ ucfirst($data->name) }}</h4>
                                                <button type="button" class="close" data-dismiss="modal"
                                                    aria-hidden="true">×</button>
                                            </div>
                                            <div class="modal-body">
                                                <iframe width="100%" height="90%"
                                                    src="{{ asset('file_uploader/company/'.\Auth::user()->companyId. '/document/'.$data->doc) }}"></iframe>
                                                <div>
                                                    <small>Created By</small>
                                                    {{ \Helper::get_user_name($data->addedby) }} <br>
                                                    <small>Updated Date</small>
                                                    {{ date("d-m-Y",strtotime($data->updated_at)) }}


                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <a href="{{ url('download/document/'.$data->doc) }}"
                                                    data-id="{{ $data->id }}" data-path="{{ $data->doc }}"
                                                    class="btn btn-success">Print</a>
                                                <button type="button" data-id="{{ $data->id }}"
                                                    class="btn btn-danger waves-effect text-left delete">Delete</button>
                                                <button type="button" class="btn btn-dark"
                                                    data-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                        <!-- /.modal-content -->
                                    </div>
                                    <!-- /.modal-dialog -->
                                </div>
                                @else

                                <a href="#" data-id="{{ $data->id }}" data-tempid="{{ $data->template_id}}"
                                    data-des=" {{ htmlspecialchars_decode($data->discription) }}"
                                    data-name="{{ $data->name }}" data-category="{{ $data->category }}"
                                    class="dwn editProduct">
                                    {{ ucfirst($data->name) }}
                                </a>

                                @endif
                            </td>
                            <td>
                                <?php $cat_name = \DB::table("all_category")->where("id", $data->category)->first();
                                if ($cat_name) {
                                    echo $cat_name->des;
                                }
                                ?>
                            </td>
                            <td><?php
                                if ($data->type == 1) {
                                    echo "Dokument";
                                } else {
                                    echo "Fil";
                                } ?>
                            </td>
                            <td><?php
                                if ($data->origin ==1) {
                                    echo "Fil: Systemmal";
                                } elseif ($data->origin ==2) {
                                    echo " Fil: Egendefinert Mal";
                                } elseif ($data->origin ==3) {
                                    echo " Fil: Egendefinert";
                                } elseif ($data->origin ==4) {
                                    echo " Dokument: Systemmal";
                                } elseif ($data->origin ==5) {
                                    echo " Dokument: Egendefinert Mal";
                                } elseif ($data->origin ==6) {
                                    echo " Dokument:  Egendefinert";
                                }
                                
                            ?> </td>
                            <td>{{ \Helper::get_user_name($data->addedby) }}</td>
                            <td>{{ date("d-m-Y",strtotime($data->updated_at)) }}</td>
                        </tr>
                        @endforeach
                        @else<tr>
                            <td colspan="4" align="center">{{ "No data Found  " }}</td>
                        </tr>
                        @endif


                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <div class="tab-pane  " id="tab5" role="tabpanel">

        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 col-8 align-self-center">
                        <h4>Category</h4>
                    </div>
                    <div class="col-md-6 col-8 align-self-center">
                        <a href="#" data-toggle="modal" data-target="#tooltipmodals"
                            class="btn float-right hidden-sm-down btn-success"><i class="mdi mdi-plus-circle"></i>
                            Legg til kategori</a>
                    </div>
                </div>
                <hr>
                <table id="category" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th>Antall Document</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(count($cData) == 0)
                        <tr>
                            <td colspan="2" align="center">{{ "No data Found  " }}</td>
                        </tr>
                        @else

                        @foreach($cData as $data)
                        <tr>
                            <td>
                                <a href="#" data-toggle="tooltip" data-id="{{ $data->id }}" data-name="{{ $data->des }}"
                                    data-original-title="Rediger" class="edit  editProduct">
                                    {{ ucfirst($data->des) }}
                                </a>

                            </td>
                            <td> @php
                                if($data->id){
                                $insCount =
                                \DB::table("companydocuments")->where("category",$data->id)->where("companyId",\Auth::user()->companyId)->count();
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
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 col-8 align-self-center">
                        <h4>Innstillinger</h4>
                    </div>
                    <div class="col-md-6 col-4 align-self-center">
                        <a href="#" class="btn float-right hidden-sm-down btn-success template_btn" data-toggle="modal"
                            data-id="89"><i class="mdi mdi-plus-circle"></i>
                            Opprett mal
                        </a>

                    </div>

                </div>
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

                    <!-- New filters on mal -->
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Format</label>

                            <select name="category" id="format" required class="form-control">
                                <option selected value=''>Alle</option>
                            </select>
                        </div>
                    </div>


                    <div class="col-md-5 ">
                        <div class="form-group">
                            <label>Opprinnelse</label><br>
                            <div class="btn-group  w-100" data-toggle="buttons">
                                <label class="btn btn-primary active" style="font-size: 12px;">
                                    <input type="radio" name="options" value="" class="fMade" id="option1"
                                        autocomplete="off"> Alle
                                </label>
                                <label class="btn btn-primary active" style="font-size: 12px;">
                                    <input type="radio" name="options" value="Systemmal" class="fMade" id="option1"
                                        autocomplete="off"> System
                                </label>
                                <label class="btn btn-primary" style="font-size: 12px;">
                                    <input type="radio" name="options" value="Egendefinert mal" class="fMade"
                                        id="option2" autocomplete="off">Egendefinert</label>
                                <!-- <label class="btn btn-primary"  style="font-size: 12px;">
                                            <input type="radio" name="options" value="Egendefinert" class="fMade" id="option3" autocomplete="off"> Egendefinert
                                        </label> -->
                            </div>

                        </div>
                    </div>

                    <div class="col-ld-1">
                        <div class="form-group">
                            <label>Eier</label>

                            <select name="category" id="scati" required class="form-control">
                                <option selected value=''>Alle</option>
                            </select>
                        </div>
                    </div>

                    <!-- New filter ends -->


                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive m-t-40">
                    <table id="template_mal" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Navn</th>
                                <th>Kategori</th>
                                <th>Format</th>
                                <th>Opprinnelse</th>
                                <th>Eier</th>

                            </tr>
                        </thead>
                        <tbody>
                            @if(count($tData) == 0)
                            <tr>
                                <td colspan="3" align="center">{{ "No data Found  " }}</td>
                            </tr>
                            @else

                            @foreach($tData as $data)
                            <!-- <tr >
                            
                            <td><a href="#"  data-des=" {{ htmlspecialchars_decode($data->des) }}" data-name="{{ $data->title }}"  data-category="{{ $data->category_id}}" data-tempid= "{{ $data->id }}" class="btn use waves-effect waves-light">{{ ucfirst($data->title) }}</a>
                            @if(\Auth::user()->role == 1)
                                        <a style="padding-left: 1em;" href="/edit/template/{{ $data->id }}" data-toggle="tooltip" data-id="{{ $data->id }}" data-original-title="Edit" class="edit  editProduct"><i class="ti-pencil-alt"></i></a>
                                        <a href="javascript:void(0)" data-toggle="tooltip" data-id="{{ $data->id }}" onclick="del();" data-original-title="Delete" class="del  deleteProduct"><i class="ti-trash"></i></a>
                                        @endif
                                        </td> -->



                            <!-- New added codes -->

                            <tr>
                                <td>
                                    @if($data->type ==2)
                                    <a href="#" data-toggle="modal" data-id="{{ $data->id }}"
                                        data-name="{{ $data->title }}" data-target="#pre{{ $data->id }}"
                                        class="editProduct ">
                                        {{ ucfirst($data->title) }}
                                    </a>
                                    <div id="pre{{ $data->id }}" class="modal fade bs-example-modal-lg" tabindex="-1"
                                        role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true"
                                        style="display: none;">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content" style="height:800px;">
                                                <div class="modal-header">
                                                    <h4 class="modal-title" id="myLargeModalLabel">
                                                        {{ ucfirst($data->title) }}</h4>
                                                    <button type="button" class="close" data-dismiss="modal"
                                                        aria-hidden="true">×</button>
                                                </div>
                                                <div class="modal-body">
                                                    <iframe width="100%" height="90%"
                                                        src="{{ asset('file_uploader/company/'.\Auth::user()->companyId. '/document/'.$data->doc) }}"></iframe>
                                                    <div>
                                                        <small>Created By</small>
                                                        {{ \Helper::get_user_name($data->created_by) }} <br>
                                                        <small>Updated Date</small>
                                                        {{ date("d-m-Y",strtotime($data->updated_at)) }}



                                                    </div>
                                                </div>


                                                <div class="modal-footer">
                                                    <a href="{{ url('download/document/'.$data->doc) }}"
                                                        data-id="{{ $data->id }}" data-path="{{ $data->doc }}"
                                                        class="btn btn-success">Print</a>
                                                    <button type="button" data-id="{{ $data->id }}"
                                                        class="btn btn-danger waves-effect text-left delete"
                                                        data-type="template">Delete</button>
                                                    <button type="button" class="btn btn-dark"
                                                        data-dismiss="modal">Close</button>

                                                </div>
                                            </div>
                                            <!-- /.modal-content -->
                                        </div>
                                        <!-- /.modal-dialog -->
                                    </div>
                                    @else

                                    <a href="#" data-id="{{ $data->id }}"
                                        data-des=" {{ htmlspecialchars_decode($data->des) }}"
                                        data-name="{{ $data->title }}" data-category="{{ $data->category_id }}"
                                        class="dwn editProduct">
                                        {{ ucfirst($data->title) }}
                                    </a>

                                    @endif
                                </td>



                                <!-- New Added codes ended -->


                                <td>
                                    @php
                                    if($data->category_id){
                                    $inId = explode(',',$data->category_id);
                                    $catname =
                                    \DB::table("all_category")->select("des")->where("id",$data->category_id)->first();

                                    }
                                    @endphp
                                    {{ isset($catname->des) ? $catname->des : ""}}
                                </td>
                                <td>

                                    <?php
                                if ($data->type == 1) {
                                    echo "Dokument";
                                } else {
                                    echo "Fil";
                                } ?>
                                </td>
                                <td><?php
                                if ($data->origin ==1) {
                                    echo "Fil: Systemmal";
                                } elseif ($data->origin ==2) {
                                    echo " Fil: Egendefinert Mal";
                                } elseif ($data->origin ==3) {
                                    echo " Fil: Egendefinert";
                                } elseif ($data->origin ==4) {
                                    echo " Dokument: Systemmal";
                                } elseif ($data->origin ==5) {
                                    echo " Dokument: Egendefinert Mal";
                                } elseif ($data->origin ==6) {
                                    echo " Dokument:  Egendefinert";
                                }
                                
                            ?> </td>
                                <td>{{ $data->created_by }}</td>

                                <!-- <td>
                                    <a href="#"  data-des=" {{ htmlspecialchars_decode($data->des) }}"  data-category="{{ $data->category_id}}" data-tempid= "{{ $data->id }}" class="btn use waves-effect waves-light btn-outline-secondary">Bruk Mal</button>
                                        @if(\Auth::user()->role == 1)
                                        <a style="padding-left: 1em;" href="/edit/template/{{ $data->id }}" data-toggle="tooltip" data-id="{{ $data->id }}" data-original-title="Edit" class="edit  editProduct"><i class="ti-pencil-alt"></i></a>
                                        <a href="javascript:void(0)" data-toggle="tooltip" data-id="{{ $data->id }}" onclick="del();" data-original-title="Delete" class="del  deleteProduct"><i class="ti-trash"></i></a>
                                        @endif
                                </td> -->
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
<div id="tooltipmodals" class="modal fade " tabindex="-1" role="dialog" aria-labelledby="tooltipmodel"
    aria-hidden="true">
    <div class="modal-dialog modal-dailog-center">
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
                <button type="button" class="btn btn-info danger-success del  deleteProduct" data-toggle="tooltip"
                    onclick="del();" data-original-title="Slett">Slett</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->

<!-- sample modal content -->
<div id="askmodal" class="modal" tabindex="-1" role="dialog" aria-labelledby="tooltipmodel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tooltipmodel">Hvilken type dokument ønsker du å opprette?</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <div class="button-group">
                    <div class="row">
                        <div class="col-sm-6">
                            <button style="float: right;" type="button" data-toggle="modal" data-id="1"
                                data-target="#filemodal"
                                class="btn waves-effect waves-light btn-lg btn-primary omodal w-100">Fil</button>
                        </div>
                        <div class="col-sm-6">
                            <button type="button" data-toggle="modal" data-target="#filemodal" data-id="2"
                                class="btn waves-effect waves-light btn-lg btn-primary omodal w-100">Dokument </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>

<!--File Modal-->
<div class="modal fade " id="filemodal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel"
    aria-hidden="true" style="display: none;">
    <div class="modal-dialog  modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="doch">Last opp fil</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <form class="needs-validation" novalidate method="POST" enctype="multipart/form-data"
                    action="{{ url('/document/save') }}">
                    <div class="form-body">
                        <div class="row" id="doc">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <!-- <label for="input-file-now-custom-3">Upload You documenft</label> -->
                                    <input type="file" id="input-file-now-custom-3" name="doc" class="dropify"
                                        data-height="100" />
                                </div>
                            </div>
                        </div>
                        <div class="row ">
                            <!--/span-->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label" id="navn">Navn </label>
                                    <input type="text" id="name" name="name"
                                        value="{{ (old('name') != '' ) ? old('name') : (isset($rData->name) ? $rData->name : '') }}"
                                        required class="form-control">
                                    <small class="invalid-feedback"> Navn is required </small>

                                    <!-- Template id -->
                                    <input type="hidden" value="0" id="istemp" name="istemp">


                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group ">
                                    <label class="control-label">Kategori</label>

                                    @php
                                    $cat =
                                    \DB::table("all_category")->where("companyId",\Auth::user()->companyId)->where("table_name","documents")->get();
                                    $cate = [];

                                    if(isset($rData->category)){
                                    $cate = explode("," , $rData->category);
                                    }
                                    if(isset($tData->category_id)){
                                    $cate = explode("," , $tData->category_id);
                                    }
                                    @endphp
                                    @if($cat)
                                    <select name="category" id="cat" required class="form-control">
                                        <option value=''>Velg kategori</option>
                                        @foreach($cat as $data)
                                        <option {{ (isset($data->id) && in_array($data->id,$cate) ? 'selected' : '') }}
                                            {{ (isset($rData->category_id) && ($rData->category_id == $data->id )) ? "selected" : '' }}
                                            value="{{ $data->id }}">{{ $data->des}}</option>
                                        @endforeach
                                    </select>
                                    <small class="invalid-feedback"> Kategori required </small>

                                    @endif
                                </div>
                            </div>

                        </div>
                        <div id="oppafo">
                            <!-- radio buttons -->
                            <div class="col-md-3.5">
                                <div class="form-group" id="oppa">
                                    <label>Oprinnelse</label><br>
                                    <div class="btn-group" data-toggle="buttons">
                                        <label class="btn btn-primary active">
                                            <input type="radio" name="options" value="1" class="sMade" id="option1"
                                                autocomplete="off"> Systemmal
                                        </label>
                                        <label class="btn btn-primary">
                                            <input type="radio" name="options" value="2" class="sMade" id="option2"
                                                autocomplete="off"> Egendefinert Mal</label>
                                        <label class="btn btn-primary">
                                            <input type="radio" name="options" value="3" class="sMade" id="option3"
                                                autocomplete="off"> Egendefinert
                                        </label>
                                    </div>
                                </div>

                            </div>



                            <hr>
                            <h4 class="mt-4 control-label" id="oppa3">Hvem er denne filen relevant for?</h4>
                            <div class="my-5 row ">
                                <!--/span-->
                                <div class="col-md-6">
                                    <div class="form-group ">
                                        <label class="control-label">Avdeling(Depatment)</label>

                                        @php
                                        $cat =
                                        \DB::table("all_category")->where("companyId",\Auth::user()->companyId)->where("table_name","documents")->get();
                                        $cate = [];

                                        if(isset($rData->category)){
                                        $cate = explode("," , $rData->category);
                                        }
                                        if(isset($tData->category_id)){
                                        $cate = explode("," , $tData->category_id);
                                        }
                                        @endphp
                                        @if($cat)
                                        <select name="department" id="dep" class="form-control">
                                            <option value=''>Alle</option>

                                        </select>
                                        <small class="invalid-feedback"> Kategori required </small>

                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group ">
                                        <label class="control-label">Knytt opp dokumentet </label>

                                        @php
                                        $cat =
                                        \DB::table("all_category")->where("companyId",\Auth::user()->companyId)->where("table_name","documents")->get();
                                        $cate = [];

                                        if(isset($rData->category)){
                                        $cate = explode("," , $rData->category);
                                        }
                                        if(isset($tData->category_id)){
                                        $cate = explode("," , $tData->category_id);
                                        }
                                        @endphp
                                        @if($cat)
                                        <select name="assigned" id="cat" class="form-control">
                                            <option value=''>Velg kategori</option>
                                            @foreach($cat as $data)
                                            <option
                                                {{ (isset($data->id) && in_array($data->id,$cate) ? 'selected' : '') }}
                                                {{ (isset($rData->category_id) && ($rData->category_id == $data->id )) ? "selected" : '' }}
                                                value="{{ $data->id }}">{{ $data->des}}</option>
                                            @endforeach
                                        </select>
                                        <small class="invalid-feedback"> Kategori required </small>

                                        @endif
                                    </div>
                                </div>
                                <input type="hidden" id="xid" name="id" value="" />

                            </div>

                        </div>

                        {{ csrf_field() }}

                        <input type="hidden" name="doc_type" id="ty" />
                        <div class="row pt-3" id="des">

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label">Innhold</label>
                                    <div class="s mb-5"></div>
                                    <input type="hidden" name="discription" id="disc">
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="template_id" id="template_id" />
                        {{--<div class="row pt-3 hide" id="edit">
                            <input type="hidden" name="type" value="1" />

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label">Beskrivelse</label>
                                    <div class="r mb-5"></div>
                                    <input type="hidden" name="discription" id="disc">

                                </div>
                            </div>
                        </div>--}}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger waves-effect text-left delete"
                            id="dit">Delete</button>
                        <button type="submit" class="btn btn-success"> <i class="fa fa-check"></i> Lagre</button>
                        <button type="button" class="btn btn-inverse" data-dismiss="modal">Avbryt</button>
                        @if(isset($rData->id) && $rData->id !=0)
                        <button type="button" data-toggle="tooltip" data-id="{{ isset($rData->id) ? $rData->id : '' }}"
                            data-original-title="Delete" onclick="del(this)"
                            class="deleteProduct btn btn-warning">Delete</button>
                        @endif
                    </div>
                </form>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>


    <!-- Mal Modal -->

    <div class="modal fade " id="malmodal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel"
        aria-hidden="true" style="display: none;">
        <div class="modal-dialog  modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="doch">Last opp fil</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                </div>
                <div class="modal-body">
                    <form class="needs-validation" novalidate method="POST" enctype="multipart/form-data"
                        action="{{ url('/document/save') }}">
                        <div class="form-body">
                            <div class="row" id="doc">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <!-- <label for="input-file-now-custom-3">Upload You documenft</label> -->
                                        <input type="file" id="input-file-now-custom-3" name="doc" class="dropify"
                                            data-height="100" />
                                    </div>
                                </div>
                            </div>
                            <div class="row ">
                                <!--/span-->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="control-label">Navn </label>
                                        <input type="text" id="name" name="name"
                                            value="{{ (old('name') != '' ) ? old('name') : (isset($rData->name) ? $rData->name : '') }}"
                                            required class="form-control">
                                        <small class="invalid-feedback"> Navn is required </small>

                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group ">
                                        <label class="control-label">Kategori</label>

                                        @php
                                        $cat =
                                        \DB::table("all_category")->where("companyId",\Auth::user()->companyId)->where("table_name","documents")->get();
                                        $cate = [];

                                        if(isset($rData->category)){
                                        $cate = explode("," , $rData->category);
                                        }
                                        if(isset($tData->category_id)){
                                        $cate = explode("," , $tData->category_id);
                                        }
                                        @endphp
                                        @if($cat)
                                        <select name="category" id="cat" required class="form-control">
                                            <option value=''>Velg kategori</option>
                                            @foreach($cat as $data)
                                            <option
                                                {{ (isset($data->id) && in_array($data->id,$cate) ? 'selected' : '') }}
                                                {{ (isset($rData->category_id) && ($rData->category_id == $data->id )) ? "selected" : '' }}
                                                value="{{ $data->id }}">{{ $data->des}}</option>
                                            @endforeach
                                        </select>
                                        <small class="invalid-feedback"> Kategori required </small>

                                        @endif
                                    </div>
                                </div>

                            </div>
                            <!-- radio buttons -->
                            <div class="col-md-3.5">
                                <div class="form-group" id="oppa">
                                    <label>Oprinnelse</label><br>
                                    <div class="btn-group" data-toggle="buttons">
                                        <label class="btn btn-primary active">
                                            <input type="radio" name="options" value="1" class="sMade" id="option1"
                                                autocomplete="off"> Systemmal
                                        </label>
                                        <label class="btn btn-primary">
                                            <input type="radio" name="options" value="2" class="sMade" id="option2"
                                                autocomplete="off"> Egendefinert Mal</label>
                                        <label class="btn btn-primary">
                                            <input type="radio" name="options" value="3" class="sMade" id="option3"
                                                autocomplete="off"> Egendefinert
                                        </label>
                                    </div>
                                </div>

                            </div>



                            <hr>
                            <h4 class="mt-4 control-label" id="oppa3">Hvem er denne filen relevant for?</h4>
                            <div class="my-5 row ">
                                <!--/span-->
                                <div class="col-md-6">
                                    <div class="form-group ">
                                        <label class="control-label">Avdeling(Depatment)</label>

                                        @php
                                        $cat =
                                        \DB::table("all_category")->where("companyId",\Auth::user()->companyId)->where("table_name","documents")->get();
                                        $cate = [];

                                        if(isset($rData->category)){
                                        $cate = explode("," , $rData->category);
                                        }
                                        if(isset($tData->category_id)){
                                        $cate = explode("," , $tData->category_id);
                                        }
                                        @endphp
                                        @if($cat)
                                        <select name="department" id="dep" class="form-control">
                                            <option value=''>Alle</option>

                                        </select>
                                        <small class="invalid-feedback"> Kategori required </small>

                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group ">
                                        <label class="control-label">Knytt opp dokumentet </label>

                                        @php
                                        $cat =
                                        \DB::table("all_category")->where("companyId",\Auth::user()->companyId)->where("table_name","documents")->get();
                                        $cate = [];

                                        if(isset($rData->category)){
                                        $cate = explode("," , $rData->category);
                                        }
                                        if(isset($tData->category_id)){
                                        $cate = explode("," , $tData->category_id);
                                        }
                                        @endphp
                                        @if($cat)
                                        <select name="assigned" id="cat" class="form-control">
                                            <option value=''>Velg kategori</option>
                                            @foreach($cat as $data)
                                            <option
                                                {{ (isset($data->id) && in_array($data->id,$cate) ? 'selected' : '') }}
                                                {{ (isset($rData->category_id) && ($rData->category_id == $data->id )) ? "selected" : '' }}
                                                value="{{ $data->id }}">{{ $data->des}}</option>
                                            @endforeach
                                        </select>
                                        <small class="invalid-feedback"> Kategori required </small>

                                        @endif
                                    </div>
                                </div>
                                <input type="hidden" id="xid" name="id" value="" />

                            </div>

                            {{ csrf_field() }}

                            <input type="hidden" name="doc_type" id="ty" />
                            <div class="row pt-3" id="des">

                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="control-label">Innhold</label>
                                        <div class="s mb-5"></div>
                                        <input type="hidden" name="discription" id="disc">
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="template_id" id="template_id" />
                            {{--<div class="row pt-3 hide" id="edit">
                            <input type="hidden" name="type" value="1" />

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label">Beskrivelse</label>
                                    <div class="r mb-5"></div>
                                    <input type="hidden" name="discription" id="disc">

                                </div>
                            </div>
                        </div>--}}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger waves-effect text-left delete"
                                id="dit">Delete</button>
                            <button type="submit" class="btn btn-success"> <i class="fa fa-check"></i> Lagre</button>
                            <button type="button" class="btn btn-inverse" data-dismiss="modal">Avbryt</button>
                            @if(isset($rData->id) && $rData->id !=0)
                            <button type="button" data-toggle="tooltip"
                                data-id="{{ isset($rData->id) ? $rData->id : '' }}" data-original-title="Delete"
                                onclick="del(this)" class="deleteProduct btn btn-warning">Delete</button>
                            @endif
                        </div>
                    </form>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>

        @endsection