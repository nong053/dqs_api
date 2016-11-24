<?php

namespace App\Http\Controllers;

use App\SystemConfig;

use DB;
use Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class SystemConfigController extends Controller
{

	public function __construct()
	{
	   $this->middleware('jwt.auth');
	}
   
    public function index()
    {
		try {
			$item = SystemConfig::firstOrFail();
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'System Configuration not found in DB.']);
		}		
		return response()->json($item);
    }
	
	public function kpi_date(Request $request)
	{
 		try {
			$item = SystemConfig::firstOrFail();
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'System Configuration not found in DB.']);
		}	
		
        $validator = Validator::make($request->all(), [
            'default_kpi_date' => 'required|integer|min:1|max:28',
			'kpi_date_m1' => 'required|integer|min:1|max:28',
			'kpi_date_m2' => 'required|integer|min:1|max:28',
			'kpi_date_m3' => 'required|integer|min:1|max:28',
			'kpi_date_m4' => 'required|integer|min:1|max:28',
			'kpi_date_m5' => 'required|integer|min:1|max:28',
			'kpi_date_m6' => 'required|integer|min:1|max:28',
			'kpi_date_m7' => 'required|integer|min:1|max:28',
			'kpi_date_m8' => 'required|integer|min:1|max:28',
			'kpi_date_m9' => 'required|integer|min:1|max:28',
			'kpi_date_m10' => 'required|integer|min:1|max:28',
			'kpi_date_m11' => 'required|integer|min:1|max:28',
			'kpi_date_m12' => 'required|integer|min:1|max:28'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'data' => $validator->errors()]);
        } else {
			$item->fill($request->all());
			$item->save();
		}		
		
		return response()->json(['status' => 200, 'data' => $item]);
	}
	
	public function export_file(Request $request)
	{
  		try {
			$item = SystemConfig::firstOrFail();
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'System Configuration not found in DB.']);
		}	
		
        $validator = Validator::make($request->all(), [
            'export_file_path' => 'required|max:500',
			'export_citizen_max_record' => 'required|integer',
			'export_mobile_max_record' => 'required|integer',
			'export_include_date_flag' => 'required|boolean',
			'export_nof_date_delete' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'data' => $validator->errors()]);
        } else {
			$item->fill($request->all());
			$item->save();
		}		
 
		return response()->json(['status' => 200, 'data' => $item]);
	}	
	
	public function import_file(Request $request)
	{
  		try {
			$item = SystemConfig::firstOrFail();
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'System Configuration not found in DB.']);
		}	
		
        $validator = Validator::make($request->all(), [
            'import_file_path' => 'required|max:500',
			'import_max_file_size' => 'required|integer',
			'import_include_date_flag' => 'required|boolean',
			'import_nof_date_delete' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'data' => $validator->errors()]);
        } else {
			$item->fill($request->all());
			$item->save();
		}		
 
		return response()->json(['status' => 200, 'data' => $item]);
	}	
	
	public function warning_branch(Request $request)
	{
  		try {
			$item = SystemConfig::firstOrFail();
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'System Configuration not found in DB.']);
		}	
		
        $validator = Validator::make($request->all(), [
			'nof_contact_date' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'data' => $validator->errors()]);
        } else {
			$item->fill($request->all());
			$item->save();
		}		
 
		return response()->json(['status' => 200, 'data' => $item]);
	}	
	
	public function grade_date(Request $request)
	{
  		try {
			$item = SystemConfig::firstOrFail();
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'System Configuration not found in DB.']);
		}	
		
        $validator = Validator::make($request->all(), [
			'all_cust_grade_calculate_date' => 'required|date|date_format:Y-m-d',
			'grade_data_source' => 'required|max:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'data' => $validator->errors()]);
        } else {
			$item->fill($request->all());
			$item->save();
		}		
 
		return response()->json(['status' => 200, 'data' => $item]);
	}	
}