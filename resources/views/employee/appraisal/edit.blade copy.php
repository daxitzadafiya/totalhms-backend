@extends('templates.monster.main')

@push('before-styles')
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css') }}" rel="stylesheet" type="text/css">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    li.nav-item {
        WIDTH: 100%;
        max-width: 400px;
        min-width: 400px;
    }
</style>
@endpush

@push('after-scripts')
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/js/select2.full.min.js') }}" type="text/javascript"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.js"></script>

<script>
    var app_id = "{{ $id }}"

    jQuery(document).ready(function($) {
        $(".delquestion").css({
            position: 'relative',
            top: '53%',
            left: '10px'
        });

        $('.repeater').repeater();
        $('.topic').select2({
            placeholder: 'Velg Emne'
        });


        $(document).on('click', '.addquestion', function() {
            var _this = $(this);
            _this.parents("repeater").children(".question").append("sanjayj");
        });
        $(document).on('click', '.addRow', function() {
            var _this = $(this);
            var id = _this.attr("data-id");
            var htm = '<div class="row questionz align-items-sm-center"><div class="col-md-4"><div class="form-group"> <label>Spørsmål </label><textarea class="form-control" name="resource[' + id + '][questions][]"></textarea></div></div><div class="col-md-2"><div class="form-group"> <input type="checkbox" name="resource[' + id + '][private][]" value="2" class="form-group"> <label>Private </label></div></div><div class="col-md-5"><div class="form-group"> <label>Notater</label><textarea class="form-control " name="resource[' + id + '][notes][]"></textarea></div></div><div class="col-md-1"> <button type="button" data-id="' + id + '" class="btn  btn-danger waves-effect waves-light delquestion"><i class="fa fa-minus"></i></button></div></div>';
            _this.parents(".allcat").find(".addquesrow").append(htm);
        });

        $(document).on('click', '.topicRow', function() {
            swal({
                  //title: "Advarsel",
                    text: "You want to Add?",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "Green",
                    cancelButtonColor: "Red",
                    confirmButtonText: "Topic",
                    cancelButtonText: "Question",
            }).then(result => {
                $('.preloader').show();
                if (result.value) {
                        var type = 1 ;
                        addRowTopic(type);
                } else if (result.dismiss === swal.DismissReason.cancel){
                        var type = 2 ;
                        addRowTopic(type);
                    }
                 else{
                       $('.preloader').hide();

                    swal.closeModal();
                 }
            });

        });
        function addRowTopic(type = null){
                $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: 'POST',
                        url: '/appraisal/add/topic/row',
                        data: {"type" : type}
                    }).done(function(msg) {
                        $('.preloader').hide();
                        $('.quet').append(msg);
                    });
        }
        $(document).on('click', '.addtopic', function() {
            var _this = $(this);
            var topic_id = _this.attr("data-topicid")
            if (topic_id != 0) {
                swal({
                    title: "Advarsel",
                    text: "Are you sure want to add topic!",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Ja, Save!",
                    cancelButtonText: "Avbryt",
                }).then(result => {
                    $('.preloader').show();

                    if (result.value) {
                        $.ajax({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            type: 'POST',
                            url: '/appraisal/use/topic',
                            data: {
                                "app_id": app_id,
                                "topic_id": topic_id,
                            },

                        }).done(function(msg) {

                            $('.quet').append(msg);
                            var adbtn = '<span><button  data-id="' + topic_id + '" type="button" style="float:right;"  class="btn  btn-xs btn-danger waves-effect waves-light delcat"><i class="fa fa-minus"></i></button></span>';
                            $("#adbtn-" + topic_id).replaceWith('');

                        });

                    } else if (
                        result.dismiss === swal.DismissReason.cancel
                    ) {
                        swal("Avbrutt", "Ingen ting ble slettet!", "error");
                    }
                    $('.preloader').hide();

                    swal.closeModal();
                });

            }
        });
        $(document).on('change', '.private', function() {
            var _this = $(this);
            var qval
            var qid = _this.attr("data-qid");
            if (this.checked) {
                qval = 1;
            } else {
                qval = 0;
            }
            updateQuestion(qid, qval)
        });
        $(document).on('change', '.answer', function() {
            var _this = $(this);
            var qval
            var qid = _this.attr("data-qid");
            var answer = _this.val();
            if ($(this).parents(".questionz").find(".private").is(':checkbox:checked') == true) {
                qval = 1;
            } else {
                qval = 0;
            }

            updateQuestion(qid, qval, answer)
        });
        $(document).on('click', '.delcat', function() {
            var _this = $(this);
            var catid = _this.attr("data-id");

            if (catid != "") {

                swal({
                    title: "Advarsel",
                    text: "Are you sure want to delet!",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Ja, Save!",
                    cancelButtonText: "Avbryt",
                }).then(result => {
                    // swal("Slettet!", "Sletting utført.", " btn-xs");
                    $('.preloader').show();
                    if (result.value) {
                        _this.parents(".allcat").remove();
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

        });
        $(document).on('click', '.delquestion', function() {
            var _this = $(this);
            var qid = _this.attr("data-id");

            if (qid != "") {

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
                    $('.preloader').hide();
                    if (result.value) {
                        _this.parents(".questionz").remove();
                    }

                });

            }

        });
        $(document).on('click', '.delappraisal', function() {
            var _this = $(this);
            var catid = _this.attr("data-id");

            if (catid != "") {

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

                    if (result.value) {
                        $('.preloader').show();
                        $.ajax({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            type: 'POST',
                            url: '{{ url("/delete/appraisal/category") }}',
                            data: {
                                'id': catid,
                                'type': "appraisal"
                            },

                        }).done(function(msg) {
                            if (msg == 1) {
                                $('.preloader').hide();
                                swal("Deleted !", " ", "success").then(function() {
                                    window.location.href = "/ansatte/appraisals";
                                });
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

        });

    });

    function updateQuestion(id, status, answer) {
        if (id) {
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                type: 'POST',
                url: '/appraisal/question/update',
                data: {
                    "id": id,
                    "status": status,
                    "answer": answer
                },

            }).done(function(msg) {
                if (msg == 1) {
                    $('.preloader').hide();
                    $("#tooltipmodals").modal('hide');

                    swal("Saved!", " ", "success").then(function() {});
                    //window.location.reload();
                } else {
                    $('.preloader').hide();
                    swal("error !", " ", "error").then(function() {});
                }
            });

        }
    }
</script>
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
                                <select required name="employee_id" class="form-control employ">
                                    <option readonly value='0'>Velg Ansatt</option>
                                    @foreach($employ as $data)
                                    <option {{ (isset($appraisal->empid) && ($appraisal->empid === $data->id) ) ? "selected" : '' }} value="{{ $data->id }}">{{ $data->name }}</option>
                                    @endforeach
                                </select>
                                @endif
                            </div>
                        </div>
                        <input type="hidden" value="{{ (isset($appraisal->id) && ($appraisal->id != '' ) ) ?  $appraisal->id : '' }}" name="appraisal_id" />
                        <div class="col-md-6">
                            <label>Status</label>
                            <div class="form-group">
                                <select name="status" required class="form-control">
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
                        <div class="tab-content">
                            @if(count($appData) != 0)
                            @foreach($appData as $key=>$data)
                            @php
                            $question = \DB::table("appraisalquestion")->where("topic_id",$data->id)->get();
                            @endphp
                            <div class="tab-pane {{ ($key ==0 ) ? 'active' : '' }}" id="home{{$key}}" role="tabpanel">
                                @if($question)
                                @foreach($question as $item)
                                <h5>{{ $item->question }} ?</h5>
                                @endforeach
                                @endif
                            </div>
                            @endforeach
                            @endif
                        </div>
                    </div>
                    <hr>


                    <div class="quet"></div>

                    <div class="form-action">
                        <a href="JavaScript:void(0);" class="btn float-right hidden-sm-down btn-info topicRow"><i class="mdi mdi-plus-circle"></i>
                            Legg til ressurs
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>


    @if($topic)
    @foreach($topic as $data)
    <div class="allcat" id="catrow-{{ $data->id }}">
        <div class="card card-body">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Emne</label>

                        <div class="input-group">
                            <input type="text" class="form-control" name="resource[{{ $data->id}}][topic]" value="{{ $data->name}}" />
                            <div class="input-group-append">
                                <button type="button" data-id="{{ $data->id }}" class="btn  btn-danger waves-effect waves-light delcat"><i class="fas fa-trash"></i></button>
                                <button type="button" data-id="{{ $data->id }}" class="btn  btn-success waves-effect waves-light  addRow"><i class="fa fa-plus"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="addquesrow">
            </div>
            @php
            $question = \DB::table("employeeappraisalquestion")->where("topic_id",$data->id)->get();
            @endphp
            @if($question)
            @foreach($question as $item)
            <div class="row questionz align-items-sm-center">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Spørsmål </label>
                        <textarea class="form-control" data-qid="{{ $item->id }}" name="resource[{{ $data->id}}][questions][]">{{ isset($item->question) ? $item->question : '' }}</textarea>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <input type="checkbox" name="resource[{{ $data->id }}][private][]" value="2" {{ (isset($item->private ) && ($item->private == 2)) ? "checked" : ''}} data-qid="{{ $item->id }}" class="form-group">
                        <label>Private </label>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group">
                        <label>Notater</label>
                        <textarea class="form-control " data-qid="{{ $item->id }}" name="resource[{{ $data->id}}][notes][]">{{ isset($item->notes) ? $item->notes : '' }}</textarea>
                    </div>

                </div>
                <div class="col-md-1">
                    <button type="button" data-id="{{ $item->id }}" class="btn  btn-danger waves-effect waves-light delquestion"><i class="fa fa-minus"></i></button>
                </div>
            </div>
            @endforeach
            @endif
        </div>
    </div>
    @endforeach
    @endif

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <button style="float:right; margin-left:1%;" class='btn btn-info btn-danger danger-success delappraisal {{ isset($appraisal->id) ? "show" : "hide" }} ' type="button" {{ (isset($appraisal->id) && ($appraisal->id != '') ) ? "data-id = ".$appraisal->id : '' }} data-original-title="Slett">Slett</button>

                    <button type="submit" class="btn float-left hidden-sm-down btn-success cvstopic">Save</button>
                </div>
            </div>
        </div>
    </div>
</form>
<!--Add topic-->
<div id="topicmodal" class="modal fade " tabindex="-1" role="dialog" aria-labelledby="tooltipmodel" aria-hidden="true">
    <div class="modal-dialog  modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tooltipmodel">Legg til Emne</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <form method="post" action="{{ url('/appraisal/add/employee/topic') }}">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="recipient-name" class="control-label">Topic Navn:</label>
                                <input type="text" name="topicname" class="form-control" required id="catname">
                                <input type="hidden" name="id" class="form-control" value=" 0">
                                <input type="hidden" name="app_id" class="form-control" value="{{ isset($appraisal->id) ? $appraisal->id : '' }}">
                                <span class="help"></span>
                            </div>
                        </div>
                        <div class="col-md-6">

                            {{ csrf_field() }}
                            <div class="repeater">
                                <div data-repeater-list="question">
                                    <div data-repeater-item>
                                        <div class="question">
                                            <div class="form-group">
                                                <label>Spørsmål</label>

                                                <textarea class="form-control" required name="question"></textarea>
                                                <small class="invalid-feedback"> Spørsmål is required </small>
                                                <input data-repeater-delete type="button" class="btn btn-xs btn-danger" style="float:right;" value="Delete" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button data-repeater-create type="button" class="btn btn-xs btn-info waves-effect waves-light">Add</button>
                            </div>
                        </div>
                    </div>

            </div>
            <div class="modal-footer">
                <button type="submit" class="btn  btn-success btn-xs waves-effect waves-light">Lagre endringer</button>
                </form>

                <button type="button" class="btn btn-info danger-warning" data-dismiss="modal">Lukk</button>
                <button type="button" class="btn btn-info btn-danger del  deleteProduct" data-toggle="tooltip" onclick="del();" data-original-title="Slett">Slett</button>
            </div>
        </div>
    </div>
</div>

@endsection
