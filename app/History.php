<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Validator;
use Carbon\Carbon;
use App\DojoUtility;

class History extends Model
{
	use SoftDeletes;
	protected $dates = ['deleted_at'];

	protected $table = 'histories';
	protected $fillable = [
				'patient_id',
				'history_note',
				'history_pathway'];
	
    protected $guarded = ['patient_id'];
    protected $primaryKey = 'patient_id';
    public $incrementing = true;
    
	protected $casts = [
            'history_pathway'=>'array'
    ];

	public function validate($input, $method) {
			$rules = [
				'history_pathway'=>'required',
			];

			
			
			$messages = [
				'required' => 'This field is required'
			];
			
			return validator::make($input, $rules ,$messages);
	}

	
}
