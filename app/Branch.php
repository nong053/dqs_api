<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
	 
	const CREATED_AT = 'created_dttm';
	const UPDATED_AT = 'updated_dttm';	 
    protected $table = 'dqs_branch';
	protected $primaryKey = 'brcd';
	public $incrementing = false;
	//public $timestamps = false;
	protected $guarded = array();
}