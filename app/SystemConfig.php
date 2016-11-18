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
    protected $table = 'dqs_system_config';
	protected $primaryKey = 'config_id';
	public $timestamps = false;
	protected $guarded = array();
}