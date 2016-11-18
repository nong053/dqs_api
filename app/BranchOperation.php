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
    protected $table = 'dqs_branch_operation';
	protected $primaryKey = 'operation_id';
	public $incrementing = true;
	public $timestamps = false;
	protected $guarded = array();
}