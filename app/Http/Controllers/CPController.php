<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Log;
use App\CPHelper;
use App\Consultation;
use App\History;

class CPController extends Controller
{
		public function generate(Request $request, $patient_id,  $consultation_id, $soap, $problem, $section=null)
		{
				if ($soap=='pmh') {
						$problem = 'pmh';
						$target_problem = $request->target_problem;
				} else {
						$problem = $request->target_problem?:$problem;
				}

				$helper = new CPHelper();

				$problem_list = $helper->getProblemList($soap, $problem);

				if (empty($section)) {
					$section = strtolower($problem_list[0]);
				}

				$filename = $problem." - ".$section;

				if (!empty($request->filename)) {
						$filename = $request->filename;
				}

				Log::info("---->>>>".$filename);
				$pathways = $helper->getPathways($soap, $filename);

				//$groups = $helper->getGroups($helper->getPathways($soap, $problem." - ".$section));
				//$groups = $helper->getGroups($pathways);


				$groups = $helper->getSectionGroups($soap, $problem, $section);

				$kvs = [];
				if ($soap=='pmh') {
						$history = History::where('patient_id', $request->patient_id)->first();
						$kvs = $history->history_pathway??null;
				} else {
						$consultation = Consultation::where('consultation_id', $consultation_id)->first();
						$kvs = $consultation->consultation_pathway??null;
				}

				//Log::info(json_encode($kvs[$soap][$helper->toId($problem)][$helper->toId($section)]??null, JSON_PRETTY_PRINT));
				Log::info(json_encode($kvs, JSON_PRETTY_PRINT));

				//Log::info("------PATHWAYS-----");
				//Log::info($pathways);
				/*
				$parent_details = [];
				$psgd = [];
				if ($request->parent) {
						$tree = explode("---",$request->parent);
						$filename = null;
						foreach($tree as $index=>$t) {
								$psgd = $helper->getPSGD($t);
								$obj = $helper->pathwayObject($soap, $psgd[0], $psgd[1], $psgd[2], $filename);
								$detail = $obj['details'][$psgd[3]];
								$filename = $detail['detail_submenu'];
								array_push($parent_details, $detail);
						}
				}
				 */
				return view('pathways', [
					'pathways'=>$pathways,	
					'helper'=>$helper,
					'section'=>$section,
					'section_id'=>$helper->toId($section),
					'soap'=>$soap,
					'parent'=>$request->parent??null,
					'kvs'=>$kvs??[],
					'problem_list'=>$problem_list??null,
					'problem'=>$problem,
					'filename'=>$request->filename??null,
					'groups'=>$groups??null,
					'parent_details'=>$parent_details??null,
					'consultation_id'=>$consultation_id,
					'patient_id'=>$request->patient_id,
					'target_problem'=>$target_problem??$problem,
				]);
		}


		public function create(Request $request) 
		{
				Log::info("==REQUEST==");
				Log::info($request);

				$helper = new CPHelper();
				$consultation_id = $request->consultation_id;

				$kvs = [];
				$key = $request->key;
				$value = $request->value;
				$description = $request->description??null;
				$soap = $request->soap;


				$keys = explode("---",$request->key);
				$pgd = $helper->getPSGD($keys[count($keys)-1]);
				[$problem, $section, $group, $detail] = $helper->getPSGD($keys[count($keys)-1]);
				unset($keys[count($keys)-1]);

				/*
				Log::info($problem);
				Log::info($section);
				Log::info($group);
				Log::info($detail);
				Log::info("Filename: ".$request->filename);
				 */
				$obj = $helper->pathwayObject($soap, $problem, $section, $group, $request->filename);
				Log::info(".................");
				Log::info($obj);

				$detail_text = $obj['details'][$detail]['detail_text'];

				$node['value'] = $value;
				if ($obj['group_style']==3) {
						$node['text'] = str_replace("<insert_text>", $description, $detail_text);
				} else {
						//$node['text'] = str_replace("<insert_text>", $value, $detail_text);
						$node['text'] = $detail_text;
				}
				$node['description'] = $description;
				$node['index'] = $obj['details'][$detail]['detail_index'];
				$node['boolean'] = $request->yesno??null;

				if (empty($node['text'])) $node['text'] = strtolower($obj['details'][$detail]['detail']);
				
				$removeCheckedItems = false;
				$removeRadio = false;
				$radioId = null;
				
				if ($obj['group_style']==4) {
						/** Set group text to empty if radio selected **/
						if ($obj['details'][$pgd[3]]['detail_index']==1) {
								// Remove all checked items
								$obj['group_text']="<insert_text>";
								$removeCheckedItems = true;
						} else {
								$removeRadio = true;
								$array_values = array_values($obj['details']);
								Log::info($array_values[0]['detail']);
								$radioId = $helper->toId($array_values[0]['detail']);
						}
				}

				$consultation = new Consultation();
				$history = new History();

				if ($soap=='pmh') {
						$history = History::find($request->patient_id);
						if ($history) {
								$kvs = $history->history_pathway;
						} else {
								$history = new History();
								$history->patient_id = $request->patient_id;
						}
				} else {
						$consultation = Consultation::where('consultation_id', $consultation_id)->first();
						if ($consultation) {
								$kvs = $consultation->consultation_pathway;
						} else {
								$consultation = new Consultation();
								$consultation->consultation_id = $request->consultation_id;
						}
				}

				switch (count($keys)) {
						case 0: // Root
								if ($obj['group_style']==2) {
										unset($kvs[$soap][$problem][$pgd[1]][$pgd[2]]);
								}
								if ($obj['group_style']==4 and $removeCheckedItems) {
										$kvs[$soap][$problem][$pgd[1]][$pgd[2]] = null;
								}
								if ($obj['group_style']==4 and $removeRadio) {
										unset($kvs[$soap][$problem][$pgd[1]][$pgd[2]][$radioId]);
								}
								$kvs[$soap][$problem][$pgd[1]][$pgd[2]]['group_text'] = $obj['group_text'];
								$kvs[$soap][$problem][$pgd[1]][$pgd[2]]['group_index'] = $obj['group_index'];
								$kvs[$soap][$problem][$pgd[1]][$pgd[2]]['group'] = $obj['group'];
								$kvs[$soap][$problem][$pgd[1]][$pgd[2]][$detail] = $node;
								$kvs[$soap][$problem][$pgd[1]][$pgd[2]][$detail]['child'] = null;
								break;
						case 1:
								$root_pgd = $helper->getPSGD($keys[0]);

								$child = $kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child'];

								if ($obj['group_style']==2) {
										unset($child[$pgd[2]]);
								}
								if ($obj['group_style']==4 and $removeCheckedItems) {
										unset($child[$pgd[2]]);
										//$child = null;
								}
								if ($obj['group_style']==4 and $removeRadio) {
										unset($child[$pgd[2]][$radioId]);
								}

								$child[$pgd[2]]['group_text'] = $obj['group_text'];
								$child[$pgd[2]]['group_index'] = $obj['group_index'];
								$child[$pgd[2]]['group'] = $obj['group'];
								$child[$pgd[2]]['filename'] = $request->filename;
								$child[$pgd[2]][$pgd[3]] = $node;
								$child[$pgd[2]][$pgd[3]]['child'] = null;

								$kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child'] = $child;

								Log::info("----------");
								Log::info($kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']);
								break;
						case 2:
								$root_pgd = $helper->getPSGD($keys[0]);
								$child1_pgd = $helper->getPSGD($keys[1]);

								$child = $kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']; 
								$child2 = $child[$child1_pgd[2]][$child1_pgd[3]]['child'];

								if ($obj['group_style']==2) {
										unset($child2[$pgd[2]]);
								}
								if ($obj['group_style']==4 and $removeCheckedItems) {
										unset($child2[$pgd[2]]);
										//$child2= null;
								}
								if ($obj['group_style']==4 and $removeRadio) {
										unset($child2[$pgd[2]][$radioId]);
								}
								$child2[$pgd[2]]['group_text'] = $obj['group_text'];
								$child2[$pgd[2]]['group_index'] = $obj['group_index'];
								$child2[$pgd[2]]['group'] = $obj['group'];
								$child2[$pgd[2]]['filename'] = $request->filename;
								$child2[$pgd[2]][$pgd[3]] = $node;
								$child2[$pgd[2]][$pgd[3]]['child'] = null;


								$kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']
										[$child1_pgd[2]][$child1_pgd[3]]['child'] = $child2;
								break;
						case 3:
								$root_pgd = $helper->getPSGD($keys[0]);
								$child1_pgd = $helper->getPSGD($keys[1]);
								$child2_pgd = $helper->getPSGD($keys[2]);

								$child = $kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']; 
								$child2 = $child[$child1_pgd[2]][$child1_pgd[3]]['child'];
								$child3 = $child2[$child2_pgd[2]][$child2_pgd[3]]['child'];

								if ($obj['group_style']==2) {
										unset($child3[$pgd[2]]);
								}
								if ($obj['group_style']==4 and $removeCheckedItems) {
										$child3 = null;
								}
								if ($obj['group_style']==4 and $removeRadio) {
										unset($child3[$pgd[2]][$radioId]);
								}

								$child3[$pgd[2]]['group_text'] = $obj['group_text'];
								$child3[$pgd[2]]['group_index'] = $obj['group_index'];
								$child3[$pgd[2]]['group'] = $obj['group'];
								$child3[$pgd[2]]['filename'] = $request->filename;
								$child3[$pgd[2]][$pgd[3]] = $node;
								$child3[$pgd[2]][$pgd[3]]['child'] = null;

								$kvs	[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']
										[$child1_pgd[2]][$child1_pgd[3]]['child'] 
										[$child2_pgd[2]][$child2_pgd[3]]['child'] = $child3;
								break;
						case 4:
								$root_pgd = $helper->getPSGD($keys[0]);
								$child1_pgd = $helper->getPSGD($keys[1]);
								$child2_pgd = $helper->getPSGD($keys[2]);
								$child3_pgd = $helper->getPSGD($keys[3]);

								$child = $kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']; 
								$child2 = $child[$child1_pgd[2]][$child1_pgd[3]]['child'];
								$child3 = $child2[$child2_pgd[2]][$child2_pgd[3]]['child'];
								$child4 = $child3[$child3_pgd[2]][$child3_pgd[3]]['child'];

								if ($obj['group_style']==2) {
										unset($child4[$pgd[2]]);
								}
								if ($obj['group_style']==4 and $removeCheckedItems) {
										unset($child4[$pgd[2]]);
										//$child4 = null;
								}
								if ($obj['group_style']==4 and $removeRadio) {
										unset($child4[$pgd[2]][$radioId]);
								}

								$child4[$pgd[2]]['group_text'] = $obj['group_text'];
								$child4[$pgd[2]]['group_index'] = $obj['group_index'];
								$child4[$pgd[2]]['group'] = $obj['group'];
								$child4[$pgd[2]]['filename'] = $request->filename;
								$child4[$pgd[2]][$pgd[3]] = $node;
								$child4[$pgd[2]][$pgd[3]]['child'] = null;

								$kvs	[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']
										[$child1_pgd[2]][$child1_pgd[3]]['child'] 
										[$child2_pgd[2]][$child2_pgd[3]]['child'] 
										[$child3_pgd[2]][$child3_pgd[3]]['child'] = $child4;
								break;
						case 5:
								$root_pgd = $helper->getPSGD($keys[0]);
								$child1_pgd = $helper->getPSGD($keys[1]);
								$child2_pgd = $helper->getPSGD($keys[2]);
								$child3_pgd = $helper->getPSGD($keys[3]);
								$child4_pgd = $helper->getPSGD($keys[4]);

								$child = $kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']; 
								$child2 = $child[$child1_pgd[2]][$child1_pgd[3]]['child'];
								$child3 = $child2[$child2_pgd[2]][$child2_pgd[3]]['child'];
								$child4 = $child3[$child3_pgd[2]][$child3_pgd[3]]['child'];
								$child5 = $child4[$child4_pgd[2]][$child4_pgd[3]]['child'];

								if ($obj['group_style']==2) {
										unset($child5[$pgd[2]]);
								}
								if ($obj['group_style']==4 and $removeCheckedItems) {
										unset($child5[$pgd[2]]);
										//$child5 = null;
								}
								if ($obj['group_style']==4 and $removeRadio) {
										unset($child5[$pgd[2]][$radioId]);
								}

								$child5[$pgd[2]]['group_text'] = $obj['group_text'];
								$child5[$pgd[2]]['group_index'] = $obj['group_index'];
								$child5[$pgd[2]]['group'] = $obj['group'];
								$child5[$pgd[2]]['filename'] = $request->filename;
								$child5[$pgd[2]][$pgd[3]] = $node;
								$child5[$pgd[2]][$pgd[3]]['child'] = null;

								$kvs	[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']
										[$child1_pgd[2]][$child1_pgd[3]]['child'] 
										[$child2_pgd[2]][$child2_pgd[3]]['child'] 
										[$child3_pgd[2]][$child3_pgd[3]]['child']
										[$child4_pgd[2]][$child4_pgd[3]]['child'] = $child5;
								break;
				}

				if ($soap == 'pmh') {
						$history->history_pathway = $kvs;
						Log::info($history);
						Log::info($kvs);
						$history->save();
						Log::info("SAVEEEED!!!!!!!!!!!!!!!!!");
				} else {
						$consultation->consultation_pathway = $kvs;
						$consultation->save();
				}
				//Log::info(json_encode($kvs[$soap][$problem]??null, JSON_PRETTY_PRINT));
				Log::info(json_encode($kvs, JSON_PRETTY_PRINT));
				return "Record saved.";
		}
	
		public function remove(Request $request)
		{
				Log::info("-------REMOVE---------");
				Log::info($request);
				$branches = explode("---", $request->ids);
				$consultation_id = $request->consultation_id;
				if (count($branches)==1) {
						$this->remove_root($request, $consultation_id);
				} else {
						$this->remove_branches($request, $consultation_id);
				}

				return "Remove data...";
		}

		public function remove_branch($consultation_id, $soap, $id)
		{
				$helper = new CPHelper();
				$consultation = null;
				$history = null;
				$kvs = [];

				if ($soap == 'pmh') {
						$history = History::find($request->patient_id);
						$kvs = $history->history_pathway;
				} else {
						$consultation = Consultation::where('consultation_id', $consultation_id)->first();
						$kvs = $consultation->consultation_pathway;
				}

				$branches = explode("---", $id);

				foreach($branches as $index=>$b) {
						$branch[$index] = $helper->getPSGD($b);
				}

				Log::info(count($branches)-1);
				switch (count($branches)-1) {
					case 1: // First branch
							$root_pgd = $helper->getPSGD($branches[0]);
							$child1_pgd = $helper->getPSGD($branches[1]);

							unset($kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']
									[$child1_pgd[2]][$child1_pgd[3]]);

							if (count($kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child'][$child1_pgd[2]])<=4) {
									Log::info("Remove node.....");
									unset($kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']
											[$child1_pgd[2]]);
							}
							break;
					case 2:
							$root_pgd = $helper->getPSGD($branches[0]);
							$child1_pgd = $helper->getPSGD($branches[1]);
							$child2_pgd = $helper->getPSGD($branches[2]);
							unset($kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']
									[$child1_pgd[2]][$child1_pgd[3]]['child']
									[$child2_pgd[2]][$child2_pgd[3]]);

							if (count($kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']
									[$child1_pgd[2]][$child1_pgd[3]]['child']
									[$child2_pgd[2]])<=2) 
							{
									unset($kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']
											[$child1_pgd[2]][$child1_pgd[3]]['child']
											[$child2_pgd[2]]);
							}
							break;
					case 3:
							$root_pgd = $helper->getPSGD($branches[0]);
							$child1_pgd = $helper->getPSGD($branches[1]);
							$child2_pgd = $helper->getPSGD($branches[2]);
							$child3_pgd = $helper->getPSGD($branches[3]);

							unset($kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']
									[$child1_pgd[2]][$child1_pgd[3]]['child']
									[$child2_pgd[2]][$child2_pgd[3]]['child']
									[$child3_pgd[2]][$child3_pgd[3]]);

							if (count($kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']
									[$child1_pgd[2]][$child1_pgd[3]]['child']
									[$child2_pgd[2]][$child2_pgd[3]]['child']
									[$child3_pgd[2]])<=2)
							{
									unset($kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']
											[$child1_pgd[2]][$child1_pgd[3]]['child']
											[$child2_pgd[2]][$child2_pgd[3]]['child']
											[$child3_pgd[2]]);
							}
							break;
					case 4:
							$root_pgd = $helper->getPSGD($branches[0]);
							$child1_pgd = $helper->getPSGD($branches[1]);
							$child2_pgd = $helper->getPSGD($branches[2]);
							$child3_pgd = $helper->getPSGD($branches[3]);
							$child4_pgd = $helper->getPSGD($branches[4]);

							unset($kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']
									[$child1_pgd[2]][$child1_pgd[3]]['child']
									[$child2_pgd[2]][$child2_pgd[3]]['child']
									[$child3_pgd[2]][$child3_pgd[3]]['child']
									[$child4_pgd[2]][$child4_pgd[3]]);

							if (count($kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']
									[$child1_pgd[2]][$child1_pgd[3]]['child']
									[$child2_pgd[2]][$child2_pgd[3]]['child']
									[$child3_pgd[2]][$child3_pgd[3]]['child']
									[$child4_pgd[2]])<=2) {
									
									unset($kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']
											[$child1_pgd[2]][$child1_pgd[3]]['child']
											[$child2_pgd[2]][$child2_pgd[3]]['child']
											[$child3_pgd[2]][$child3_pgd[3]]['child']
											[$child4_pgd[2]]);
							}
							break;
					case 5:
							$root_pgd = $helper->getPSGD($branches[0]);
							$child1_pgd = $helper->getPSGD($branches[1]);
							$child2_pgd = $helper->getPSGD($branches[2]);
							$child3_pgd = $helper->getPSGD($branches[3]);
							$child4_pgd = $helper->getPSGD($branches[4]);
							$child5_pgd = $helper->getPSGD($branches[5]);

							unset($kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']
									[$child1_pgd[2]][$child1_pgd[3]]['child']
									[$child2_pgd[2]][$child2_pgd[3]]['child']
									[$child3_pgd[2]][$child3_pgd[3]]['child']
									[$child4_pgd[2]][$child4_pgd[3]]['child']
									[$child5_pgd[2]][$child5_pgd[3]]);

							if (count($kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']
									[$child1_pgd[2]][$child1_pgd[3]]['child']
									[$child2_pgd[2]][$child2_pgd[3]]['child']
									[$child3_pgd[2]][$child3_pgd[3]]['child']
									[$child4_pgd[2]][$child4_pgd[3]]['child']
									[$child5_pgd[2]])<=2) {
									
									unset($kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']
											[$child1_pgd[2]][$child1_pgd[3]]['child']
											[$child2_pgd[2]][$child2_pgd[3]]['child']
											[$child3_pgd[2]][$child3_pgd[3]]['child']
											[$child4_pgd[2]][$child4_pgd[3]]['child']
											[$child5_pgd[2]]);
							}
							break;
				}

				if ($soap == 'pmh') {
						$history->history_pathway = $kvs;
						$history->save();
				} else {
						$consultation->consultation_pathway = $kvs;
						$consultation->save();
				}

				Log::info(json_encode($kvs, JSON_PRETTY_PRINT));
		}

		public function remove_branches($request, $consultation_id) 
		{
				$soap = $request->soap;
				$ids = explode(";", $request->ids);
				foreach($ids as $id) {
					$this->remove_branch($consultation_id, $soap, $id);
				}
		}

		public function remove_root(Request $request, $consultation_id) 
		{
				$soap = $request->soap;
				$problem = $request->problem;
				$section = $request->section;

				$helper = new CPHelper();

				$consultation = null;
				$history = null;

				if ($soap=='pmh') {
						$history = History::find($request->patient_id);
						$kvs = $history->history_pathway??null;
				} else {
						$consultation = Consultation::where('consultation_id', $consultation_id)->first();
						$kvs = $consultation->consultation_pathway??null;
				}

				if ($kvs) {
						$ids = explode(";",$request->ids);

						Log::info(count($ids));
						foreach($ids as $id) {
								[$problem, $section, $group, $detail] = $helper->getPSGD($id);
								
								if (!empty($kvs[$soap][$problem][$section][$group])) {
										unset($kvs[$soap][$problem][$section][$group][$detail]);
										if (count($kvs[$soap][$problem][$section][$group])==3) {
												unset($kvs[$soap][$problem][$section][$group]);
										}
										if (count($kvs[$soap][$problem][$section])==0) {
												unset($kvs[$soap][$problem][$section]);
										}
										if (count($kvs[$soap][$problem])==0) {
												unset($kvs[$soap][$problem]);
										}
										if (count($kvs[$soap])==0) {
												unset($kvs[$soap]);
										}
								}


								if ($soap == 'pmh') {
										$history->history_pathway = $kvs;
										$history->save();
								} else {
										$consultation->consultation_pathway = $kvs;
										$consultation->save();
								}

								Log::info(json_encode($kvs, JSON_PRETTY_PRINT));
						}
				}
		}

}
