<?php

namespace App\Http\Controllers;

use App\Org;
use App\VehicleType;

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

class VehicleTypeController extends Controller
{

	public function __construct()
	{

	   $this->middleware('jwt.auth');
	}
	
	public function index(Request $request)
	{		
		
		$items = DB::select("
			select * from vehicle_type
		");
		return response()->json($items);
	}
	
	

	
	
	
	
	
	public function store(Request $request)
	{
	
		$validator = Validator::make($request->all(), [
			'vehicle_type' => 'required|max:255|unique:vehicle_type'
			
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 400, 'data' => $validator->errors()]);
		} else {
			$item = new VehicleType;
			$item->fill($request->all());
			$item->save();
		}
	
		return response()->json(['status' => 200, 'data' => $item]);	
	}
	
	public function show($vehicle_type_id)
	{
		try {
			$item = VehicleType::findOrFail($vehicle_type_id);

			
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Vehicle Type not found.']);
		}
		return response()->json($item);
	}
	
	public function update(Request $request, $vehicle_type_id)
	{
		try {
			$item = VehicleType::findOrFail($vehicle_type_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Vehicle Type not found.']);
		}
		
		$validator = Validator::make($request->all(), [

			'vehicle_type' => 'required|max:255|unique:vehicle_type,vehicle_type,' . $vehicle_type_id . ',vehicle_type_id'
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 400, 'data' => $validator->errors()]);
		} else {
			$item->fill($request->all());
			$item->save();
		}
	
		return response()->json(['status' => 200, 'data' => $item]);
				
	}
	
	public function destroy($vehicle_type_id)
	{
		try {
			$item = VehicleType::findOrFail($vehicle_type_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Vehicle type not found.']);
		}	

		try {
			$item->delete();
		} catch (Exception $e) {
			//print_r($e);
			if ($e->errorInfo[1] == 1451) {
				return response()->json(['status' => 400, 'data' => 'Cannot delete because this Vehicle type is in use.']);
			} else {
				return response()->json($e->errorInfo);
			}
		}
		
		return response()->json(['status' => 200]);
		
	}	
}
