@extends('layouts.app')

@section('content')
<h1>
@if (empty($history))
	New History
@else
	Edit History
@endif
</h1>
<br>

@if (empty($history))
	{{ Form::model($history, ['url'=>'histories', 'class'=>'form-horizontal']) }} 
@else
	{{ Form::model($history, ['route'=>['histories.update',$history->patient_id],'method'=>'PUT', 'class'=>'form-horizontal']) }} 
@endif
    
	
    <div class='form-group  @if ($errors->has('history_note')) has-errors @endif'>
        <label>history_note<span style='color:red;'> *</span></label>
        {{ Form::text('history_note', null, ['class'=>'form-control col-sm-12','placeholder'=>'','maxlength'=>'65535']) }}
        @if ($errors->has('history_note')) {{ $errors->first('history_note') }} @endif
    </div>

    <div class='form-group  @if ($errors->has('history_pathway')) has-errors @endif'>
        <label>history_pathway<span style='color:red;'> *</span></label>
        {{ Form::text('history_pathway', null, ['class'=>'form-control col-sm-12','placeholder'=>'',]) }}
        @if ($errors->has('history_pathway')) {{ $errors->first('history_pathway') }} @endif
    </div>

    <div class='form-group'>
        <a class="btn btn-secondary" href="/histories" role="button">Cancel</a>
        {{ Form::submit('Save', ['class'=>'btn btn-primary']) }}
    </div>


{{ Form::close() }}

@endsection
