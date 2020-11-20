<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>CP</title>

<script type="text/javascript" src="/js/jquery-3.5.1.min.js"></script>
	</head>
<body>
<h1>Clinical Pathways</h1>

<?php
	$form = null;
	$group_style = null;
	$position = 0;
	$inputbox_type = null;
	$inputbox_side = null;
	$detail_flag = false;
?>
@foreach ($pathways as $index=>$path)

	@if ($helper->stringStartsWith($path, "<form>"))
		<?php
			$group_style = null;
			$position = 0;
			$detail = null;
			$detail_flag = false;
			$detail_submenu = null;
		?>
	@endif

	@if ($helper->stringStartsWith($path, "<group_style>"))
	<?php $group_style = $helper->removeFromString("<group_style>", $path) ?>
	@endif

	@if ($helper->stringStartsWith($path, "<group>"))
			<?php
				$position = 0;
				$group = $helper->removeFromString("<group>", $path);
			?>
	<div id="{{ $group }}">
			<h2>{{ $group }}</h2>
	@endif

	@if ($helper->stringStartsWith($path, "<detail>") or empty($helper->removeBreakLine($path)))
			@if ($detail_flag && $detail != $helper->removeFromString("<detail>", $path)) 
			<table>
				<tr>
					<td>
					@if ($group_style==1)
						<input type="checkbox" name="{{ $id }}" id="{{ $id }}" value="1" @if (array_key_exists($id, $kvs)) checked @endif>
					@endif

					@if ($group_style==2)
						<?php
						$radio_id = $helper->toId($problem."-".$group);
						if ($parent) $id = $parent."-".$id;
						?>
						<input type="radio" name="{{ $radio_id }}" id="{{ $radio_id }}" value="{{ $position }}" @if (array_key_exists($id, $kvs)) checked @endif> 
					@endif

					@if ($group_style==3)
						<input type="radio" name="{{ $id }}" id="{{ $id }}" value="1"> Yes
						<input type="radio" name="{{ $id }}" id="{{ $id }}" value="0"> No
					@endif

					@if ($group_style==4)
						@if ($position==1) 
						<input type="radio" name="{{ $id }}" id="{{ $id }}" value="1">
						@else
						<input type="checkbox" name="{{ $id }}" id="{{ $id }}" value="1" @if (array_key_exists($id, $kvs)) checked @endif>
						@endif
					@endif
					</td>
					<td>
						@if ($detail_submenu)
								<a href='/cp/{{ $soap }}/{{ urldecode($detail_submenu) }}?parent={{ $id }}'>
						@endif
						{!! $helper->inputBox($inputbox_type, $inputbox_side, $detail, $id, $kvs[$id]??null, $position, $group_style) !!}
						@if ($detail_submenu)
								...</a>
						@endif
					</td>
				</tr>
			</table>
			<?php 
					$detail_flag = false; 
					$detail_submenu = null;
			?>
			@endif
	@endif

	@if ($helper->stringStartsWith($path, "<detail>"))
	<?php 
			$inputbox_type = null;
			$inputbox_side = null;
			$detail_flag = true;
			$position += 1;
			$detail = $helper->removeFromString("<detail>", $path);
			$id = $detail;
			if (empty($detail)) {
					$detail = "<>";
					$id = "empty";
			}

			$id = $helper->toId($problem."-".$group."-".$id);
			if ($parent) $id = $parent."-".$id;
	?>
	@endif

	@if ($helper->stringStartsWith($path, "<detail_submenu>"))
	<?php $detail_submenu = $helper->removeFromString("<detail_submenu>", $path) ?>
	@endif

	@if ($helper->stringStartsWith($path, "<detail_inputbox>"))
	<?php 
			if ($helper->wordContains($path, "<inte>")) $inputbox_type = "int";
			if ($helper->wordContains($path, "<date>")) $inputbox_type = "date";
			if ($helper->wordContains($path, "<text>")) $inputbox_type = "text";
			if ($helper->wordContains($path, "<doub>")) $inputbox_type = "double";
			if ($helper->wordContains($path, "<L>")) $inputbox_side = "L";
			if ($helper->wordContains($path, "<R>")) $inputbox_side = "R";
	?>
	@endif



@endforeach

<meta name="csrf-token" content="{{ csrf_token() }}">
<script>
$(document).ready(function(){
		$('input[type=checkbox]').change(function(){
				var id = $(this).attr('id');
				var checked = $(this).prop("checked");
				var value = $("#"+id+"_input").val();
				console.log("check: "+checked);

				if (checked==false) {
					$("#"+id+"_input").val("");
				}
				if (checked==true) {
					$("#"+id+"_input").focus();
				}

				if (!value) {
						value = $("#"+id+"_label").text();
				}
				postData(id, value);
		});

		$('input[type=radio]').click(function(){
				var id = $(this).attr('id');
				var checked = $(this).prop("checked");
				var value = $(this).prop("value");
				/*
				console.log("id: "+id);
				console.log("value: "+value);
				console.log("checked: "+checked);
				 */
		});

		$('input[type=text]').keyup(function(){
				var id = $(this).attr('id');
				var value = $(this).prop("value");
				var root = id.substring(0, id.length-6);
				var pos = $(this).attr("input-position");
				var style = $(this).attr("group-style");
				console.log("text:root: "+ root);
				/*
				console.log("text:id: "+id);
				console.log("text:value: "+value);
				console.log("pos: " + pos);
				 */

				//var checkedOption = $("input[id='"+root+"']:checked");
				//console.log("checked option: "+checkedOption.prop('value'));
				
				switch(style) {
				case '2':
						var roots = root.split("-");
						root = "";
						for (i=0;i<roots.length-1;i++) {
							root += roots[i];
							if (i<roots.length-2) root += "-";
						}
						if (value) $("input[name="+root+"][value=" + pos + "]").prop('checked', true);
						break;
				default:
						$("#"+root).prop("checked", true);
						break;
				};
		});

		$('input[type=text]').focusout(function(){
				var id = $(this).attr('id');
				var value = $(this).prop("value");
				var root = id.substring(0, id.length-6);
				var pos = $(this).attr("input-position");
				var style = $(this).attr("group-style");

				console.log("text:id: "+id);
				console.log("text:value: "+value);
				console.log("text:pos: " + pos);

				if (value) postData(root, value);
		});
});


function postData(key, value) {
		var dataString = "key="+key+"&value="+value;
		$.ajax({
		type: "POST",
				headers: {'X-CSRF-TOKEN': $('meta[name=csrf-token]').attr('content')},
				url: "{{ route('cp.post') }}",
				data: dataString,
				success: function(data){
						console.log(data);
				}
		});
}


</script>
</body>
</html>

