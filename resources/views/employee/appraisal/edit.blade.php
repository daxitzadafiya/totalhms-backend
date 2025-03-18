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
        width: 100%;
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
@section('content')
@include('common.errors')
@include('common.success')
<form action="{{ url('/appraisal/add') }}" id="myForm" method="post">
    <div class="row">
        <div class="col-lg-12">
            <div class="card card-outline-info">
                <div class="card-header">
                    <h4 class="mb-0 text-white">Opprett medarbeidersamtale</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label>Ansatt</label>
                            <div class="form-group">
                                @if($employ)
                                <select required name="employee_id"  {{ isset($appraisal->empid) ? "disabled " : '' }} class="form-control employ employId">
                                    <option readonly value='0'>Velg Ansatt</option>
                                    @foreach($employ as $data)
                                    <option {{ (isset($appraisal->empid) && ($appraisal->empid === $data->id) ) ? "selected " : '' }} value="{{ $data->id }}">{{ $data->name }}</option>
                                    @endforeach
                                </select>
                                @endif
                                <small class="invalid-feedback emperr hide"> employee is required </small>

                            </div>
                        </div>
                        <input type="hidden" value="{{ (isset($appraisal->id) && ($appraisal->id != '' ) ) ?  $appraisal->id : '' }}" name="appraisal_id" />
                        <div class="col-md-6">
                            <label>Status</label>
                            <div class="form-group">
                                <select name="status" required class="form-control status">
                                    <option {{ (isset($appraisal->status) && ($appraisal->status === 1)) ? "selected" : '' }} value="1">Ny</option>
                                    <option {{ (isset($appraisal->status) && ($appraisal->status === 2)) ? "selected" : '' }} value="2">Gjennomført</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{ csrf_field()}}
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Legg til fra Ressurser</h4>
                    <div class="vtabs">
                        <ul class="nav nav-tabs tabs-vertical" role="tablist">
                            @if(count($appData) != 0)
                            @foreach($appData as $key=>$data)
                            <li class="nav-item">
                                <a class="nav-link {{ ($key ==0 ) ? 'active' : '' }}" data-toggle="tab" href="#home{{$key}}" data-topicid="{{ $data->id }}" role="tab"><span class="hidden-sm-up"><i class="ti-home"></i></span>
                                    <span class="hidden-xs-down">{{ ucfirst($data->name) }}</span>
                                    <span id="adbtn-{{ $data->id }}" class="btnspan"><button data-topicid="{{ $data->id }}" class="btn btn-success btn-xs float-right addtopic"><i class="fa fa-plus"></i></button></span>
                                </a>
                            </li>
                            @endforeach
                            @endif
                        </ul>
                        <div class="tab-content" style="width:100%;">
                            @if(count($appData) != 0)
                            @foreach($appData as $key=>$data)
                            @php
                            $question = \DB::table("appraisalquestion")->where("topic_id",$data->id)->get();
                            @endphp
                            <div class="tab-pane {{ ($key ==0 ) ? 'active' : '' }}" id="home{{$key}}" role="tabpanel">
                                @if($question)
                                @foreach($question as $item)
                                <div class="row navi" style="margin-bottom:10px;">
                                    <div class="col-md-6">
                                        <h5>{{ $item->question }} ?</h5>
                                    </div>
                                    <div class="col-md-6">
                                        <span id="adbtn-{{ $item->id }}" class="btnspan float-right">
                                            <button data-questionid="{{ $item->id }}" data-topic_id="{{ $item->topic_id }}" type="button" class="btn btn-success btn-xs float-right addquestion"><i class="fa fa-plus"></i></button></span>
                                    </div>
                                </div>
                                @endforeach
                                @endif
                            </div>
                            @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="board ts">
        @if(count($topic) >0 )
        @foreach($topic as $index=>$data)
        <div class="board-column todo">
            <div class="board-column-header row m-0">
                <h3 class="text-light m-0 text-left">{{ $data->name }}
                <input type="hidden" name="resource[{{$data->id}}][topic_id]" value="{{ $data->id }}" /></h3>
                <input type="hidden" name="resource[{{$data->id}}][topic_name]" value="{{ $data->name }}" />
                <input type="hidden" name="resource[{{$data->id}}][app_id]" value="{{ $appraisal->id }}" />

                <a href="javascript:void(0)" class="btn waves-effect waves-light ml-auto btn-info btn-sm bg-success add_item" data-categoryid="{{$data->id}}" data-id="{{ $data->id }}">Nytt spørsmål</a>
                <a href="javascript:void(0)" data-catid="{{ $data->id }}" data-currentCatID="{{$data->id}}" class="btn btn-danger btn-sm  ml-3 waves-light waves-effect deletetemp removeCategory">Slett</a>
            </div>
            <div class="board-column-content" id="topicId_{{$data->id}}">
                <!--input type="hidden" class="form-control" value="{{ isset($tData->id)? $tData->id : 0 }}" id="currentOrderId"-->
                @php
                // $qdata = \DB::table("templatequestion")->where("template_id",$tData->id)->where("categoryid",$data->id)->orderBy('position')->get();
                $qdata = \DB::table("employeeappraisalquestion")->where("topic_id",$data->id)->get();

                $i = 1;
                @endphp
                @if(count($qdata) != 0)
                @foreach($qdata as $in=>$qx)
                <div class="board-item">
                    <div class="board-item-content">
                              <div class="row">
                                <div class="col-md-4 ">
                                    <div class="form-group">
                                    <label>Emne</label>
                                        <input type="text" data-qid="{{ $qx->id }}" data-catid="{{ $data->id }}" class="form-control quest" required  value="{{ $qx->question }} " placeholder="Spørsmål.."><small class="invalid-feedback"> Tekst er påkrevd! </small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><input type="checkbox"  {{ ($qx->private == 1) ? "checked" : '' }} class="chj" value="1"> Vise kommentarfelt?</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Notater</label>
                                        <input type="text" class="form-control" data-qid="{{ $qx->id }}"  value="{{ isset($qx->notes) ? $qx->notes : '' }}" >
                                    </div>
                                </div>
                               <div class="col-md-1">
                                     <button type="button" data-id="{{ $qx->id }}" data-catid="{{ $data->id }}" class="btn btn-danger mobile btn-sm waves-effect removeItem deleteq">Slett</button>
                                    <!--button type="button" class="btn btn-success btn-sm waves-effect saveq mobile">Lagre og lukk</button-->
                                </div>
                        </div>
                    </div>
                </div>
                @endforeach
                @endif

            </div>
        </div>
        @endforeach
        @endif
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="col-md-12">
                        <div class="form-action">
                            <a href="JavaScript:void(0);" data-toggle="modal" data-target="#topicmodal" class="btn float-right hidden-sm-down btn-info"><i class="mdi mdi-plus-circle"></i>
                                Legg til
                            </a>							
                            <button style="margin-left:1%;" class='btn btn-info btn-danger danger-success delappraisal {{ isset($appraisal->id) ? "show" : "hide" }} ' type="button" {{ (isset($appraisal->id) && ($appraisal->id != '') ) ? "data-id = ".$appraisal->id : '' }} data-original-title="Slett">Slett</button>
                            <button type="submit" class="btn float-left hidden-sm-down btn-success cvstopic">Save</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!--Add topic-->
<div id="topicmodal" class="modal fade " tabindex="-1" role="dialog" aria-labelledby="tooltipmodel" aria-hidden="true">
    <div class="modal-dialog  ">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tooltipmodel">Legg til Emne</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
                <form method="post" id='frmAddTopic' action="{{ url('/appraisal/add/employee/topic') }}">
					<div class="modal-body">
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<label for="recipient-name" class="control-label">Topic Navn:</label>
									<input type="text" name="topicname" class="form-control" required id="catname">
									<input type="hidden" name="id" class="form-control" value=" 0">
									<input type="hidden" name="app_id" class="form-control" value="{{ isset($appraisal->id) ? $appraisal->id : '' }}">
									<input type="hidden" name="employee_id" id="emp_id"  value="{{ isset($appraisal->empid) ? $appraisal->empid : '' }}"  class="form-control" >
									 <span class="help"></span>
									 {{ csrf_field() }}
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="submit" class="btn  btn-success   waves-effect waves-light">Lagre endringer</button>
						<button type="button" class="btn btn-info danger-warning" data-dismiss="modal">Lukk</button>
					</div>
                </form>
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/js/select2.full.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/bootstrap-switch/bootstrap-switch.min.js') }}"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.js"></script>
<script src="{{ asset('/rtjs/appraisal/appraisaledit.js') }}"></script>
<!--script src="{{ asset('/js/muuri.js') }}"></script-->
<script src="https://cdnjs.cloudflare.com/ajax/libs/hammer.js/2.0.8/hammer.min.js"></script>
<script src="https://unpkg.com/muuri@0.6.3/dist/muuri.min.js"></script>
<script src="{{ asset('/rtjs\appraisal\murri.js') }}"></script>
<script>
    var app_id = "{{ isset($appraisal->id) ? $appraisal->id : ''  }}";
    var gurl = "{{ url('/') }}"
</script>
@endpush