<?php

namespace App\Http\Controllers;

use App\Planning;

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

class PlanningController extends Controller
{

	public function __construct()
	{

	   $this->middleware('jwt.auth');
	}
	
	public function auto(Request $request)
	{
		$items = DB::select("
			SELECT * FROM planning
		");
		return response()->json($items);	
	}
	public function list_name(Request $request)
	{
		$items = DB::select("
			select profile_id,concat(title,' ',first_name,' ',last_name) as full_name
			from profile
			where active_flag=1 order by profile_id
		");
		return response()->json($items);	
	}
	
	public function index(Request $request)
	{		
		$items = DB::select("
			SELECT id,pr.title,start_date,end_date,planning, concat(pr.first_name,' ',pr.last_name) as full_name 
			FROM planning pl
			inner join profile pr on pl.profile_id=pr.profile_id 
			-- where pl.active_flag=1
			order by id asc 

		"
		//,array('%'.$request->profile_id.'%')
	);
		return response()->json($items);
	}
	
	
	public function store(Request $request)
	{
	
		$validator = Validator::make($request->all(), [
			'profile_id' => 'required',
			'start_date' => 'required',
			'end_date' => 'required',
			'planning' => 'required',
			'active_flag' => 'required|integer',
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 400, 'data' => $validator->errors()]);
		} else {
			$item = new Planning;
			$item->fill($request->all());
			// $item->created_by = Auth::id();
			// $item->updated_by = Auth::id();
			 $item->save();
		}
	
		return response()->json(['status' => 200, 'data' => $item]);	
	}
	
	public function show(Request $request,$id)
	{
	
		
		try {

			$item = Planning::findOrFail($id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Planning not found.']);
		}
		return response()->json($item);
		
	}
	
	public function update(Request $request, $id)
	{
		try {
			$item = Planning::findOrFail($id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Planning not found.']);
		}
		
		$validator = Validator::make($request->all(), [
			'start_date' => 'required',
			'end_date' => 'required',
			'planning' => 'required',
			'active_flag' => 'required|integer',
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 400, 'data' => $validator->errors()]);
		} else {
			$item->fill($request->all());
			//$item->updated_by = Auth::id();
			$item->save();
		}
	
		return response()->json(['status' => 200, 'data' => $item]);
				
	}
	
	public function destroy($id)
	{
		try {
			$item = Planning::findOrFail($id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Planning not found.']);
		}	

		try {
			$item->delete();
		} catch (Exception $e) {
			if ($e->errorInfo[1] == 1451) {
				return response()->json(['status' => 400, 'data' => 'Cannot delete because this Position is in use.']);
			} else {
				return response()->json($e->errorInfo);
			}
		}
		
		return response()->json(['status' => 200]);
		
	}	
}
