@extends('layouts.app')

@section('content')
<h1>
Delete Consultation
</h1>
<br>
<h4>
{{ Form::open(['url'=>'consultations/'.$consultation->id, 'class'=>'pull-right']) }}
		Are you sure you want to delete the selected record ?
		<br>
		<br>
		'{{ $consultation->consultation_id }}'
		<br>
		<br>
		{{ method_field('DELETE') }}
		<a class="btn btn-secondary" href="/consultations" role="button">Cancel</a>
		{{ Form::submit('Delete', ['class'=>'btn btn-danger']) }}
{{ Form::close() }}

</h4>
@endsection
