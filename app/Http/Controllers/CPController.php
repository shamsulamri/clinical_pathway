<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Log;
use App\CPHelper;
use App\Consultation;

class CPController extends Controller
{
		public function generate(Request $request, $soap="subjective", $problem=null)
		{
				$helper = new CPHelper();
				$consultation_id = 99;
				if (!$problem) {
					if ($soap=="subjective") {
						$problem = "sore throat - history of present illness";
					}
					if ($soap=="objective") {
						$problem = "objective - physical examination";
					}
				}

				$file = Storage::get('clinical_pathways/'.$soap.'/'.$problem);

				if (empty($file)) {
					return "File not found";
				}


				$pathways = explode("\n", $file);

				$consultation = Consultation::where('consultation_id', $consultation_id)->first();
				$kvs = $consultation->consultation_pathway??null;

				Log::info(json_encode($kvs, JSON_PRETTY_PRINT));

				//$helper->compileText($soap, "sore throat - history of present illness", "location_of_soreness");

				//$obj = $helper->pathwayGroup("subjective", $problem, "duration_of_symptom");
				//Log::info($obj);

				return view('pathways', [
					'pathways'=>$pathways,	
					'helper'=>$helper,
					'problem'=>$problem,
					'problem_id'=>$helper->toId($problem),
					'soap'=>$soap,
					'parent'=>$request->parent??null,
					'kvs'=>$kvs??[],

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
				$parent = [];
				$soap = $request->soap;

				$consultation = Consultation::where('consultation_id', $consultation_id)->first();
				if ($consultation) {
						$kvs = $consultation->consultation_pathway;
				} else {
						$consultation = new Consultation();
						$consultation->consultation_id = 99;
				}

				$tree = explode("---",$request->key);
				foreach($tree as $index=>$p) {
						$parent[$index] = $helper->getPGD($p);
				}

				$parents = count($parent);
				$pgd = $parent[$parents-1];

				unset($parent[$parents-1]);

				$obj = $helper->pathwayGroup($soap, $pgd['0'], $pgd['1']);
				//Log::info($obj);

				if ($obj['group_style']==2) {
						$kvs[$soap][$pgd[0]][$pgd[1]] = null;
				}

				$detail['value'] = $value;
				$detail_text = $obj['details'][$pgd[2]]['detail_text'];
				$detail['text'] = str_replace("<insert_text>", $value, $detail_text);
				$detail['index'] = $obj['details'][$pgd[2]]['detail_index'];

				if (empty($detail['text'])) $detail['text'] = strtolower($obj['details'][$pgd[2]]['detail']);

				Log::info("Count --> ".count($parent));
				switch (count($parent)) {
						case 0:
								$kvs[$soap][$pgd[0]][$pgd[1]]['group_text'] = $obj['group_text'];
								$kvs[$soap][$pgd[0]][$pgd[1]][$pgd[2]] = $detail;
								$kvs[$soap][$pgd[0]][$pgd[1]][$pgd[2]]['child'] = null;
								break;
						case 1:
								$root_pgd = $parent[0];

								$child = $kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]]['child'];
								$child[$pgd[0]][$pgd[1]]['group_text'] = $obj['group_text'];
								$child[$pgd[0]][$pgd[1]][$pgd[2]] = $detail;
								$child[$pgd[0]][$pgd[1]][$pgd[2]]['child'] = null;

								$kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]]['child'] = $child;

								break;
						case 2:
								$root_pgd = $parent[0];
								$child1_pgd = $parent[1];

								$child = $kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]]['child']; 
								$child2 = $child[$child1_pgd[0]][$child1_pgd[1]][$child1_pgd[2]]['child'];
								$child2[$pgd[0]][$pgd[1]]['group_text'] = $obj['group_text'];
								$child2[$pgd[0]][$pgd[1]][$pgd[2]] = $detail;
								$child2[$pgd[0]][$pgd[1]][$pgd[2]]['child'] = null;

								$kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]]['child'][$child1_pgd[0]][$child1_pgd[1]][$child1_pgd[2]]['child'] = $child2;
								break;
						case 3:
								$root_pgd = $parent[0];
								$child1_pgd = $parent[1];
								$child2_pgd = $parent[2];

								$child = $kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]]['child']; 
								$child2 = $child[$child1_pgd[0]][$child1_pgd[1]][$child1_pgd[2]]['child'];
								$child3 = $child2[$child2_pgd[0]][$child2_pgd[1]][$child2_pgd[2]]['child'];

								$child3[$pgd[0]][$pgd[1]]['group_text'] = $obj['group_text'];
								$child3[$pgd[0]][$pgd[1]][$pgd[2]] = $detail;
								$child3[$pgd[0]][$pgd[1]][$pgd[2]]['child'] = null;

								$kvs	[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]]['child']
										[$child1_pgd[0]][$child1_pgd[1]][$child1_pgd[2]]['child']
										[$child2_pgd[0]][$child2_pgd[1]][$child2_pgd[2]]['child'] = $child3;
								break;
				}

				$consultation->consultation_pathway = $kvs;
				$consultation->save();
				Log::info(json_encode($kvs, JSON_PRETTY_PRINT));
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
				$branches = explode("---", $request->ids);
				$consultation_id = 99;
				$helper = new CPHelper();

				$consultation = Consultation::where('consultation_id', $consultation_id)->first();
				$kvs = $consultation->consultation_pathway;

				if ($consultation) {
						$node = $consultation->consultation_pathway;

						foreach($branches as $index=>$branch){
							$pgd = $helper->getPGD($branch);

							if (!empty($node[$pgd[0]][$pgd[1]][$pgd[2]]['child'])) {
									$node = $node[$pgd[0]][$pgd[1]][$pgd[2]]['child'];
							} else {
									break;
							}	
						}
				}

				switch ($index) {
					case 1:
							$root_pgd = $helper->getPGD($branches[0]);
							$child1_pgd = $helper->getPGD($branches[1]);
							unset($kvs[$soap][$root_pgd[0]][$root_pgd[1]][$root_pgd[2]]['child'][$child1_pgd[0]][$child1_pgd[1]][$child1_pgd[2]]);
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

		}

		public function remove_root(Request $request) 
		{
				$soap = $request->soap;
				$consultation_id = 99;
				$helper = new CPHelper();

				$consultation = Consultation::where('consultation_id', $consultation_id)->first();
				if ($consultation) {
						$ids = explode(";",$request->ids);
						$kvs = $consultation->consultation_pathway;

						Log::info(count($ids));
						foreach($ids as $id) {
								Log::info($id);
								$pgd = $helper->getPGD($id);
								if (!empty($kvs[$soap][$pgd[0]][$pgd[1]])) {
										unset($kvs[$soap][$pgd[0]][$pgd[1]][$pgd[2]]);
										if (count($kvs[$soap][$pgd[0]][$pgd[1]])==1) {
												unset($kvs[$soap][$pgd[0]][$pgd[1]]);
										}
								}
								$consultation->consultation_pathway = $kvs;
								$consultation->save();
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
