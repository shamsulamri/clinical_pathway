@extends('layouts.app')

@section('content')
<h1>
Delete History
</h1>
<br>
<h4>
{{ Form::open(['url'=>'histories/'.$history->patient_id, 'class'=>'pull-right']) }}
		Are you sure you want to delete the selected record ?
		<br>
		<br>
		'{{ $history->history_note }}'
		<br>
		<br>
		{{ method_field('DELETE') }}
		<a class="btn btn-secondary" href="/histories" role="button">Cancel</a>
		{{ Form::submit('Delete', ['class'=>'btn btn-danger']) }}
{{ Form::close() }}

</h4>
@endsection
