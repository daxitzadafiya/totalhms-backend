@extends('templates.monster.main')

@push('before-styles')
@endpush

@push('after-scripts')
@endpush

@section('content')
<div class="row page-titles">
    <div class="col-md-6 align-self-center">
        <h3 class="text-themecolor mb-0 mt-0">Risikoanalyse</h3>
    </div>
    <div class="col-md-6">
    <a href="JavaScript:void(0);" data-toggle="modal" class="btn hidden-sm-down btn-success float-right"><i class="mdi mdi-plus-circle"></i>Lag ny</a>
    </div>
</div>
<!-- riskanalysis list -->
<div class="card">
    <div class="card-body">
        <table id="Riskanalysis" class="table table-hover">
            <thead>
                <tr>
                    <th>Navn</th>
                    <th>Dato</th>
                    <th>Lagt til av</th>
                    <th>Prioritet</th>
                    <th>status</th>
                    <th>Antall risikoelementer</th>
                    <th>Handling se endre</th>
                </tr>
            </thead>
        </table>
    </div>
</div>-
@endsection