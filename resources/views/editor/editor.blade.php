<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>CP</title>

<script type="text/javascript" src="/js/jquery-3.5.1.min.js"></script>
	</head>
<body>
<?php
$insert_here = "..";
?>

<h1><a href="/cp/{{ $soap }}">Clinical Pathways</a></h1>

<h1>Editor</h1>

<label id='consultation_note' contenteditable=true>{{ $consultation->consultation_note??$insert_here }}</label>
@foreach ($pathways as $index=>$path)
	@if ($helper->stringStartsWith($path, "<group>"))
			<?php
				$position = 0;
				$group = $helper->removeFromString("<group>", $path);
				$group_id = $helper->toId($group);
				$text = $helper->compileText($soap, $problem, $group);
				$note = $helper->getNote($soap, $problem, $group);
			?>
			@if (!empty($text))
			<label id='{{ $group_id }}_static'>{{ $text }}</label>
			<label id='{{ $group_id }}' contenteditable=true>{{ $note??$insert_here }}</label>
			@endif
	@endif

@endforeach

<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
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
				var dataString = "soap={{ $soap }}&problem={{ $problem }}&group="+id+"&value="+value;
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
</body>
</html>
