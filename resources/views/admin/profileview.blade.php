@extends('templates.monster.main')


@section('content')

@include('common.errors')
@include('common.success')
<div class="row">
    @php
    $user = \Auth::user();
    @endphp
    <!-- Column -->
    <div class="col-md-12 col-lg-12 col-xlg-12">
        <div class="card">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs profile-tab" role="tablist">
                <li class="nav-item"> <a class="nav-link active" data-toggle="tab" href="#profile" role="tab">Profile</a> </li>
                <li class="nav-item"> <a class="nav-link " data-toggle="tab" href="#settings" role="tab">Settings</a> </li>
            </ul>
            <!-- Tab panes -->
            <div class="tab-content">

                <!--second tab-->
                <div class="tab-pane active" id="profile" role="tabpanel">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 col-xs-6 border-right"> <strong>Full Name</strong>
                                <br>
                                <p class="text-muted">
                                    {{ ucfirst($user->name) }}
                                </p>
                            </div>
                            <hr>
                            <div class="col-md-6 col-xs-6 border-right"> <strong>Mobile</strong>
                                <br>
                                <p class="text-muted">{{ $user->phone }}</p>
                            </div>
                            <div class="col-md-6 col-xs-6 border-right"> <strong>Email</strong>
                                <br>
                                <p class="text-muted">{{ $user->email }}</p>
                            </div>
                            <div class="col-md-6 col-xs-6"> <strong>Address</strong>
                                <br>
                                <p class="text-muted">{{ $user->address }}</p>
                            </div>
                            <div class="col-md-6 col-xs-6 border-right"> <strong>Personal Number</strong>
                                <br>
                                <p class="text-muted">{{ $user->personal_no }}</p>
                            </div>
                            <hr>
                            <div class="col-md-6 col-xs-6 border-right"> <strong>City</strong>
                                <br>
                                <p class="text-muted">{{ $user->city }}</p>
                            </div>
                            <div class="col-md-6 col-xs-6 border-right"> <strong>Account No.</strong>
                                <br>
                                <p class="text-muted">{{ $user->account_no }}</p>
                            </div>
                            <div class="col-md-6 col-xs-6"> <strong>Zip Code</strong>
                                <br>
                                <p class="text-muted">{{ $user->zip_code }}</p>
                            </div>
                            <div class="col-md-6 col-xs-6"> <strong>Rolle</strong>
                                <br>
                                <p class="text-muted">
                                    @php
                                    if($user->user_role == 1){
                                    echo "Super-Admin";
                                    }elseif($user->user_role ==2){
                                    echo "Company-Admin";

                                    }
                                    @endphp
                                </p>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="tab-pane" id="settings" role="tabpanel">
                    <div class="card-body">
                        <form class="form-horizontal form-material needs-validation" novalidate method="POST" action="{{ url('user/update') }}">
                            <div class="form-group">
                                <label class="col-md-12">Navn</label>
                                <div class="col-md-12">
                                    <input type="text" name="name" value="{{ $user->name }}" required class="form-control form-control-line">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="example-email" class="col-md-12">Email</label>
                                <div class="col-md-12">
                                    <input type="email" class="form-control form-control-line" name="email" value="{{ $user->email }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-12">Phone</label>
                                <div class="col-md-12">
                                    <input type="text" name="phone" value="{{ $user->phone }}" class="form-control form-control-line">
                                </div>
                                <input type="hidden" name="id" value="{{  $user->id }}" />
                                <!--/span-->
                            </div>
                            {{ csrf_field() }}
                            <div class="form-group">
                                <label class="col-md-12">Password</label>
                                <div class="col-md-12">
                                    <input type="password" name="password" class="form-control form-control-line">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-12">Account No</label>
                                <div class="col-md-12">
                                    <input type="text" name="account_no" value="{{ $user->account_no }}" class="form-control form-control-line">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-12">Personal No.</label>
                                <div class="col-md-12">
                                    <input type="text" name="personal_no" value="{{ $user->personal_no }}" class="form-control form-control-line">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-12">Address</label>
                                <div class="col-md-12">
                                    <input type="text" value="{{ $user->address }}" name="address" class="form-control form-control-line">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-12">City</label>
                                <div class="col-md-12">
                                    <input type="text" value="{{ $user->city }}" name="city" class="form-control form-control-line">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-12">Zipcode</label>
                                <div class="col-md-12">
                                    <input type="text" value="{{ $user->zipcode }}" name="zip_code" class="form-control form-control-line">
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-12">
                                    <button class="btn btn-success">Update Profile</button>
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
<!-- Row -->
<!-- ============================================================== -->
<!-- End PAge Content -->
<!-- ============================================================== -->
@endsection