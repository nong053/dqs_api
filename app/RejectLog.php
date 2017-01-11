<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RejectLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
	// const CREATED_AT = 'created_dttm';
	// const UPDATED_AT = 'updated_dttm';	 	 
	const CREATED_AT = 'etl_dttm';	 
	const UPDATED_AT = null;	 
    protected $table = 'dqs_reject_log';
	protected $primaryKey = 'reject_id';
	public $incrementing = true;
	//public $timestamps = true;
	protected $guarded = array();
	//protected $hidden = ['created_by', 'updated_by', 'created_dttm', 'updated_dttm'];
}