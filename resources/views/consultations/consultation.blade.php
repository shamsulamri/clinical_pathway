@extends('layouts.app')

@section('content')
<h1>
@if (empty($consultation))
	New Consultation
@else
	Edit Consultation
@endif
</h1>
<br>

@if (empty($consultation))
	{{ Form::model($consultation, ['url'=>'consultations', 'class'=>'form-horizontal']) }} 
@else
	{{ Form::model($consultation, ['route'=>['consultations.update',$consultation->consultation_id],'method'=>'PUT', 'class'=>'form-horizontal']) }} 
@endif
    
	
    <div class='form-group  @if ($errors->has('consultation_pathways')) has-errors @endif'>
        <label>consultation_pathways</label>
        {{ Form::text('consultation_pathways', null, ['class'=>'form-control col-sm-12','placeholder'=>'',]) }}
        @if ($errors->has('consultation_pathways')) {{ $errors->first('consultation_pathways') }} @endif
    </div>

    <div class='form-group'>
        <a class="btn btn-secondary" href="/consultations" role="button">Cancel</a>
        {{ Form::submit('Save', ['class'=>'btn btn-primary']) }}
    </div>


{{ Form::close() }}

@endsection
