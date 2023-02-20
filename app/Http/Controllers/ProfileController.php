<?php

namespace App\Http\Controllers;

use App\Profile;

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

class ProfileController extends Controller
{

	public function __construct()
	{

	   $this->middleware('jwt.auth');
	}
	
	// PROFILE_ID	CARD_ID	PASSPORT_ID	TITLE	FIRST_NAME	LAST_NAME	GENDER	NATIONALITY	DATE_OF_BIRTH	RELIGION	ADDRESS	CREATED_DATE	CREATED_BY	UPDATED_DATE	UPDATED_BY	ACTIVE_FLAG
	public function index(Request $request)
	{		
		$items = DB::select("
			SELECT 
			PROFILE_ID,TITLE,EMAIL,FIRST_NAME,LAST_NAME,POSITION,GENDER,
			DATE_OF_BIRTH,ADDRESS,CREATED_DATE,CREATED_BY,	
			UPDATED_DATE,UPDATED_BY,ACTIVE_FLAG,role
			FROM profile
			/*where ACTIVE_FLAG='1'*/
			order by FIRST_NAME,LAST_NAME

		"
		//,array('%'.$request->profile_id.'%')
	);
		return response()->json($items);
	}
	
	public function military_rank(Request $request)
	{		
		$items = DB::select("
			SELECT 
			*
			FROM military_rank
			/*where ACTIVE_FLAG='1'*/
			order by id

		"
		//,array('%'.$request->profile_id.'%')
	);
		return response()->json($items);
	}

	
	public function store(Request $request)
	{
	//'org_code' => 'required|max:15|unique:org',
		$validator = Validator::make($request->all(), [
			'EMAIL' => 'required|unique:profile',
			'PASSWORD' => 'required',
			'FIRST_NAME' => 'required',
			'LAST_NAME' => 'required',
			'POSITION' => 'required',
			'ACTIVE_FLAG' => 'required|integer',
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 400, 'data' => $validator->errors()]);
		} else {
			$item = new Profile;
			$item->fill($request->all());
			// $item->created_by = Auth::id();
			// $item->updated_by = Auth::id();
			 $item->PASSWORD=bcrypt($request->PASSWORD);
			 $item->save();
		}
	
		return response()->json(['status' => 200, 'data' => $item]);	
	}
	
	public function show(Request $request,$id)
	{
	
		
		try {

			$item = Profile::findOrFail($id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Profile not found.']);
		}
		return response()->json($item);
		
	}
	
	public function update(Request $request, $id)
	{
		
		try {
			$item = Profile::findOrFail($id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Profile not found.']);
		}
		//'org_code' => 'required|max:15|unique:org,org_name,' . $org_id . ',org_id',
		$validator = Validator::make($request->all(), [
			'email' => 'required|unique:profile,' . $id . ',profile_id',
			'password' => 'required',
			'FIRST_NAME' => 'required',
			'LAST_NAME' => 'required',
			'POSITION' => 'required',
			'ACTIVE_FLAG' => 'required',
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 400, 'data' => $validator->errors()]);
		} else {
			$item->fill($request->all());
			//$item->updated_by = Auth::id();
			
			$item->password=bcrypt($request->password);
			
			
			$item->save();
			return response()->json(['status' => 200, 'data' => $item]);
		}
	
		
				
	}
	public function updateNew(Request $request, $id)
	{
		
		try {
			$item = Profile::findOrFail($id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Profile not found.']);
		}
		
		$validator = Validator::make($request->all(), [
			'email' => 'required',
			//'password' => 'required',
			'FIRST_NAME' => 'required',
			'LAST_NAME' => 'required',
			'POSITION' => 'required',
			'ACTIVE_FLAG' => 'required|integer',
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 400, 'data' => $validator->errors()]);
		} else {
			//$item->fill($request->all());
			if(!empty($request->password)){
				$item->password=bcrypt($request->password);
			}
			$item->email=$request->email;
			$item->TITLE=$request->TITLE;
			$item->GENDER=$request->GENDER;
			$item->DATE_OF_BIRTH=$request->DATE_OF_BIRTH;
			$item->FIRST_NAME=$request->FIRST_NAME;
			$item->LAST_NAME=$request->LAST_NAME;
			$item->POSITION=$request->POSITION;
			$item->ADDRESS=$request->ADDRESS;
			$item->ACTIVE_FLAG=$request->ACTIVE_FLAG;
			$item->role=$request->role;


			
			$item->save();
			return response()->json(['status' => 200, 'data' => $item]);
		}
	
		
				
	}
	
	public function destroy($id)
	{
		try {
			$item = Profile::findOrFail($id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Profile not found.']);
		}	

		try {
			$item->delete();
			return response()->json(['status' => 200]);
		} catch (Exception $e) {
			if ($e->errorInfo[1] == 1451) {
				return response()->json(['status' => 400, 'data' => 'Cannot delete because this Position is in use.']);
			} else {
				return response()->json($e->errorInfo);
			}
		}
		
		
		
	}	
}
