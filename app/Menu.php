<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
	
	const CREATED_AT = 'created_dttm';
	const UPDATED_AT = 'updated_dttm';
    protected $table = 'dqs_menu';
	protected $primaryKey = 'menu_id';
	public $incrementing = true;
	//public $timestamps = false;
	protected $guarded = array();
}