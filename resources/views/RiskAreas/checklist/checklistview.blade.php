@extends('templates.monster.main')

@push('before-styles')
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css') }}"
    rel="stylesheet" type="text/css">

    <style>
        a.editquestion{

            color:grey ;
            
        }
        a.editquestion:hover{
            color:blue;
        }

    </style>
@endpush

@push('after-scripts')
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.js"></script>
<script src="{{ asset('/filejs/riskareas/checklist/checklistview.js') }}"></script>
@endpush

@section('content')
@include('common.errors')
@include('common.success')
<div class="row page-titles">
    <div class="col-md-6 align-self-center">
        <h3 class="text-themecolor mb-0 mt-0">Sjekklister</h3>
    </div>
    <div class="col-md-6">
        
    </div>
</div>


<div class="row">
    <div class="col-12">
        <ul class="nav customtab" role="tablist">

            <li class="">
                <a class="nav-link active" data-toggle="tab" href="#tab1" role="tab">
                    <span class="hidden-sm-up"> <i class="ti-home"></i></span>
                    <span class="hidden-xs-down">Reported Checklist</span>
                </a>
            </li>

            <li class="">
                <a class="nav-link" data-toggle="tab" href="#tab2" role="tab">
                    <span class="hidden-sm-up"> <i class="ti-home"></i></span>
                    <span class="hidden-xs-down">Aktive  Checklist</span>
                </a>
            </li>

            <li class="">
                <a class="nav-link" data-toggle="tab" href="#tab3" role="tab">
                    <span class="hidden-sm-up"> <i class="ti-home"></i></span>
                    <span class="hidden-xs-down">Resources</span>
                </a>
            </li>

            <li class="">
                <a class="nav-link" data-toggle="tab" href="#tab4" role="tab">
                    <span class="hidden-sm-up"> <i class="ti-home"></i></span>
                    <span class="hidden-xs-down">Kategory</span>
                </a>
            </li>
        </ul>
    </div>
</div>

<div class="tab-content">

    <div class="tab-pane active" id="tab1" role="tabpanel">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                        <a href="{{ url('/add/checklist') }}" class="btn hidden-sm-down btn-success float-right"><i class="mdi mdi-plus-circle"></i>Lag ny sjekkliste</a>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Kategory</label>
                            <select name="catego" id="categoid" required class="form-control">
                                <option selected value=''>Alle</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" id="statusid" required class="form-control">
                                <option selected value=''>Alle</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Done By</label>
                            <select name="done" id="doneid" required class="form-control">
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

        <!-- for the check list -->
        <div class="card">
            <div class="card-body">

                <table id="Checklists" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Checklist</th>
                            <th>Kategori</th>
                            <th>Dato</th>
                            <th>Done by</th>
                            <th>Status</th>
                            <th>Closed Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

    </div>

    <div class="tab-pane" id="tab2" role="tabpanel">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <a href="{{ url('/add/sjekkliste')}}" class="btn btn-success float-right"><i class="mdi mdi-plus-circle"></i>nytt sjekkliste</a>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Kategory</label>
                            <select name="catego" id="categoid" required class="form-control">
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
                <div class="table-responsive m-t-40">
                    <table id="topics" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Active Checklist Navn</th>
                                <th>Kategory</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(count($templates) == 0)
                            <tr>
                                <td colspan="3" align="center">{{ "No data Found  " }}</td>
                            </tr>
                            @else
                            @foreach($templates as $data)
                            <tr>
                                <td><a href="{{ url('/edit/sjekkliste/'  . $data->id) }}" data-id="{{ $data->id }}" data-name="{{ $data->checklistname }}">{{ ucfirst($data->checklistname) }}</a></td>
                               <td><?php
                                    $cat_name = \DB::table("check_category")->where("id", $data->category)->first();
                                    if ($cat_name) {
                                    echo $cat_name->name;
                                    }
                                    ?>
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

    

    <div class="tab-pane" id="tab3" role="tabpanel">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <a href="{{ url('/add/inactchecklist')}}" class="btn btn-success float-right"><i class="mdi mdi-plus-circle"></i>nytt sjekkliste</a>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Kategory</label>
                            <select name="catego" id="categoid" required class="form-control">
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
                <div class="table-responsive m-t-40">
                    <table id="resource" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Checklist Navn</th>
                                <th>Kategory</th>
                                <!-- <th>Action</th> -->
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                            @if(count($inactivetemp) == 0)
                            <tr>
                                <td colspan="3" align="center">{{ "No data Found  " }}</td>
                            </tr>
                            @else
                            @foreach($inactivetemp as $data)
                            <tr>
                                <td><a href="{{ url('/edit/inactsjekkliste/'  . $data->id) }}" data-id="{{ $data->id }}" data-name="">{{ ucfirst($data->checklistname) }}</a></td>
                               <td><?php
                                    $cat_name = \DB::table("check_category")->where("id", $data->category)->first();
                                    if ($cat_name) {
                                    echo $cat_name->name;
                                    }
                                    ?>
                               </td>
                                <!-- <td><button type="submit" class="btn btn-warning btn-xs waves-effect waves-light change_type" data-id="{{ $data->id }}">Add to Active Checklist</button></td> -->
                            </tr>
                            @endforeach
                            @endif

                            <!-- @if(count($chktopic) == 0)
                            <tr>
                                <td colspan="3" align="center">{{ "No data Found  " }}</td>
                            </tr>
                            @else

                            @foreach($chktopic as $data)
                            <tr>
                                <td><a href="JavaScript:void(0);" data-toggle="modal" class="edittopic" data-id="{{ $data->id }}" data-name="{{ $data->name }}">{{ ucfirst($data->name) }}</a></td>
                                @php
                                $question = \DB::table("check_question")->where("chktopic_id",$data->id)->get();
                                @endphp
                                <td>
                                        @if($question)
                                        @foreach($question as $item)
                                        <a href="JavaScript:void(0);" data-toggle="modal" class="editquestion" data-id="{{ $item->id }}" data-question="{{ $item->question }}">
                                        <p>{{ $item->question }}</p></a>
                                        @endforeach
                                        @endif
                                        <a href="JavaScript:void(0);"  data-toggle="modal" class="btn btn-success btn-xs float-right addquest" data-id="{{ $data->id }}"><i class="fa fa-plus"></i></a>
                            </td>
                            <td><button type="submit" class="btn btn-warning btn-xs waves-effect waves-light change_type float-right" data-id="{{ $data->id }}">Add to Active Checklist</button></td>
                            </tr>
                            @endforeach
                            @endif -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane" id="tab4" role="tabpanel">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                            <a href="JavaScript:void(0);" data-toggle="modal" class="btn btn-success float-right addcat"><i class="mdi mdi-plus-circle"></i>nytt Kategory</a>
                    </div>
                </div>
                <hr>
                <br>
                <div class="table-responsive m-t-40">
                    <table id="category" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Kategory Navn</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(count($chkcat) == 0)
                            <tr>
                                <td colspan="3" align="center">{{ "No data Found  " }}</td>
                            </tr>
                            @else
                            @foreach($chkcat as $data)
                            <tr>
                                <td><a href="JavaScript:void(0);" data-toggle="modal" class="editcat" data-id="{{ $data->id }}" data-name="{{ $data->name }}">{{ $data->name }}</a></td>
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
                                <input type="hidden" name="type" id="stat_type" value="0">
                                <small class="invalid-feedback dp">Name Required</small> </small>
                                {{ csrf_field() }}
                            </div>
                        </div>
                    </div>

            </div>
            <div class="modal-footer">
                <button type="submit" class="btn  btn-success waves-effect waves-light">Lagre endringer</button>
                </form>
                <button type="button" class="btn btn-danger delete_topic" id="del_top" data-id="{{ $data->id }}"><i class="fa fa-trash"></i> Slett </button>
                <button type="button" class="btn btn-info danger-warning" data-dismiss="modal">Lukk</button>
            </div>
        </div>
    </div>
</div>


<!--Edit Question-->
<div id="editquesmodal" class="modal fade " tabindex="-1" role="dialog" aria-labelledby="tooltipmodel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tooltipmodel">Legg til kontrollpunkt</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <form class="editquesform" method="post" action="{{ url('/add/resourcequestion') }} ">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="recipient-name" class="control-label">kontrollpunkt Navn:</label>
                                <input type="text" name="chkquestion" id="chkques" class="form-control" required
                                    class="form-control" value="{{ (old('chkquestion') != '' ) ? old('chkquestion') : (isset($data->chkquestion ) ? $data->chkquestion : '' ) }}">

                                <input type="hidden" name="id" id="quesid" value="">
                                <small class="invalid-feedback dp">kontrollpunkt Required</small> </small>
                                {{ csrf_field() }}
                            </div>
                        </div>
                    </div>

            </div>
            <div class="modal-footer">
                <button type="submit" class="btn  btn-success waves-effect waves-light">Lagre endringer</button>
                </form>
                <button type="submit" class="btn btn-danger delete_question" data-id="{{ $data->id }}"><i class="fa fa-trash"></i> Slett </button>
                <button type="button" class="btn btn-info danger-warning" data-dismiss="modal">Lukk</button>
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
                <button type="button" data-id="" class="btn btn-danger waves-effect text-left del_cat">Delete</button>
                </form>
                <button type="button" class="btn btn-info danger-warning" data-dismiss="modal">Lukk</button>
            </div>
        </div>
    </div>
</div>


<!--Add topic-->
<!-- <div id="chkcat" class="modal fade " tabindex="-1" role="dialog" aria-labelledby="tooltipmodel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tooltipmodel">Legg til Topic</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <form class="check_category" method="post" action="">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="recipient-name" class="control-label">Topic Navn:</label>
                                <input type="text" name="chkname" id="chkname" class="form-control" required
                                    class="form-control">

                                <input type="hidden" id="chkid" value="">
                                <small class="invalid-feedback dp">Name Required</small></small>
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
</div> -->

<!-- Question modal -->
<div id="chkquestionmodal" class="modal fade " tabindex="-1" role="dialog" aria-labelledby="tooltipmodel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tooltipmodel">Legg til</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <form class="topicform" method="post" action="{{ url('/add/chkquestion') }}">
                    <div class="row">
                        <div class="col-md-12">
                            {{ csrf_field() }}
                            <div class="repeater">
                                <div data-repeater-list="question">
                                    <div data-repeater-item>
                                        <div class="question">
                                            <div class="form-group row">
                                                <div class="col-md-12">
                                                    <label>Question</label>
                                                    <input class="form-control" required name="question" />
                                                    <small class="invalid-feedback"> Question is required </small>
                                                <!-- <div class="col-md-2">
                                                    <label>Answer</label><br>
                                                    <input type="radio" name="answer" value="0">Yes
                                                    <input type="radio" name="answer" value="1">No
                                                    <input type="radio" name="answer" value="2">Unclear
                                                </div><br>
                                                <div class="col-md-4">
                                                    <label>Comment</label>
                                                    <input class="form-control" name="comment" />
                                                </div> -->
                                                <input data-repeater-delete type="button" class="btn btn-xs btn-danger float-right"  value="Delete" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button data-repeater-create type="button" class="btn btn-xs btn-info waves-effect waves-light">Add</button>
                            </div>
                        </div>
                                                <input  type="hidden" name="topicid" id="topic_id" value="">
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

<!-- Add templates for superadmin -->
<div id="resourcemodal" class="modal fade " tabindex="-1" role="dialog" aria-labelledby="tooltipmodel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tooltipmodel">Legg til </h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <form class="topicform" method="post" action="{{ url('/add/resourcequestion') }}">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="recipient-name" class="control-label">Topic Navn:</label>

                                <select name="topic_id" class="form-control" required id="catname">
                                    @if($chktopic)
                                    @foreach($chktopic as $data)
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

<!-- Modal for select between topic and question -->
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
                        <button data-target="#chktopicmodal" data-toggle="modal" class="btn btn-success float-right float-right w-75" data-dismiss="modal">Emne</button>
                    </div>
                    <div class="col-md-6">
                        <button data-target="#resourcemodal" data-toggle="modal" data-dismiss="modal" class="btn btn-warning float-left w-75">Kontrollpunkt</button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal for select between topic and question for active checklist -->
<div id="actselectmodal" class="modal fade " tabindex="-1" role="dialog" aria-labelledby="tooltipmodel" aria-hidden="true">
    <div class="modal-dialog  ">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tooltipmodel">Select Type</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <button data-target="#acttopicmodal" data-toggle="modal" class="btn btn-success float-right float-right w-75" data-dismiss="modal">Emne</button>
                    </div>
                    <div class="col-md-6">
                        <button data-target="#actresourcemodal" data-toggle="modal" data-dismiss="modal" class="btn btn-warning float-left w-75">Kontrollpunkt</button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- modal for editing the checklists -->
<!-- @if($chktopic)
@foreach($chktopic as $data)

<div id="editmodal-{{ $data->id }}" class="modal fade " tabindex="-1" role="dialog" aria-labelledby="tooltipmodel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tooltipmodel">Legg til Topic</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <form class="" method="post" action="{{ url('/add/resourcequestion') }}">
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
                        $question = \DB::table("check_question")->where("chktopic_id",$data->id)->get();
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
                <button type="submit" class="btn btn-info danger-warning">Add to Active Checklist</button>
                <button type="submit" class="btn  btn-success waves-effect waves-light">Lagre endringer</button>
                </form>

                <button type="button" data-id="{{ $data->id }}" class="btn btn-info btn-danger danger-success del  deleteProduct" data-dismiss="modal" data-toggle="tooltip" data-original-title="Slett">Slett</button>
            </div>
        </div>
    </div>
</div>

@endforeach
@endif -->

<!--Add active topic-->
<div id="acttopicmodal" class="modal fade " tabindex="-1" role="dialog" aria-labelledby="tooltipmodel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tooltipmodel">Legg til Topic</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <form class="acttopicform" method="post" action="">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="recipient-name" class="control-label">Topic Navn:</label>
                                <input type="text" name="actname" id="actname" class="form-control" required
                                    class="form-control" value="{{ (old('actname') != '' ) ? old('actname') : (isset($data->actname ) ? $data->actname : '' ) }}">

                                <input type="hidden" id="actid" value="">
                                <input type="hidden" name="type" id="type" value="1">
                                <small class="invalid-feedback dp">Name Required</small> </small>
                                {{ csrf_field() }}
                            </div>
                        </div>
                    </div>

            </div>
            <div class="modal-footer">
                <button type="submit" class="btn  btn-success waves-effect waves-light">Lagre endringer</button>
                </form>
                <button type="button" class="btn btn-danger delete_topic" id="del_top" data-id="{{ $data->id }}"><i class="fa fa-trash"></i> Slett </button>
                <button type="button" class="btn btn-info danger-warning" data-dismiss="modal">Lukk</button>
            </div>
        </div>
    </div>
</div>


<!-- Add templates for active question -->
<div id="actresourcemodal" class="modal fade " tabindex="-1" role="dialog" aria-labelledby="tooltipmodel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tooltipmodel">Legg til </h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <form class="topicform" method="post" action="{{ url('/add/resourcequestion') }}">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="recipient-name" class="control-label">Topic Navn:</label>

                                <select name="topic_id" class="form-control" required id="catname">
                                    @if($actchktopic)
                                    @foreach($actchktopic as $data)
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

<div id="activechecklistmodal" class="modal fade " tabindex="-1" role="dialog" aria-labelledby="tooltipmodel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="tooltipmodel">Legg til </h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <form class="topicform" method="post" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="recipient-name" class="control-label">Topic Navn:</label>

                                <select name="topic_id" class="form-control" required id="catname">
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


@endsection