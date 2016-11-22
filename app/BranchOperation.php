<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BranchOperation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
	const CREATED_AT = 'created_dttm';
	const UPDATED_AT = 'updated_dttm';	 
	
    protected $table = 'dqs_branch_operation';
	protected $primaryKey = 'operation_id';
	public $incrementing = true;
//	public $timestamps = false;
	protected $guarded = array();
	protected $hidden = ['created_by', 'updated_by', 'created_dttm', 'updated_dttm'];
}