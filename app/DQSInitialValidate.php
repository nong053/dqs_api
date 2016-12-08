<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DQSInitialValidate extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
	 
    protected $table = 'dqs_initial_validate';
	protected $primaryKey = 'initial_validate_id';
	//public $incrementing = true;
	public $timestamps = false;
	protected $guarded = array();
	//protected $hidden = ['created_by', 'updated_by', 'created_dttm', 'updated_dttm'];
}