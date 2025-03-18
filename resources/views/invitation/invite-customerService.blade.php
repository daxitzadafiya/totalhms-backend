<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Invitation</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.min.js"></script>
   
    <style>
    .error{
            color: #FF0000; 
            }
    </style>

</head>

<body>
<div class="d-flex flex-column justify-content-center vh-100 align-items-center gap-5">
<div class="text-center">
    <img src="{{url('text-logo.png')}}" alt="logo">
</div>
    @if(session('status'))
    <div class="alert alert-success">
        {{ session('status') }}
    </div>
    @endif
<form method="POST" action="{{ route('customerService.invitation.accepet') }}" class="w-25 mx-auto shadow-lg p-4 mb-5 bg-body rounded" id="invitation-form">
    @csrf
    <input type="hidden" name="code" value="{{ $code }}">
    <input type="hidden" name="email" value="{{ $email }}">

    <div  id="emailHelp" class="form-text fs-5 text-center mb-4">Invitation Customer Service</div>
    <div class="mb-3">
        <label for="exampleInputEmail1" class="form-label fw-bold">Email address</label>
        <input id="email" placeholder="Email" type="email"  class="@error('email') is-invalid @enderror form-control" name="email" value="{{ $email ?? old('email') }}" disabled>
    </div>
    <div class="mb-3">
        <label for="exampleInputPassword1" class="form-label fw-bold">Password</label>
        <input id="password" placeholder="Password" type="password" class="@error('password') is-invalid @enderror form-control" name="password" autofocus>
        @error('password')
        <div class="alert alert-danger mt-1 mb-1">{{ $message }}</div>
       @enderror
    </div>
    <div class="mb-3">
        <label class="form-check-label my-1 fw-bold" for="exampleCheck1">Confirm Password</label>
        <input id="password_confirm" placeholder="{{ __('Confirm Password') }}" type="password" class="@error('password_confirm') is-invalid @enderror form-control" name="password_confirm">
        @error('password_confirm')
        <div class="alert alert-danger mt-1 mb-1">{{ $message }}</div>
       @enderror
    </div>  
    <button type="submit" class="btn btn-success w-100 my-3"> {{ __('Set Password') }}</button>
</form>
</div>    
</body>

<script>
    $(document).ready(function () {
        if ($("#invitation-form").length > 0) {
        $("#invitation-form").validate({
  
            rules: {
                password: {
                required: true,
                minlength: 5
             },
            password_confirm: {
            required: true,
            minlength: 5,
            equalTo: "#password"
         }
            },
            messages: {
  
                password: {
                    required: "Please enter password",
                },
                password_confirm: {
                    required: "Password does not match !",
                },
            },
        })
    } 
    });
 </script>

</html>



