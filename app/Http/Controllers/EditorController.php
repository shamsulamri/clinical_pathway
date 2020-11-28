<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Log;
use App\CPHelper;
use App\Consultation;

class EditorController extends Controller
{
	public function generate(Request $request, $soap="subjective", $problem=null)
	{
			$soaps = ["subjective", "objective", "assessment_plan"];

			$helper = new CPHelper();
			$consultation_id = 99;

			$consultation = Consultation::where('consultation_id',$consultation_id)->first();
			$kvs = $consultation->consultation_pathway;

			$problems = [];
			foreach($soaps as $soap) {
					if (!empty($kvs[$soap])) {
							foreach($kvs[$soap] as $key=>$problem) {
								if (!in_array($helper->toId($key), $problems)) {
										Log::info($key);
										array_push($problems, $key);
								}
							}
					}
			}

			Log::info("--------------------------------------------");
			foreach($soaps as $soap) {
					Log::info($soap);
					Log::info("=========");
					foreach($problems as $problem) {
							$problem = str_replace("_", " ", $problem);
							Log::info($problem);
							Log::info(">>>>>>>>>>>>>>>>>>");
							$problem_list = $helper->getProblemList($soap, $problem);
							foreach($problem_list as $section) {
									$filename = $problem." - ".strtolower($section);
									if (!empty($section)) {
											$pathways = $helper->getPathways($soap, $filename);
											if ($pathways) {
													foreach ($pathways as $index=>$path) {
															if ($helper->stringStartsWith($path, "<group>")) {
																	$group = $helper->removeFromString("<group>", $path);
																	$text = $helper->compileText($consultation_id, $soap, $problem, $section, $group);
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

			Log::info($problems);

			return view('editor.editor', [
					'pathways'=>$pathways,	
					'helper'=>$helper,
					'problems'=>$problems,
					'soap'=>$soap,
					'consultation'=>$consultation,
					'soaps'=>$soaps,
					'kvs'=>$kvs,
					'consultation_id'=>$consultation_id,
			]);
	}
	
	public function add(Request $request)
	{
			$helper = new CPHelper();
			$id = $request->id;
			$value = $request->value;

			$consultation_id = 99;
			$consultation = Consultation::where('consultation_id', $consultation_id)->first();
			$kvs = $consultation->consultation_pathway;

			if (empty($consultation)) {
					$consultation = new Consultation();
					$consultation->consultation_id = $consultation_id;
			}

			if ($request->id != 'consultation_note') {
					$ids = count(explode("--", $request->id));

					if ($ids>2) {
							[$soap, $problem, $section, $group] = explode("--",$request->id);
							$kvs[$soap][$problem][$section][$group]['note']=$value;
					} else {
							[$soap, $problem] = explode("--",$request->id);
							$kvs[$soap][$problem]['note']=$value;
					}
			} else {
					$consultation->consultation_note = $value;
			}


			$consultation->consultation_pathway = $kvs;
			$consultation->save();

			$helper->pretty($kvs);

			return "Note added...";
	}

	public function add2(Request $request)
	{
			$id = $request->id;
			$value = $request->value;

			Log::info($request);
			return "X";
			$helper = new CPHelper();
			$soap = $request->soap;
			$problem = $request->problem;
			$group = $helper->toId($request->group);
			$value = $request->value;

			$consultation_id = 99;
			$consultation = Consultation::where('consultation_id', $consultation_id)->first();

			if (empty($consultation)) {
					$consultation = new Consultation();
					$consultation->consultation_id = $consultation_id;
			}

			if ($group=="consultation_note") {
					$consultation->consultation_note = $value;
					$consultation->save();
			} else {

					$kvs = $consultation->consultation_pathway;
					$kvs[$soap][$problem][$group]['note']=$value;

					$consultation->consultation_pathway = $kvs;
					$consultation->save();

					$helper->pretty($kvs);
			}
			
			return "Note added...";
	}
}
