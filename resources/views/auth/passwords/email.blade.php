@extends('templates.monster.application')

@section('layout-content')

    <section id="wrapper" class="login-register login-sidebar" style="background-image:url(/vendor/wrappixel/monster-admin/4.2.1/assets/images/background/login-register.jpg);">
        <div class="login-box card">
            <div class="card-body">
                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif
                <form method="POST" action="{{ route('password.email') }}">
                        @csrf
                    <div class="form-group ">
                        <div class="col-xs-12">
                            <h3>{{ __('Reset Password') }}</h3>
                            <p class="text-muted">Enter your Email and instructions will be sent to you! </p>
                        </div>
                    </div>
                    <div class="form-group ">
                        <div class="col-xs-12">
                            <input id="email" type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" name="email" value="{{ old('email') }}" required>
                        </div>
                    </div>
                    <div class="form-group text-center m-t-20">
                        <div class="col-xs-12">
                            <button class="btn btn-primary btn-lg btn-block text-uppercase waves-effect waves-light" type="submit"> {{ __('Send Password Reset Link') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

@endsection
