<?php

namespace App\Http\Controllers;


use App\SystemConfig;
use DB;
use Validator;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use download;

class ImportExportController extends Controller
{

	// public function __construct()
	// {

	   // $this->middleware('jwt.auth');
	// }
   
    public function upload(Request $request)
    {
		$item = SystemConfig::firstOrFail();
		$item->kpi_date_m1 = 28;
		$item->save();
		$counter = 0;
		// foreach ($request->file as $f)
		// {
			// $counter += 1;
		// }
		//$test = $request->file()[0]->move('C:/dqsfiles/','mobile1.txt');
		// $test = [
			// "name" => $request->file()[0]->getClientOriginalName(),
			// "size" => $request->file()[0]->getClientSize(),
		// ];
		$read = array();
		foreach(file('C:/dqsfiles/mobile1.txt') as $line) {
			// loop with $line for each line of yourfile.txt
			$read[] = explode('|',$line);
		}		
		$headers = array('Content-Type: application/text');		
		return response()->download('C:/dqsfiles/mobile1.txt','test.txt',$headers);
    }
	
	public function show($region_id)
	{
		try {
			$item = Region::findOrFail($region_id);
			$region = Branch::where("region",$item->region_code)->first();
			$item->regdesc = $region->regdesc;
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Region not found.']);
		}
		return response()->json($item);
	}
	
	public function store(Request $request)
	{
        $validator = Validator::make($request->all(), [
            'region_code' => 'required|numeric|digits_between:1,18|unique:dqs_region',
			'operation_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'data' => $validator->errors()]);
        } else {
		
			$branch = DB::select("
				select region
				from dqs_branch
				where region = ?
			", array($request->region_code));
			
			if (empty($branch)) {
				return response()->json(['status' => 400, 'data' => ['region_code' => ['Region Code not found in Branch Table']]]);
			}
			
			$item = new Region;
			$item->region_code = $request->region_code;
			$item->operation_id = $request->operation_id;
			$item->created_by = Auth::user()->personnel_id;
			$item->updated_by = Auth::user()->personnel_id;
			$item->save();
		}
		
		return response()->json(['status' => 200, 'data' => $item]);	
	}
	
	public function getRegionName(Request $request)
	{
		$item = Branch::where("region",$request->region_code)->select('region','regdesc')->first();
		return response()->json($item);
	}
	
	public function update(Request $request, $region_id)
	{
		try {
			$item = Region::findOrFail($region_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Region not found.']);
		}
		
        $validator = Validator::make($request->all(), [
            'region_code' => 'required|numeric|digits_between:1,18|unique:dqs_region',
			'operation_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'data' => $validator->errors()]);
        } else {
			$branch = DB::select("
				select region
				from dqs_branch
				where region = ?
			", array($request->region_code));
			
			if (empty($branch)) {
				return response()->json(['status' => 400, 'data' => ['region_code' => ['Region Code not found in Branch Table']]]);
			}		
			$item->fill($request->all());
			$item->updated_by = Auth::user()->personnel_id;
			$item->save();
		}
		
		return response()->json(['status' => 200, 'data' => $item]);
				
	}
	
	public function destroy($region_id)
	{
		try {
			$item = Region::findOrFail($region_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Region not found.']);
		}	

		try {
			
			$item->delete();
		} catch (QueryException $e) {
			if ($e->errorInfo[1] == 547) {
				return response()->json(['status' => 400, 'data' => 'Foreign key conflict error. Please ensure that this Region is not referenced in another module.']);
			} else {
				return response()->json($e);
			}
		}
		
		return response()->json(['status' => 200]);
		
	}	
	
}