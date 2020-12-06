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


		public function inputBox($type, $side, $label, $id, $value, $position, $group_style, $group_id,$soap, $detail_submenu, $problem, $detail_text)
		{
				if ($label=="<>") $label = "";

				$style = "";
				$text_value =$value;
				if ($group_style == 3) {
						$style = "style='padding-top:8px'"; 
						$text_value = $detail_text??null;
				}

				if (!empty($detail_submenu)) {
						$link = "<a id='".$id."' 
									detail_submenu='?filename=".$detail_submenu."&parent=".$id."'
									group-style='".$group_style."'
									group-id='".$group_id."'
									href='#'>";
						$label = $link.$label."</a><label ".$style.">&nbsp;</label>";
				} else {
						$label = "<label ".$style." id='".$id."' group-style='".$group_style."'>".$label."</label>";
				}

				$input = "";
				if ($type=="text") $input = "<input type='text' id='".$id."' name='".$id."' value='".$value ."' input-position=".$position.">";
				if ($type=="date") $input = "<input type='text' id='".$id."' name='".$id."' value='".$value ."' input-position=".$position.">";
				if ($type=="int") $input = "<input type='text' id='".$id."' name='".$id."' value='".$value ."' input-position=".$position.">";
				if ($type=="double") $input = "<input type='text' id='".$id."' name='".$id."' value='".$value ."' input-position=".$position.">";
				
				$input = "<input type='text' id='".$id.
						"' name='".$id.
						"' value='".$text_value.
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
				$filepath = 'clinical_pathways/'.$soap.'/'.$filename;

				if (!Storage::exists($filepath)) {
						return null;
				}

				$file = Storage::get($filepath);
				$pathways = explode("\n", $file);
				array_push($pathways, "\n");

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

		public function getGroups($pathways)
		{
				$groups = [];

				foreach($pathways as $index=>$p) {
						if ($this->stringStartsWith($p, "<group>")) {
								$group = $this->removeFromString("<group>", $p);
								array_push($groups, $group);
						}
				}

				return $groups;
		}

		public function getSectionGroups($soap, $problem, $section)
		{
				$filename = $problem." - ".$section;
				$filename = str_replace("_", " ", $filename);

				Log::info("Section....");
				Log::info($filename);

				$file = Storage::get('clinical_pathways/'.$soap.'/'.$filename);
				$groups = [];
				$pathways = $this->getPathways($soap, $filename);

				foreach ($pathways as $index=>$p) {
						if ($this->stringStartsWith($p, "<group>")) { 
								$group_name = $this->removeFromString("<group>", $p);
								array_push($groups, $group_name);
						}
				}

				return $groups;

		}

		public function pathwayObject($soap, $problem, $section, $group, $filename=null)
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
				$break_now =false;

				foreach ($pathways as $index=>$p) {
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
								$group_newline = 0;
								$group_separator = "";
								$group_name = $this->removeFromString("<group>", $p);
								$group_name = $this->toId($group_name);
								$detail_line = "";
								$addObject = false;
								$yes_text = "";
								$no_text = "";
								$detail_label = "";

								$pathway['group']= $this->removeFromString("<group>", $p);
								if ($group_name==$group) {
										$flag=true;
								}
						}

						if ($flag) {
								if ($this->stringStartsWith($p, "<group_text>")) $pathway['group_text']= $this->removeFromString("<group_text>", $p);
								if ($this->stringStartsWith($p, "<new_line>")) $pathway['group_newline']= 1;
								if ($this->stringStartsWith($p, "<separator>")) $pathway['group_separator']= $this->removeFromString("<separator>", $p);
								if ($this->stringStartsWith($p, "<yes_text>")) $pathway['yes_text'] = $this->removeFromString("<yes_text>", $p);
								if ($this->stringStartsWith($p, "<no_text>")) $pathway['no_text'] = $this->removeFromString("<no_text>", $p);
								if ($this->stringStartsWith($p, "<group_style>")) {
										$pathway['group_style']= $this->removeFromString("<group_style>", $p);
								}

								/*** Detail ***/
								if ($this->stringStartsWith($p, "<detail>") && !empty($detail_line)) {
										if ($p != $detail_line) {
												$addObject = true;
										}
								}

								if ($addObject or $break_now) {
										$obj = [
												'detail'=>$detail,
												'detail_text'=>$detail_text??null,
												'detail_inputbox'=>$detail_inputbox??null,
												'detail_submenu'=>$detail_submenu??null,
												'detail_index'=>$detail_index??null,
												'detail_label'=>$detail_label??null,
										];

										$detail_id = $this->toId($detail);
										$details[$detail_id] = $obj;
										//$detail_text = "";
										if ($break_now) break;
								}

								if ($this->stringStartsWith($p, "<detail>")) {
										$detail_text = "";
										$detail_label = "";
										$detail = $this->removeFromString("<detail>", $p);
										if (empty($detail)) $detail = "empty";
										$detail_index++;
										$detail_line = $p;
								}
								if ($this->stringStartsWith($p, "<detail_text>")) {
										$detail_text = $this->removeFromString("<detail_text>", $p);
										if (empty($detail_text)) { $detail_label = "<blank>"; }
								}
								if ($this->stringStartsWith($p, "<detail_inputbox>")) $detail_inputbox = $this->removeFromString("<detail_inputbox>", $p);
								if ($this->stringStartsWith($p, "<detail_submenu>")) $detail_submenu = $this->removeFromString("<detail_submenu>", $p);

								if (strlen($p)==0 or $index==count($pathways)) {
										$break_now=true;		
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

		public function compileText($consultation_id, $soap, $problem, $section, $group)
		{
				$problem = $this->toId($problem);
				$section = $this->toId($section);
				$group = $this->toId($group);

				$consultation = Consultation::where('consultation_id',$consultation_id)->first();

				$obj = $this->pathwayObject($soap, $problem, $section, $group);
				if ($group=="general") {
						//Log::info($obj);
				}

				if ($consultation) {
						$kvs = $consultation->consultation_pathway;
						if (!empty($kvs[$soap][$problem][$section][$group])) {
								$details = $kvs[$soap][$problem][$section][$group];
								Log::info("----------------START-----------------");
								return $this->loopDetails($soap, $details, $obj);
						}
				}

				return;
		}

		public function loopDetails($soap, $details, $obj=null, $isRoot=true)
		{
				$str = null;
				$str_yes = null;
				$str_no = null;
				$child_str = null;
				$group_text = $details['group_text'];
				$yes_text = $obj['yes_text']??null;
				$no_text = $obj['no_text']??null;
				$newline = empty($obj['group_newline'])?0:1;
				$separator = $obj['group_separator']??null;
				$filename = $details['filename']??null;
				$has_children = false;

				unset($details['group_text']);
				unset($details['group_newline']);
				unset($details['group_separator']);
				unset($details['yes_text']);
				unset($details['no_text']);
				unset($details['note']);
				unset($details['filename']);

				usort($details, function($a, $b){
						return $a['index'] <=> $b['index'];
				});

				foreach($details as $index=>$detail) {
						if ($detail['child']) {
							$group = array_keys($detail['child'])[0];
							$child_filename = $detail['child'][$group]['filename'];
							Log::info("---->>>> ".$group);
							Log::info("---->>>> ".$filename);
							$child_obj = $this->pathwayObject($soap, null, null, $group, $child_filename);
							$child_str = $this->loopDetails($soap, $detail['child'][$group], $child_obj, false);

							$has_children = true;
							Log::info("---->>>> ".$child_str);
						}

						// Combine detail text

						if ($obj['group_style']==3) {
								if ($detail['value']==1) {
										$str_yes = $str_yes.$detail['text'].", ";
								}
								if ($detail['value']==2) {
										$str_no = $str_no.$detail['text'].", ";
								}
						} else {
								//$detail_text = $detail['text']=="empty"?$detail['value']:$detail['text'];
								if (strtolower($detail['value'])==strtolower($detail['text'])) {
									$detail_text = $detail['text'];
								} else {
									$detail_text = "X".trim($detail['text'])=='empty'?'':$detail['text'];
									$detail_text = trim($detail_text);
								}

								if ($child_str) {
										if (strtolower($detail['text'])==substr(strtolower($child_str),0, strlen($detail['text']))) {
											$str = $str." ".$child_str;
											Log::info("IIIA ".($isRoot?"ROOT":"CHILD").": ".$str);
										} else {
											Log::info(">>>> has children: ".$has_children);
											if ($has_children and $isRoot and !empty($obj['details'][$detail_text]['detail_label'])) {
													$str = $str." ".$child_str;
											} else {
													$str = $str." ".$detail_text." ".$child_str;
											}
										}
										Log::info("IIIB ".($isRoot?"ROOT":"CHILD").": ".$str);
										$child_str = null;
								} else {
										$str = $str.$detail_text;
										Log::info("TTTC ".($isRoot?"ROOT":"CHILD").": ".$str);
								}
						}

						if (!empty($str)) {
								// Separator between detail
								Log::info("HHHH ".($isRoot?"ROOT":"CHILD").": ".$str);
								$str = trim($str).($separator??", ")." ";
								Log::info("HHHH ".($isRoot?"ROOT":"CHILD").": ".$str);
						}

				}

				// Compile sentences 
				
				if ($obj['group_style']==3) {
						if ($str_yes) {
								Log::info("YES TEXT: ".$yes_text);
								$str = $this->commaAnd($str_yes, $separator);
								if (strpos($yes_text, "<insert_text>") !==false) {
										$str = str_replace("<insert_text>", $str, $yes_text);
								} else {
										$str = $yes_text." ".$str;
								}
						}
						if ($str_no) {
							Log::info("NO TEXT: ".$no_text);
							if (strpos($no_text, "<insert_text>") !==false) {
									$temp_str = str_replace("<insert_text>", $this->commaAnd($str_no), $no_text);
									$str = $str." ".$temp_str;
							} else {
									$str = $str." ".$no_text." ".$this->commaAnd($str_no, $separator);
							}
						}
						if (empty($str_yes)) {
							$str = str_replace("but", "", $str);
							$str = ucfirst(trim($str));
						}
						Log::info("EEEE: ".$str);
				} else {
						Log::info("XXX ".($isRoot?"ROOT":"CHILD").": ".$str);
						if (!$has_children) {
								$str = $this->commaAnd($str, $separator);
						}
						Log::info("Separator ".$separator);
						Log::info("XXX ".($isRoot?"ROOT":"CHILD").": ".$str);
				}

				$str = $this->removeCharAtEnd(",", trim($str));
				$str = $this->removeCharAtEnd(";", trim($str));
				$str = str_replace("<insert_text>", $str, $group_text);

				if ($newline==1) {
						$str = "<br>".$str;
				}

				Log::info(($isRoot?"ROOT":"CHILD").": ".$str);
				return $str;
		}

		public function commaAnd($str, $separator=null, $group_style=null)
		{
				Log::info("COMMA: ".$str);
				$words = explode(", ", $str);
				array_pop($words);
				if (empty($words)) return $str;
				$final = null;
				foreach($words as $index=>$word) {
						if (!empty($word)) {
								//Log::info("Word: ".$word);
								$final = $final.$word;
								if ($index+1<count($words)-1) {
										$final = $final.($separator?:", ");
								} else {
										if ($index+1==count($words)) break;
										$final = $final.($separator?:" and ");
								}
						}
				}

				return $final;

		}

		public function removeCharAtEnd($chr, $str)
		{
				if (substr($str,strlen($str)-1,strlen($str))==$chr) {
					$str = substr($str,0,strlen($str)-1);
				}

				return $str;
		}

		public function getNote($soap, $problem, $section=null, $group=null) 
		{
				$consultation_id = 99;
				$soap = $this->toId($soap);
				$problem = $this->toId($problem);
				$section = $this->toId($section);
				$group = $this->toId($group);

				$consultation = Consultation::where('consultation_id', $consultation_id)->first();

				if ($consultation) {
						$kvs = $consultation->consultation_pathway;
						if ($group) {
							$note = $kvs[$soap][$problem][$section][$group]['note']??null;
						} elseif ($section) {
							$note = $kvs[$soap][$problem][$section]['note']??null;
						} else {
							$note = $kvs[$soap][$problem]['note']??null;
						}
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

		public function getProblemList($soap, $problem)
		{
				$helper = new CPHelper();
				$file = Storage::get('clinical_pathways/'.$soap.'/'.$problem);

				$problems = explode("\n", $file);
				return $problems;
		}
}
