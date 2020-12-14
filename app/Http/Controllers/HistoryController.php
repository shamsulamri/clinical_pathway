<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\History;
use Log;
use DB;
use Session;


class HistoryController extends Controller
{
	public $paginateValue=10;

	public function __construct()
	{
			//$this->middleware('auth');
	}

	public function index()
	{
			$histories = History::orderBy('history_note')
					->paginate($this->paginateValue);

			return view('histories.index', [
					'histories'=>$histories
			]);
	}

	public function create()
	{
			$history = new History();
			return view('histories.history', [
					'history' => null,
				
					]);
	}

	public function store(Request $request) 
	{
			$history = new History();
			$valid = $history->validate($request->all(), $request->_method);

			if ($valid->passes()) {
					$history = new History($request->all());
					$history->patient_id = $request->patient_id;
					$history->save();
					Session::flash('message', 'Record successfully created.');
					return redirect('/history/search/'.$history->patient_id);
			} else {
					return redirect('/histories/create')
							->withErrors($valid)
							->withInput();
			}
	}

	public function edit($id) 
	{
			$history = History::findOrFail($id);
			return view('histories.history', [
					'history'=>$history,
				
					]);
	}

	public function update(Request $request, $id) 
	{
			$history = History::findOrFail($id);
			$history->fill($request->input());


			$valid = $history->validate($request->all(), $request->_method);	

			if ($valid->passes()) {
					$history->save();
					Session::flash('message', 'Record successfully updated.');
					return redirect('/history/search/'.$id);
			} else {
					return view('histories.edit', [
							'history'=>$history,
				
							])
							->withErrors($valid);			
			}
	}
	
	public function delete($id)
	{
		$history = History::findOrFail($id);
		return view('histories.destroy', [
			'history'=>$history
			]);

	}
	public function destroy($id)
	{	
			History::find($id)->delete();
			Session::flash('message', 'Record deleted.');
			return redirect('/histories');
	}
	
	public function search(Request $request)
	{
			$histories = History::where('history_note','like','%'.$request->search.'%')
					->orWhere('patient_id', 'like','%'.$request->search.'%')
					->orderBy('history_note')
					->paginate($this->paginateValue);

			return view('histories.index', [
					'histories'=>$histories,
					'search'=>$request->search
					]);
	}
}
