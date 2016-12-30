<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UsageLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
	 
	const CREATED_AT = 'usage_dttm';	 
	const UPDATED_AT = null;	 
    protected $table = 'dqs_usage_log';
	protected $primaryKey = 'usage_id';
	public $incrementing = true;
	//public $timestamps = false;
	protected $guarded = array();
	//protected $hidden = ['created_by', 'updated_by', 'created_dttm', 'updated_dttm'];
}