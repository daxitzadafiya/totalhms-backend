@extends('templates.monster.main')

@push('before-styles')
<style>
    div#goals_filter {
        display: none;
    }
    div#template_filter {
        display: none;
    }

</style>
<link rel="stylesheet" type="text/css" href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/datatables/media/css/dataTables.bootstrap4.css') }}">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css') }}" rel="stylesheet" type="text/css">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/daterangepicker/daterangepicker.css') }}" rel="stylesheet">
@endpush

@push('after-scripts')
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/moment/moment.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/daterangepicker/daterangepicker.js') }}"></script>
<!-- This is data table -->
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/datatables/datatables.js') }}"></script>
<script src="/filejs/company/goals/listgoals.js"></script>
<!-- end - This is for export functionality only -->
<script>
   
</script>

@endpush

@section('content')

@include('common.errors')
@include('common.success')
<div class="row page-titles">
    <div class="col-md-6 col-8 align-self-center">
        <h3 class="text-themecolor mb-0 mt-0">HMS-mål for foretaket</h3>
    </div>
    <div class="col-md-6 col-4 align-self-center">
        <a href="{{ url('foretak/goal/ny')}}" class="btn float-right hidden-sm-down btn-success"><i class="mdi mdi-plus-circle"></i>
        Opprett mål</a>

    </div>
</div>
<ul class="nav  customtab" role="tablist">


    <li class="">
        <a class="nav-link   active  " data-toggle="tab" href="#tab4" role="tab">
            <span class="hidden-sm-up"> <i class="ti-home"></i></span>
            <span class="hidden-xs-down">Mål</span>
        </a>
    </li>
  {{--<li class="">
        <a class="nav-link   " data-toggle="tab" href="#tab6" role="tab">
            <span class="hidden-sm-up"> <i class="ti-home"></i></span>
            <span class="hidden-xs-down">Mal</span>
        </a>
    </li>--}}


</ul>
<div class="tab-content  ">


    <div class="tab-pane  active  " id="tab4" role="tabpanel">
            @php
            //1=new,2=pending,3=action plan,4=closed
            $new = \DB::table("goals")->where("companyId",1)->where("status",1)->count();
            $pending = \DB::table("goals")->where("companyId",1)->where("status",2)->count();
            $all = \DB::table("goals")->where("companyId",1)->count();
            $closed = \DB::table("goals")->where("companyId",1)->where("status",4)->count();

            @endphp
        <div class="card">
            <div class="card-body">
            <div class="row m-t-40">
                    <!-- Column -->
                    <div class="col-md-6 col-lg-3 col-xlg-3">
                        <div class="card">
                            <div class="box bg-info text-center">
                                <h1 class="font-light text-white">{{ $all }}</h1>
                                <h6 class="text-white">Totalt</h6>
                            </div>
                        </div>
                    </div>
                    <!-- Column -->
                    <div class="col-md-6 col-lg-3 col-xlg-3">
                        <div class="card">
                            <div class="box bg-primary text-center">
                                <h1 class="font-light text-white">{{ $new }}</h1>
                                <h6 class="text-white">Nye mål</h6>
                            </div>
                        </div>
                    </div>
                    <!-- Column -->
                    <div class="col-md-6 col-lg-3 col-xlg-3">
                        <div class="card">
                            <div class="box bg-success text-center">
                                <h1 class="font-light text-white">{{ $pending }}</h1>
                                <h6 class="text-white">Pågående</h6>
                            </div>
                        </div>
                    </div>
                    <!-- Column -->
                    <div class="col-md-6 col-lg-3 col-xlg-3">
                        <div class="card">
                            <div class="box bg-dark text-center">
                                <h1 class="font-light text-white">{{ $closed}}</h1>
                                <h6 class="text-white">Fullført</h6>
                            </div>
                        </div>
                    </div>
                    <!-- Column -->
                </div>
                <h4>Innstillinger</h4>
                <hr>
                <br>
                <div class="row">

                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Status</label>

                            <select name="type" id="type" required class="form-control">
                                <option selected value=''>Alle</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2.5">
                        <div class="form-group">
                            <label>Periode</label>
                            <div class='input-group mb-3'>
                                <input type='text' class="form-control daterange" />
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
                            <label>Type</label>

                            <select id="status" required class="form-control">
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
                   {{--<div class="col-md-3.5">
                        <div class="form-group">
                            <label>Opprinnelse</label><br>
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-primary active">
                                    <input type="radio" name="options" value="" class="sMade" id="option1" autocomplete="off" checked> Alle
                                </label>
                                <label class="btn btn-primary">
                                    <input type="radio" name="options" value="2" class="sMade" id="option2" autocomplete="off">Egendefinert</label>
                                <label class="btn btn-primary">
                                    <input type="radio" name="options" value="1" class="sMade" id="option3" autocomplete="off"> Tilpasset mal
                                </label>
                            </div>
                        </div>--}}

                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                    <table id="goals" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Navn</th>
                                <th>Type</th>
                                <th>Ansvarlig</th>
                                <th>Status</th>
                                <th>Lagt til</th>
                            </tr>
                        </thead>
                    </table>
            </div>
        </div>
    </div>

<div class="tab-pane  " id="tab6" role="tabpanel">
    @if(count($tData) != 0)
        <div class="card">
            <div class="card-body">
                <h4>Innstillinger</h4>
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

                </div>
            </div>
        </div>
        @endif
        <div class="card">
            <div class="card-body">
                <div class="table-responsive m-t-40">
                    <table id="template" class="table table-hover">
                        <thead>
                            <tr>
                                <th>Instruks</th>
                                <th>Kategori</th>
                                <th>Valg</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(count($tData) == 0)
                            <tr>
                                <td colspan="3" align="center">{{ "No data Found  " }}</td>
                            </tr>
                            @else

                            @foreach($tData as $data)
                            <tr>
                                <td>{{ ucfirst($data->title) }}</td>
                                <td>
                                    @php
                                    if($data->category_id){
                                    $inId = explode(',',$data->category_id);
                                    $catname = \DB::table("all_category")->select("des")->where("id",$data->category_id)->first();

                                    }
                                    @endphp
                                    {{ isset($catname->des) ? $catname->des : ""}}
                                </td>
                                <td>
                                    <a href="/template/rutine/{{ $data->id }}" class="btn waves-effect waves-light btn-outline-secondary">Bruk Mal</button>
                                        @if(\Auth::user()->role == 1)
                                        <a style="padding-left: 1em;" href="/edit/template/{{ $data->id }}" data-toggle="tooltip" data-id="{{ $data->id }}" data-original-title="Edit" class="edit  editProduct"><i class="ti-pencil-alt"></i></a>
                                        <a href="javascript:void(0)" data-toggle="tooltip" data-id="{{ $data->id }}" onclick="del();" data-original-title="Delete" class="del  deleteProduct"><i class="ti-trash"></i></a>
                                        @endif
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



</div>
</div>
</div>
</div>

@endsection

