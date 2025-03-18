@extends('templates.monster.main')

@section('content')


@include('common.errors')
@include('common.success')
<div class="row page-titles">
    <div class="col-md-6 col-8 align-self-center">
        <h3 class="text-themecolor mb-0 mt-0">Template</h3>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="javascript:void(0)">Company</a></li>
            <li class="breadcrumb-item active">Instruction Template</li>
        </ol>
    </div>
    <div class="col-md-6 col-4 align-self-center">
        {{--<button class="right-side-toggle waves-effect waves-light btn-info btn-circle btn-sm float-right ml-2"><i class="ti-settings text-white"></i></button>

        <a href="{{ url('/add/template')}}" class="btn float-right hidden-sm-down btn-success"><i class="mdi mdi-plus-circle"></i> Add Company</a>
        --}}
    </div>
</div>
<!-- ============================================================== -->
<!-- Start Page Content -->
<!-- ============================================================== -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body"> 
                    <div class="form-body">
                            <div class="row pt-3">
                                <div class="col-md-12">
                                        <div class="form-group">
                                                <label class="control-label">Title</label>
                                                <br>
                                                {{ (isset($vData->title) ? $vData->title : '') }}
                                             </div>
                                </div>
                                <!--/span-->
    
    
                                <!--/span-->
                                <div class="col-md-12">
                                    <div class="form-group ">
                                        <label class="control-label">Category</label>
                                            <br>
                                        @php
                                        if(isset($vData->category_id)){
                                            $cat = \DB::table("all_category")->where("table_name","instructions")->where("companyId",\Auth::user()->companyId)
                                        ->where("id",$vData->category_id)->first();     
                                        }
                                        @endphp
                                        @if($cat)
                                      <b> {{ ucfirst($cat->des )}}</b>
                                        @endif
                                      
                            </div>
                            </div>
                                <div class="col-md-12">
                                    <div class="form-group ">
                                        <label class="control-label">Template</label>
                                            <br>
                                   
                                            {!! isset($vData->des) ? htmlspecialchars_decode($vData->des) : '' !!}                                      
                            </div>
              

               
        </div>
    </div>
</div>
@endsection
