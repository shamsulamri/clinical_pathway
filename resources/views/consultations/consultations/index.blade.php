@extends('layouts.app')

@section('content')
<h1>Consultation Index
<a href='/consultations/create' class='btn btn-primary float-right'><span class='fas fa-plus'></span></a>
</h1>
<form action='/consultation/search' method='post'>
	<div class='input-group mb-3'>
		<input type='text' class='form-control' placeholder="Find" name='search' value='{{ isset($search) ? $search : '' }}' autocomplete='off' autofocus>
		<span class='input-group-append'>
			<button type="submit" class="btn btn-outline-secondary"> <span class='fas fa-search'></span></button> 
		</span>
	</div>
	<input type='hidden' name="_token" value="{{ csrf_token() }}">
</form>
<br>

@if ($consultations->total()>0)
<table class="table table-hover">
 <thead>
	<tr> 
    <th>id</th> 
    <th>consultation_id</th>
	@can('system-administrator')
	<th></th>
	@endcan
	</tr>
  </thead>
	<tbody>
@foreach ($consultations as $consultation)
	<tr>
			<td>
					<a href='{{ URL::to('consultations/'. $consultation->id . '/edit') }}'>
							{{$consultation->id}}
					</a>
			</td>
			<td>
					{{$consultation->consultation_id}}
			</td>
			<td align='right'>
			@can('system-administrator')
					<a class='btn btn-danger btn-xs' href='{{ URL::to('consultation/delete/'. $consultation->id) }}'>Delete</a>
			@endcan
			</td>
	</tr>
@endforeach
@endif
</tbody>
</table>
@if (isset($search)) 
	{{ $consultations->appends(['search'=>$search])->render() }}
	@else
	{{ $consultations->render() }}
@endif
<br>
@if ($consultations->total()>0)
	{{ $consultations->total() }} records found.
@else
	No record found.
@endif
@endsection
