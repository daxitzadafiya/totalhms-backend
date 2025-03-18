@extends('templates.monster.main')

@push('before-styles')
@endpush

@push('after-scripts')
@endpush

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card card-outline-info">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0 text-white">Opprett Sjekklister</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label><b>Kategori</b></label>
                                @if($category)
                                <div class="form-group">
                                    <select name="categ" class="form-control">
                                        <option value=''>Velg Kategory</option>
                                        @foreach($category as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif
                        </div>
                        <div class="col-md-6">
                            <label><b>Sjekkliste</b></label>
                                <div class="form-group">
                                @if($templates)
                                    <select name="checklists" class="form-control">
                                        <option calue=''>Velg Sjekkliste</option>
                                        @foreach($templates as $temp)
                                        <option value="{{ $temp->id }}">{{ $temp->checklistname }} </option>
                                        @endforeach
                                    </select>
                                @endif
                                </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><b>Project</b></label>
                                <input type="text" name="project" id="project_id" class="form-control" required value="">
                            </div>
                        </div>
                    </div>

                   
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card card-outline-info">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0 text-white card-title">Topic</h4>
                </div>
                <div class="card-body">
                    <div class="card border" >
                        <div class="card-body" >
                            <div class="row">
                                <div class="col-md-4">
                                    <label>Did you turn off the lights?</label>
                                    <div class="form-group">
                                        <label><input type="radio" name="answer" value="0">Yes</label>
                                        <label><input type="radio" name="answer" value="1">No </label>
                                        <label><input type="radio" name="answer" value="2">Uncertain</label>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <textarea class="form-control" ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


  
@endsection

