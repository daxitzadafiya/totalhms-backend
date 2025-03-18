@extends('templates.monster.main')

@push('before-styles')
<link rel="stylesheet" href="https://demo.worksuite.biz/plugins/bower_components/bootstrap-datepicker/bootstrap-datepicker.min.css">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
@endpush

@push('after-scripts')
<script src="https://demo.worksuite.biz/plugins/bower_components/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/select2/dist/js/select2.full.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/gauge/gauge.min.js') }}"></script>

<script src="{{ asset('/filejs/Project/editproject.js') }}"></script>

<script>
// Adding the widget
jQuery(function () {
    "use strict";
    // ============================================================== 
    // Foo1 total visit
    // ============================================================== 
    var opts = {
        angle: 0, // The span of the gauge arc
        lineWidth: 0.42, // The line thickness
        radiusScale: 1, // Relative radius
        pointer: {
            length: 0.64, // // Relative to gauge radius
            strokeWidth: 0.04, // The thickness
            color: '#000000' // Fill color
        },
        limitMax: false,     // If false, the max value of the gauge will be updated if value surpass max
        limitMin: false,     // If true, the min value of the gauge will be fixed unless you set it manually
        colorStart: '#009efb',   // Colors
        colorStop: '#009efb',    // just experiment with them
        strokeColor: '#E0E0E0',  // to see which ones work best for you
        generateGradient: true,
        highDpiSupport: true     // High resolution support
    };
    var target = document.getElementById('unique'); // your canvas element
    var gauge = new Gauge(target).setOptions(opts); // create sexy gauge!
    <?php
             $currentdate = strtotime(date('Y-m-d'));
             $startdate = strtotime($docs->startdate);
             $enddate = strtotime($docs->closeddate);
             $total = $enddate-$startdate;
             $remaining = $enddate-$currentdate;
             $tot = round($total / 86400);
             $remaindays = round($remaining / 86400 );

    ?>
    gauge.maxValue = <?php echo $tot ?>; // set max gauge value
    gauge.setMinValue(0);  // Prefer setter over gauge.minValue = 0
    gauge.animationSpeed = 32; // set animation speed (32 is default value)
    gauge.set(<?php echo $remaindays ?>);

});
</script>

@endpush

@section('content')
@include('common.errors')
@include('common.success')

<div class="row">
    <div class="col-md-6">
        <div class="card card-outline-warning">
            <div class="card-header">
                <h4 class="mb-0 text-white">Project</h4>
            </div>
            <div class="card-body" style="margin:10%;">
                <h3 class="card-title">{{ isset($docs->project_name) ? ucfirst($docs->project_name) : ''}}</h3>
                <small>Project number : </small><span class="card-text">{{ isset($docs->project_id) ? ucfirst($docs->project_id) : ''}}</span>
                <br>
                <small>Address : </small> <span class="card-text">{{ isset($docs->address) ? ucfirst($docs->address) : ''}}</span><br>
                <small>City : </small><span class="card-text">{{ isset($docs->city) ? ucfirst($docs->city) : ''}}</span><br>
                <small>Zip code : </small><span class="card-text">{{ isset($docs->zipcode) ? ucfirst($docs->zipcode) : ''}}</span><br>
                <small>Estimated time consumption : </small><span class="card-text">{{ isset($docs->budget) ? ucfirst($docs->budget) : ''}}</span><br>
                <!-- <small>StartDate : </small><span class="card-text">{{ isset($docs->startdate) ? ucfirst($docs->startdate) : ''}}</span><br>
                <small>ClosedDate : </small><span class="card-text">{{ isset($docs->closeddate) ? ucfirst($docs->closeddate) : ''}}</span><br> -->
                <!-- <small>Project Responsible : </small><span class="card-text"></span><br> -->
                <hr class="mt-5">
                <a href="javascript:void(0);" data-toggle="modal" class="btn btn-inverse editproject">Endre project</a>
            </div>
        </div>
    </div>
    @php
        $currentdate = strtotime(date('Y-m-d'));
        $startdate = strtotime($docs->startdate);
        $enddate = strtotime($docs->closeddate);
        $total = $enddate-$startdate;
        $remaining = $enddate-$currentdate;
        $tot = round($total / 86400);
        $remaindays = round($remaining / 86400 );
    @endphp
    <div class="col-lg-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <h4 class="text-center"> {{ $remaindays }} Days Left</h4>
                <div class="gaugejs-box">
                    <canvas id="unique" class="gaugejs">guage</canvas>
                </div>
            </div>
            <div class="box border-top text-center">
                <h4 class="font-medium mb-0">Start date : {{ $docs->startdate }}</h4>
                <h4 class="font-medium mb-0">Date of completion : {{ $docs->closeddate }}</h4> 
                <h4 class="font-medium mb-0">Total days : {{ $tot }} </h4>   
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-6 col-md-6">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-outline-info">
                    <div class="card-header">
                        <h4 class="mb-0 text-white">Suppliers</h4>
                    </div>
                    <div class="card-body" >
                        <table class="table">
                            <thead>
                                <th>Navn</th>
                                <th>Vat-Number</th>
                                <th>Address</th>
                                <!-- <th>City</th> -->
                                <!-- <th>Zipcode</th> -->
                                <th>Phone</th>
                                <th>Mail</th>
                            </thead>
                            <tbody>
                                @php
                                    $supply = \DB::table('project_suppliers')->whereIn('id', explode(',',$docs->suppliers))->get();
                                @endphp
                                @if($supply)
                                @foreach($supply as $supp)
                                <tr>
                                    <td><a href="JavaScript:void(0);" class="edit_supply" data-toggle="modal" data-id="{{ $supp->id }}" data-name="{{ $supp->name }}" data-vat_number="{{ $supp->vat_number }}"
                                         data-address="{{ $supp->address }}" data-city="{{ $supp->city }}" data-zipcode="{{ $supp->zipcode }}" data-phone="{{ $supp->phone }}" data-mail="{{ $supp->mail }}">{{ $supp->name }}</a></td>
                                    <td>{{ $supp->vat_number }}</td>
                                    <td>{{ $supp->address }}</td>
                                    <!-- <td>{{ $supp->city }}</td>
                                    <td>{{ $supp->zipcode }}</td> -->
                                    <td>{{ $supp->phone }}</td>
                                    <td>{{ $supp->mail }}</td>
                                </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                        <hr class="mt-5">
                        <a href="javascript:void(0);" data-toggle="modal" class="btn btn-inverse add_supply">New Supplier</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!--Project Modal -->
<div id="project_modal"  class="modal fade" tabindex="-1" role="dialog" aria-labelledby="vcenter" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            
            <div class="modal-header">
                <h4>Edit Project</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            
            <div class="modal-body">
                <form method="post" action="{{ url('/project/add') }}">
                
                <div class="row">
                    <div class="col-md-6">
                        <label class="control-label"><b>Project name:</b></label>
                        <div class="form-group">
                            <input type="text" name="project_name" value="{{ (old('project_name') != '' ) ? old('project_name') : (isset($docs->project_name) ? $docs->project_name : '') }}" class="form-control" required>
                        </div>
                    </div>
                    {{ csrf_field() }}
                    <div class="col-md-6">
                        <label><b>Project number:</b></label>
                            <div class="form-group">
                                <input type="number" name="project_id" value="{{ (old('project_id') != '' ) ? old('project_id') : (isset($docs->project_id) ? $docs->project_id : '') }}" class="form-control" required>
                            </div>
                    </div>
                </div>
                <input type="hidden" name="id" value="{{ $docs->id }}">
                 <div class="row">
                    <div class="col-md-6">
                        <label><b>Contracting authority:</b></label>
                            <div class="form-group">
                            @if($contract_contact)
                                <select name="contract_contact" class="form-control" required>
                                @foreach($contract_contact as $contact)
                                    <option {{ (isset($docs->contract_contact) && ($docs->contract_contact == $contact->id) ) ? "selected" : '' }} value='{{ $contact->id }}'>{{ $contact->bname}}</option>
                                @endforeach
                                </select>
                            @endif
                            </div>
                    </div>
                    <div class="col-md-6">
                        <label><b>Address:</b></label>
                            <div class="form-group">
                                <input type="text" name="address" value="{{ (old('address') != '' ) ? old('address') : (isset($docs->address) ? $docs->address : '') }}" class="form-control" required>
                            </div>
                    </div>
                </div>

                  <div class="row">
                            <div class="col-md-6">
                                <label><b>City:</b></label>
                                    <div class="form-group">
                                        <input type="text" name="city" value="{{ (old('city') != '' ) ? old('city') : (isset($docs->city) ? $docs->city : '') }}" class="form-control" required>
                                    </div>
                            </div>
                            <div class="col-md-6">
                                <label><b>Zip code:</b></label>
                                    <div class="form-group">
                                        <input type="number" name="zipcode" value="{{ (old('zipcode') != '' ) ? old('zipcode') : (isset($docs->zipcode) ? $docs->zipcode : '') }}" class="form-control" required>
                                    </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <label><b>Start date:</b></label>
                                    <div class="form-group">
                                        <div class="input-group">
                                            <input type="text" name='startdate' id="start_date" value="{{ (old('startdate') != '' ) ? old('startdate') : (isset($docs->startdate) ? $docs->startdate : '') }}"  class=" form-control form-control-danger  ">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">
                                                        <span class="ti-calendar"></span>
                                                    </span>
                                                </div>
                                        </div>
                                    </div>
                            </div>
                            <div class="col-md-6">
                                <label><b>Date of completion:</b></label>
                                    <div class="form-group">
                                        <div class="input-group">
                                            <input type="text" name='closeddate' id="closed_date" value="{{ (old('closeddate') != '' ) ? old('closeddate') : (isset($docs->closeddate) ? $docs->closeddate : '') }}" class=" form-control form-control-danger  ">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">
                                                        <span class="ti-calendar"></span>
                                                    </span>
                                                </div>
                                        </div>
                                    </div>
                            </div>
                        </div>

                        
                        <div class="row">
                            <div class="col-md-6">
                                <label><b>Project Manager:</b></label>
                                    @php
                                        $man = $docs->responsible ? explode(',',$docs->responsible) : [] ;
                                        $emp_involved = $docs->attendee ? explode(',', $docs->attendee) : [];
                                        $supps = $docs->suppliers ? explode(',', $docs->suppliers) : [];
                                    @endphp
                                    <div class="form-group">
                                    @if($employees)
                                        <select class="select2 mb-2 select2-multiple form-control" name="responsible[]" style="width: 100%" multiple="multiple" data-placeholder="Choose">
                                        @foreach($employees as $employ)
                                            <option {{ (isset($docs->responsible) && in_array($employ->id, $man ) ) ? "selected" : '' }} value='{{ $employ->id }}'>{{ $employ->name }}</option>
                                        @endforeach
                                        </select>
                                    @endif
                                    </div>
                            </div>
                            <div class="col-md-6">
                                <label><b>Estimated time consumption:</b></label>
                                    <div class="form-group">
                                        <input type="number" name="budget" class="form-control" value="{{ (old('budget') != '' ) ? old('budget') : (isset($docs->budget) ? $docs->budget : '') }}" placeholder="hours" required>
                                    </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <label><b>Employees involved:</b></label>
                                    <div class="form-group">
                                    @if($employees)
                                        <select class="select2 mb-2 select2-multiple form-control" name="attendee[]" style="width: 100%" multiple="multiple" data-placeholder="Choose">
                                        @foreach($employees as $employ) 
                                            <option {{ (isset($docs->attendee) && in_array($employ->id,  $emp_involved ) ) ? "selected" : '' }} value='{{ $employ->id }}'>{{ $employ->name }}</option>
                                        @endforeach
                                        </select>
                                    @endif
                                    </div>
                            </div>
                            <div class="col-md-6">
                                <label><b>Involved contractors</b></label>
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="input-group">
                                                    <select class="select2 mb-2 select2-multiple form-control" name="other_supplier[]" style="width: 100%" multiple="multiple" data-placeholder="Choose">
                                                    @if($supplier)
                                                    @foreach($supplier as $supp)
                                                        <option {{ (isset($docs->suppliers) && in_array($supp->id,  $supps ) ) ? "selected" : '' }}   value='{{ $supp->id }}'>{{ $supp->name}}</option>
                                                    @endforeach
                                                    @endif
                                                    </select>
                                                </div>
                                            </div>
                                            <!-- <div class="col-md-1">
                                                <div class="input-group-append">
                                                    <span class="input-group-addon">
                                                        <button type="button" class="btn btn-warning add_supplier"><i class="fa fa-plus"></i></button>
                                                    </span>
                                                </div>
                                            </div> -->
                                        </div>
                                    </div>
                            </div>
                        </div>

            </div>
            
            <div class="modal-footer">
                <button type="submit" class="btn btn-success waves-effect text-left">Lagre</button>
            </div>
            </form>

        </div>
    </div>
</div>



<!--Supplier Modal -->
<div id="supplier_modal"  class="modal fade" tabindex="-1" role="dialog" aria-labelledby="vcenter" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            
            <div class="modal-header">
                <h4>Add suppliers</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            
            <div class="modal-body">
                <form method="post" action="{{ url('/add/suppliers') }}">
                
                <div class="row">
                    <div class="col-md-6">
                        <label class="control-label">Name:</label>
                        <div class="form-group">
                            <input type="text" name="supplier_name" id="name" class="form-control" required>
                        </div>
                    </div>
                    {{ csrf_field() }}
                    <div class="col-md-6">
                        <label class="control-label">VAT-Number</label>
                        <div class="form-group">
                            <input type="number" name="vat_number" id="vatnumber" class="form-control" required>
                        </div>
                    </div>
                </div>  
                <input type="hidden" name="id" id="supp_id" value="">
                <input type="hidden" name="proj_id" value="{{ $docs->id }}">
                <input type="hidden" name="proj_supp" value="{{ $docs->suppliers }}">
                <div class="row">
                    <div class="col-md-6">
                        <label class="control-label">Address:</label>
                        <div class="form-group">
                            <input type="text" name="address" id="address" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="control-label">City:</label>
                        <div class="form-group">
                            <input type="text" name="city" id="city" class="form-control" required>
                        </div>
                    </div>
                </div>  

                <div class="row">
                    <div class="col-md-6">
                        <label class="control-label">Zipcode:</label>
                        <div class="form-group">
                            <input type="text" name="zipcode" id="zipcode" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="control-label">Phone:</label>
                        <div class="form-group">
                            <input type="number" name="phone" id="phone" class="form-control" required>
                        </div>
                    </div>
                </div> 

                <div class="row">
                    <div class="col-md-6">
                        <label class="control-label">Mail:</label>
                        <div class="form-group">
                            <input type="text" name="mail" id="mail" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label>Category</label>
                        <div clss="form-group">
                        @php
                        $cat = \DB::table("all_category")->where("table_name","suppliers")->get();
                        @endphp
                        @if($cat)
                            <select name="supplier_category" class="form-control" required>
                            @foreach($cat as $data)
                                    <option value='{{ $data->id }}'>{{ $data->des }}</option>
                            @endforeach
                            </select>
                        @endif
                        </div>
                    </div>
                </div> 


            </div>
            
            <div class="modal-footer">
                <button type="submit" class="btn btn-success waves-effect text-left">Lagre</button>
            </div>
            </form>

        </div>
    </div>
</div>

@endsection