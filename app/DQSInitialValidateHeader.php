<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DQSInitialValidateHeader extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
	 
    protected $table = 'dqs_initial_validate_header';
	protected $primaryKey = 'validate_initial_header_id';
	//public $incrementing = true;
	public $timestamps = false;
	protected $guarded = array();
	//protected $hidden = ['created_by', 'updated_by', 'created_dttm', 'updated_dttm'];
}