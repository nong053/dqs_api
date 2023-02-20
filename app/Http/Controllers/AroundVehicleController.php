<?php

namespace App\Http\Controllers;

use App\Org;
use App\AroundVehicle;

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

class AroundVehicleController extends Controller
{

	public function __construct()
	{

	   $this->middleware('jwt.auth');
	}
	
	public function index(Request $request)
	{		
		
		$items = DB::select("
			select * from around_vehicle
		");
		return response()->json($items);
	}
	
	public function store(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'around_name' => 'required|max:255|unique:around_vehicle'
			
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 400, 'data' => $validator->errors()]);
		} else {
			$item = new AroundVehicle;
			$item->fill($request->all());
			$item->save();
		}
	
		return response()->json(['status' => 200, 'data' => $item]);	
	}
	
	public function show($id)
	{
		try {
			$item = AroundVehicle::findOrFail($id);

			
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Vehicle Type not found.']);
		}
		return response()->json($item);
	}
	
	public function update(Request $request, $id)
	{
		try {
			$item = AroundVehicle::findOrFail($id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Around Vehicle not found.']);
		}
		
		$validator = Validator::make($request->all(), [

			'around_name' => 'required|max:255|unique:around_vehicle,around_name,' . $id . ',id'
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 400, 'data' => $validator->errors()]);
		} else {
			$item->fill($request->all());
			$item->save();
		}
	
		return response()->json(['status' => 200, 'data' => $item]);
				
	}
	
	public function destroy($id)
	{
		try {
			$item = AroundVehicle::findOrFail($id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Around Vehicle not found.']);
		}	

		try {
			$item->delete();
		} catch (Exception $e) {
			//print_r($e);
			if ($e->errorInfo[1] == 1451) {
				return response()->json(['status' => 400, 'data' => 'Cannot delete because this Around Vehicle  is in use.']);
			} else {
				return response()->json($e->errorInfo);
			}
		}
		
		return response()->json(['status' => 200]);
		
	}	
}
