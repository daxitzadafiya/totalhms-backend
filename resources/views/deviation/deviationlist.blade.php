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

    .dataTables_filter {
    display: none;
}
</style>
<?php $tab = \Session::get('tab'); ?>
<link rel="stylesheet" href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/dropify/dist/css/dropify.min.css">
<link rel="stylesheet" type="text/css" href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/datatables/media/css/dataTables.bootstrap4.css') }}">
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css" rel="stylesheet" type="text/css">
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/daterangepicker/daterangepicker.css" rel="stylesheet">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="https://demo.worksuite.biz/plugins/bower_components/bootstrap-datepicker/bootstrap-datepicker.min.css">

@endpush
@push('after-scripts')
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/dropify/dist/js/dropify.min.js"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/js/select2.full.min.js') }}" type="text/javascript"></script>

<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/moment/moment.js"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/daterangepicker/daterangepicker.js"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/datatables/datatables.js') }}"></script>
<script src="https://demo.worksuite.biz/plugins/bower_components/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
<script src="{{ asset('/filejs/appraisal/appraisallist.js') }}" type="text/javascript"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.repeater/1.2.1/jquery.repeater.js"></script>
<script src="{{ asset('/filejs/deviation/deviationlist.js') }}"></script>
@endpush
@section('content')
@include('common.errors')
@include('common.success')

<div class="row page-titles">
    <div class="col-md-6 align-self-center">
        <h3 class="text-themecolor mb-0 mt-0">Behandle avvik</h3>
    </div>
    <div class="col-md-6">
    <a href="JavaScript:void(0);" data-toggle="modal" class="btn hidden-sm-down btn-success add_dev float-right"><i class="mdi mdi-plus-circle"></i>Opprett Avvik</a>
    </div>
</div>

<!-- tab-1 for the filters -->
<div class="card">
            @php
            //0=new,1=ongoing,3=moved to,4=completed
            $new = \DB::table("deviationlists")->where("companyId",1)->where("status",0)->count();
            $ongoing = \DB::table("deviationlists")->where("companyId",1)->where("status",1)->count();
            $moved = \DB::table("deviationlists")->where("companyId",1)->where("status",2)->count();
            $closed = \DB::table("deviationlists")->where("companyId",1)->where("status",3)->count();
            @endphp
            <div class="card-body">
                <div class="row m-t-40">
                     <!-- Column -->
                     <div class="col-md-6 col-lg-3 col-xlg-3">
                        <div class="card">
                            <div class="box bg-info text-center">
                                <h1 class="font-light text-white">{{ $new }}</h1>
                                <h6 class="text-white">nye</h6>
                            </div>
                        </div>
                    </div>
                    <!-- Column -->
                    <div class="col-md-6 col-lg-3 col-xlg-3">
                        <div class="card">
                            <div class="box bg-primary text-center">
                                <h1 class="font-light text-white">{{ $ongoing }}</h1>
                                <h6 class="text-white">under behandling</h6>
                            </div>
                        </div>
                    </div>
                    <!-- Column -->
                    <div class="col-md-6 col-lg-3 col-xlg-3">
                        <div class="card">
                            <div class="box bg-success text-center">
                                <h1 class="font-light text-white">{{ $moved }}</h1>
                                <h6 class="text-white">Flyttet til handlingsplan</h6>
                            </div>
                        </div>
                    </div>
                    <!-- Column -->
                    <div class="col-md-6 col-lg-3 col-xlg-3">
                        <div class="card">
                            <div class="box bg-dark text-center">
                                <h1 class="font-light text-white">{{ $closed }}</h1>
                                <h6 class="text-white">Fullført</h6>
                            </div>
                        </div>
                    </div>
                </div>
                    <hr>
                    <br>
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>stauts</label>
                            <select name="status" id="statid" required class="form-control">
                                <option selected value=''>Alle</option>
                            </select>
                        </div>
                    </div>
                
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Rapportert av</label>
                            <select name="employee" id="empid" required class="form-control">
                                <option selected value=''>Alle</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Behandlet av</label>
                            <select name="reason" id="beheld" required class="form-control">
                                <option selected value=''>Alle</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Dato</label>
                            <div class='input-group mb-3'>
                                <input type='text' class="form-control date_fil" />
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
                            <label>Søk</label>
                            <input type="text" class="form-control" name="srch" id="srch">
                        </div>
                    </div>

                    <!-- <input type="hidden" id="min" value="">
                    <input type="hidden" id="max" value=""> -->
                        
                        </div>
                    </div>
                </div>

   <!-- for the deviation list -->
<div class="card">
   <div class="card-body">
        <table id="doctable" class="table table-hover">
            <thead>
                <tr>
                    <th>Avvik</th>
                    <th>Dato</th>
                    <th>Rapportert av</th>
                    <th>Status</th>
                    <th>Behandlet av</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
</div>

<!--Deviation Add modal -->
<div class="modal fade " id="dev_model" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog  modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"> Opprett avvik</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <form class="needs-validation" novalidate method="POST" enctype="multipart/form-data" action="{{ url('/avvik/add') }}">
                    <div class="form-body">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label class="control-label">Avvik:</label>
                                    <div class="form-group">
                                        <input type="text" name="subject" required class="form-control">
                                    </div>
                            </div>
                            <div class="col-md-6">
                                <label class="control-label">Sted oppdaget:</label>
                                    <div class="form-group">
                                        @if($place)
                                        <select class="select2 mb-2 select2-multiple" id="placo" name="place" style="width: 100%" multiple="multiple" data-placeholder="Choose">
                                            @foreach($place as $data)
                                            <option value="{{ $data->id }}"  >{{ $data->place_name}}</option>
                                            @endforeach
                                        </select>
                                        @endif
                                            <!-- <a href="JavaScript:void(0);" class="btn btn-outline btn-success add_place text-light float-right">Opprett sted <i class="fa fa-plus" aria-hidden="true"></i></a>  -->
                                    </div>
                            </div>
                        </div>
                        <input type="hidden" name="status" value="0">
                        <div class="row">
                            <div class="col-md-12">
                                <label class="control-label">Beskrivelse</label>
                                    <div class="form-group">
                                        <textarea name="description" rows="5" style="width:100%;"></textarea>
                                    </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                    <label class="control-label">Konsekvens for:</label>
                                        <div class="form-group">
                                            <select name="consequence" class="form-control" required>
                                                <option value=''>Velg</option>
                                                <option value='0'>Foretaket</option>
                                                <option value='1'>Kunden</option>
                                                <option value='2'>Andre</option>
                                            <select>
                                        </div>
                            </div>
                           
                            <div class="col-md-6">
                                <label class="control-label">forslag til forbedring:</label>
                                    <div class="form-group">
                                        <input type="text" name="proposal" required class="form-control">
                                    </div>
                            </div>
                        </div>
                        <div class="row">
                            
                            <div class="col-md-12">
                                <label class="control-label">Legg til bilde</label>
                                <div class="form-group drop_file">
                                    <input type="file" name="doc" class="dropify" />
                                </div>
                            </div>
                        </div>
                        <hr>
                        <!-- To be removed -->
                        <div class="row ">
                            <div class="col-md-12">
                                <div class="form-group ">
                                    <label class="control-label">Rapportert av:</label>
                                    @if($employ)
                                    <select name="employee" required class="form-control">
                                        <option value=''>Velg ansatt</option>
                                       @foreach($employ as $data)
                                            <option value="{{ $data->id }}">{{ $data->name}}</option>
                                       @endforeach
                                    </select>
                                    <small class="invalid-feedback">Employee required</small>
                                    @endif
                                </div>
                            </div>
                            <!-- <div class="col-md-6">
                                <label class="control-label">Dato:</label>
                                    <div class="form-group">
                                        <div class="input-group" >
                                            <input type="text" name='emp_date' id="devrange" class=" form-control  form-control-danger  ">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">
                                                    <span class="ti-calendar"></span>
                                                    </span>
                                                </div>
                                        </div>
                                    </div>
                            </div> -->
                        </div> 
                        <div class="row">
                            <div class="col-md-6">
                                <div class="checkbox checkbox-info checkbox-circle">
                                    <input id="checkbox8" name="anonymous" type="checkbox" value="1">
                                        <label for="checkbox8">Ønsker du å være anonym? </label>
                                </div>
                            </div>
                        </div>

                    </div>
            </div>
                        {{ csrf_field() }}
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success"> <i class="fa fa-check"></i> Lagre</button>
                        <button type="button" class="btn btn-inverse" data-dismiss="modal">Avbryt</button>
                    </div>
                </form>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>

    
<!-- Modal to show the add place -->
<div id="dev_place" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="vcenter" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="vcenter">Opprett Place</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <!----Form Data -->
                <form action="javascript:void(0);" class="place" novalidate>
                    <div class="form-group row">
                        <label class="control-label col-lg-3">Place Navn</label>
                        <div class="col-lg-9">
                            <input type="text" name="pName" required class="form-control">
                            <small class="invalid-feedback">Place Navn is required </small>
                        </div>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success waves-effect text-left" >Lagre</button>
            </div>
            </form>

        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>


@endsection