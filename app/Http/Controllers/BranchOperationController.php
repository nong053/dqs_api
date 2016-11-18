<?php

namespace App\Http\Controllers;

use App\BranchOperation;

use DB;
use Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BranchOperationController extends Controller
{

	public function __construct()
	{
	   $this->middleware('jwt.auth');
	}
   
    public function index()
    {
        $items = BranchOperation::all();
		return response()->json($items);
    }
	
	public function store(Request $request)
	{
        $validator = Validator::make($request->all(), [
			'operation_code' => 'required|max:10',
            'operation_name' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'data' => $validator->errors()]);
        } else {
			$item = new BranchOperation;
			$item->operation_code = $request->operation_code;
			$item->operation_name = $request->operation_name;
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
			'operation_code' => 'required|max:10',
            'operation_name' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'data' => $validator->errors()]);
        } else {
			$item->fill($request->all());
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
		
		$item->delete();
		
		return response()->json(['status' => 200]);
		
	}
	
}