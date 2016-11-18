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
    protected $table = 'dqs_file';
	protected $primaryKey = 'file_id';
	public $incrementing = false;
	public $timestamps = false;
	protected $guarded = array();
}