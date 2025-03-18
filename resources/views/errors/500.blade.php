@extends('templates.application.master')

@section('template-custom-js')

    <script src="{{ asset('/vendor/wrappixel/material-pro/4.2.1/material/js/custom.min.js') }}"></script>

@endsection

@section('layout-content')
<section id="wrapper" class="error-page">
        <div class="error-box">
            <div class="error-body text-center">
                <h1 class="text-info">500</h1>
                <h3 class="text-uppercase">Intern server error !</h3>
                <p class="text-muted m-t-30 m-b-30">Prøv igjen om litt</p>
                <a href="{{ url("/") }}" class="btn btn-info btn-rounded waves-effect waves-light m-b-40">Tilbake til forsiden</a> </div>
        </div>
    </section>
@endsection