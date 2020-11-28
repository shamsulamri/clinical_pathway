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

				$consultation = Consultation::where('consultation_id', $consultation_id)->first();
				$kvs = $consultation->consultation_pathway??null;

				//Log::info(json_encode($kvs[$soap][$helper->toId($problem)][$helper->toId($section)]??null, JSON_PRETTY_PRINT));
				Log::info(json_encode($kvs, JSON_PRETTY_PRINT));

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
				$soap = $request->soap;

				$consultation = Consultation::where('consultation_id', $consultation_id)->first();
				if ($consultation) {
						$kvs = $consultation->consultation_pathway;
				} else {
						$consultation = new Consultation();
						$consultation->consultation_id = 99;
				}

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
				$obj = $helper->pathwayGroup($soap, $problem, $section, $group, $request->filename);

				Log::info($obj);

				$node['value'] = $value;
				$detail_text = $obj['details'][$detail]['detail_text'];
				$node['text'] = str_replace("<insert_text>", $value, $detail_text);
				$node['index'] = $obj['details'][$detail]['detail_index'];

				if (empty($node['text'])) $node['text'] = strtolower($obj['details'][$detail]['detail']);
				
				switch (count($keys)) {
						case 0:
								if ($obj['group_style']==2) {
										unset($kvs[$soap][$problem][$pgd[1]][$pgd[2]]);
								}
								$kvs[$soap][$problem][$pgd[1]][$pgd[2]]['group_text'] = $obj['group_text'];
								$kvs[$soap][$problem][$pgd[1]][$pgd[2]][$detail] = $node;
								$kvs[$soap][$problem][$pgd[1]][$pgd[2]][$detail]['child'] = null;
								break;
						case 1:
								Log::info("-->-->--");
								$root_pgd = $helper->getPSGD($keys[0]);
								Log::info($root_pgd);

								$child = $kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child'];
								Log::info($child);
								Log::info($pgd);

								if ($obj['group_style']==2) {
										$child = null;
								}
								$child[$pgd[2]]['group_text'] = $obj['group_text'];
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
								$child2[$pgd[2]]['group_text'] = $obj['group_text'];
								$child2[$pgd[2]][$pgd[3]] = $node;
								$child2[$pgd[2]][$pgd[3]]['child'] = null;


								$kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]][$root_pgd[3]]['child']
										[$child1_pgd[2]][$child1_pgd[3]]['child'] = $child2;
								break;
						case 3:
								$root_pgd = $helper->getPSGD($keys[0]);
								$child1_pgd = $helper->getPSGD($keys[1]);
								$child2_pgd = $helper->getPSGD($keys[2]);

								$child = $kvs[$soap][$root_pgd[0]][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]]['child']; 
								$child2 = $child[$child1_pgd[0]][$child1_pgd[1]][$child1_pgd[2]]['child'];
								$child3 = $child2[$child2_pgd[0]][$child2_pgd[1]][$child2_pgd[2]]['child'];

								if ($obj['group_style']==2) {
										$child3= null;
								}

								$child3[$pgd[0]][$pgd[1]]['group_text'] = $obj['group_text'];
								$child3[$pgd[0]][$pgd[1]][$pgd[2]] = $node;
								$child3[$pgd[0]][$pgd[1]][$pgd[2]]['child'] = null;

								$kvs	[$soap][$root_pgd[0]][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]]['child']
										[$child1_pgd[0]][$child1_pgd[1]][$child1_pgd[2]]['child']
										[$child2_pgd[0]][$child2_pgd[1]][$child2_pgd[2]]['child'] = $child3;
								break;
				}

				$consultation->consultation_pathway = $kvs;
				$consultation->save();
				Log::info(json_encode($kvs[$soap][$problem]??null, JSON_PRETTY_PRINT));
				return "Record saved.";
		}
	
		public function remove(Request $request)
		{
				$branches = explode("---", $request->ids);
				if (count($branches)==1) {
						$this->remove_root($request);
				} else {
						$this->remove_branch($request);
				}

		}

		public function remove_branch($request) 
		{
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
							Log::info("--------------------");
							Log::info($root_pgd);
							Log::info($child1_pgd[2]);
							Log::info($child1_pgd[3]);
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
				//$consultation->save();

				Log::info(json_encode($kvs, JSON_PRETTY_PRINT));

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
								Log::info(">> ".$id);
								Log::info($soap);
								Log::info([$problem, $section, $group, $detail]);
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
