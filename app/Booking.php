<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
	 
	const CREATED_AT = 'created_date';
	const UPDATED_AT = 'updated_date';	 
    protected $table = 'booking_vehicle';
	protected $primaryKey = 'booking_id';
	public $incrementing = true;
	//public $timestamps = false;
	protected $guarded = array('booking_date');
	protected $hidden = ['created_dttm', 'updated_dttm'];
}

	//id 	start_date 	end_date 	planning 	profile_id 	active_flag 	created_dttm 	updated_dttm