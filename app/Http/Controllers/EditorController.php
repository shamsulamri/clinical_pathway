<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Log;
use App\CPHelper;
use App\Consultation;
use App\History;

class EditorController extends Controller
{
	public $patient_id = 1;

	public function generate(Request $request, $consultation_id, $soap="subjective", $problem=null)
	{
			$helper = new CPHelper();

			$soap = $request->soap;
			//$soap = "pmh";
			$soaps = null;
			$kvs = [];
			$editor_note = null;

			if ($soap=='pmh') {
					$soaps = array("pmh"=>"pmh");
					$history = History::find(1);
					if (empty($history)) {
							return "No other history";
					}
					$kvs = $history->history_pathway;
					$editor_note = $history->history_note;
			} else {
					$soaps = array("subjective"=>"Subjective", "objective"=>"Objective", "assessment_plan"=>"Assessment and Plan");
					$consultation = Consultation::where('consultation_id',$consultation_id)->first();
					if (empty($consultation)) {
							return "No consultation";
					}
					$kvs = $consultation->consultation_pathway;
					$editor_note = $consultation->consultation_note;
			}

			$problems = [];
			foreach($soaps as $soap_key=>$soap) {
					if (!empty($kvs[$soap_key])) {
							foreach($kvs[$soap_key] as $key=>$problem) {
								if (!in_array($helper->toId($key), $problems)) {
										array_push($problems, $key);
								}
							}
					}
			}

			/*
			foreach($soaps as $soap_key=>$soap) {
					Log::info($soap);
					Log::info("=========");
					foreach($problems as $problem) {
							$problem = str_replace("_", " ", $problem);
							Log::info($problem);
							Log::info(">>>>>>>>>>>>>>>>>>");
							$problem_list = $helper->getProblemList($soap_key, $problem);
							foreach($problem_list as $section) {
									$filename = $problem." - ".strtolower($section);
									if (!empty($section)) {
											$pathways = $helper->getPathways($soap_key, $filename);
											if ($pathways) {
													foreach ($pathways as $index=>$path) {
															if ($helper->stringStartsWith($path, "<group>")) {
																	$group = $helper->removeFromString("<group>", $path);
																	$text = $helper->compileText($consultation_id, $soap_key, $problem, $section, $group);
																	if ($text) {
																			Log::info($text);
																	}
															}
													}
											}
									}
							}
					}
			}

			*/

			Log::info($problems);

			return view('editor.editor', [
					'helper'=>$helper,
					'problems'=>$problems,
					'soap'=>$soap,
					'editor_note'=>$editor_note,
					'soaps'=>$soaps,
					'kvs'=>$kvs,
					'consultation_id'=>$consultation_id,
					'isEdit'=>$request->edit??false,
					'problem'=>$request->problem,
					'consultation_id'=>$consultation_id,
			]);
	}
	
	public function add(Request $request)
	{
			Log::info($request);
			$soap = $request->soap;
			$helper = new CPHelper();
			$id = $request->id;
			$value = $request->value;

			$consultation = null;
			if ($soap=='pmh') {
					$history = History::where('patient_id', $this->patient_id)->first();
					$kvs = $history->history_pathway??null;
					if (empty($history)) {
							$history = new History();
							$history->patient_id = $this->patient_id;
					}
			} else {
					$consultation_id = $request->consultation_id;
					$consultation = Consultation::where('consultation_id', $consultation_id)->first();
					$kvs = $consultation->consultation_pathway;
					if (empty($consultation)) {
							$consultation = new Consultation();
							$consultation->consultation_id = $consultation_id;
					}
			}


			if ($request->id != 'consultation_note') {
					$ids = count(explode("--", $request->id));

					if ($ids==4) {
							[$soap, $problem, $section, $group] = explode("--",$request->id);
							$kvs[$soap][$problem][$section][$group]['note']=$value;
					} elseif ($ids==3) {
							[$soap, $problem, $section] = explode("--",$request->id);
							$kvs[$soap][$problem][$section]['note']=$value;
					} else {
							[$soap, $problem] = explode("--",$request->id);
							$kvs[$soap][$problem]['note']=$value;
					}
			} else {
					if ($soap=='pmh') {
							$history->history_note = $value;
					} else {
							$consultation->consultation_note = $value;
					}
			}


			if ($soap=='pmh') {
					$history->history_pathway = $kvs;
					$history->save();
					Log::info("++++++++++++");
					Log::info($history);
			} else {
					$consultation->consultation_pathway = $kvs;
					$consultation->save();
			}

			$helper->pretty($kvs);

			return "Note added...";
	}

	public function problem($consultation_id)
	{
			$problems = ['nursing care', 
					'home care',
					'sore throat', 
					'abdominal pain',
					'anxiety',
					'asthma',
					'red eye',
					'back pain',
					'chest pain',
					'dermatology',
					'diabetes mellitus',
					'dysuria',
					'fever',
					'ear pain',
					'hematemesis',
					'hyperlipidaemia',
					'hypertension',
					'incised wound',
					'pregnancy',
					'vaginal discharge',
					'joint pain',
					'motor vehicle accident',
					'occupational therapy',
					'palliative care',
					'cardiovascular system',
					'endocrine system',
					'gastrointestinal system',
					'haematological system',
					'respiratory system',
					'orthopaedics',
			];
			return view('editor.problem', [
				'problems'=>$problems,
				'consultation_id'=>$consultation_id,
			]);
	}
}
