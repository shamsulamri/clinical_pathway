<?php

namespace App;

use Log;

class CPHelper 
{
		public function removeFromString($replace, $str) {
			$str = $this->removeBreakLine($str);
			$new = str_replace($replace, '', $str);
			return $new;
		}

		public function stringStartsWith($str, $start) {
				if (substr($str,0,strlen($start))==$start) {
					return true;
				}

				return false;
		}

		public function removeBreakLine($str)
		{
			$str = preg_replace( "/\r|\n/", "", $str );
			return $str;
		}

		public function toId($str)
		{
				$str = strtolower($str);
				$str = str_replace(" ", "_", $str);
				return $str;
		}

		public function wordContains($str, $word)
		{
				if (strpos($str, $word) !== false) {
						return true;
				} else {
						return false;
				}
		}


		public function inputBox($type, $side, $label, $id, $value, $position, $group_style)
		{
				if ($label=="<>") $label = "";
				$label = "<label id='".$id."_label'>".$label."</label>";

				$id = $id."_input";
				$input = "";
				if ($type=="text") $input = "<input type='text' id='".$id."' name='".$id."' value='".$value ."' input-position=".$position.">";
				if ($type=="date") $input = "<input type='text' id='".$id."' name='".$id."' value='".$value ."' input-position=".$position.">";
				if ($type=="int") $input = "<input type='text' id='".$id."' name='".$id."' value='".$value ."' input-position=".$position.">";
				if ($type=="double") $input = "<input type='text' id='".$id."' name='".$id."' value='".$value ."' input-position=".$position.">";
				
				$input = "<input type='text' id='".$id.
						"' name='".$id.
						"' value='".$value .
						"' input-position=".$position.
						" group-style=".$group_style.
						">";

				if ($side=="L") {
						$input = $input." ".$label;
				} elseif ($side=="R") {
						$input = $label." ".$input;
				} else {
						$input = $label;
				}

				return $input;

		}
}
