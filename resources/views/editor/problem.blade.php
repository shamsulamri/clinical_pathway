@extends('layouts.app')

@section('content')
<style>
body {
	padding-top: 10px;
	padding-left: 16px;
}
</style>

<h3>Problem List</h3>
<br>

@foreach($problems as $problem)

<h5>
<a href="/cp/{{ $patient_id }}/{{ $consultation_id }}/subjective/{{ $problem }}">{{ ucwords($problem) }}</a>
<br>
</h5>
@endforeach

@endsection

