<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Planning extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
	 
	const CREATED_AT = 'created_dttm';
	const UPDATED_AT = 'updated_dttm';	 
    protected $table = 'planning';
	protected $primaryKey = 'id';
	public $incrementing = true;
	//public $timestamps = false;
	protected $guarded = array();
	//protected $hidden = ['created_by', 'updated_by', 'created_dttm', 'updated_dttm'];
}

	//id 	start_date 	end_date 	planning 	profile_id 	active_flag 	created_dttm 	updated_dttm