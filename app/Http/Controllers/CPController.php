<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Log;
use App\CPHelper;
use App\Consultation;

class CPController extends Controller
{
		public function generate(Request $request, $soap, $problem, $section=null)
		{
				$helper = new CPHelper();
				$consultation_id = 99;

				$problem_list = $helper->getProblemList($soap, $problem);

				if (empty($section)) {
					$section = strtolower($problem_list[0]);
				}

				$filename = $problem." - ".$section;

				if (!empty($request->filename)) {
						$filename = $request->filename;
				}

				$pathways = $helper->getPathways($soap, $filename);

				Log::info($pathways);
				//$groups = $helper->getGroups($helper->getPathways($soap, $problem." - ".$section));
				//$groups = $helper->getGroups($pathways);
				$groups = $helper->getSectionGroups($soap, $problem, $section);

				$consultation = Consultation::where('consultation_id', $consultation_id)->first();
				$kvs = $consultation->consultation_pathway??null;

				//Log::info(json_encode($kvs[$soap][$helper->toId($problem)][$helper->toId($section)]??null, JSON_PRETTY_PRINT));
				//Log::info(json_encode($kvs, JSON_PRETTY_PRINT));

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
				]);
		}


		public function create(Request $request) 
		{
				Log::info("==REQUEST==");
				Log::info($request);

				$helper = new CPHelper();
				$consultation_id = 99;
				$consultation = new Consultation();

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


				$detail_text = $obj['details'][$detail]['detail_text'];
				Log::info(".................");
				Log::info($obj);

				$node['value'] = $value;
				if ($obj['group_style']==3) {
						$node['text'] = str_replace("<insert_text>", $description, $detail_text);
				} else {
						$node['text'] = str_replace("<insert_text>", $value, $detail_text);
				}
				$node['description'] = $description;
				$node['index'] = $obj['details'][$detail]['detail_index'];
				$node['boolean'] = $request->yesno??null;

				if (empty($node['text'])) $node['text'] = strtolower($obj['details'][$detail]['detail']);
				
				$clearAll = false;
				
				if ($obj['group_style']==4) {
						/** Set group text to empty if radio selected **/
						if ($obj['details'][$pgd[3]]['detail_index']==1) {
								$obj['group_text']="<insert_text>";
								$clearAll = true;
						}
				}

				$consultation = Consultation::where('consultation_id', $consultation_id)->first();
				if ($consultation) {
						$kvs = $consultation->consultation_pathway;
				} else {
						$consultation = new Consultation();
						$consultation->consultation_id = 99;
				}

				Log::info("COunt keys: ".count($keys));
				switch (count($keys)) {
						case 0:
								if ($obj['group_style']==2) {
										unset($kvs[$soap][$problem][$pgd[1]][$pgd[2]]);
								}
								if ($obj['group_style']==4 and $clearAll) {
										$kvs[$soap][$problem][$pgd[1]][$pgd[2]] = null;
								}
								$kvs[$soap][$problem][$pgd[1]][$pgd[2]]['group_text'] = $obj['group_text'];
								$kvs[$soap][$problem][$pgd[1]][$pgd[2]][$detail] = $node;
								$kvs[$soap][$problem][$pgd[1]][$pgd[2]][$detail]['child'] = null;
								break;
						case 1:
								$root_pgd = $helper->getPSGD($keys[0]);

								$child = $kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child'];

								if ($obj['group_style']==2) {
										$child = null;
								}
								if ($obj['group_style']==4 and $clearAll) {
										$child = null;
								}
								$child[$pgd[2]]['group_text'] = $obj['group_text'];
								$child[$pgd[2]]['filename'] = $request->filename;
								$child[$pgd[2]][$pgd[3]] = $node;
								$child[$pgd[2]][$pgd[3]]['child'] = null;

								$kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child'] = $child;

								break;
						case 2:
								$root_pgd = $helper->getPSGD($keys[0]);
								$child1_pgd = $helper->getPSGD($keys[1]);

								$child = $kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']; 
								$child2 = $child[$child1_pgd[2]][$child1_pgd[3]]['child'];

								if ($obj['group_style']==2) {
										$child2 = null;
								}
								if ($obj['group_style']==4 and $clearAll) {
										$child2= null;
								}
								$child2[$pgd[2]]['group_text'] = $obj['group_text'];
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

								//$child = $kvs[$soap][$root_pgd[0]][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]]['child']; 
								//$child2 = $child[$child1_pgd[0]][$child1_pgd[1]][$child1_pgd[2]]['child'];
								//$child3 = $child2[$child2_pgd[0]][$child2_pgd[1]][$child2_pgd[2]]['child'];

								if ($obj['group_style']==2) {
										$child3= null;
								}
								if ($obj['group_style']==4 and $clearAll) {
										$child3 = null;
								}


								$child3[$pgd[2]]['group_text'] = $obj['group_text'];
								$child3[$pgd[2]]['filename'] = $request->filename;
								$child3[$pgd[2]][$pgd[3]] = $node;
								$child3[$pgd[2]][$pgd[3]]['child'] = null;

								/**
								$kvs	[$soap][$root_pgd[0]][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]]['child']
										[$child1_pgd[0]][$child1_pgd[1]][$child1_pgd[2]]['child']
										[$child2_pgd[0]][$child2_pgd[1]][$child2_pgd[2]]['child'] = $child3;
										**/

								$kvs	[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']
										[$child1_pgd[2]][$child1_pgd[3]]['child'] 
										[$child2_pgd[2]][$child2_pgd[3]]['child'] = $child3;
								break;
				}

				$consultation->consultation_pathway = $kvs;
				$consultation->save();
				//Log::info(json_encode($kvs[$soap][$problem]??null, JSON_PRETTY_PRINT));
				Log::info(json_encode($kvs, JSON_PRETTY_PRINT));
				return "Record saved.";
		}
	
		public function remove(Request $request)
		{
				Log::info("-------REMOVE---------");
				Log::info($request);
				$branches = explode("---", $request->ids);
				if (count($branches)==1) {
						$this->remove_root($request);
				} else {
						$this->remove_branches($request);
				}

				return "Remove data...";
		}

		public function remove_branch($soap, $id)
		{
				$helper = new CPHelper();
				$consultation_id = 99;
				$consultation = Consultation::where('consultation_id', $consultation_id)->first();
				$kvs = $consultation->consultation_pathway;

				$branches = explode("---", $id);

				foreach($branches as $index=>$b) {
						$branch[$index] = $helper->getPSGD($b);
				}

				switch (count($branches)-1) {
					case 1: // First branch
							$root_pgd = $helper->getPSGD($branches[0]);
							$child1_pgd = $helper->getPSGD($branches[1]);

							unset($kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']
									[$child1_pgd[2]][$child1_pgd[3]]);

							if (count($kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child'][$child1_pgd[2]])==1) {
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

							/*
							unset($kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]]['child']
									[$child1_pgd[0]][$child1_pgd[1]][$child1_pgd[2]]['child']
									[$child2_pgd[0]][$child2_pgd[1]][$child2_pgd[2]]['child']
									[$child3_pgd[0]][$child3_pgd[1]][$child3_pgd[2]]);
							 */
							break;
				}

				$consultation->consultation_pathway = $kvs;
				$consultation->save();

				Log::info(json_encode($kvs, JSON_PRETTY_PRINT));
		}

		public function remove_branches($request) 
		{
				$soap = $request->soap;
				$ids = explode(";", $request->ids);
				foreach($ids as $id) {
					$this->remove_branch($soap, $id);
				}

				/*


				Log::info("Remove branch...................");
				$soap = $request->soap;
				$branches = explode(";", $request->ids);
				$consultation_id = 99;
				$helper = new CPHelper();

				$consultation = Consultation::where('consultation_id', $consultation_id)->first();
				$kvs = $consultation->consultation_pathway;

				if ($consultation) {
						$test_node = $consultation->consultation_pathway;

						foreach($branches as $index=>$branch){
							$pgd = $helper->getPSGD($branch);

							if (!empty($test_node[$soap][$pgd[0]][$pgd[1]][$pgd[2]][$pgd[3]]['child'])) {
									Log::info("Child exist...");
							} else {
									break;
							}	
						}
				}

				switch ($index) {
					case 1:
							$root_pgd = $helper->getPSGD($branches[0]);
							$child1_pgd = $helper->getPSGD($branches[1]);
							unset($kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']
									[$child1_pgd[2]][$child1_pgd[3]]);
							break;
					case 2:
							$root_pgd = $helper->getPGD($branches[0]);
							$child1_pgd = $helper->getPGD($branches[1]);
							$child2_pgd = $helper->getPGD($branches[2]);
							unset($kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]]['child']
									[$child1_pgd[0]][$child1_pgd[1]][$child1_pgd[2]]['child']
									[$child2_pgd[0]][$child2_pgd[1]][$child2_pgd[2]]);
							break;
					case 3:
							$root_pgd = $helper->getPGD($branches[0]);
							$child1_pgd = $helper->getPGD($branches[1]);
							$child2_pgd = $helper->getPGD($branches[2]);
							$child3_pgd = $helper->getPGD($branches[3]);
							unset($kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]]['child']
									[$child1_pgd[0]][$child1_pgd[1]][$child1_pgd[2]]['child']
									[$child2_pgd[0]][$child2_pgd[1]][$child2_pgd[2]]['child']
									[$child3_pgd[0]][$child3_pgd[1]][$child3_pgd[2]]);
							break;
				}

				$consultation->consultation_pathway = $kvs;
				$consultation->save();

				//Log::info(json_encode($kvs, JSON_PRETTY_PRINT));
				*/
		}

		public function remove_root(Request $request) 
		{
				$soap = $request->soap;
				$problem = $request->problem;
				$section = $request->section;

				$consultation_id = 99;
				$helper = new CPHelper();

				$consultation = Consultation::where('consultation_id', $consultation_id)->first();
				if ($consultation) {
						$ids = explode(";",$request->ids);
						$kvs = $consultation->consultation_pathway;

						Log::info(count($ids));
						foreach($ids as $id) {
								[$problem, $section, $group, $detail] = $helper->getPSGD($id);
								
								if (!empty($kvs[$soap][$problem][$section][$group])) {
										unset($kvs[$soap][$problem][$section][$group][$detail]);
										if (count($kvs[$soap][$problem][$section][$group])==1) {
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
								$consultation->consultation_pathway = $kvs;
								$consultation->save();
								Log::info(json_encode($kvs, JSON_PRETTY_PRINT));
						}
				}

				/**
				return "X";

				$consultation_id = 99;

				$ids = explode(";",$request->ids);

				$consultation = Consultation::where('consultation_id', $consultation_id)->first();

				if ($consultation) {
						$kvs = $consultation->consultation_pathway;

						foreach($ids as $id) {
								unset($kvs[$id]);
						}
						$consultation->consultation_pathway = $kvs;
						$consultation->save();
						Log::info("Record removed....");
						Log::info(json_encode($kvs, JSON_PRETTY_PRINT));
						return "Record removed.";
				}
				**/
		}

}
