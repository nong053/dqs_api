<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Rule extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
	 
	const CREATED_AT = 'created_dttm';
	const UPDATED_AT = 'updated_dttm';	 
    protected $table = 'dqs_rule';
	protected $primaryKey = 'rule_id';
	public $incrementing = true;
	//public $timestamps = false;
	protected $guarded = array();
}