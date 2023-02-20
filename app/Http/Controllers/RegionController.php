<?php

namespace App\Http\Controllers;

use App\Region;
use App\Branch;

use StdClass;
use DB;
use Validator;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class RegionController extends Controller
{

	public function __construct()
	{

	   $this->middleware('jwt.auth');
	}
   
    public function index(Request $request)
    {
		if (empty($request->search_all)) {
			$query ="			
				select z.*, row_number() over (order by region_code asc) seq
				from 
				(
					select distinct a.region_id, a.region_code, b.regdesc, c.operation_id, c.operation_name
					from dqs_region a
					left outer join dqs_branch b
					on a.region_code = b.region
					left outer join dqs_branch_operation c
					on a.operation_id = c.operation_id
				) z
				order by region_code asc
			";				

			// Get all items you want
			$items = DB::select($query);
		} else {
			$q = "%" . $request->search_all . "%";
		//	$qflag = $request->search_all;
			$items = DB::select("
				select z.*, row_number() over (order by region_code asc) seq
				from 
				(			
					select distinct a.region_id, row_number() over (order by a.region_code asc) seq, a.region_code, b.regdesc, c.operation_id, c.operation_name
					from dqs_region a
					left outer join dqs_branch b
					on a.region_code = b.region
					left outer join dqs_branch_operation c
					on a.operation_id = c.operation_id
					where a.region_code like ?
					or b.regdesc like ?
					or c.operation_name like ?
				) z
				order by region_code asc
			", array($q, $q, $q));

		}

		// Get the current page from the url if it's not set default to 1
		empty($request->page) ? $page = 1 : $page = $request->page;
		
		// Number of items per page
		empty($request->rpp) ? $perPage = 10 : $perPage = $request->rpp;

		// Start displaying items from this number;
		$offSet = ($page * $perPage) - $perPage; // Start displaying items from this number

		// Get only the items you need using array_slice (only get 10 items since that's what you need)
		$itemsForCurrentPage = array_slice($items, $offSet, $perPage, false);

		// Return the paginator with only 10 items but with the count of all items and set the it on the correct page
		$result = new LengthAwarePaginator($itemsForCurrentPage, count($items), $perPage, $page);			


		return response()->json($result);
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
		if (empty($request->region_code)) {
			$nothing = new StdClass;
			return response()->json($nothing);
		} else {
			$item = Branch::where("region",$request->region_code)->select('region','regdesc')->first();
			return response()->json($item);
		}
	}
	
	public function update(Request $request, $region_id)
	{
		try {
			$item = Region::findOrFail($region_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Region not found.']);
		}
		
        $validator = Validator::make($request->all(), [
            'region_code' => 'required|numeric|digits_between:1,18|unique:dqs_region,region_code,' . $region_id . ',region_id',
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
		
		$check = DB::select("
			select region, count(1) as active_count
			from dqs_branch
			where region = ?
			and close_flag = 0
			group by region
			having count(1) > 0	
		", array($item->region_code));
		
		if (!empty($check)) {
			return response()->json(['status' => 400, 'data' => 'ไม่สามารถลบข้อมูลได้ เนื่องจากมีการใช้งานอยู่']);
		}
		
		try {
			
			$item->delete();
		} catch (QueryException $e) {
			if ($e->errorInfo[1] == 547) {
				return response()->json(['status' => 400, 'data' => 'ไม่สามารถลบข้อมูลได้ เนื่องจากมีการใช้งานอยู่']);
			} else {
				return response()->json($e);
			}
		}
		
		return response()->json(['status' => 200]);
		
	}	
	
}