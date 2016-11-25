<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
	const CREATED_AT = 'created_dttm';
	const UPDATED_AT = 'updated_dttm';	 	 
    protected $table = 'dqs_system_config';
	protected $primaryKey = 'config_id';
	//public $timestamps = false;
	protected $guarded = array();
}