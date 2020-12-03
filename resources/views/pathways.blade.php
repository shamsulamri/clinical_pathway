@extends('layouts.app')

@section('content')

 <!-- Side navigation -->
<div class="sidenav">
		<a href="/cp">Problems List</a>
		<a href="/editor">Editor</a>
		<hr>
		<strong>
		<a href="/cp/subjective/{{ $problem }}">Subjective</a>
		<a href="/cp/objective/{{ $problem }}">Objective</a>  
		<a href="/cp/assessment_plan/{{ $problem }}">Assesstment and Plan</a>
		</strong>
		<hr>
		@if($problem_list)
				@foreach($problem_list as $index=>$p)
					<a href="/cp/{{ $soap }}/{{ $problem }}/{{ strtolower($p) }}"><strong>{{ $p }}</strong></a>
					@if ($helper->toId($section)==$helper->toId($p))
						@if (count($groups)>1)
								@foreach($groups as $link)
									@if ($parent)
										 <a href="/cp/{{ $soap }}/{{ $problem }}/{{ $section }}#{{ $helper->toId($link) }}">&nbsp;&nbsp;&nbsp;{{ $link }}</a>
									@else
										 <a href="#{{ $helper->toId($link) }}">&nbsp;&nbsp;&nbsp;{{ $link }}</a>
									@endif
								@endforeach
						@endif
					@endif
				@endforeach
		@endif
</div>

<!-- Page content -->
<div class="main">
<h2>{{ ucwords($problem) }}</h2>

<h3>{{ ucwords($section) }}</h3>
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
		@if (!empty($group_id))
			<!-- end of group -->
			</table>
		</div>
		@endif
		<?php
			$group_style = null;
			$position = 0;
			$detail = null;
			$detail_flag = false;
			$detail_submenu = null;
			$detail_text = null;
			$group_id = null;
			$radio_id = null;
		?>
	@endif

	@if ($helper->stringStartsWith($path, "<group_style>"))
	<?php $group_style = $helper->removeFromString("<group_style>", $path) ?>
	@endif

	@if ($helper->stringStartsWith($path, "<group>"))
			<?php
				$position = 0;
				$group = $helper->removeFromString("<group>", $path);
				$group_id = $helper->toId($group);


			?>
	<div id="{{ $group_id }}">
			<!-- start of group -->
			<br>
			<h4>
			{{ $group }}
			</h4>
			<table>
	@endif

	@if ($helper->stringStartsWith($path, "<detail>") or empty($helper->removeBreakLine($path)))
			@if ($detail_flag && $detail != $helper->removeFromString("<detail>", $path)) 
<?php
	if ($parent) {
				$child_kvs = $kvs;
				$roots = explode("---",$parent);
				foreach($roots as $index=>$root) {
						$branch[$index] = $helper->getPSGD($root);
				}

				switch (count($roots)-1) {
						case 0:
								$root_pgd = $branch[0];
								$child_kvs = $child_kvs[$soap][$problem][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child'];
								$detail_value = $child_kvs[$group_id][$detail_id]['value']??null;
								$detail_text = $child_kvs[$group_id][$detail_id]['text']??null;
								break;
						case 1:
								$root_pgd = $branch[0];
								$child_kvs = $child_kvs[$soap][$problem][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child'];
								$detail_value = $child_kvs[$group_id][$detail_id]['value']??null;
								if (!empty($branch[1])) {
										$root_pgd = $branch[1];
										$child_kvs = $child_kvs[$root_pgd[2]][$root_pgd[3]]['child'];
										$detail_value = $child_kvs[$group_id][$detail_id]['value']??null;
										$detail_text = $child_kvs[$group_id][$detail_id]['text']??null;
								}
								break;
						case 2:
								$root_pgd = $branch[0];
								$child_kvs = $child_kvs[$soap][$problem][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child'];
								if (!empty($branch[1])) {
										$root_pgd = $branch[1];
										$child_kvs = $child_kvs[$root_pgd[2]][$root_pgd[3]]['child'];
								}
								if (!empty($branch[2])) {
										$root_pgd = $branch[2];
										$child_kvs = $child_kvs[$root_pgd[2]][$root_pgd[3]]['child'];
										$detail_value = $child_kvs[$group_id][$detail_id]['value']??null;
										$detail_text = $child_kvs[$group_id][$detail_id]['text']??null;
								}
								break;
				}
	} else {

				$detail_value = $kvs[$soap][$problem][$section_id][$group_id][$detail_id]['value']??null;
				$detail_text = $kvs[$soap][$problem][$section_id][$group_id][$detail_id]['text']??null;
	}
?>
				<tr>
					<td width=@if ($group_style==3) 60 @else 30 @endif>
					@if ($group_style==1)
						<input type="checkbox" 
								name="{{ $id }}" 
								id="{{ $id }}" 
								value="1" 
								group-style=1 
								@if ($detail_value)) checked @endif
					@endif

					@if ($group_style==2)
						<?php
						$checkFlag = false;
						$radio_id = $helper->toId($problem."-".$group);
						if (!empty($inputbox_type)) {
							if ($detail_value) $checkFlag=true;
						} else {
								//if (array_key_exists($radio_id, $kvs)) {
								if ($detail_value) {
										if ($detail_value==$position) $checkFlag=true;
								}
						}
						?>
						<input type="radio" 
								name="{{ $radio_id }}" 
								id="{{ $id }}" 
								input-id="{{ $id }}"
								group-style="2"
								group-id="{{ $group_id }}"
								value="{{ $position }}" @if ($detail_value) checked @endif> 
					@endif

					@if ($group_style==3)
						<?php
						$yes = "";
						$no = "";
						if (!empty($detail_value)) {
								if ($detail_value==1) $yes = "checked";
								if ($detail_value==2) $no = "checked";
						}
						?>
						<input type="radio" name="{{ $id }}" id="{{ $id }}" value="1" group-style="3" {{ $yes }}> Yes
					</td>
					<td width=@if ($group_style==3) 60 @else 30 @endif>
						<input type="radio" name="{{ $id }}" id="{{ $id }}" value="2" group-style="3" {{ $no }}> No
					@endif

					@if ($group_style==4)
						@if ($position==1) 
						<?php
							$checkFlag = false;
							if ($detail_value) $checkFlag=true;
						?>
						<input type="radio" name="{{ $id }}" id="{{ $id }}" value="1" group-style="4" group-id="{{ $group_id }}" @if ($checkFlag) checked @endif>
						@else
						<input type="checkbox" 
								name="{{ $id }}" 
								id="{{ $id }}" 
								value="1" 
								group-style="4"
								group-id="{{ $group_id }}"
								@if ($detail_value) checked @endif>
						@endif
					@endif
					</td>
					<td>
						{!! $helper->inputBox($inputbox_type, $inputbox_side, $detail, $id, 
								$detail_value,
								$position, 
								$group_style, 
								$group_id,
								$soap,
								$detail_submenu??null,
								$problem,
								$detail_text
								) 
						!!}
					</td>
				</tr>

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
			$detail_id = $helper->toId($detail);
			$id = $detail;
			if (empty($detail)) {
					$detail = "<>";
					$id = "empty";
					$detail_id = "empty";
			}

			$problem = $helper->toId($problem);
			$group = $helper->toId($group);
			$id = $helper->toId($id);
			//$id = $problem."-".$group."__".$id;
			$id = $problem."--".$section_id."-".$group."__".$id;
			if ($parent) $id = $parent."---".$group."__".$detail_id;
	?>
	@endif

	@if ($helper->stringStartsWith($path, "<detail_submenu>"))
	<?php 
			$detail_submenu = $helper->removeFromString("<detail_submenu>", $path);
	?>
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
</div>
<script>
var selectedOption = null;
var selectedInputBox = null;
$(document).ready(function(){

		/*
		$('label').click(function(){
				var style = $(this).attr("group-style");
				if (style == 3) return;
				var id = $(this).attr('id');
				var radioButton = $("#"+id+":radio");
				var checkButton = $("#"+id+":checkbox");
				console.log("Radio: "+radioButton.prop('id'));
				console.log("Check: "+checkButton.prop('id'));
				
				value = $(this).text();

				if (radioButton.prop('id')) {
						var isChecked = radioButton.prop("checked");
						radioButton.prop("checked", !isChecked);
						if (!isChecked) {
								createData(id, value);
						} else {
								removeData(id);
						}
				}

				if (checkButton.prop('id')) {
						var isChecked = checkButton.prop("checked");
						if (!isChecked) {
								createData(id, value);
						} else {
								removeData(id);
						}
						checkButton.prop("checked", !isChecked);
				}

		});
		 */

		$('a').click(function(){
				var id = $(this).attr('id');
				var inputBox = $("input[type=text][id="+id+"]");
				var value = inputBox.val();
				var detail_submenu = $(this).attr('detail_submenu');
				var group_id = $(this).attr("group-id");
				var style = $(this).attr("group-style");

				var isChecked = $("#"+id+":checkbox").prop("checked");
				if (style == 2) {
						isChecked = $("#"+id+":radio").prop("checked");
				}

				if (style == 4) {
						console.log(group_id);
						$('#'+group_id+' input:radio:checked').each(function() {
								$(this).prop("checked", false);
								removeData($(this).attr("id"));
						});
				}

				if (isChecked==false) {
						$("#"+id+":checkbox").prop("checked", true);
						$("#"+id+":radio").prop("checked", true);
						if (!value) {
								value = $("a[id="+id+"]").text();
						}

						var dataString = "key="+id+"&value="+value+"&soap={{ $soap }}&filename={{ $filename }}";
						$.ajax({
						type: "POST",
								headers: {'X-CSRF-TOKEN': $('meta[name=csrf-token]').attr('content')},
								url: "{{ route('cp.create') }}",
								data: dataString,
								success: function(data){
										console.log(data);
										console.log(detail_submenu);
										$(location).attr('href', detail_submenu);
								}
						});
				} else {
						$(location).attr('href', detail_submenu);
				}
		});

		$('input[type=checkbox]').change(function(){
				var id = $(this).attr('id');
				var checked = $(this).prop("checked");
				//var value = $("#"+id+":checkbox").prop("checked");
				//alert(value);
				var inputBox = $("input[type=text][id="+id+"]");
				var value = inputBox.val();
				var style = $(this).attr("group-style");
				var group_id = $(this).attr("group-id");

				if (checked==false) {
					inputBox.val("");
					removeData(id);
				}

				if (checked==true) {
						inputBox.focus();
						if (!value) {
								value = $("label[id="+id+"]").text();
						}
						createData(id, value);

						if (style==4) {
								// Uncheck all radio button
								$('#'+group_id+' input:radio:checked').each(function() {
										$(this).prop("checked", false);
										removeData($(this).attr("id"));
								});
						}
				}

		});

		// ************ RADIO ***************
		
		$('input[type=radio]').mousedown(function(){
				var group_id = $(this).attr("group-id");
				var id = $(this).attr('id');
				var style = $(this).attr("group-style");

				//selectedOption = $("input[id='"+id+"']:checked");
				switch (style) {
						case "3":
								selectedOption = $("input[id="+id+"]:checked");
								console.log("----");
								console.log(selectedOption.val());
								console.log($(this).val());
								break;
						default:
								selectedOption = $("#"+group_id+" input:radio:checked");
								break;
				}
		});

		$('input[type=radio]').click(function(){
				var id = $(this).attr('id');
				var input_id = $(this).attr('input-id');
				var inputBox = $("input[type=text][id="+input_id+"]");
				var value = inputBox.val();
				var style = $(this).attr("group-style");
				var group_id = $(this).attr("group-id");

				if ($(this).prop("value")==selectedOption.prop("value")) {
						$(this).prop("checked", false);
						inputBox.val("");

						switch (style) {
								case "2":
										removeData(input_id);
										break;
								default:
										removeData(id);
										break;
						};

				} else {
						if (!value) {
								//value = $("label[id="+input_id+"]").text();
								value = $(this).val();
								console.log(value);
						}
						console.log(id);
						if (style==3) {
								text = $("input[type=text][id="+id+"]").val();
								createData(id, value, text);
						} else {
								createData(id, value);
						}
						inputBox.focus();

						switch (style) {
								case "4":
										var ids = "";
										$('#'+group_id+' input:checkbox:checked').each(function() {
												$(this).prop("checked", false);
												ids += $(this).attr("id")+";";
										});

										if (ids) {
												ids = ids.substring(0, ids.length - 1);
												//removeData(ids);
										}
										break;
								case "2":
										var inputBox = $("input[type=text][id="+selectedOption.attr('input-id')+"]");
										inputBox.val("");
										break;
						}

				}
		});

		// ************ TEXT ***************

		$('input[type=text]').keydown(function(){
				var id = $(this).attr('id');
		});

		$('input[type=text]').keyup(function(){
				var id = $(this).attr('id');
				var value = $(this).prop("value");
				var pos = $(this).attr("input-position");
				var style = $(this).attr("group-style");
				var group_id = $(this).attr("group-id");
				/*
				console.log("text:id: "+id);
				console.log("text:value: "+value);
				console.log("pos: " + pos);
				 */

				//var checkedOption = $("input[id='"+id+"']:checked");
				//console.log("checked option: "+checkedOption.prop('value'));
				

				switch(style) {
				case '2':
						var ids = "";
						if (value) {
								$("input[id="+id+"]").prop('checked', true);

								$('#'+group_id+' :input:text').each(function() {
										if (id != $(this).attr("id")) {
											$(this).val("");
											ids += $(this).attr("id")+";";
										}
								});

								ids = ids.substring(0,ids.length-1);
								console.log("-------------");
								console.log(ids);
								//removeData(ids);
						}
						break;
				default:
						$("#"+id+":checkbox").prop("checked", true);
						break;
				};
		});

		$('input[type=text]').focusout(function(){
				var id = $(this).attr('id');
				var value = $(this).prop("value");
				var root = id; //id.substring(0, id.length-6);
				var pos = $(this).attr("input-position");
				var style = $(this).attr("group-style");

				console.log("text:id: "+id);
				console.log("text:value: "+value);
				console.log("text:pos: " + pos);
				console.log("text:root: " + root);

				if (value) createData(root, value, value);
		});
});

function createData(key, value, description) {
		var dataString = "key="+key+"&value="+value+"&soap={{ $soap }}&filename={{ $filename }}";
		if (description) {
			dataString = dataString+"&description="+description;
		}
		console.log(dataString);
		$.ajax({
		type: "POST",
				headers: {'X-CSRF-TOKEN': $('meta[name=csrf-token]').attr('content')},
				url: "{{ route('cp.create') }}",
				data: dataString,
				success: function(data){
						console.log(data);
				}
		});
}

function removeData(key) {
		var dataString = "ids="+key+"&soap={{ $soap }}";
		console.log(dataString);
		$.ajax({
		type: "POST",
				headers: {'X-CSRF-TOKEN': $('meta[name=csrf-token]').attr('content')},
				url: "{{ route('cp.remove') }}",
				data: dataString,
				success: function(data){
						console.log(data);
				}
		});
}

window.addEventListener( "pageshow", function ( event ) {
        var historyTraversal = event.persisted ||
                         ( typeof window.performance != "undefined" &&
                              window.performance.navigation.type === 2 );
        if ( historyTraversal ) {
        // Handle page restore.
        window.location.reload();
        }
});
</script>
@endsection
