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

			$soap = $helper->toId($soap);
			$problem = $helper->toId($problem);

			return view('editor.editor', [
					'pathways'=>$pathways,	
					'helper'=>$helper,
					'problem'=>$problem,
					'soap'=>$soap,
					'consultation'=>$consultation,
			]);
	}

	public function add(Request $request)
	{
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
