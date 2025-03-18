@extends('templates.monster.main')

@push('before-styles')
@endpush

@push('after-scripts')
@endpush

@section('content')
<div class="row page-titles">
    <div class="col-md-6 align-self-center">
        <h3 class="text-themecolor mb-0 mt-0">Vernerunder</h3>
    </div>
    <div class="col-md-6">
    <a href="JavaScript:void(0);" data-toggle="modal" class="btn hidden-sm-down btn-success float-right"><i class="mdi mdi-plus-circle"></i>Lag ny vernerunde</a>
    </div>
</div>

<!-- for the safetyinspectionlist list -->
<div class="card">
    <div class="card-body">
        <table id="Mappings" class="table table-hover">
            <thead>
                <tr>
                    <th>sist endret</th>
                    <th>Sist endret av</th>
                    <th>Vernerunde</th>
                    <th>Antall kontrollpunkt</th>
                    <th>handling</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection