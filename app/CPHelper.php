<?php

namespace App;

use Illuminate\Support\Facades\Storage;
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
				$str = trim($str);
				$str = strtolower($str);
				$str = str_replace("(", "", $str);
				$str = str_replace(")", "", $str);
				$str = str_replace("?", "", $str);
				$str = str_replace(".", "", $str);
				$str = trim($str);
				$str = str_replace(" ", "_", $str);
				$str = trim($str);
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


		public function inputBox($type, $side, $label, $id, $value, $position, $group_style, $group_id,$soap, $detail_submenu, $problem)
		{
				if ($label=="<>") $label = "";

				if (!empty($detail_submenu)) {
						$link = "<a id='".$id."' 
									detail_submenu='?filename=".$detail_submenu."&parent=".$id."'
									group-style='".$group_style."'
									group-id='".$group_id."'
									href='#'>";
						$label = $link."<label id='".$id."'>".$label."</label></a>";
				} else {
						$label = "<label id='".$id."'>".$label."</label>";
				}

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
						" group-id='".$group_id."'>";

				if ($side=="L") {
						$input = $input." ".$label;
				} elseif ($side=="R") {
						$input = $label." ".$input;
				} else {
						$input = $label;
				}

				return $input;

		}

		public function getPathways($soap, $filename, $pathways=null)
		{
				$helper = new CPHelper();
				$file = Storage::get('clinical_pathways/'.$soap.'/'.$filename);
				$pathways = explode("\n", $file);

				foreach($pathways as $index=>$p) {
						if ($helper->stringStartsWith($p, "<load>")) {
								$filename = $helper->removeFromString("<load>", $p);

								$file = Storage::get('clinical_pathways/'.$soap.'/'.$filename);
								$details = explode("\n", $file);

								unset($pathways[$index]);
								array_splice($pathways, $index, 0, $details);
						}
				}

				return $pathways;
		}

		public function pathwayGroup($soap, $problem, $section, $group, $filename=null)
		{
				if (empty($filename)) {
						$filename = $problem." - ".$section;
						$filename = str_replace("_", " ", $filename);
				}

				$file = Storage::get('clinical_pathways/'.$soap.'/'.$filename);
				if (empty($file)) {
					Log::info("FILE NOT FOUND!!!!!!!!! ".$filename);
				}

				$pathways = $this->getPathways($soap, $filename);

				$flag = false;
				$break_now = false;

				foreach ($pathways as $p) {
						$p = $this->removeBreakLine($p);
						if ($this->stringStartsWith($p, "<group>")) { 
								$pathway = [];
								$details = [];
								$detail = "";
								$detail_id = "";
								$detail_text = "";
								$detail_inputbox = "";
								$current_detail = "";
								$detail_submenu = "";
								$detail_index = 0;
								$group_name = $this->removeFromString("<group>", $p);
								$group_name = $this->toId($group_name);

								$pathway['group']= $this->removeFromString("<group>", $p);
								if ($group_name==$group) $flag=true;
						}

						if ($flag) {
								if ($this->stringStartsWith($p, "<group_text>")) $pathway['group_text']= $this->removeFromString("<group_text>", $p);
								if ($this->stringStartsWith($p, "<group_style>")) {
										$pathway['group_style']= $this->removeFromString("<group_style>", $p);
								}

								/*** Detail ***/
								if ($detail != $current_detail or $break_now == true) {
										$obj = [
												'detail'=>$current_detail,
												'detail_text'=>$detail_text??null,
												'detail_inputbox'=>$detail_inputbox??null,
												'detail_submenu'=>$detail_submenu??null,
												'detail_index'=>$detail_index??null,
										];

										$detail_id = $this->toId($current_detail);
										$details[$detail_id] = $obj;
										$current_detail = $detail;
										$detail_text = "";
										if ($break_now) break;
								}
								if ($this->stringStartsWith($p, "<detail>")) {
										$detail = $this->removeFromString("<detail>", $p);
										if (empty($detail)) $detail = "empty";
										if ($current_detail=="") $current_detail = $detail;
										$detail_index++;
								}
								if ($this->stringStartsWith($p, "<detail_text>")) $detail_text = $this->removeFromString("<detail_text>", $p);
								if ($this->stringStartsWith($p, "<detail_inputbox>")) $detail_inputbox = $this->removeFromString("<detail_inputbox>", $p);
								if ($this->stringStartsWith($p, "<detail_submenu>")) $detail_submenu = $this->removeFromString("<detail_submenu>", $p);
								if (strlen($p)==0) {
										$break_now = true;
								}
						}
				}

				$pathway['details'] = $details??null;
				return $pathway;
		}

		public function getPSGD($str)
		{
				// Get detail
				$items = explode("__", $str);
				if (count($items)>1) {
					$detail = $items[1];
					$str = $items[0];
				}


				// Get problem
				$items = explode("--", $str);
				if (count($items)==1) {
					$group = $str;
					return [null, null, $group, $detail];
				}
				$problem = $items[0];
				$str = $items[1];

				// Get group
				$items = explode("-", $str);
				$group = $items[1];

				// Get section
				$section = $items[0];

				return [$problem, $section, $group, $detail];
		}

		public function getPGD2($str)
		{
				$items = explode("__", $str);
				if (count($items)>1) {
					$detail = $items[1];
				}

				$items = explode("-", $items[0]);
				for($i=0;$i<count($items);$i++) {
					$items[$i] = trim($items[$i]);
				}

				if (count($items)>2) {
					$merged_items = [];
					for($i=0;$i<count($items)-1;$i++) {
						array_push($merged_items, $items[$i]);
					}
					$problem = implode("-", $merged_items);
					$group = $items[count($items)-1];
				} else {
					$problem = $items[0];
					$group = $items[1];
				}

				return [$problem, $group, $detail??null];
		}

		public function compileText($soap, $problem, $section, $group)
		{
				//$problem = $this->toId($problem." - ".$section);
				$group = $this->toId($group);
				$consultation = Consultation::where('consultation_id',99)->first();

				$obj = $this->pathwayGroup($soap, $problem, $section, $group);

				if ($consultation) {
						$kvs = $consultation->consultation_pathway;

						if (!empty($kvs[$soap][$problem][$group])) {
								$details = $kvs[$soap][$problem][$group];
								return $this->loopDetails($details, $obj);
						}
				}

				return;
		}


		public function loopDetails($details, $obj = null)
		{
				$root_text = $details['group_text'];
				unset($details['group_text']);
				unset($details['note']);
				usort($details, function($a, $b){
						return $a['index'] <=> $b['index'];
				});

				$sentence = "";
				foreach($details as $detail) {
					if (!empty($detail['text'])) {
							$child_text = "";
							if ($detail['child']) {
								$group = null;
								$problem = null;
								foreach($detail['child'] as $key=>$node) {
									$problem = $key;		
								}
								foreach($detail['child'][$problem] as $key=>$node) {
									$group = $key;		
								}

								$group_text = $detail['child'][$problem][$group]['group_text'];
								$child_text = $this->loopDetails($detail['child'][$problem][$group]);

							}

							$sentence = $sentence.($detail['text']?:$detail); //.$child_text;
							if (empty($child_text)) {
									$sentence = $sentence.", ";
							} else {
									$sentence = $sentence." ".$child_text;
							}
					}
				}

				if ($obj['group_style']==4) {
						foreach($obj['details'] as $key=>$node) {
							break;
						}
						if (array_key_exists($key, $details)) {
							$sentence = $detail['text'];
							return $sentence;
						}
				}

				//$group_text = $details['group_text'];
				
				$sentence = trim($sentence);
				$sentence = $this->removeCharAtEnd(",", $sentence);
				$sentence = str_replace("<insert_text>", $sentence, $root_text);
				$sentence = str_replace(" and .", ".", $sentence);


				return $sentence;
		}

		public function removeCharAtEnd($chr, $str)
		{
				if (substr($str,strlen($str)-1,strlen($str))==$chr) {
					$str = substr($str,0,strlen($str)-1);
				}

				return $str;
		}

		public function getNote($soap, $problem, $group) 
		{
				$consultation_id = 99;
				$soap = $this->toId($soap);
				$problem = $this->toId($problem);
				$group = $this->toId($group);

				$consultation = Consultation::where('consultation_id', $consultation_id)->first();

				if ($consultation) {
						$kvs = $consultation->consultation_pathway;
						$note = $kvs[$soap][$problem][$group]['note']??null;
						return $note;
				}

				return null;

		}
		/**
		public function generateSentence($details)
		{
				$sentence = "";
				foreach($details as $detail) {
					if (!empty($detail['text'])) {
							$sentence = $sentence.($detail['text']?:$detail).", ";
					}
				}

				$group_text = $details['group_text'];
				$sentence = substr($sentence, 0, strlen($sentence)-2); 
				$sentence = str_replace("<insert_text>", $sentence, $group_text);
				Log::info($sentence);

				return $sentence;
		}
		**/


		public function pretty($json) 
		{
				Log::info(json_encode($json, JSON_PRETTY_PRINT));
		}
}
