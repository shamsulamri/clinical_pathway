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
	{{ Form::model($consultation, ['route'=>['consultations.update',$consultation->id],'method'=>'PUT', 'class'=>'form-horizontal']) }} 
@endif
    
	
    <div class='form-group  @if ($errors->has('consultation_id')) has-errors @endif'>
        <label>consultation_id<span style='color:red;'> *</span></label>
        {{ Form::select('consultation_id', $consultation,null, ['class'=>'form-control',]) }}
        @if ($errors->has('consultation_id')) {{ $errors->first('consultation_id') }} @endif
    </div>

    <div class='form-group  @if ($errors->has('consultation_pathway')) has-errors @endif'>
        <label>consultation_pathway</label>
        {{ Form::text('consultation_pathway', null, ['class'=>'form-control col-sm-12','placeholder'=>'',]) }}
        @if ($errors->has('consultation_pathway')) {{ $errors->first('consultation_pathway') }} @endif
    </div>

    <div class='form-group'>
        <a class="btn btn-secondary" href="/consultations" role="button">Cancel</a>
        {{ Form::submit('Save', ['class'=>'btn btn-primary']) }}
    </div>


{{ Form::close() }}

@endsection
