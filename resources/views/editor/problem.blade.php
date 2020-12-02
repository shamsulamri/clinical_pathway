@extends('layouts.app')

@section('content')

<h1>Problem List</h1>

@foreach($problems as $problem)

<h3>
<a href="/cp/subjective/{{ $problem }}">{{ ucwords($problem) }}</a>
<br>
</h3>
@endforeach

@endsection

