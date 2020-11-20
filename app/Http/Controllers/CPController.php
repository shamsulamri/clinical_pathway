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

				$pathways = explode("\n", $file);

				$consultation = Consultation::where('consultation_id', $consultation_id)->first();
				$kvs = $consultation->consultation_pathway??null;

				Log::info($kvs);
				return view('pathways', [
					'pathways'=>$pathways,	
					'helper'=>new CPHelper,
					'problem'=>$problem,
					'soap'=>$soap,
					'parent'=>$request->parent??null,
					'kvs'=>$kvs??[],

				]);
		}

		public function post(Request $request) 
		{
				Log::info($request);
				$consultation_id = 99;
				$consultation = new Consultation();
				$kvs = [];
				$key = $request->key;
				$value = $request->value;

				$consultation = Consultation::where('consultation_id', $consultation_id)->first();
				if ($consultation) {
						$kvs = $consultation->consultation_pathway;
				} else {
					$consultation = new Consultation();
					$consultation->consultation_id = 99;
				}
				$kvs[$key] = $value;
				Log::info($kvs);
				$consultation->consultation_pathway = $kvs;
				$consultation->save();
				return "World";
		}
	
}
