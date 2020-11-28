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

<h1><a href="/cp/subjective/sore throat">Clinical Pathways</a></h1>

<h1>Editor</h1>

<label id='consultation_note' contenteditable=true>{{ $consultation->consultation_note??$insert_here }}</label>
<br>
<?php
			foreach($soaps as $soap) {

					?>
					<h2>{{ $soap }}</h2>
					<?php
					foreach($problems as $problem) {
							$problem = str_replace("_", " ", $problem);
							$note = $helper->getNote($soap, $problem);
							?>
							<h3>{{ $problem }}</h3>
							<?php
							?>
							<label id='{{ $soap."--".$helper->toId($problem) }}' contenteditable=true>{{ $note??$insert_here }}</label>
							<?php
							$problem_list = $helper->getProblemList($soap, $problem);
							foreach($problem_list as $section) {
									$filename = $problem." - ".strtolower($section);
									if (!empty($section)) {
											$pathways = $helper->getPathways($soap, $filename);
											if ($pathways) {
													foreach ($pathways as $index=>$path) {
															if ($helper->stringStartsWith($path, "<group>")) {
																	$group = $helper->removeFromString("<group>", $path);
																	$group_id = $helper->toId($group);
																	$text = $helper->compileText($consultation_id, $soap, $problem, $section, $group_id);
																	$note = $helper->getNote($soap, $problem, $section, $group);
																	if ($text) {
																			?>
																			<label id='static'>{{ $text }}</label>
																			<label id='{{ $soap."--".$helper->toId($problem)."--".$helper->toId($section)."--".$helper->toId($group_id) }}' contenteditable=true>{{ $note??$insert_here }}</label>
																			<?php
																	}
															}
													}
											}
									}
							}
					}
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
				var dataString = "id="+id+"&value="+value;
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
