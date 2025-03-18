@extends('templates.monster.main')
@push('after-styles')
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/css/select2.min.css" rel="stylesheet"
    type="text/css" />
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/summernote/dist/summernote-bs4.css') }}" rel="stylesheet" />
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css" rel="stylesheet"
    type="text/css">
<link rel="stylesheet" href="https://bootstrap-tagsinput.github.io/bootstrap-tagsinput/dist/bootstrap-tagsinput.css">

<style>
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

<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/js/select2.full.min.js"
    type="text/javascript"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/monster/js/jasny-bootstrap.js"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/summernote/dist/summernote-bs4.min.js') }}"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js"></script>
<script src="https://bootstrap-tagsinput.github.io/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.js"></script>
<script src="{{ asset('/filejs/company/instruction/addinstruction.js') }}"></script>
<script>
$(".s").summernote({
    height: 350,
});
$('.s').summernote('code', '{!! isset($iData->discription) ? htmlspecialchars_decode($iData->discription) : (isset($tData->des) ? htmlspecialchars_decode($tData->des) : '')  !!}');

var room = 1;

function add_field() {
    room++;

    var objTo = document.getElementById('education_fields')
    var divtest = document.createElement("div");
    divtest.setAttribute("class", "form-group removeclass" + room);
    var rdiv = 'removeclass' + room;
    divtest.innerHTML =
        '<div class="row"><div class="col-sm-6 nopadding"><div class="form-group"> <label class="control-label">Aktivitet</label><textarea   class="form-control" required  name="activities[]" ></textarea><small class="invalid-feedback"> Aktivit is required </small></div></div><div class="col-lg-6 nopadding"><div class="form-group"> <label>Ansvarlig ansatt</label><div class="input-group"> @php $user = DB::table("users")->where("user_role","!=",1)->where("id","!=",\Auth::user()->id)->get(); $uid = []; if(isset($iData->responsible_person)){ $uid = explode("," , $iData->responsible_person); } @endphp @if($user)<div style="width:85%;" > <select name="responsible_person[]" required  class="form-control pr-' +
        room +
        '"><option value="">Velg</option> @foreach($user as $data)<option {{ (isset($data->id) && in_array($data->id,$uid) ? "selected" : "") }} value="{{ $data->id }}">{{ $data->name}}</option> @endforeach </select><small class="invalid-feedback"> Select  Ansvarlig ansatt </small></div> @endif<div style="width:15%;" ><div class="input-group-append"> <button class="btn btn-danger" type="button" onclick="remove_education_fields(' +
        room +
        ');"> <i class="fa fa-minus"></i> </button></div></div></div></div></div><div class="clear"></div> </row>';
    objTo.appendChild(divtest)
    $(".per-" + room).select2();

}

function remove_education_fields(rid) {
    $('.removeclass' + rid).remove();
}

function removeRow(e) {
    var id = $(e).data("id");
    console.log(id);
    $('.xr-' + id).remove();
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
                <h4 class="mb-0 text-white"> Opprett ny instruks</h4>
            </div>
            <div class="card-body">

                <form class="needs-validation" novalidate method="POST" action="{{ url('/save/instruction') }}">
                    <div class="form-body">
                        <div class="row pt-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Navn</label>
                                    <input type="text" name="name"
                                        value="{{ (old('name') != '' ) ? old('name') : (isset($iData->name) ? $iData->name : '') }}"
                                        required class="form-control">
                                    <small class="invalid-feedback"> Instruks is required </small>
                                </div>
                            </div>
                            <!--/span-->
                            <!--/span-->
                            <div class="col-md-6">
                                <div class="form-group ">
                                    <label class="control-label">Kategori</label>

                                    @php
                                    $cat =
                                    \DB::table("all_category")->where("companyId",\Auth::user()->companyId)->where("table_name","instructions")->get();
                                    $cate = [];

                                    if(isset($iData->category)){
                                    $cate = explode("," , $iData->category);
                                    }
                                    @endphp
                                    @if($cat)
                                    <select name="category" id="cat" required class="form-control">
                                        <option value=''>Velg kategori</option>
                                        @foreach($cat as $data)
                                        <option {{ (isset($data->id) && in_array($data->id,$cate) ? 'selected' : '') }}
                                            {{ (isset($tData->category_id) && ($tData->category_id == $data->id )) ? "selected" : '' }}
                                            value="{{ $data->id }}">{{ $data->des}}</option>
                                        @endforeach
                                        <option value="addins">Ny Kategori</option>
                                    </select>
                                    <small class="invalid-feedback"> Select a category </small>
                                    @endif
                                </div>
                            </div>
                            <!-- <div class="col-md-1">
                                <div class="form-group ">
                                    <label class="control-label">Legg til kategori</label>
                                    <button type="button" data-toggle="modal" data-target="#tooltipmodals"
                                        class="btn btn-rounded btn-block btn-primary"><small>Click </small></button>

                                </div>
                            </div> -->
                            <input type="hidden" name="id"
                                value="{{ isset($iData->id) ? ($iData->id != 0 ) ? $iData->id : '' :''}}" />
                            <input type="hidden" name="template_id"
                                value="{{ isset($iData->template_id) ? ($iData->template_id != 0 ) ? $iData->template_id : '' :''}}"
                                id="template_id" />
                            <!--/span-->
                        </div>

                        {{ csrf_field() }}
                        <div class="row pt-3">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label">Beskrivelse</label>
                                    <div class="s mb-5"></div>
                                    <input type="hidden" name="discription" id="des">
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--/row-->

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">

                                    <!---Foeach for edit section --->
                                    <?php
                                    if (isset($iData->activities) && $iData->activities != "") {
                                        $activity = explode(',', $iData->activities);
                                        $i = 1;
                                        foreach ($activity as $key => $value) {
                                            $actData = \DB::table('all_activities')->select('all_activities.responsible_person as person', 'all_activities.des as activity', "all_activities.id as id", "users.id as uId", "users.name as uName")->join("users", "all_activities.responsible_person", "=", "users.id")->where("all_activities.id", $value)->get()->toArray();
                                            $nData[] = $actData;
                                        } ?>
                                    @foreach ( $nData as $index=>$data )
                                    <div class="row xr-{{ $data[0]->id }}">
                                        <div class="col-sm-6 nopadding">
                                            <div class="form-group">
                                                <label class="control-label">Aktivitet</label>

                                                {{--<input type="text" required class="form-control act" value="{{ $data[0]->activity }}"
                                                name="activities[]" placeholder="Activities">--}}
                                                <textarea required class="form-control act"
                                                    name="activities[]">{{ $data[0]->activity }}</textarea>
                                            </div>
                                        </div>

                                        <div class="col-sm-6 ">
                                            <div class="form-group">
                                                <label>Ansvarlig ansatt</label>

                                                <div class="input-group">
                                                    @php
                                                    $user =
                                                    \DB::table("users")->where("user_role","!=",1)->where('id',"!=",\Auth::user()->id)->get();
                                                    $uid = [];
                                                    if(isset($data[0]->person)){
                                                    $uid = explode("," , $data[0]->person);
                                                    }
                                                    @endphp
                                                    @if($user)
                                                    <div style="width:85%;">
                                                        <select required name="responsible_person[]"
                                                            class="form-control ">
                                                            <option value=''>Velg</option>
                                                            @foreach($user as $xdata)
                                                            <option
                                                                {{ (isset($xdata->id) && in_array($xdata->id,$uid) ? 'selected' : '') }}
                                                                value="{{ $xdata->id }}">{{ $xdata->name}}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    @if($index != 0 )
                                                    <div style="width:15%;">
                                                        <div class="input-group-append">
                                                            <button class="btn btn-danger" type="button"
                                                                data-id="{{ $data[0]->id }}" onclick="removeRow(this);">
                                                                <i class="fa fa-minus"></i> </button> </div>
                                                    </div>
                                                    @endif
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                    <div class="input-group-append">
                                        <button class="btn btn-success" type="button" onclick="add_field();"><i
                                                class="fa fa-plus"></i></button>
                                    </div>
                                    <?php
                                    } else { ?>
                                    <div class="row acti">
                                        <div class="col-sm-6 nopadding">
                                            <div class="form-group">
                                                <label>Aktivitet</label>

                                                <textarea {{ isset($iData->id) ? "" : "required" }} class="form-control"
                                                    name="activities[]"></textarea>
                                                <small class="invalid-feedback"> Acktiviti is required </small>

                                            </div>
                                        </div>

                                        <div class="col-sm-6 ">
                                            <div class="form-group">
                                                <label>Ansvarlig ansatt</label>
                                                <div class="input-group">
                                                    @php
                                                    $user =
                                                    \DB::table("users")->where("user_role","!=",1)->where('id',"!=",\Auth::user()->id)->get();
                                                    $uid = [];
                                                    if(isset($iData->responsible_person)){
                                                    $uid = explode("," , $iData->responsible_person);
                                                    }
                                                    @endphp
                                                    @if($user)
                                                    <div style="width:85%;">
                                                        <select name="responsible_person[]" required
                                                            class="form-control ">
                                                            <option value=''>Velg</option>
                                                            @foreach($user as $data)
                                                            <option
                                                                {{ (isset($data->id) && in_array($data->id,$uid) ? 'selected' : '') }}
                                                                value="{{ $data->id }}">{{ $data->name}}</option>
                                                            @endforeach
                                                        </select>
                                                        <small class="invalid-feedback"> Select Ansvarlig ansatt
                                                        </small>

                                                    </div>
                                                    <div style="width:15%;">
                                                        <div class="input-group-append">
                                                            <button class="btn btn-success" type="button"
                                                                onclick="add_field();"><i
                                                                    class="fa fa-plus"></i></button>
                                                        </div>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php } ?>
                                    <div id="education_fields"></div>


                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-success"> <i class="fa fa-check"></i> Lagre</button>
                        <button type="button" class="btn btn-inverse">Avbryt</button>
                        @if(isset($iData->id) && $iData->id !=0)
                        <button type="button" data-toggle="tooltip" data-id="{{ isset($iData->id) ? $iData->id : ''}}"
                            data-original-title="Delete" onclick="del(this)"
                            class="deleteProduct btn btn-danger">Delete</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- sample modal content -->
<div id="tooltipmodals" class="modal fade " tabindex="-1" role="dialog" aria-labelledby="tooltipmodel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tooltipmodel">Legg til kategori</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="form-group">
                        <label for="recipient-name" class="control-label">kategori navn:</label>
                        <input type="text" class="form-control" required id="catname">
                        <span class="help"></span>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn  sv btn-success waves-effect waves-light">Lagre endringer</button>
                <button type="button" class="btn btn-info danger-effect" data-dismiss="modal">Lukk</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->

<!-- Row -->@endsection