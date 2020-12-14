@extends('layouts.app')

@section('content')
<h1>History Index
<a href='/histories/create' class='btn btn-primary float-right'><span class='fas fa-plus'></span></a>
</h1>
<form action='/history/search' method='post'>
	<div class='input-group mb-3'>
		<input type='text' class='form-control' placeholder="Find" name='search' value='{{ isset($search) ? $search : '' }}' autocomplete='off' autofocus>
		<span class='input-group-append'>
			<button type="submit" class="btn btn-outline-secondary"> <span class='fas fa-search'></span></button> 
		</span>
	</div>
	<input type='hidden' name="_token" value="{{ csrf_token() }}">
</form>
<br>

@if ($histories->total()>0)
<table class="table table-hover">
 <thead>
	<tr> 
    <th>patient_id</th> 
    <th>history_note</th>
	@can('system-administrator')
	<th></th>
	@endcan
	</tr>
  </thead>
	<tbody>
@foreach ($histories as $history)
	<tr>
			<td>
					<a href='{{ URL::to('histories/'. $history->patient_id . '/edit') }}'>
							{{$history->patient_id}}
					</a>
			</td>
			<td>
					{{$history->history_note}}
			</td>
			<td align='right'>
			@can('system-administrator')
					<a class='btn btn-danger btn-xs' href='{{ URL::to('history/delete/'. $history->patient_id) }}'>Delete</a>
			@endcan
			</td>
	</tr>
@endforeach
@endif
</tbody>
</table>
@if (isset($search)) 
	{{ $histories->appends(['search'=>$search])->render() }}
	@else
	{{ $histories->render() }}
@endif
<br>
@if ($histories->total()>0)
	{{ $histories->total() }} records found.
@else
	No record found.
@endif
@endsection
