<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
	const CREATED_AT = 'created_dttm';
	const UPDATED_AT = 'updated_dttm';	 	 
    protected $table = 'dqs_file';
	protected $primaryKey = 'file_id';
	public $incrementing = false;
	//public $timestamps = false;
	protected $guarded = ['file_name', 'source_file_path', 'target_file_path'];
	protected $hidden = ['created_by', 'updated_by', 'created_dttm', 'updated_dttm'];	
}