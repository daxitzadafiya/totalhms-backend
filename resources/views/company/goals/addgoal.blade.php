@extends('templates.monster.main')
@push('before-styles')
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/icheck/skins/all.css" rel="stylesheet">
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/css/select2.min.css" rel="stylesheet" type="text/css" />

<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/bootstrap-select/bootstrap-select.min.css" rel="stylesheet" />
@endpush
@push('after-styles')
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/summernote/dist/summernote-bs4.css" rel="stylesheet" />
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css" rel="stylesheet" type="text/css">
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/bootstrap-switch/bootstrap-switch.min.css" rel="stylesheet">

<style>
input.man.xe {
    margin-left: 4px;
}
small#lk {
    margin-left: 5px;
}
    .note-toolbar.card-header {
        background-color: bisque;
    }

    .dropdown-toggle {
        width: 30% !important;
    }

    .note-button {
        width: 20% !important;
    }

    .dropdown-menu.dropdown-style {
        width: 100% !important;
    }

    .note-button .btn-group {
        width: 100% !important;
    }

    #activities {
        width: 90% !important;
    }
</style>

@endpush

@push('after-scripts')
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/monster/js/jasny-bootstrap.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/summernote/dist/summernote-bs4.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/bootstrap-switch/bootstrap-switch.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/switchery/dist/switchery.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/bootstrap-select/bootstrap-select.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/js/select2.full.min.js') }}" type="text/javascript"></script>
<script src="/filejs/company/goals/addgoals.js"></script>
<script type="text/javascript">
 $(".s").summernote({
        height: 300,
        //toolbar: [
          //  ['style', ['bold', 'italic', 'underline', 'clear']],
            //['font', ['strikethrough', 'superscript', 'subscript']],
            //['para', ['ul', 'ol', 'paragraph']],
            //['view', ['codeview']],
        //],
    });
$('.s').summernote('code', '{!! isset($gData->discription) ? htmlspecialchars_decode($gData->discription) : (isset($tData->des) ? htmlspecialchars_decode($tData->des) : '')  !!}');

var room = 1;

function add_field() {
    room++;
    var objTo = document.getElementById('education_fields')
    var divtest = document.createElement("div");
    divtest.setAttribute("class", "form-group removeclass" + room);
    var rdiv = 'removeclass' + room;
    divtest.innerHTML = '<div class="row"><div class="col-sm-6 nopadding"><div class="form-group"> <label class="control-label">Aktivitet knyttet til mål</label><textarea   class="form-control" required  name="act_data[' + room + '][activities]" ></textarea><small class="invalid-feedback"> Aktivit is required </small></div></div><div class="col-lg-6 nopadding"><div class="form-group"> <label>Ansvarlig </label><div class="input-group"> @php $user = DB::table("users")->where("user_role","!=",1)->where("id","!=",\Auth::user()->id)->get(); $uid = []; if(isset($gData->responsible_employee)){ $uid = explode("," , $gData->responsible_employee); } @endphp @if($user)<div style="width:80%;" > <select name="act_data[' + room + '][responsible_employee][]" required  multiple class="form-control res pr-' + room + '">@foreach($user as $data)<option {{ (isset($data->id) && in_array($data->id,$uid) ? "selected" : "") }} value="{{ $data->id }}">{{ $data->name}}</option> @endforeach </select><small class="invalid-feedback"> Select  Ansvarlig ansatt </small></div> @endif<div style="width:20%;" ><div class="input-group-append"> <button class="btn btn-danger" type="button" onclick="remove_education_fields(' + room + ');"> <i class="fa fa-minus"></i> </button></div></div></div></div></div><div class="clear"></div> </row>';
    objTo.appendChild(divtest)
    $(".res").select2({
        placeholder: "Velg",
        dropdownAdapter: $.fn.select2.amd.require('select2/selectAllAdapter')
    });
}

</script>
@endpush
@section('content')

@include('common.errors')
@include('common.success')

<!-- ============================================================== -->
<!-- Start Page Content -->
<!-- ============================================================== -->
<!-- Row -->
<div class="row">
    <div class="col-lg-12">
        <div class="card card-outline-info">
            <div class="card-header">
                <h4 class="mb-0 text-white">Opprett eller endre mål</h4>
            </div>
            <div class="card-body">
                <form class="needs-validation" novalidate method="POST" action="{{ url('/save/goal') }}">
                    <div class="form-body">
                        <div class="row pt-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Navn</label>
                                    <input type="text" name="name" value="{{ (old('name') != '' ) ? old('name') : (isset($gData->name) ? $gData->name : '') }}" required class="form-control">
                                    <small class="invalid-feedback">Navn is required </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group ">
                                    <label class="control-label">Type</label>
                                    <div class="form-group ">
                                        <div class="bt-switch">
                                            <input type="checkbox" {{ isset($gData->type) ?  ($gData->type == 1 ) ? 'checked' : '' :'checked'  }}  {{ isset($gData->id) ? "disabled"   : ''  }} data-on-color="warning" value="1"   id="typeee" data-off-color="danger" data-on-text="Hovedmål" data-off-text="Delmål">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="type" id="ty" value="{{ isset($gData->type) ?  $gData->type : 1  }}" />
                            <input type="hidden" name="id" value="{{ isset($gData->id) ? ($gData->id != 0 ) ? $gData->id : '' :''}}" />
                            <input type="hidden" name="template_id" value="{{ isset($gData->template_id) ? ($gData->template_id != 0 ) ? $gData->template_id : '' :''}}" id="template_id" />
                            {{ csrf_field() }}
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label">Beskrivelse</label>
                                    <div class="s mb-5"></div>
                                    <input type="hidden" name="discription" id="des">
                                </div>
                            </div>
                        </div>

                        <!---Foeach for edit section --->
                        <?php
                        if (isset($gData->activities) && $gData->activities != "") {
                            $activity = explode(',', $gData->activities);
                            $i = 1;
                            foreach ($activity as $key => $value) {
                                $actData = \DB::table('all_activities')
                                ->select('all_activities.responsible_person as person', 'all_activities.des as activity', "all_activities.id as id", "users.id as uId", "users.name as uName")
                                ->join("users", "all_activities.responsible_person", "=", "users.id")->where("all_activities.id", $value)->get()->toArray();
                                $nData[] = $actData;
                            } ?>
                         @if($nData)
                        @foreach ( $nData as $index=>$data )
                        <div class="row xr-{{ $data[0]->id }}">
                            <div class="col-sm-6 nopadding">
                                <div class="form-group">
                                    <label class="control-label">Aktivitet knyttet til mål</label>
                                    <textarea class="form-control act" required name="act_data[{{$index}}][activities]">{{ $data[0]->activity }}</textarea>
                                </div>
                            </div>
                            <div class="col-sm-6 ">
                                <div class="form-group">
                                    <label>Ansvarlig </label>
                                    <div class="input-group">
                                        @php
                                        $user = \DB::table("users")->where("user_role","!=",1)->where('id',"!=",\Auth::user()->id)->get();
                                        $uid = [];
                                        if(isset($data[0]->person)){
                                        $uid = explode("," , $data[0]->person);
                                        }
                                        @endphp
                                        @if($user)
                                        <div style="width:85%;">
                                            <select required name="act_data[{{$index}}][responsible_employee][]" multiple class="form-control res">
                                                @foreach($user as $xdata)
                                                <option {{ (isset($xdata->id) && in_array($xdata->id,$uid) ? 'selected' : '') }} value="{{ $xdata->id }}">{{ $xdata->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @if($index != 0 )
                                        <div style="width:15%;">
                                            <div class="input-group-append">
                                                <button class="btn btn-danger" type="button" data-id="{{ $data[0]->id }}" onclick="removeRow(this);"> <i class="fa fa-minus"></i> </button> </div>
                                        </div>
                                        @else
                                        <div class=" input-group-append">
                                            <button class="btn btn-success" type="button" onclick="add_field();"><i class="fa fa-plus"></i></button>
                                        </div>
                                        @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        @endforeach
@endif

                        <?php
                        } else { ?>

                        <div class="row">
                            <div class="col-sm-6  ">
                                <div class="form-group">
                                    <label>Aktivitet knyttet til mål</label>
                                    <textarea {{ isset($gData->id) ? "" : "" }} class="form-control" required name="act_data[0][activities]"></textarea>
                                    <small class="invalid-feedback"> Acktiviti is required </small>
                                </div>
                            </div>

                            <div class="col-sm-6 ">
                                <div class="form-group">
                                    <label>Ansvarlig </label>
                                    <div class="input-group">
                                        @php
                                        $user = \DB::table("users")->where("user_role","!=",1)->where('id',"!=",\Auth::user()->id)->get();
                                        $uid = [];
                                        if(isset($gData->responsible_employee)){
                                        $uid = explode("," , $gData->responsible_employee);
                                        }
                                        @endphp
                                        @if($user)
                                        <div style="width:80%;">
                                        <select required  multiple name="act_data[0][responsible_employee][]" class="form-control res">
                                            @foreach($user as $data)
                                            <option {{ (isset($data->id) && in_array($data->id,$uid) ? 'selected' : '') }} value="{{ $data->id }}">{{ $data->name }}</option>
                                            @endforeach
                                        </select>
                                        <small class="invalid-feedback"> Select Ansvarlig  </small>
                                        </div>
                                        <div style="width:20%;">
                                        <div class="input-group-append">
                                            <button class="btn btn-success" type="button" onclick="add_field();"><i class="fa fa-plus"></i></button>
                                        </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                        <div id="education_fields"></div>
                        <div class="col-md-12">

                            <div class="form-actions ">
                                <button type="submit" class="btn btn-success x"> <i class="fa fa-check"></i> Lagre</button>
                                <button type="button" class="btn btn-inverse">Avbryt</button>
                                @if(isset($gData->id) && $gData->id !=0)
                                <button type="button" data-toggle="tooltip" data-id="{{ isset($gData->id) ? $gData->id : ''}}" data-original-title="Delete" onclick="del(this)" class="deleteProduct btn btn-inverse">Slett</button>
                                @endif
                            </div>
                        </div>
                </form>
            </div>
        </div>
    </div>

    @endsection