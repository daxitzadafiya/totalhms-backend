@extends('templates.monster.main')

@push('before-styles')
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css" rel="stylesheet"
    type="text/css">
<link rel="stylesheet" type="text/css"
    href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/datatables/media/css/dataTables.bootstrap4.css') }}">

<style>
td.protd {
    color: #0198ca;
    cursor: pointer;
}
</style>
@endpush

@push('after-scripts')
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/moment/moment.js"></script>
<script src="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/datatables/datatables.js') }}"></script>
<script src="https://cdn.datatables.net/buttons/1.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.flash.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.2.2/js/buttons.print.min.js"></script>
<script src="/filejs/absence/absencelist.js"></script>
@endpush

@section('content')
<div class="row page-titles">
    <div class="col-md-6 col-8 align-self-center">
        <h3 class="text-themecolor mb-0 mt-0">Fravær</h3>
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">Sickness self-declaration </li>
        </ol>
    </div>
    <div class="col-md-6">
        <a href="{{ url('/ansatte/absenceadd')}}" class="btn float-right hidden-sm-down btn-success"><i
                class="mdi mdi-plus-circle"></i>Opprett fravær</a>
    </div>
</div>

<!-- tab-1 for the filters -->

<div class="card">
    <div class="card-body">
        <div class="row m-t-40">
            <!-- Column -->
            <div class="col-md-6 col-lg-2">
                <div class="card">
                    <div class="box bg-info text-center">
                        <h1 class="font-light text-white">{{ $new }}</h1>
                        <h6 class="text-white">nye</h6>
                    </div>
                </div>
            </div>
            <!-- Column -->
            <div class="col-md-6 col-lg-2">
                <div class="card">
                    <div class="box bg-primary text-center">
                        <h1 class="font-light text-white">{{ $processing }}</h1>
                        <h6 class="text-white">under behandling</h6>
                    </div>
                </div>
            </div>
            <!-- Column -->
            <div class="col-md-6 col-lg-2">
                <div class="card">
                    <div class="box bg-success text-center">
                        <h1 class="font-light text-white">{{ $Doc_Pending }}</h1>
                        <h6 class="text-white">Mangler Sykemelding</h6>
                    </div>
                </div>
            </div>
            <!-- Column -->
            <div class="col-md-6 col-lg-2">
                <div class="card">
                    <div class="box bg-dark text-center">
                        <h1 class="font-light text-white">{{ $closed }}</h1>
                        <h6 class="text-white">Registrert</h6>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-2">
                <div class="card">
                    <div class="box bg-danger text-center">
                        <h1 class="font-light text-white">{{ $declined }}</h1>
                        <h6 class="text-white">Avslått</h6>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <br>
        <div class="row">
            <div class="col-md-2">
                <div class="form-group">
                    <label>Ansatt</label>
                    <select name="employee" id="empid" required class="form-control">
                        <option selected value=''>Alle</option>
                    </select>
                </div>
            </div>

            <div class="col-md-2">
                <div class="form-group">
                    <label>Fraværsgrunn</label>
                    <select name="reason" id="absid" required class="form-control">
                        <option selected value=''>Alle</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="statid" required class="form-control">
                        <option selected value=''>Alle</option>
                    </select>
                </div>
            </div>


        </div>
    </div>
</div>



<!-- Tab-2 for the lists from database -->
<div class="card">
    <div class="card-body">
        <table id="doctable" class="table table-hover">
            <thead>
                <tr>
                    <th>Ansatt</th>
                    <th>Fraværsgrunn</th>
                    <th>Lengde</th>
                    <th>Status</th>
                    <!-- <th>Action</th> -->
                </tr>
            </thead>
            <tbody>
                @php
                $users= \DB::table('absence_list')->get();
                @endphp
                @if($users)
                <tr>
                    @foreach($users as $data)

                    <?php
                $cat_name = \DB::table("users")->where("id", $data->emp_id)->first();
                $cname='';
                if ($cat_name) {
                    $cname= $cat_name->name;
                }
                ?>
                    <!-- <a href="{{ url('/ansatte/absenceprocess/' . $data->id) }}" data-id="{{ $data->id }}" class="proc" >{{$cname}}</a> -->
                    <td data-id="{{ $data->id }}" class='protd'>
                        {{$cname}}

                    </td>
                    <td><?php
$cat_name = \DB::table("absence_reason")->where("id", $data->abs_reason)->first();
if ($cat_name) {
    echo $cat_name->name;
}
?>
                    </td>
                    <td>
                        <?php
                    echo $data->total_days . " dager";
                    ?>
                    </td>
                    <?php
                    if ($data->status == 0) {
                        $status = "Ny";
                    } elseif ($data->status == 1) {
                        $status= "Under behandling ";
                    } elseif ($data->status == 2) {
                        $status= "Mangler Sykemelding";
                    } elseif ($data->status == 3) {
                        $status = "Avslått";
                    } elseif ($data->status ==4) {
                        $status= "Registrert";
                    }
                    ?>
                    <td>
                        {{ $status }}
                    </td>
                    <!-- <td> -->
                    <!-- <a href="{{ url('/ansatte/absenceprocess/'.$data->id) }}" data-id="{{ $data->id }}" class="proc" ><i class="icon-pencil"></i></a> -->
                    <!-- <a href="javascript:void(0);" data-id="{{ $data->id }}" class="delete_absence"><i class="icon-trash" > -->
                    <!-- </i></a> -->
                    <!-- </td> -->
                </tr>
                @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>

@endsection