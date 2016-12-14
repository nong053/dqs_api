<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExplainFile extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
	 
    protected $table = 'dqs_explain_file';
	protected $primaryKey = 'explain_file_id';
	public $incrementing = true;
	public $timestamps = false;
	protected $guarded = array();
	//protected $hidden = ['created_by', 'updated_by', 'created_dttm', 'updated_dttm'];
}