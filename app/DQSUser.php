<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DQSUser extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
	 
	const CREATED_AT = 'created_dttm';
	const UPDATED_AT = 'updated_dttm';	 
    protected $table = 'dqs_user';
	protected $primaryKey = 'personnel_id';
	//public $incrementing = true;
	//public $timestamps = false;
	protected $fillable = ['role_id','revised_cost_center'];
}