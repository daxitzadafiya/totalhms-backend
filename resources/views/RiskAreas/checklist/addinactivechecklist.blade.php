@extends('templates.monster.main')

@push('before-styles')
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css') }}" rel="stylesheet" type="text/css">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/x-editable/dist/bootstrap3-editable/css/bootstrap-editable.css') }}" rel="stylesheet" />
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/bootstrap-switch/bootstrap-switch.min.css') }}" rel="stylesheet">
<style>
 li.nav-item {
        width: 100%;
        max-width: 400px;
        min-width: 400px;
    }

    .navi-link.active,
    .navi:hover,
    .navi.active:focus {
        background: #009efb;
        border: 0px;
        color: #ffffff;
    }

    .navi {
        padding: .5rem 1rem;
        border-radius: 5px;
    }

    textarea.edtg.form-control.item {
        width: 90%;
    }


    .board-column-header.row {
        padding-top: 10px;
        padding-bottom: 10px;
    }

    a.btn.waves-effect.waves-light.btn-xs.btn-info.add_item {
        position: absolute;
        z-index: 999;
        right: 10px;
        top: 9px;
    }


    /* Board */
    textarea.edtg {
        width: 100%;
    }

    /* Board */

    .board {
        position: relative;

    }

    .board-column {
        position: absolute;
        left: 0;
        right: 0;
        width: 100%;
        background: #f0f0f0;
        border-radius: 3px;
        z-index: 1;
    }

    .board-column.muuri-item-releasing {
        z-index: 2;
    }

    .board-column.muuri-item-dragging {
        z-index: 99999;
        cursor: move;
    }

    .board-column-header {
        position: relative;
        line-height: 50px;
        overflow: hidden;
        padding: 0 20px;
        text-align: center;
        background: #fff;
        color: #fff;
        border-radius: 3px 3px 0 0;
    }

    .ribbon:hover {
        background: #01b8d8;
        cursor: default;
    }

    .board-column.todo .board-column-header {
        background: #4A9FF9;
    }

    .board-column.working .board-column-header {
        background: #f9944a;
    }

    .board-column.done .board-column-header {
        background: #2ac06d;
    }

    .board-column-content {
        position: relative;
        border: 10px solid transparent;
        min-height: 95px;
    }

    .board-item {
        position: absolute;
        width: 33.33%;
        margin: 5px 0;
    }

    .board-item.muuri-item-releasing {
        z-index: 9998;
    }

    .board-column.muuri-item-dragging {
        z-index: 3;
        cursor: move;
    }

    .board-item.muuri-item-dragging {
        z-index: 999999;
        cursor: move;
    }

    .board-item.muuri-item-hidden {
        z-index: 0;
    }

    .board-item-content {
        position: relative;
        padding: 8px;
        background: #fff;
        border-radius: 4px;
        font-size: 17px;
        cursor: move;
        -webkit-box-shadow: 0px 1px 3px 0 rgba(0, 0, 0, 0.2);
        box-shadow: 0px 1px 3px 0 rgba(0, 0, 0, 0.2);
    }

    .savedQuestion {
        cursor: pointer;
    }

    form.qform {
        cursor: pointer;
    }
</style>
@endpush

@push('after-scripts')
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.js"></script>
<script src="{{ asset('/filejs/riskareas/checklist/addinactivechecklist.js') }}"></script>
<script src="{{ asset('/js/muuri.js') }}"></script>
<script src="{{ asset('/filejs/riskareas/checklist/checklistmurri.js') }}"></script>

@endpush

@section('content')
@include('common.errors')
@include('common.success')


<div class="row">
    <div class="col-lg-12">
        <div class="card card-outline-info">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0 text-white">Opprett Sjekklister</h4>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ url('/add/activechecklist') }}"> 
                    <div class="row">
                        <div class="col-md-6">
                            <label><b>Name</b></label>
                                <div class="form-group">
                                    <input type="text" name="chklistname" class="form-control" id="naam" required value="{{ isset($checklist->checklistname) ? $checklist->checklistname : '' }}" >
                                </div>
                        </div>
                    
                        <div class="col-md-6">
                            <label><b>Kategori</b></label>
                                @if($category)
                                <div class="form-group">
                                    <select name="categ" id="category" class="form-control">
                                        <option value=''>Velg Kategory</option>
                                        @foreach($category as $cat)
                                        <option {{ (isset($checklist->category) && ($checklist->category === $cat->id) ) ? "selected " : '' }} value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                        <option value='add'>Add Kategory</option>
                                    </select>
                                </div>
                                @endif
                        </div>
                    </div>
                    <div class="row">
                        <!-- <div class="col-md-6">
                            <label><b>Type</b></label>
                                <div class="form-group">
                                    <input type="text" name="type" id="type_id" class="form-control" required value="{{ isset($checklist->types) ? $checklist->types : '' }}">
                                </div>
                        </div> -->
                        <div class="col-md-6">
                            <label><b>Project</b></label>
                                <div class="form-group">
                                    <input type="text" name="project" id="project_id" class="form-control" required value="{{ isset($checklist->project) ? $checklist->project : '' }}">
                                </div>
                        </div>
                    </div>
                    {{ csrf_field() }}
                    <input type="hidden" name="stat_type" id="status_type" value="0">
                        <div class="board ts">
                            @if(count($topic)  > 0 )
                            @foreach($topic as $index=>$data)
                            <div class="board-column todo">
                                <div class="board-column-header row m-0">
                                    <h3 class="text-light m-0 text-left">{{ $data->name}}</h3>
                                    <a href="javascript:void(0)" class="btn waves-effect waves-light ml-auto btn-info btn-sm bg-success add_item" data-categoryid="{{$data->id}}" data-id="{{ $data->id }}">Nytt Kontrolpoint</a>
                                    <a href="javascript:void(0)" data-catid="{{ $data->id }}" data-currentCatID="{{ $data->id }}" class="btn btn-danger btn-sm  ml-3 waves-light waves-effect deletetemp removeCategory">Slett</a>

                                </div>
                                <input type="hidden" name="checklist_id" value="{{ $checklist->id }}">
                                <div class="board-column-content">
                                    <div class="board-item hide">
                                        <div class="board-item-content">
                                        <div class="row ">
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                            <small class="invalid-feedback">Tekst påkrevd!</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-1">
                                                    <button type="button btn-danger" class="btn btn-danger mobile btn-sm waves-effect removeItem deleteq">Slett</button>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                            @endif
                        </div>
                                     
                    <div>
                    <div class="row">
                        <div class="col-md-12 ">
                            <a href="JavaScript:void(0);" data-toggle="modal" class="btn btn-info addtopic"> <i class="fa fa-plus"></i> Opprett Topic</a>
                            <button type="submit" class="btn btn-success sub"> <i class="fa fa-check"></i> Lagre</button>
                        </div>
                    </div>
                    </div>
                   </form>
                </div>
            </div>
        </div>
    </div>
</div>


<!--Add category-->
<div id="chkcatmodal" class="modal fade " tabindex="-1" role="dialog" aria-labelledby="tooltipmodel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tooltipmodel">Legg til Kategory</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <form class="chkcatform" method="post" action="">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="recipient-name" class="control-label">Kategory Navn:</label>
                                <input type="text" name="catname" id="catname" class="form-control" required
                                    class="form-control">

                                <input type="hidden" id="catid" value="">
                                <small class="invalid-feedback dp">Kategory Required</small> </small>
                                {{ csrf_field() }}
                            </div>
                        </div>
                    </div>

            </div>
            <div class="modal-footer">
                <button type="submit" class="btn  btn-success waves-effect waves-light">Lagre endringer</button>
                </form>
                <button type="button" class="btn btn-info danger-warning" data-dismiss="modal">Lukk</button>
            </div>
        </div>
    </div>
</div>


<!--Add topic-->
<div id="chktopicmodal" class="modal fade " tabindex="-1" role="dialog" aria-labelledby="tooltipmodel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tooltipmodel">Legg til Topic</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <form class="chktopicform" method="post" action="">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="recipient-name" class="control-label">Topic Navn:</label>
                                <input type="text" name="chkname" id="chkname" class="form-control" required
                                    class="form-control" value="{{ (old('chkname') != '' ) ? old('chkname') : (isset($data->chkname ) ? $data->chkname : '' ) }}">

                                <input type="hidden" id="chkid" value="">
                                <input type="hidden" name="checklist_id" id="chklistid" value="{{ isset($checklist->id) ? $checklist->id : '' }}">
                                <input type="hidden" name="checklistname" id="chn">
                                <input type="hidden" name="category" id="cats">
                                <!-- <input type="hidden" name="type" id="stat_type" value="0"> -->
                                <small class="invalid-feedback dp">Name Required</small> </small>
                                {{ csrf_field() }}
                            </div>
                        </div>
                    </div>

            </div>
            <div class="modal-footer">
                <button type="submit" class="btn  btn-success waves-effect waves-light">Lagre endringer</button>
                </form>
                <button type="button" class="btn btn-info danger-warning" data-dismiss="modal">Lukk</button>
            </div>
        </div>
    </div>
</div>

@endsection
