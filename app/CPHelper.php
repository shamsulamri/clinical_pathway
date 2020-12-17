<?php

namespace App;

use Illuminate\Support\Facades\Storage;
use Log;

class CPHelper 
{
		public $full_text = "";

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
				$str = str_replace("+", "", $str);
				$str = str_replace("(", "", $str);
				$str = str_replace(")", "", $str);
				$str = str_replace("?", "", $str);
				$str = str_replace(".", "", $str);
				$str = str_replace(",", "", $str);
				$str = str_replace("/ ", "", $str);
				$str = str_replace("/", "", $str);
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


		public function inputBox($type, $side, $label, $id, $value, $position, $group_style, $group_id,$soap, $detail_submenu, $problem, $detail_text, $target_problem)
		{
				if ($label=="<>") $label = "";

				$style = "";
				$text_value =$value;
				if ($group_style == 3) {
						$style = "style='padding-top:8px'"; 
						//$text_value = $detail_text??null;
				}

				if (!empty($detail_submenu)) {
						$link = "<a id='".$id."' 
									detail_submenu='?filename=".$detail_submenu."&parent=".$id."&target_problem=".$target_problem."'
									group-style='".$group_style."'
									group-id='".$group_id."'
									href='#'>";
						$label = $link.$label."<label ".$style.">&nbsp;</label>...</a>";
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

				$filepath = strtolower($filepath);

				if (!Storage::exists($filepath)) {
						Log::info("DOES NOT EXIST: ".$filepath);
						return null;
				}

				$file = Storage::get($filepath);
				$pathways = explode("\n", $file);
				array_push($pathways, "\n");

				$filenames = [];
				foreach($pathways as $index=>$p) {
						if ($helper->stringStartsWith($p, "<load>")) {
								$filename = strtolower($helper->removeFromString("<load>", $p));
								array_push($filenames, $filename);
						}
				}

				foreach ($filenames as $filename) {
						foreach($pathways as $index=>$p) {
								if ($helper->stringStartsWith($p, "<load>")) {
										$found = strtolower($helper->removeFromString("<load>", $p));
										if ($found == $filename) {
												$file = Storage::get('clinical_pathways/'.$soap.'/'.$filename);
												$details = explode("\n", $file);

												unset($pathways[$index]);
												array_splice($pathways, $index, 0, $details);
										}
								}
						}
				}

				array_push($pathways, "\n");
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
				$filename = strtolower($filename);
				
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
				if ($soap=='pmh') $problem = 'pmh';

				if (empty($filename)) {
						$filename = $problem." - ".$section;
						$filename = str_replace("_", " ", $filename);
				}

				$filename = strtolower($filename);

				$file = Storage::get('clinical_pathways/'.$soap.'/'.$filename);
				if (empty($file)) {
					Log::info("FILE NOT FOUND!!!!!!!!! ".$filename);
				} else {
					//Log::info("FOUND ".$filename);
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
								$pathway['group_index']=$index;
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

		public function compileText($patient_id, $consultation_id, $soap, $problem, $section, $group)
		{
				$problem = $this->toId($problem);
				$section = $this->toId($section);
				$group = $this->toId($group);

				$obj = $this->pathwayObject($soap, $problem, $section, $group);

				$str = "";
				$kvs = null;

				if ($soap=='pmh') {
						$history = History::find($patient_id);
						if ($history) {
								$kvs = $history->history_pathway;
						}
				} else {
						$consultation = Consultation::where('consultation_id',$consultation_id)->first();
						if ($consultation) {
								$kvs = $consultation->consultation_pathway;
						}
				}

				if ($kvs) {
						if (!empty($kvs[$soap][$problem][$section][$group])) {
								Log::info("----------------START-----------------");
								//Log::info($soap." - ".$problem." - ".$section." - ".$group);
								$path = ["soap"=>$soap, "problem"=>$problem, "section"=>$section, "group"=>$group];
								$group_details = $kvs[$soap][$problem][$section][$group];
								//Log::info($group_details);
								$str = $str.$this->compileGroup($group_details, $path);
						}
				}

				$str = str_replace("<blank>", "", $str);
				$str = str_replace(" ()", "", $str);
				$str = str_replace(".)", ")", $str);
				$str = str_replace("( ", "(", $str);
				$str = str_replace(" ;", "", $str);
				$str = str_replace("..", ".", $str);
				$str = rtrim($str, "; ");
				Log::info($str);
				return $str;
		}


		public function compileGroup($group, $path=null)
		{
				$str = "";
				$str_yes ="";
				$str_no = "";
				$obj = $this->pathwayObject($path['soap'], $path['problem'], $path['section'], $path['group']);

				Log::info("----------------GROUP-----------------");
				//Log::info($group);
				$group_text = $group['group_text'];
				$group_index = $group['group_index'];
				$group_filename = null;
				$group_name = $group['group'];
				if (!empty($group['note'])) {
					$group_note = $group['note'];
				}
				unset($group['group_text']);
				unset($group['group_index']);
				unset($group['group']);
				unset($group['note']);

				if (!empty($group['filename'])) {
						$group_filename = $group['filename'];
						unset($group['filename']);
						$group_name = $this->toId($group_name);
						Log::info("-------------->".$group_name);
						$obj = $this->pathwayObject($path['soap'], null, null, $group_name, $group_filename);
				}
				
				//$this->pretty($obj);
				
				/** Sort detail in each group **/
				usort($group, function($a, $b){
						return $a['index'] <=> $b['index'];
				});


				foreach($group as $detail) {
						$child_str="";
						if ($detail['child']) {
								Log::info("----------------CHILD-----------------");
								/** Sort group **/
								usort($detail['child'], function($a, $b){
										return $a['group_index'] <=> $b['group_index'];
								});

								foreach($detail['child'] as $key=>$child) {
										$child_str = $child_str." ".$this->compileGroup($child, $path);
								}

								Log::info("CHILD STR: ".$child_str);
						}

						/** Compile detail text **/
						if ($obj['group_style']==3) {
								Log::info($detail['text']);
								Log::info($detail['description']);
								if ($detail['description']) {
										$detail['text'] = str_replace("<insert_value>", trim($detail['description']), $detail['text']);
								}

								if ($detail['value']==1) {
										$str_yes = $str_yes.$detail['text'].$child_str."@@@";
								} else {
										$str_no = $str_no.$detail['text'].$child_str."@@@";
								}
								Log::info("YES: ".$str_yes);
								Log::info("NO: ".$str_no);
						} else {
								if (strpos($detail['text'], "<insert_text>") !== false) {
										//$str = $str.str_replace("<insert_text>", trim($child_str), $detail['text'])."@@@";
										$detail['text'] = str_replace("<insert_text>", trim($child_str), $detail['text'])."@@@";
								} else {
										//$str = $str.$detail['text'].$child_str."@@@";
										$detail['text'] = $detail['text'].$child_str."@@@";

								}

								if ($detail['description']) {
										if (strpos($detail['text'], "<insert_value>") !== false) {
												$detail['text'] = str_replace("<insert_value>", $detail['description'], $detail['text']);
										} else {
												$detail['text'] = $detail['description'].$detail['text'];
										}
								}

								$str = $str.$detail['text'];
						}

						//$str = str_replace("<insert_value>", $detail['description'], $str);

				}

				if ($obj['group_style']==3) {
						$group_str = "";
						if ($str_yes) {
						Log::info("!!!!!!!!!!!!!!!");
						Log::info($obj['yes_text']);
								$str_yes = $this->cleanStr($str_yes, $obj['group_separator']??null);
								if (strpos($obj['yes_text'], "<insert_text>") !== false) {
										Log::info("!!!!!!!!!!!!!!!");
										$group_str = str_replace("<insert_text>", $str_yes, $obj['yes_text']);
								} else {
										$group_str = $obj['yes_text'].$str_yes;
								}
						}

						if ($str_no) {
								$str_no = $this->cleanStr($str_no, $obj['group_separator']??null);
								if (empty($str_yes)) {
										$group_str = "No ".$str_no;
								} else {
										if (strpos($obj['no_text'], "<insert_text>") !== false) {
												$group_str = $group_str.str_replace("<insert_text>", $str_no, $obj['no_text']);
										} else {
												$group_str = $group_str." ".$obj['no_text']." ".$str_no;
										}

								}
						}

						Log::info("GROUP TEXT: ".$group_text);
						$group_str = str_replace("<insert_text>", trim($group_str), $group_text);
				} else {
						//Log::info($str);
						/** Compile group text **/
						Log::info("DDDDDD ".$str);
						Log::info("DDDDDD ".$group_text);
						$group_str = str_replace("<insert_text>", $str, $group_text);
						$group_str = $this->cleanStr($group_str, $obj['group_separator']??null);
				}

				//Log::info($group_str);
				if(!empty($obj['group_newline'])) {
						if ($obj['group_newline']==1) $group_str = "<br>".$group_str;
				}

				//$group_str = strtolower($group_str);
				//$group_str = ucfirst($group_str);
				Log::info($str);
				return $group_str;

		}


		public function cleanStr($str, $separator=null) 
		{
				$words = explode("@@@", $str);
				//array_pop($words);
				$s = null;
				foreach($words as $index=>$word) {
						$s = $s.$word;

						if ($separator) {
								if ($index+2<sizeof($words)) {
										$s = $s.$separator." ";
								}
						} else {
								if ($index+3<sizeof($words)) {
									$s = $s.", ";
								}
								if ($index==sizeof($words)-3) {
									$s = $s." and ";
								}
						}
				}

				//Log::info("CLEAN: ".$s);
				return $s;
		}

		public function compileText2($consultation_id, $soap, $problem, $section, $group)
		{
				$problem = $this->toId($problem);
				$section = $this->toId($section);
				$group = $this->toId($group);
				//Log::info($soap." - ".$problem." - ".$section." - ".$group);
				$obj = $this->pathwayObject($soap, $problem, $section, $group);

				if ($soap=='pmh') {
						$history = History::find(1);
						if ($history) {
								$kvs = $history->history_pathway;
								if (!empty($kvs[$soap][$problem][$section][$group])) {
										$details = $kvs[$soap][$problem][$section][$group];
										Log::info("----------------START-----------------");
										return $this->loopDetails($soap, $details, $obj);
								}
						}

				} else {
						$consultation = Consultation::where('consultation_id',$consultation_id)->first();

						if ($consultation) {
								$kvs = $consultation->consultation_pathway;
								if (!empty($kvs[$soap][$problem][$section][$group])) {
										$details = $kvs[$soap][$problem][$section][$group];
										Log::info("----------------START-----------------");
										
										/*
										$x = $details['pain']['child'];
										usort($x, function($a, $b){
												return $a['group_index'] <=> $b['group_index'];
										});
										Log::info($this->pretty($details));
										Log::info($this->pretty($x));
										 */
										 
										return $this->loopDetails($soap, $details, $obj);
								}
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
				$no_text = $obj['no_text']??"but no";
				$newline = empty($obj['group_newline'])?0:1;
				$separator = $obj['group_separator']??null;
				$filename = $details['filename']??null;
				$has_children = false;
				Log::info($details);

				unset($details['group_text']);
				unset($details['group_newline']);
				unset($details['group_separator']);
				unset($details['yes_text']);
				unset($details['no_text']);
				unset($details['note']);
				unset($details['filename']);

				/*
				usort($details, function($a, $b){
						return $a['index'] <=> $b['index'];
				});
				 */

				//Log::info($this->pretty($details));

				foreach($details as $index=>$detail) {
						if ($detail['child']) {

								/*
								$x = $detail['child'];
								usort($x, function($a, $b){
										return $a['group_index'] <=> $b['group_index'];
								});
								Log::info("=========== XXXXX ============");
								Log::info($this->pretty($x));
								 */

							foreach($detail['child'] as $group=>$child) {
									//$group = array_keys($child)[0];
									$child_filename = $child['filename'];
									Log::info("---->>>> ".$group);
									Log::info("---->>>> ".$child_filename);
									$child_obj = $this->pathwayObject($soap, null, null, $group, $child_filename);
									$child_str = $child_str.$this->loopDetails($soap, $child, $child_obj, false);

									$has_children = true;
									Log::info("---->>>> ".$child_str);
							}
						}

						Log::info("---------GROUP STYLE----------");
						Log::info($obj['group']);
						Log::info($obj['group_style']);
						// Combine detail text
						if ($obj['group_style']==3) {
								if ($detail['value']==1) {
										if (!empty($child_str)) {
												$str_yes = $str_yes.$detail['text']." ".$child_str.", ";
												$child_str = null;
										} else {
												$str_yes = $str_yes.$detail['text'].", ";
										}
								}
								if ($detail['value']==2) {
										$str_no = $str_no.$detail['text'].", ";
								}
						} else {
								//$detail_text = $detail['text']=="empty"?$detail['value']:$detail['text'];
								if (strtolower($detail['value'])==strtolower($detail['text'])) {
										Log::info("ZZZZ");
										$detail_text = $detail['text'];
								} else {
										Log::info("vvvv");
										$detail_text = trim($detail['text'])=='empty'?'':$detail['text'];
										$detail_text = trim($detail_text);
										Log::info("LLLLL: ".$detail_text);
								}

								if ($child_str) {
										if (strtolower($detail['text'])==substr(strtolower($child_str),0, strlen($detail['text']))) {
											$str = $str." ".$child_str;
											Log::info("IIIA ".($isRoot?"ROOT":"CHILD").": ".$str); 
										} else {
											Log::info(">>>> has children: ".$has_children);
											Log::info("IIIB ".($isRoot?"ROOT":"CHILD").": ".$str);
											if ($has_children and $isRoot and !empty($obj['details'][$detail_text]['detail_label'])) {
													Log::info("BBBBB");
													$str = $str." ".$child_str;
											} else {
													Log::info("FFFFF");
													Log::info($detail_text);
													if ($isRoot) {
															$str = str_replace("<insert_text>", $child_str, $detail_text);
													} else {
															$str = $str." ".$detail_text." ".$child_str;
													}
											}
											Log::info("IIIBBB ".($isRoot?"ROOT":"CHILD").": ".$str);
										}
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
								Log::info($str_yes);
								if ($isRoot) {
										$str = $this->commaAnd($str_yes, $separator);
								}

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
						Log::info(">>>> has children: ".$has_children);
						if (!$has_children) {
								$str = $this->commaAnd($str, $separator);
						}
						Log::info("XXX ".($isRoot?"ROOT":"CHILD").": ".$str);
				}

				$str = $this->removeCharAtEnd(",", trim($str));
				$str = $this->removeCharAtEnd(";", trim($str));
				$str = str_replace("<insert_text>", $str, $group_text);
				$str = str_replace("<blank> ", "", $str);
				$str = str_replace("(<blank>)", "", $str);
				$str = str_replace(" <blank>)", ")", $str);

				if ($newline==1) {
						$str = "<br>".$str;
				}

				Log::info(($isRoot?"ROOT":"CHILD").": ".$str);
				return $str;
		}

		public function commaAnd($str, $separator=null, $group_style=null)
		{
				Log::info("COMMA: ".$str);
				Log::info("SEPARATOR: ".$separator);
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

		public function getNote($patient_id, $consultation_id, $soap, $problem, $section=null, $group=null) 
		{
				if ($soap=='pmh') $problem='pmh';

				//$consultation_id = 99;
				//$patient_id = 1;

				$soap = $this->toId($soap);
				$problem = $this->toId($problem);
				$section = $this->toId($section);
				$group = $this->toId($group);

				$history = null;
				$consultation = null;

				if ($soap=='pmh') {
						$history = History::find($patient_id);
				} else {
						$consultation = Consultation::where('consultation_id', $consultation_id)->first();
				}

				//Log::info($soap."-".$problem."-".$section."-".$group);
				if ($history) {
						$kvs = $history->history_pathway;
						if ($group) {
							$note = $kvs[$soap][$problem][$section][$group]['note']??null;
						} elseif ($section) {
							$note = $kvs[$soap][$problem][$section]['note']??null;
						} else {
							$note = $kvs[$soap][$problem]['note']??null;
						}
						return $note;
				}


				if ($consultation) {
						Log::info("======================>>>>");
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
				$path = 'clinical_pathways/'.$soap.'/'.$problem;
				$path = strtolower($path);

				$helper = new CPHelper();
				$file = Storage::get($path);

				$problems = explode("\n", $file);
				return $problems;
		}
}
