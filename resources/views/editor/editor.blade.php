@extends('layouts.app')

@section('content')
<style>
body {
	padding-top: 0px;
	padding-left: 0px;
}
</style>

<?php
$insert_here = "..";
$current_section = "";
?>

<a class='btn btn-warning' href="/cp/{{ $patient_id }}/{{ $consultation_id }}/subjective/{{ $problem }}"><i class="fa fa-chevron-left" aria-hidden="true"></i> Back</a>
<a class="btn @if($isEdit) btn-primary @else btn-secondary @endif" href="/editor/{{ $patient_id }}/{{ $consultation_id }}/{{ $problem }}?soap={{ $soap }}&edit={{ !$isEdit }}">Toggle Edit</a>

<br>
<a href="/editor/{{ $patient_id }}/{{ $consultation_id }}/{{ $problem }}?soap=cc">Complain</a>
<a href="/editor/{{ $patient_id }}/{{ $consultation_id }}/{{ $problem }}?soap=pmh">PMH</a>
<br>
<br>
@if ($isEdit)
<strong>
<label id='consultation_note' class='text text-primary' contenteditable=true>{{ $editor_note??$insert_here }}</label>
</strong>
@else
@if ($editor_note)
<label id='consultation_note'>{{ $editor_note }}</label>
@endif
@endif
<?php
	foreach($soaps as $soap_key=>$soap) {

			?>
			@if ($soap != 'pmh')
			<h3>{{ $soap }}</h3>
			@endif
			<?php
			foreach($problems as $index=>$problem) {
					$problem = str_replace("_", " ", $problem);
					$note = $helper->getNote($patient_id, $consultation_id, $soap_key, $problem);
					?>
					@if ($index>0)
					<br>
					<br>
					@endif
					@if (count($problems)>1)
							<h4>{{ ucwords($problem) }}</h4>
					@endif
				@if ($note)
					@if ($isEdit)	
					<strong>
					<label class="text text-primary"  id='{{ $soap_key."--".$helper->toId($problem) }}' contenteditable=true>{{ $note??$insert_here }}</label>
					</strong>
						<br>
						<br>
					@else
						{{ $note }}
						<br>
						<br>
					@endif
				@endif
					<?php
					$current_section = "";
					$problem_list = $helper->getProblemList($soap_key, $problem);
					foreach($problem_list as $section) {
							$filename = $problem." - ".strtolower($section);
							if (!empty($section)) {
									$pathways = $helper->getPathways($soap_key, $filename);
									if ($pathways) {
											foreach ($pathways as $index=>$path) {
													if ($helper->stringStartsWith($path, "<group>")) {
															$group = $helper->removeFromString("<group>", $path);
															$group_id = $helper->toId($group);
															$text = $helper->compileText($patient_id, $consultation_id, $soap_key, $problem, $section, $group_id);
															$section_note = $helper->getNote($patient_id, $consultation_id, $soap_key, $problem, $section);
															$note = $helper->getNote($patient_id, $consultation_id, $soap_key, $problem, $section, $group);
															if ($text) {
																	if ($current_section != $section) {
?>
																	@if ($current_section)
																		<br>
																		<br>
																	@endif
																		<h5>{{ $section }}</h5>
																			@if ($isEdit)	
																			<strong>
																			<label class="text text-primary"  id='{{ $soap_key."--".$helper->toId($problem)."--".$helper->toId($section) }}' contenteditable=true>{{ $section_note??$insert_here }}</label>
																			</strong>
																			@else
																				@if ($note)
																				{{ $note }}
																				@endif
																			@endif
<?php
																			$current_section = $section;
																	}
																	if ($isEdit) {
?>
																	{!! $text !!}
<strong>
																	<label class="text text-primary" id='{{ $soap_key."--".$helper->toId($problem)."--".$helper->toId($section)."--".$helper->toId($group_id) }}' contenteditable=true>{{ $note??$insert_here }}</label>
</strong>
<?php
																	} else {
																			Log::info($text);
?>
																	{!! $text !!} {{ $note??null }}
<?php
																	}
																	
															}
?>
<?php
													}
											}
									}
							}
					}
			}
?>
			<br>
			<br>
<?php
	}
?>
<meta name="csrf-token" content="{{ csrf_token() }}">
<script>

var current_note_index = "";

function parse(str) {
		var args = [].slice.call(arguments, 1),
				i = 0;
		return str.replace(/%s/g, () => args[i++]);
}

$(document).ready(function(){

		$(window).keydown(function(event){
				if(event.keyCode == 13) {
						event.preventDefault();
						return false;
				}
		});

		$(document).on('focus', 'label', function(e) {
				var id = e.currentTarget.id;
				var note = e.currentTarget.textContent;
				note = note.replace(/(\r\n|\n|\r)/gm,"");
				note = note.trim();
				oldText = note;
				if (oldText=="{{ $insert_here }}") oldText = "";
				//console.log("focus");
				//console.log(note);
		});

		$(document).on('blur', 'label', function(e) {
				var name = e.currentTarget.name;
				var id = e.currentTarget.id;
				var note = e.currentTarget.textContent;
				note = note.replace(/(\r\n|\n|\r)/gm,"");
				if (note=='') {
						e.currentTarget.textContent = "{{ $insert_here }}";
				}
				console.log(id);
				console.log(note);
				addNote(id,note);
				//console.log("blur");
		});

		$(document).on('keydown', 'label', function(e) {
				var note = e.currentTarget.textContent;
				if (note.trim()=='{{ $insert_here }}') {
						e.currentTarget.textContent = "";
				}
		});

		function addNote(id, value) {
				var dataString = "soap={{ $soap }}&patient_id={{ $patient_id }}&consultation_id={{ $consultation_id }}&id="+id+"&value="+value;
				dataString = parse(dataString);
				console.log(dataString);
				$.ajax({
				type: "POST",
						headers: {'X-CSRF-TOKEN': $('meta[name=csrf-token]').attr('content')},
						url: "{{ route('editor.add') }}",
						data: dataString,
						success: function(data){
								console.log(data);
						}
				});
		}

});
</script>
@endsection
