@extends('templates.monster.main')
@push('after-styles')

<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/summernote/dist/summernote-bs4.css" rel="stylesheet" />
<link href="/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.css" rel="stylesheet" type="text/css">


@endpush

@push('after-scripts')
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/summernote/dist/summernote-bs4.min.js') }}"></script>
<script src="{{ asset('/vendor/wrappixel/monster-admin/4.2.1/assets/plugins/sweetalert/sweetalert.min.js') }}"></script>
<script type="text/javascript">


        $('.inline-editor').summernote({
            airMode: true,
            placeholder: "entert your text"
        });
</script>
@endpush
@section('content')

@include('common.errors')
@include('common.success')

<div class="row">
    <div class="col-lg-12">
        <div class="card card-outline-info">
            <div class="card-header">
                <h4 class="mb-0 text-white">Risk Area</h4>
            </div>
            <div class="card-body">
            <h4>Bedriftens risikoområder </h4>

               <p>Både mellommenneskelige forhold, materielle verdier og ytre miljø vil være områder som vi må kartlegge når det gjelder risiko. Risikovurdering vil være en kontinuerlig måte å tenke sikkerhet på, og gjennomføres i tillegg når det har skjedd endringer som kan få betydning, som f.eks. innkjøp av maskiner og utstyr, ombygninger og flere nyansatte.</p>

                <p>Vi kan starte med å stille noen enkle spørsmål for å danne oss et bilde av bedriftens risikoområder:</p>

                <p> Hva kan gå galt? </p>
                <ul>
                <li>Hva er sannsynligheten for at det skal skje?</li>
                <li>Hvilke negative forhold, skader og ulykker kan ramme ansatte i vår virksomhet?</li>
                <li>Hva kan vi gjøre for å hindre dette? Er de forholdsreglene som allerede er tatt tilstrekkelige, eller bør det ytterligere tiltak til?</li>
                <li>Hva kan vi gjøre for å redusere konsekvensene hvis noe skjer?</li>
                <li>Det er sannsynligheten for at noe skjer og hvor stor konsekvensen av hendelsen vil bli som sier oss noe om hvilken risiko det utgjør.</li>
                <li>Hver maskin og arbeidsoppgave har vært vurdert med hensyn til risiko. Der det er erkjent særlig høy risiko, vil dette følges opp av rutiner og arbeidsinstrukser, som overvåking og bruk av personlig verneutstyr.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="card card-body">
            <form class="needs-validation" novalidate method="POST" action="{{ url('/save/riskarea') }}">
                <div class="form-body">
                    <div class="row pt-3">
                        {{ csrf_field() }}
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="control-label">Beskrivelse</label>
                                    <textarea name="discription" class="inline-editor">{!! isset($riskData->discription) ? htmlspecialchars_decode($riskData->discription) :   ''  !!}</textarea>
                                    <input name="id" value="0" type="hidden" />
                            </div>
                        </div>
                    </div>
                        @csrf
                    <div class="col-md-12">

                        <div class="form-actions ">
                            <button type="submit" class="btn btn-success x"> <i class="fa fa-check"></i> Lagre</button>
                            @if(isset($riskData->id) && $riskData->id !=0)
                            <button type="button" data-toggle="tooltip" data-id="{{ isset($riskData->id) ? $riskData->id : ''}}" data-original-title="Delete" onclick="del(this)" class="deleteProduct btn btn-inverse">Slett</button>
                            @endif
                        </div>
                    </div>
            </form>
        </div>
    </div>
</div>

@endsection