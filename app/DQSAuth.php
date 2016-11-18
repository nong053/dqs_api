<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DQSAuth extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
	 
	const CREATED_AT = 'created_dttm';
	const UPDATED_AT = null;
    protected $table = 'dqs_authorization';
	protected $primaryKey = null;
	public $incrementing = false;
	public $timestamps = true;
	protected $guarded = array();
}