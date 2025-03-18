@extends('templates.monster.main')
@push('after-styles')
<link rel="stylesheet" href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/dropify/dist/css/dropify.min.css') }}">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/daterangepicker/daterangepicker.css') }}" rel="stylesheet">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/summernote/dist/summernote-bs4.css') }}" rel="stylesheet" />
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css') }}" rel="stylesheet" type="text/css">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/ion-rangeslider/css/ion.rangeSlider.css') }}" rel="stylesheet">
<link href="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/ion-rangeslider/css/ion.rangeSlider.skinModern.css') }}" rel="stylesheet">
<style>
    .note-toolbar.card-header {
        background-color: bisque;
    }


    .align-self-center {
        padding: 17px;
        margin: 0 auto;
    }
</style>

@endpush

@push('after-scripts')
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/dropify/dist/js/dropify.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/ion-rangeslider/js/ion-rangeSlider/ion.rangeSlider.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/monster/js/jasny-bootstrap.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/summernote/dist/summernote-bs4.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/moment/moment.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/daterangepicker/daterangepicker.js') }}"></script>
<script src="{{ asset('/filejs/employee/employeeview.js') }}"></script>
@endpush
@section('content')

@include('common.errors')
@include('common.success')
<!-- ============================================================== -->
<!-- Start Page Content -->
<!-- ============================================================== -->
@php
if ($eData->profile_image) {
$path = '/file_uploader/company/'.$eData->companyId.'/employee/profile/'. $eData->id .'/'. $eData->profile_image ;
}else{
$path = "/vendor/wrappixel/monster-admin/4.2.1/assets/images/users/1.jpg";
}
@endphp
<div class="row">
    <!-- Column -->
    <div class="col-md-4 col-xs-12">
        <div class="card">
            <div class=" card-inverse social-profile d-flex ">
                <div class="align-self-center"> <img src="{{ asset($path)}}" class="img-circle" width="100">
                    <h4 class="card-title">{{ isset($eData->name) ? ucfirst($eData->name) : ''}}</h4>
                </div>
            </div>
        </div>
        <div class="card">

            <div class="card-body">
                {{--
            <small class="text-muted">Email address </small>
                <h6>hannagover@gmail.com</h6> <small class="text-muted p-t-30 db">Phone</small>
                <h6>+91 654 784 547</h6> <small class="text-muted p-t-30 db">Address</small>
                <h6>71 Pilgrim Avenue Chevy Chase, MD 20815</h6>
                <div class="map-box">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d470029.1604841957!2d72.29955005258641!3d23.019996818380896!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x395e848aba5bd449%3A0x4fcedd11614f6516!2sAhmedabad%2C+Gujarat!5e0!3m2!1sen!2sin!4v1493204785508" width="100%" height="150" frameborder="0" style="border:0" allowfullscreen></iframe>
                </div> <small class="text-muted p-t-30 db">Social Profile</small>
                <br/>
                <button class="btn btn-circle btn-secondary"><i class="fab fa-facebook"></i></button>
                <button class="btn btn-circle btn-secondary"><i class="fab fa-twitter"></i></button>
                <button class="btn btn-circle btn-secondary"><i class="fab fa-youtube"></i></button>
            --}}
            </div>
        </div>
    </div>
    <!-- Column -->
    <!-- Column -->
    <div class="col-md-8 col-xs-12">
        <div class="card">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs profile-tab" role="tablist">
                <li class="nav-item "><a class="nav-link active" href="#profile" data-toggle="tab"> <span class="visible-xs"><i class="fa fa-user"></i></span> <span class="hidden-xs">Profile</span> </a> </li>
                <li class="nav-item"><a class="nav-link" href="#dependants" data-toggle="tab" aria-expanded="true"> <span class="visible-xs"><i class="icon-layers"></i></span> <span class="hidden-xs">Pårørende</span> </a> </li>
                <li class="nav-item"><a class="nav-link" href="#projects" data-toggle="tab" aria-expanded="true"> <span class="visible-xs"><i class="icon-layers"></i></span> <span class="hidden-xs">Projects</span> </a> </li>
                <li class="nav-item"><a class="nav-link" href="#tasks" data-toggle="tab" aria-expanded="false"> <span class="visible-xs"><i class="icon-list"></i></span> <span class="hidden-xs">Tasks</span> </a> </li>
                <li class="nav-item"><a class="nav-link" href="#leaves" data-toggle="tab" aria-expanded="false"> <span class="visible-xs"><i class="icon-logout"></i></span> <span class="hidden-xs">Leaves</span> </a> </li>
                <li class="nav-item"><a class="nav-link" href="#docs" data-toggle="tab" aria-expanded="false"> <span class="visible-xs"><i class="icon-docs"></i></span> <span class="hidden-xs">Documents</span> </a> </li>
                <li class="nav-item"><a class="nav-link sets" data-over_time= "{{ $eData->over_time }}" data-holiday="{{ $eData->holidays }}" href="#settings" data-toggle="tab" aria-expanded="false"> <span class="visible-xs"><i class="icon-clock"></i></span> <span class="hidden-xs">Innstillinger</span> </a> </li>

            </ul>
            <!-- Tab panes -->
            <!-- First Tab -->
            <div class="tab-content">
                <div class="tab-pane active" id="profile" role="tabpanel">
                    <div class="card-body">
                        <h5><b>Ansattinformasjon:</b></h5>
                        <div class="row">
                            <div class="col-md-6"> <strong>Navn</strong> <br>
                                <p class="text-muted">{{ isset($eData->name)? ucfirst($eData->name) : 'Nil' }}</p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-4 col-xs-6 "> <strong>Personnummer</strong> <br>
                                <p class="text-muted">{{ isset($eData->personal_no)? $eData->personal_no : 'Nil' }}</p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-xs-6 col-md-4  ">
                                <strong>Adresse</strong> <br>
                                    <p class="text-muted">{{ isset($eData->address)? ucfirst($eData->address) : 'Nil' }}</p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-xs-6 col-md-4  ">
                                <strong>Poststed</strong> <br>
                                    <p class="text-muted">{{ isset($eData->city)? ucfirst($eData->city) : 'Nil' }}</p>
                            </div>
                            <div class="col-xs-6 col-md-4  ">
                                <strong>Postnummer</strong> <br>
                                    <p class="text-muted">{{ isset($eData->zipcode)? ucfirst($eData->zipcode) : 'Nil' }}</p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-4  "> <strong>E-post</strong> <br>
                                <p class="text-muted">{{ isset($eData->email)? $eData->email : 'Nil' }}</p>
                            </div>
                            <div class="col-xs-6  "> <strong>Telefonnummer</strong> <br>
                                <p class="text-muted">{{ isset($eData->phone)? $eData->phone : 'Nil' }}</p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-4 "> <strong>Kontonr</strong> <br>
                                <p class="text-muted">{{ isset($eData->acount_no)? $eData->acount_no : 'Nil' }}</p>
                            </div>
                            <div class="col-md-4 "> <strong>Timelønn</strong> <br>
                                <p class="text-muted">{{ isset($eData->salary)? $eData->salary : 'Nil' }}</p>
                            </div>
                        </div>
                        <hr>
                    </div>

                    <div class="card-body">
                        <h5><b>Arbeidsforhold:</b></h5>
                            <div class="row">
                                <div class="col-md-4"> <strong>Department</strong> <br>
                                    <p class="text-muted">{{ isset($eData->departments)? $eData->departments : 'Nil' }}</p>
                                </div>
                            <div class="col-md-4 "> <strong>Job Title</strong> <br>
                                <p class="text-muted">{{ isset($eData->user_role)? ucfirst($eData->user_role) : 'Nil' }}</p>
                            </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-4 "> <strong>Nærmeste leder</strong> <br>
                                    <p class="text-muted">{{ isset($eData->nearest_manager)? $eData->nearest_manager : 'Nil' }}</p>
                                </div>
                                <div class="col-md-4 "> <strong>Ansatt dato</strong> <br>
                                    <p class="text-muted">{{ isset($eData->employed_since)? $eData->employed_since : 'Nil' }}</p>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-4 "> <strong>Stillingsbrøk: 0-100%</strong> <br>
                                    <p class="text-muted">{{ isset($eData->emp_per)? $eData->emp_per : 'Nil' }}</p>
                                </div>
                                <div class="col-md-4 "> <strong>Ferie</strong> <br>
                                    <p class="text-muted">{{ isset($eData->holidays)? $eData->holidays : 'Nil' }}</p>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="card-body">
                            <h5><b>Tillegg:</b></h5>
                                <div class="row">
                                    <div class="col-md-4"> <strong>Overtidstillegg</strong> <br>
                                        <p class="text-muted">{{ isset($eData->over_time)? $eData->over_time : 'Nil' }}</p>
                                    </div>
                                    <div class="col-md-4"> <strong>Helge-/nattillegg</strong> <br>
                                        <p class="text-muted">{{ isset($eData->night_allowance)? $eData->night_allowance : 'Nil' }}</p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-4"> <strong>Skattekommune</strong> <br>
                                        <p class="text-muted">{{ isset($eData->tax_area)? $eData->tax_area : 'Nil' }}</p>
                                </div>
                        </div>

                    </div>
                </div>
                <!--second tab-->
                <div class="tab-pane" id="dependants" role="tabpanel">
                    <div class="card-body">
                        <a href="javascript:void(0);" data-target="#dependt" data-toggle="modal" class="btn btn-inverse pull-right">Opprett pårørende</a>
                        <table class="table">
                            <thead>
                                <th>Navn</th>
                                <th>Telefonnummer</th>
                                <th>Relasjon</th>
                            </thead>
                            @php
                            if($eData->id){
                            $dependents = \DB::table("dependants")->where("employee_id",$eData->id)->get();
                            }
                            @endphp
                            <tbody>
                                @if($dependents)
                                @foreach ($dependents as $item)
                                <tr>
                                    <td>
                                    <a href="javascript:void(0);" data-id="{{$item->id}}" data-dob="{{$item->dob}}"  data-dName="{{$item->dName}}"  data-dPhone="{{$item->dPhone}}" data-relation="{{$item->relation}}" data-target="#dependt" data-toggle="modal" class="depadd">
                                    {{ $item->dName }}
                                    </a></td>
                                    <td>{{ $item->dPhone }}</td>
                                    <td>
                                    <?php $cat_name = \DB::table("dependentrelations")->where("id", $item->relation)->first();
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
                <!-- sixth tab -->
                <div class="tab-pane" id="docs" role="tabpanel">
                    <div class="card-body">
                        <a href="javascript:void(0);" data-target="#document" data-toggle="modal" class="btn btn-inverse pull-right">Ny Dokument</a>
                        <table class="table">
                            <thead>
                                <th>FilName</th>
                                <th>Kategory</th>
                                <th>Valg</th>
                            </thead>
                            <tbody>
                                @php
                                $document = \DB::table("employee_doc")->where('user_id', $eData->id )->get();
                                @endphp
                                <tr>
                                    @if($document)
                                    @foreach($document as $doc)
                                    <td>
                                    <a href="#" data-toggle="modal"  data-id="{{ $doc->id }}" data-name="{{ $doc->filename }}" data-target="#pre{{ $doc->id }}" class="editProduct "  >
                                    {{ $doc->filename }} </a>

                                    <!-- View the document -->
                                    <div id="pre{{ $doc->id }}" class="modal fade bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel"  aria-hidden="true" style="display: none;">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content" style="height:800px;">
                                            <div class="modal-header">
                                                <h4 class="modal-title" id="myLargeModalLabel">{{ ucfirst($doc->filename) }}</h4>
                                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                            </div>
                                            <div class="modal-body">
                                                <iframe width ="100%" height="90%" src="{{ asset('file_uploader/company/'.\Auth::user()->companyId. '/document/'.$doc->doc) }}"></iframe>

                                            </div>
                                        </div>
                                        <!-- /.modal-content -->
                                    </div>
                                    <!-- /.modal-dialog -->
                                </div>



                                    </td>
                                    <td>  <?php $cat_name = \DB::table("all_category")->where("id", $doc->category)->first();
if ($cat_name) {
    echo $cat_name->des;
}
?> </td>

                                    <td>
                                        <a href="javascript:void(0);" data-id="{{$doc->id}}"  data-filename="{{$doc->filename}}"  data-category="{{$doc->category}}" data-target="#document" data-toggle="modal" class="docedt"><i class="icon-pencil"></i></a>
                                        <a href="javascript:void(0);" data-id="{{ $doc->id }}" class="delete_doc"><i class="icon-trash"></i></a>
                                    </td>

                                </tr>
                                @endforeach
                                    @endif
                            </tbody>
                        </table>
                    </div>
                </div>


                <!-- 6th tab ends -->
                <div class="tab-pane" id="projects" role="tabpanel">
                    <div class="card-body">

                    </div>
                </div>

                <div class="tab-pane"  id="settings" role="tabpanel">
                    <div class="card-body">
                        <form class="form-horizontal form-material" method="post" enctype="multipart/form-data" action='{{ url("/ansatte/add/employee") }}'>
                            {{ csrf_field() }}
                            <div class="card-body">
                                <h5><b>Ansattinformasjon:</b></h5>
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label class="control-label">Navn</label>
                                            <input type="text" name="name" value="{{ isset($eData->name) ? ucfirst($eData->name) : ''}}" required="" class="form-control" aria-required="true" aria-invalid="false">
                                            <input type="hidden" id="xid" name="id" value="" />
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                             <label>Personnummer</label>
                                                <div>
                                                    <input type="number" name="department" value="{{ isset($eData->personal_no) ? ucfirst($eData->personal_no) : ''}}" required="" class="form-control" aria-required="true" aria-invalid="false">
                                                </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label>Adresse</label>
                                                <div>
                                                    <input type="text" name="address" value="{{ isset($eData->address) ? ucfirst($eData->address) : ''}}" required="" class="form-control" aria-required="true" aria-invalid="false">
                                                </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label>Poststed</label>
                                                <div>
                                                    <input type="text" name="city" value="{{ isset($eData->city) ? ucfirst($eData->city) : ''}}" required="" class="form-control" aria-required="true" aria-invalid="false">
                                                </div>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Postnummer</label>
                                                <div>
                                                    <input type="number" name="zipcode" value="{{ isset($eData->zipcode) ? ucfirst($eData->zipcode) : ''}}" required="" class="form-control" aria-required="true" aria-invalid="false">
                                                </div>
                                        </div>
                                    </div>
                                        <div class="row">
                                            <div class="form-group col-md-6">
                                                <label for="example-email">E-post</label>
                                                    <div>
                                                        <input type="email" name="epost" value="{{ isset($eData->email) ? ucfirst($eData->email) : ''}}" required="" class="form-control" aria-required="true" aria-invalid="false">
                                                    </div>
                                            </div>
                                            <div class="form-group col-md-6" >
                                                <label>TeleFonnummer</label>
                                                    <div >
                                                        <input type="number" name="phone" value="{{ isset($eData->phone) ? ucfirst($eData->phone) : ''}}" required="" class="form-control" aria-required="true" aria-invalid="false">
                                                    </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="form-group col-md-6">
                                                <label>Kontonr</label>
                                                    <div>
                                                        <input type="number" name="acount_no" value="{{ isset($eData->acount_no)? $eData->acount_no : 'Nil' }}" required="" class="form-control" aria-required="true" aria-invalid="false">
                                                    </div>
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label>Timelønn</label>
                                                    <div >
                                                        <input type="number" name="salary" value="{{ isset($eData->salary) ? ucfirst($eData->salary) : ''}}" required="" class="form-control" aria-required="true" aria-invalid="false">
                                                    </div>
                                            </div>
                                        </div>
                            </div>
                            <div class="card-body">
                                <h5><b>Arbeidsforhold:</b></h5>
                                    <div class="row">
                                         @php
                                            $cid = \Auth::User()->companyId;
                                            $departments = \DB::table('companydepartment')->where('companyId', $cid)->get();
                                            $companyroles = \DB::table('companyroles')->where('companyId', $cid)->get();
                                        @endphp
                                        <div class="form-group col-md-6">
                                            <label>Avdeling</label>
                                                @if($departments)
                                                <div>
                                                    <select class="form-control form-control-line depart" name="departments">
                                                    @foreach($departments as $data)
                                                        <option {{ isset( $eData->departments) && $eData->departments == $data->id ? "selected" : "" }} value="{{ $data->id }}">{{ $data->name }}</option>
                                                    @endforeach
                                                    </select>
                                                </div>
                                                @endif
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Job Title</label>
                                            @if($companyroles)
                                                <div>
                                                    <select name="role" required class="form-control form-control-line roles">
                                                        @foreach($companyroles as $data)
                                                        <option {{ isset( $eData->user_role) && $eData->user_role == $data->id ? "selected" : "" }} value="{{ $data->id }}">{{ $data->jobtitle }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row">
                                        @if($employee)
                                        <div class="form-group col-md-6">
                                            <label>Nærmeste lede</label>
                                                <div>
                                                <select class="form-control form-control-line" name="near_manager">
                                                    <option value="">Velg nearest manager </option>
                                                    @foreach($employee as $data)
                                                        <option {{ isset( $eData->nearest_manager) && $eData->nearest_manager == $data->id ? "selected" : "" }} value="{{ $data->id }}">{{ $data->name }}</option>
                                                    @endforeach
                                                    </select>
                                                </div>
                                        @endif
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Ansatt dato</label>
                                                <div>
                                                    <input type="text" name='emp_date' id="ansatt_date" required="" class="form-control" aria-required="true" aria-invalid="false" value="{{ isset($eData->employed_since)? $eData->employed_since : 'Nil' }}">
                                                </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label>Stillingsbrøk: 0-100%</label>
                                                <div>
                                                    <input type="number" name="emp_percentage" value="{{ isset($eData->emp_per) ? ucfirst($eData->emp_per) : ''}}" required="" class="form-control" aria-required="true" aria-invalid="false">
                                                </div>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label class="control-label">Ferie</label>
                                                <div>
                                                    <input type="text" id="holiday" name="holiday" class="form-control  form-control-danger" value="{{ (old('holiday') != '' ) ? old('holiday') :  '' }}">
                                                        <small class="invalid-feedback"> Holiday required </small>
                                                </div>
                                        </div>
                                    </div>
                            </div>
                            <div class="card-body">
                                <h5><b>Tillegg:</b></h5>
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label class="control-label">Overtidstillegg</label>
                                                <div>
                                                    <input type="text" id="range_01" name="over_time" class="form-control  form-control-danger" value="{{ (old('over_time') != '' ) ? old('over_time') :  '' }}">
                                                        <small class="invalid-feedback"> this required</small>
                                                </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label>Helge-/nattillegg</label>
                                                <div>
                                                    <input type="number" name="nighshift" value="{{ isset($eData->night_allowance)? $eData->night_allowance : 'Nil' }}" required="" class="form-control" aria-required="true" aria-invalid="false">
                                                </div>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Skattekommune</label>
                                                <div>
                                                    <input type="number" name="tax_area" value="{{ isset($eData->tax_area)? $eData->tax_area : 'Nil' }}" required="" class="form-control" aria-required="true" aria-invalid="false">
                                                </div>
                                        </div>
                                    </div>
                            </div>
                                        <div class="form-group col-md-6">
                                            <div>
                                                <button type="submit" class="btn btn-success update" data-id="{{ $eData->id  }}" >Update Profile</button>
                                            </div>
                                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Column -->
</div>




<!--Kontatct Modal -->
<div id="verticalcenter" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="vcenter" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <!----Form Data -->
                <form action="javascript:void(0);" class="needs-validation" novalidate>
                    <div class="row">

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Foretaksnavn</label>
                                <input type="text" name="name" value="{{ (old('name') != '' ) ? old('name') : (isset($eData->bname) ? $eData->bname : '') }}" required class="form-control">
                                <small class="invalid-feedback"> Name is required </small>
                            </div>
                        </div>
                        <!--/span-->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Organisasjonsnummer</label>
                                <input type="number" required name='vat_no' class="form-control  form-control-danger" value="{{ (old('vat_no') != '' ) ? old('vat_no') : (isset($eData->vat_no) ? $eData->vat_no : '') }}">
                                <small class="invalid-feedback"> Organisasjonsnummer is required </small>
                            </div>
                        </div>
                        <!--/span-->
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Adresse</label>
                                <input type="text" required name="address" class="form-control  form-control-danger" value="{{ (old('address') != '' ) ? old('address') : (isset($eData->address) ? $eData->address : '') }}">
                                <small class="invalid-feedback"> Adresse required </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Poststed</label>
                                <input type="text" required name='city' class="form-control  form-control-danger" value="{{ (old('city') != '' ) ? old('city') : (isset($eData->city) ? $eData->city : '') }}">
                                <small class="invalid-feedback"> Poststed required </small>
                                <input type="hidden" name="id" value="{{ isset($eData->id) ? ($eData->id != 0 ) ? $eData->id : '' :''}}" />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Postnummer</label>
                                <input type="text" required name="zipcode" class="form-control  form-control-danger" value="{{ (old('zipcode') != '' ) ? old('zipcode') : (isset($eData->zipcode) ? $eData->zipcode : '') }}">
                                <small class="invalid-feedback"> Postnummer is required </small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">Kategori</label>
                                @php
                                $cat = \DB::table("all_category")->where("table_name","contacts")->get();
                                @endphp
                                @if($cat)
                                <select name="category" required class="form-control custom-select">
                                    <option value="">Select category
                                    <option>
                                        @foreach($cat as $data)
                                    <option {{ (isset($eData->category) && ($eData->category == $data->id) ) ? "selected" : '' }} value="{{ $data->id }}">{{ $data->des}}</option>
                                    @endforeach
                                </select>
                                <small class="invalid-feedback"> Kategori a category </small>
                                @endif
                            </div>
                        </div>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success waves-effect text-left">Lagre</button>
                {{-- <button type="button" class="btn btn-danger waves-effect text-left">Sellte</button>--}}
            </div>
            </form>

        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- Contak person modal -->
<div id="kontact" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="vcenter" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="vcenter">Ny Dependant</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <!----Form Data -->
                <form action="javascript:void(0);" class="cvalid" novalidate>
                    <div class="form-group row">
                        <label class="control-label col-lg-3">Navn</label>
                        <div class="col-lg-9">
                            <input type="text" name="cNavn" id="cName" value="{{ (old('cNavn') != '' ) ? old('name') : '' }}" required class="form-control">
                            <small class="invalid-feedback"> Navn is required </small>
                        </div>
                    </div>
                    @php
                                    $cat = \DB::table("dependentrelations")->where("companyId",\Auth::user()->companyId)->get();
                                    $cate = [];

                                    if(isset($rData->category)){
                                    $cate = explode("," , $rData->category);
                                    }
                                    if(isset($tData->category_id)){
                                    $cate = explode("," , $tData->category_id);
                                    }
                    @endphp
                    @if($cat)
                                    <div class="form-group row">
                                    <label class="control-label col-lg-3">Relation</label>
                                    <div class="col-lg-9">
                                    <select name="category" id="cat" required class="form-control">
                                        <option value=''>Velg kategori</option>
                                        @foreach($cat as $data)
                                        <option {{ (isset($data->id) && in_array($data->id,$cate) ? 'selected' : '') }} {{ (isset($rData->category_id) && ($rData->category_id == $data->id )) ? "selected" : '' }} value="{{ $data->id }}">{{ $data->name}}</option>
                                        @endforeach
                                    </select>
                                    <small class="invalid-feedback"> Kategori required </small>
                                    <input type="hidden" id="id" value="0" name="id">
                                    </div>
                                    </div>
                    @endif
                    <!-- <div class="form-group row">
                        <label class="control-label col-lg-3">Relation</label>
                        <div class="col-lg-9">
                            <input type="text" name="relation" id="relation" value="{{ (old('relation') != '' ) ? old('relation') : ''}}" required class="form-control">
                            <small class="invalid-feedback"> Epost is required </small>
                            <input type="hidden" value="{{ isset($eData->id) ? $eData->id : ''}}" name="employee_id">
                            <input type="hidden" id="id" value="0" name="id">
                        </div>
                    </div> -->
                    <div class="form-group row">
                        <label class="control-label col-lg-3">Telefonnummer</label>
                        <div class="col-lg-9">
                            <input type="number" id="cPhone" name="dPhone" value="{{ (old('dPhone') != '' ) ? old('dPhone') :  '' }}" required class="form-control">
                            <small class="invalid-feedback">Telefonnummer is required </small>
                        </div>
                    </div>

            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success waves-effect text-left">Lagre</button>
                {{--<button type="button" class="btn btn-danger waves-effect text-left">Sellte</button>--}}
            </div>
            </form>

        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>

<div id="document" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="vcenter" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered " style="max-width:800px;">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="vcenter">Dokumenter</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <!----Form Data -->
                <form action="{{url('/employee/doc')}}" class="doclog" method="post" enctype="multipart/form-data">
                    {{csrf_field()}}

                    <!-- New added field -->

                        <div class="row ">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label" id="navn">Filnavn</label>
                                    <input type="text" id="filename" name="filename" value="{{ (old('filename') != '' ) ? old('filename') : '' }}" required class="form-control">
                                    <small class="invalid-feedback"> Navn is required </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group ">
                                    <label class="control-label">Kategori</label>
                                    @php
                                    $cat = \DB::table("all_category")->where("companyId",\Auth::user()->companyId)->where("table_name","documents")->get();
                                    $cate = [];

                                    if(isset($rData->category)){
                                    $cate = explode("," , $rData->category);
                                    }
                                    if(isset($tData->category_id)){
                                    $cate = explode("," , $tData->category_id);
                                    }
                                    @endphp
                                    @if($cat)
                                    <select name="category" id="category" required class="form-control">
                                        <option value=''>Velg kategori</option>
                                        @foreach($cat as $data)
                                        <option {{ (isset($data->id) && in_array($data->id,$cate) ? 'selected' : '') }} {{ (isset($rData->category_id) && ($rData->category_id == $data->id )) ? "selected" : '' }} value="{{ $data->id }}">{{ $data->des}}</option>
                                        @endforeach
                                    </select>
                                    <small class="invalid-feedback"> Kategori required </small>
                                    @endif
                                </div>
                            </div>
                            <input type="hidden" id="docid" name="id" value="" />
                            <input type="hidden" name="user_id" value="{{ isset($eData->id) ? ($eData->id != 0 ) ? $eData->id : '' :''}}" />

                        </div>

                    <div class="row pt-3">
                        <div class="col-md-12">
                            <div class="form-group">

                                <input type="file" name="doc" class="dropify" />
                                <input type="hidden" name="type" value="3">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success waves-effect text-left">Lagre</button>
                        {{--<button type="button" class="btn btn-danger waves-effect text-left">Sellte</button>--}}
                    </div>
                </form>

            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
</div>


<!-- Modal to show the dependents -->
<div id="dependt" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="vcenter" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered ">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="vcenter">Opprett pårørende</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <!----Form Data -->
                <form action="javascript:void(0);" class="depvalid" novalidate>
                    <div class="form-group row">
                        <label class="control-label col-lg-3">Navn</label>

                        <div class="col-lg-9">
                            <input type="text" name="dName" id="depName" value="{{ (old('dName') != '' ) ? old('dName') : '' }}" required class="form-control">
                            <small class="invalid-feedback"> Navn is required </small>
                        </div>
                    </div>
                    @php
                                    $cat = \DB::table("dependentrelations")->where("companyId",\Auth::user()->companyId)->get();
                                    $cate = [];

                                    if(isset($rData->category)){
                                    $cate = explode("," , $rData->category);
                                    }
                                    if(isset($tData->category_id)){
                                    $cate = explode("," , $tData->category_id);
                                    }
                    @endphp
                    @if($cat)
                                <div class="form-group row">
                                  <label class="control-label col-lg-3">Relasjon</label>
                                    <div class="col-lg-9">
                                        <select name="category" id="categ" required class="form-control">
                                            <option value=''>Velg Relasjon</option>
                                           @foreach($cat as $data)
                                             <option {{ (isset($data->id) && in_array($data->id,$cate) ? 'selected' : '') }} {{ (isset($rData->category_id) && ($rData->category_id == $data->id )) ? "selected" : '' }} value="{{ $data->id }}">{{ $data->name}}</option>
                                        @endforeach
                                    </select>
                                    <small class="invalid-feedback"> Kategori required </small>
                                    <input type="hidden" id="dpid" value="" name="id">
                                    </div>
                                    </div>
                    @endif

                    {{ csrf_field() }}
                    <div class="form-group row">
                        <label class="control-label col-lg-3">Date Of Birth</label>
                        <div class="col-lg-9">
                            <input type="text" id="dep_date" name="dob" value="{{ (old('dob') != '' ) ? old('dob') :  '' }}" required class="form-control">
                            <small class="invalid-feedback">dob is required </small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="control-label col-lg-3">Telefonnummer</label>
                        <div class="col-lg-9">
                            <input type="number" id="depPhone" name="dPhone" value="{{ (old('dPhone') != '' ) ? old('dPhone') :  '' }}" required class="form-control">
                            <small class="invalid-feedback">Telefonnummer is required </small>
                        </div>
                    </div>
                    <input type="hidden" name="did" value="{{ isset($eData->id) ? ucfirst($eData->id) : ''}}" >

            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success waves-effect text-left" data-id="{{ isset($eData->id) ? ucfirst($eData->id) : ''}}">Lagre</button>
                <button type="button" data-id="{{ isset($eData->id) ? ucfirst($eData->id) : ''}}" class="btn btn-danger waves-effect text-left delete_depends">Sellte</button>
            </div>
            </form>

        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>



@endsection
