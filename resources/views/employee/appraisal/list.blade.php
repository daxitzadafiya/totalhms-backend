@extends('templates.monster.main')

@push('before-styles')
<style>
    div#Appraisal_filter {
        display: none;
    }

    div#template_filter {
        display: none;
    }

    div#category_filter {
        display: none;
    }
</style>
<?php $tab = \Session::get('tab'); ?>
<link rel="stylesheet" type="text/css" href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/datatables/media/css/dataTables.bootstrap4.css') }}">
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css" rel="stylesheet" type="text/css">
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/daterangepicker/daterangepicker.css" rel="stylesheet">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/css/select2.min.css') }}" rel="stylesheet" type="text/css" />

@endpush
@push('after-scripts')
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/js/select2.full.min.js') }}" type="text/javascript"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/moment/moment.js"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/daterangepicker/daterangepicker.js"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/datatables/datatables.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.js"></script>
<script src="{{ asset('/filejs/appraisal/appraisallist.js') }}" type="text/javascript"></script>

<script>
    jQuery(document).ready(function($) {
        $('.repeater').repeater();
        $('.topic').select2({
            width: '100%',
            placeholder: "Velg Emne"
        });

        $(document).on('click', '.addquestion', function() {
            var _this = $(this);
            _this.parents("repeater").children(".question").append("sanjayj");
        });
        $(document).on('click', '.del', function() {
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
                                'type': "topic"
                            },

                        }).done(function(msg) {
                            if (msg == 1) {
                                $('.preloader').hide();

                                $('#topicRow-' + catid).remove();
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
</script>
@endpush
@section('content')
@include('common.errors')
@include('common.success')

<div class="row page-titles">
    <div class="col-md-6 col-8 align-self-center">
        <h3 class="text-themecolor mb-0 mt-0">Medarbeidersamtaler</h3>
    </div>

</div>
<ul class="nav  customtab" role="tablist">
    <li class="">
        <a class="nav-link {{ isset($tab)  ? '' : 'active' }}" data-toggle="tab" data-id=4 href="#tab4" role="tab">
            <span class="hidden-sm-up"> <i class="ti-home"></i></span>
            <span class="hidden-xs-down">Medarbeidersamtaler</span>
        </a>


    </li>
    <li class="">
        <a class="nav-link {{ (isset($tab) && ($tab ==  6)) ? 'active' : '' }}" data-toggle="tab" data-id=6 href="#tab6" role="tab">
            <span class="hidden-sm-up"> <i class="ti-home"></i></span>
            <span class="hidden-xs-down">Ressurser</span>
        </a>

    </li>
</ul>
<div class="tab-content  ">
    <div class="tab-pane  {{ isset($tab) ? '' : 'active' }}" id="tab4" role="tabpanel">
        <div class="row">
            <div class="col-md-12">
                <div class="card text-black bg-white">
                    <div class="card-body">
                        <h3 class="card-title text-black">Hovedhensikten med medarbeidersamtalen er</h3>
                        <ul>
                            <li>å utvikle tillit og åpenhet for å få best mulig kommunikasjon og samarbeid i det daglige.</li>
                            <li>å veilede og planlegge i forbindelse med medarbeiderens personlige og faglige utvikling, til beste for den ansatte og selskapet.</li>
                        </ul>
                        <p class="card-text">En gang i året avtales, forberedes og gjennomføres samtalen mellom leder og den ansatte. I samtalen kommer man inn på arbeidskrav, resultater, arbeidsmiljø, arbeidsforhold i sin alminnelighet, forholdet mellom ansatte, personlig og faglig utvikling og utfordringer.

                            Tiltak som man blir enige om noteres på eget ark, hvor man også setter opp hvem som har ansvaret for gjennomføring og hvilke frister man er enige om. Opplæringstiltak settes opp på handlingsplanen.

                            Under har vi listet opp noen momenter til en medarbeidersamtale. Listen er ikke på noen måte uttømmende, men er ment som hjelp til å få samtalen i gang.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">

                <div class="row">
                    <div class="col-md-6">
                        <h4>Innstillinger</h4>
                    </div>
                    <div class="col-md-6">
                        <a href="{{ url('ansatte/appraisal/ny') }}" class="btn float-right hidden-sm-down btn-success appraise">
                            <i class="mdi mdi-plus-circle"></i>
                            Ny Medarbeidersamtale
                        </a>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Ansatt</label>
                            <select name="user" id="rcatid" required class="form-control">
                                <option selected value=''>Alle</option>
                            </select>
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
                            <label>Søk</label>
                            <input type="text" class="form-control" name="srch" id="srch">
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">

                <div class="row">

                </div>
                <table id="appraisal" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Lagt til dato</th>
                            <th>Gjennomført dato</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(count($empData) == 0)
                        <tr>
                            <td colspan="3" align="center">{{ "No data Found  " }}</td>
                        </tr>
                        @else

                        @foreach($empData as $data)
                        <tr>
                            <td><a href="{{ url('/ansatte/appraisal/'.$data->id) }}">
                                    {{ \Helper::get_user_name($data->empid) }}
                                </a></td>
                            <td>{{ date("d-m-Y",strtotime($data->created_at)) }}</td>
                            <td>{{ isset($data->completed_date) ?date("d-m-Y",strtotime($data->completed_date)) : ''  }}</td>
                            <td>{{( $data->status == 0 ) ? "Ny" : 'Gjennomført' }}</td>
                        </tr>
                        @endforeach
                        @endif

                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="tab-pane  {{ (isset($tab) && ($tab ==  6)) ? 'active' : '' }}" id="tab6" role="tabpanel">

        <div class="card">
            <div class="card-body">

                <div class="row">

                    <div class="col-md-12  float-right ">
                        <a href="JavaScript:void(0);" data-target="#selectmodal" data-toggle="modal" class=" btn float-right hidden-sm-down btn-success nytopic"><i class="mdi mdi-plus-circle"></i>
                            Ny Emne
                        </a>
                    </div>
                </div>
                <hr>
                <div class="table-responsive m-t-40">
                    <table id="template" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Navn</th>
                                <th>Questions</th>

                            </tr>
                        </thead>
                        <tbody>
                            @if(count($appData) == 0)
                            <tr>
                                <td colspan="3" align="center">{{ "No data Found  " }}</td>
                            </tr>
                            @else

                            @foreach($appData as $data)
                            <tr id="topicRow-{{ $data->id }}">
                                <td><a href="JavaScript:void(0);" data-toggle="modal" data-target="#editmodal-{{ $data->id }}" data-topicid="{{ $data->id }}">{{ ucfirst($data->name) }}</a></td>
                                @php
                                $question = \DB::table("appraisalquestion")->where("topic_id",$data->id)->get();
                                @endphp
                                <td>
                                        @if($question)
                                        @foreach($question as $item)
                                            <p>{{ $item->question }}</p>

                                        @endforeach
                                        @endif
</td>
                            </tr>
                            @endforeach
                            @endif
                            </ul>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!--Add topic-->
<div id="topicmodal" class="modal fade " tabindex="-1" role="dialog" aria-labelledby="tooltipmodel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tooltipmodel">Legg til Topic</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <form class="topicform" method="post" action="{{ url('/appraisal/add/topic') }}">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="recipient-name" class="control-label">Topic Navn:</label>
                                <input type="text" name="topicname" class="form-control" required class="form-control">
                                <input type="hidden" name="id" class="form-control" value="0">

                                {{ csrf_field() }}
                                <span class="help"></span>
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
<div id="questionmodal" class="modal fade " tabindex="-1" role="dialog" aria-labelledby="tooltipmodel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tooltipmodel">Legg til </h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <form class="topicform" method="post" action="{{ url('/appraisal/add/question') }}">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="recipient-name" class="control-label">Topic Navn:</label>

                                <select name="topic_id" class="form-control" required id="catname">
                                    @if($appData)
                                    @foreach($appData as $data)
                                    <option value="{{ $data->id }}">{{ $data->name }}</option>
                                    @endforeach
                                    @endif
                                </select>
                                <input type="hidden" name="id" class="form-control" value="0">
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
                                                <label>Question</label>
                                                <input class="form-control" required name="question" />
                                                <small class="invalid-feedback"> Question is required </small>
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
                <button type="submit" class="btn  btn-success waves-effect waves-light">Lagre endringer</button>
                </form>
                <button type="button" class="btn btn-info danger-warning" data-dismiss="modal">Lukk</button>
            </div>
        </div>
    </div>
</div>
<div id="selectmodal" class="modal fade " tabindex="-1" role="dialog" aria-labelledby="tooltipmodel" aria-hidden="true">
    <div class="modal-dialog  ">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tooltipmodel">Select Type</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <button data-target="#topicmodal" data-toggle="modal" class="btn btn-success float-right float-right w-75" data-dismiss="modal">Topic</button>
                    </div>
                    <div class="col-md-6">
                        <button data-target="#questionmodal" data-toggle="modal" data-dismiss="modal" class="btn btn-warning float-left w-75">Question</button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@if($appData)
@foreach($appData as $data)

<div id="editmodal-{{ $data->id }}" class="modal fade " tabindex="-1" role="dialog" aria-labelledby="tooltipmodel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tooltipmodel">Legg til Topic</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <form class="" method="post" action="{{ url('/appraisal/add/topic') }}">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="recipient-name" class="control-label">Topic Navn:</label>
                                <input type="text" name="topicname" class="form-control" required value="{{ $data->name }}">
                                <input type="hidden" name="id" class="form-control" value="{{ $data->id }}">

                                <span class="help"></span>
                            </div>
                        </div>
                        {{ csrf_field() }}
                        @php
                        $question = \DB::table("appraisalquestion")->where("topic_id",$data->id)->get();
                        @endphp
                        <div class="col-md-6">
                            <div class="repeater">
                                <div data-repeater-list="question">
                                    @if($question)
                                    @foreach($question as $item)
                                    <div data-repeater-item>
                                        <div class="question">
                                            <div class="form-group">
                                                <label>Question</label>
                                                <input class="form-control" required name="question" value="{{ $item->question }}" />
                                                <small class="invalid-feedback"> Question is required </small>
                                                <input data-repeater-delete type="button" class="btn btn-xs btn-danger" style="float:right;" value="Delete" />
                                            </div>
                                        </div>

                                    </div>
                                    @endforeach
                                    @endif

                                </div>
                                <button data-repeater-create type="button" class="btn btn-xs btn-info waves-effect waves-light">Add</button>
                            </div>
                        </div>
                    </div>

            </div>
            <div class="modal-footer">
                <button type="submit" class="btn  btn-success waves-effect waves-light">Lagre endringer</button>
                </form>

                <button type="button" class="btn btn-info danger-warning" data-dismiss="modal">Lukk</button>
                <button type="button" data-id="{{ $data->id }}" class="btn btn-info btn-danger danger-success del  deleteProduct" data-dismiss="modal" data-toggle="tooltip" data-original-title="Slett">Slett</button>
            </div>
        </div>
    </div>
</div>

@endforeach
@endif

@endsection