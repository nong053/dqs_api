<?php

namespace App\Http\Controllers;

use App\BranchOperation;

use DB;
use Validator;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class BranchOperationController extends Controller
{

	public function __construct()
	{
	   $this->middleware('jwt.auth');
	}
   
    public function index()
    {
        $items = DB::select("
			select a.operation_id, a.operation_name, b.ccdef, b.[desc] cost_center
			from dqs_branch_operation a
			left outer join dqs_branch b
			on a.cost_center = b.ccdef
		");
		return response()->json($items);
    }
	
	public function show($operation_id)
	{
		try {
			$item = BranchOperation::findOrFail($operation_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Branch Operation not found.']);
		}
		return response()->json($item);
	}
	
	public function store(Request $request)
	{
        $validator = Validator::make($request->all(), [
            'operation_name' => 'required|max:255',
			'cost_center' => 'required|numeric|digits_between:1,18'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'data' => $validator->errors()]);
        } else {
			$item = new BranchOperation;
			$item->operation_name = $request->operation_name;
			$item->cost_center = $request->cost_center;
			$item->created_by = Auth::user()->personnel_id;
			$item->updated_by = Auth::user()->personnel_id;
			$item->save();
		}
		
		return response()->json(['status' => 200, 'data' => $item]);	
	}
	
	public function update(Request $request, $operation_id)
	{
		try {
			$item = BranchOperation::findOrFail($operation_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Branch Operation not found.']);
		}
		
        $validator = Validator::make($request->all(), [
            'operation_name' => 'required|max:255',
			'cost_center' => 'required|numeric|digits_between:1,18'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'data' => $validator->errors()]);
        } else {
			$item->fill($request->all());
			$item->updated_by = Auth::user()->personnel_id;
			$item->save();
		}
		
		return response()->json(['status' => 200, 'data' => $item]);
				
	}
		
	public function destroy($operation_id)
	{	
		try {
			$item = BranchOperation::findOrFail($operation_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Branch Operation not found.']);
		}	

		try {
			$item->delete();
		} catch (QueryException $e) {
			if ($e->errorInfo[1] == 547) {
				return response()->json(['status' => 400, 'data' => 'Foreign key conflict error. Please ensure that this Branch Operation is not referenced in another module.']);
			} else {
				return response()->json($e);
			}
		}
		
		return response()->json(['status' => 200]);		
		
	}
	
	
}