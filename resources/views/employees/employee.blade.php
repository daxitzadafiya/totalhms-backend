@extends('templates.monster.main')

@section('content')
@include('common.success')
      
@if (count($errors) > 0)
<!-- Form Error List -->
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong>Whoops! Something went wrong!</strong>

    <br><br>

    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>

    
</div>
@endif
<div class="row">

@if($emp)
@foreach($emp as $data)
<div class="col-md-6 col-lg-6 col-xlg-4">
        <div class="card card-body">
            <div class="row">
                <div class="col-md-4 col-lg-3 text-center">
                
                   <a href="#mymodal2"  data-id="{{ $data->id }}"
                                        data-name="{{ $data->name }}" 
                                       data-email="{{ $data->email }}" 
                                       data-address="{{ $data->address }}"
                                       data-phone="{{ $data->phone }}"
                                       data-city="{{ $data->city }}"
                                       data-zipcode="{{ $data->zipcode }}"
                                       data-account_no="{{ $data->account_no }}"
                                       
                                       data-toggle="modal" class="user_dialog" > <img src="/vendor/wrappixel/monster-admin/4.2.1/assets/images/users/1.jpg" alt="user" class="img-circle img-responsive"></a>
                
                </div>
                <div class="col-md-8 col-lg-9">
                    <h4 class="mb-0">{{ $data->name }}</h4>
                    <small>
                    @if($data->user_role==1) 
                     {{ 'superadmin' }}
                     @else
                     {{ "Company Admin" }}
                     @endif
                     </small>
                    <address>

                        {{ $data->email }}  
                        <br>
                        {{ $data->address }}
                        <br>
                        {{$data->city}}
                        <br>
                        {{ $data->account_no }}
                        <br>
                        <br>
                        <abbr title="Phone">PNo:</abbr> {{ $data->phone }}
                    </address>
                </div>
            </div>
        </div>
       
    </div>
@endforeach
@endif
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script>
$(document).on("click", ".user_dialog", function () {
    var UserId= $(this).data('id');
     var UserName = $(this).data('name');
     var UserEmail = $(this).data('email');
     var UserAddress = $(this).data('address');
     var UserPhone = $(this).data('phone');
     var UserCity = $(this).data('city');
     var Userzipcode = $(this).data('zipcode');
     var UserAccountNo = $(this).data('account_no');

    $(".modal-body #idd").val( UserId);
     $(".modal-body #name").val( UserName );
     $(".modal-body #email").val( UserEmail );
     $(".modal-body #address").val( UserAddress );
     $(".modal-body #phone").val( UserPhone );
     $(".modal-body #city").val( UserCity );
     $(".modal-body #zipcode").val( Userzipcode );
     $(".modal-body #account_no").val( UserAccountNo );

});
</script>
    </div>

    <!-- sample modal content -->
    
<div id="mymodal2" class="modal fade " tabindex="-1" role="dialog" aria-labelledby="tooltipmodel" aria-hidden="true">



    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Profile</h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{url('/employee/Update')}}">

            


                {{ csrf_field() }}
                    <div class="row">
                    <div class="form-group col-md-6">
                        <label for="recipient-name" class="control-label">Name:</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ (old('name') != '' ) ? old('name') : (isset($data->name) ? $data->name : '') }}" >
                        
                    </div>
                    <div class="form-group col-md-6">
                        <label for="recipient-name" class="control-label">Email:</label>
                        <input type="text" name="email" id="email" class="form-control" value="{{ (old('email') != '' ) ? old('email') : (isset($data->email) ? $data->email : '') }}" >
                        
                    </div>
                    </div>
                   
                    <div class="form-group ">
                        <label for="recipient-name" class="control-label">Address:</label>
                        <input type="text" name="address" id="address" class="form-control" value="{{ (old('address') != '' ) ? old('address') : (isset($data->address) ? $data->address : '') }}" >
                      
                    </div>
                    <div class="form-group">
                        <label for="recipient-name" class="control-label">Phone:</label>
                        <input type="text" name="phone" id="phone" class="form-control" value="{{ (old('phone') != '' ) ? old('phone') : (isset($data->phone) ? $data->phone : '') }}" >
                      
                    </div>
                   
                     <div class="form-group">
                        <label for="recipient-name" class="control-label">Account No:</label>
                        <input type="text" name="account_no" id="account_no" class="form-control" value="{{ (old('account_no') != '' ) ? old('account_no') : (isset($data->account_no) ? $data->account_no : '') }}" >
                        <input type="hidden" name="id" id="idd" class="form-control" placeholder="Address" value="{{ (isset($data->id) ? $data->id : '') }}">
                        
                    </div>
                    <div class="row"> 
                    <div class="form-group col-md-6">
                        <label for="recipient-name" class="control-label">City:</label>
                        <input type="text" name="city" id="city" class="form-control" value="{{ (old('city') != '' ) ? old('city') : (isset($data->city) ? $data->city : '') }}" >
                      
                    </div>
                    <div class="form-group col-md-6">
                        <label for="recipient-name" class="control-label">Zip Code:</label>
                        <input type="text" name="zipcode" id="zipcode" class="form-control" value="{{ (old('zipcode') != '' ) ? old('zipcode') : (isset($data->zipcode) ? $data->zipcode : '') }}" >
                       
                    </div>
                    </div>
                    
                    <div class="form-group">
                    <button type="submit" class="btn  sv btn-danger waves-effect waves-light">Update</button>
                    </div>
                    
                </form>
            </div>
            <!-- <div class="modal-footer">
                <button type="button" class="btn  sv btn-danger waves-effect waves-light">Lagre endringer</button>
                <button type="button" class="btn btn-info danger-effect" data-dismiss="modal">Lukk</button>
            </div> -->
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
 
    

</div>
<!-- /.modal ends-->



@endsection