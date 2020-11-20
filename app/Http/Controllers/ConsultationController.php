<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Consultation;
use Log;
use DB;
use Session;


class ConsultationController extends Controller
{
	public $paginateValue=10;

	public function __construct()
	{
			//$this->middleware('auth');
	}

	public function index()
	{
			$consultations = Consultation::orderBy('consultation_id')
					->paginate($this->paginateValue);

			return view('consultations.index', [
					'consultations'=>$consultations
			]);
	}

	public function create()
	{
			$consultation = new Consultation();
			return view('consultations.consultation', [
					'consultation' => null,
				
					]);
	}

	public function store(Request $request) 
	{
			$consultation = new Consultation();
			$valid = $consultation->validate($request->all(), $request->_method);

			if ($valid->passes()) {
					$consultation = new Consultation($request->all());
					$consultation->id = $request->id;
					$consultation->save();
					Session::flash('message', 'Record successfully created.');
					return redirect('/consultation/search/'.$consultation->id);
			} else {
					return redirect('/consultations/create')
							->withErrors($valid)
							->withInput();
			}
	}

	public function edit($id) 
	{
			$consultation = Consultation::findOrFail($id);
			return view('consultations.consultation', [
					'consultation'=>$consultation,
				
					]);
	}

	public function update(Request $request, $id) 
	{
			$consultation = Consultation::findOrFail($id);
			$consultation->fill($request->input());


			$valid = $consultation->validate($request->all(), $request->_method);	

			if ($valid->passes()) {
					$consultation->save();
					Session::flash('message', 'Record successfully updated.');
					return redirect('/consultation/search/'.$id);
			} else {
					return view('consultations.edit', [
							'consultation'=>$consultation,
				
							])
							->withErrors($valid);			
			}
	}
	
	public function delete($id)
	{
		$consultation = Consultation::findOrFail($id);
		return view('consultations.destroy', [
			'consultation'=>$consultation
			]);

	}
	public function destroy($id)
	{	
			Consultation::find($id)->delete();
			Session::flash('message', 'Record deleted.');
			return redirect('/consultations');
	}
	
	public function search(Request $request)
	{
			$consultations = Consultation::where('consultation_id','like','%'.$request->search.'%')
					->orWhere('id', 'like','%'.$request->search.'%')
					->orderBy('consultation_id')
					->paginate($this->paginateValue);

			return view('consultations.index', [
					'consultations'=>$consultations,
					'search'=>$request->search
					]);
	}
}
