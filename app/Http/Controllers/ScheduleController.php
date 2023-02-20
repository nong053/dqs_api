<?php

namespace App\Http\Controllers;



use Auth;
use DB;
use File;
use Validator;
use Excel;
use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ScheduleController extends Controller
{

	public function __construct()
	{

	   //$this->middleware('jwt.auth');
	}
	
	

	
	public function index(Request $request)
	{		
		$items = DB::select("
			SELECT id,unix_timestamp(start_date) as start_date,unix_timestamp(end_date) as end_date,
			planning,pl.profile_id,pl.active_flag,concat(pr.title,' ',pr.first_name,' ',pr.last_name) as full_name 
			FROM planning pl
			inner join profile pr on pl.profile_id=pr.profile_id
			where pl.active_flag=1
			order by end_date desc

		"
		//,array('%'.$request->profile_id.'%')
	);
		return response()->json($items);
	}
	
	
	
	
}
