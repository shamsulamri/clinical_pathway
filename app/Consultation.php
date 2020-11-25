<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Validator;
use Carbon\Carbon;
use App\DojoUtility;

class Consultation extends Model
{
	use SoftDeletes;
	protected $dates = ['deleted_at'];

	protected $table = 'consultations';
	protected $fillable = [
				'consultation_id',
				'consultation_note',
				'consultation_pathway'];
	
	protected $casts = [
            'consultation_pathway'=>'array'
    ];

	public function validate($input, $method) {
			$rules = [
				'consultation_id'=>'required',
			];
			
			$messages = [
				'required' => 'This field is required'
			];
			
			return validator::make($input, $rules ,$messages);
	}

	
}
