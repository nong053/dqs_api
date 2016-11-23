<?php

namespace App\Http\Controllers;

use App\Grade;

use DB;
use Validator;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class GradeController extends Controller
{

	public function __construct()
	{

	   $this->middleware('jwt.auth');
	}
   
    public function index(Request $request)
    {
		if (empty($request->search_all)) {
			$query ="
				select grade_id, processing_seq, grade, grade_name
				from dqs_grade
				order by processing_seq asc
			";				

			// Get all items you want
			$items = DB::select($query);
		} else {
			$q = "%" . $request->search_all . "%";
		//	$qflag = $request->search_all;
			$items = DB::select("
				select grade_id, processing_seq, grade, grade_name
				from dqs_grade
				where processing_seq like ?
				or grade like ?
				or grade_name like ?
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
	
	public function show($grade_id)
	{
		try {
			$item = Grade::findOrFail($grade_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Grade not found.']);
		}
		return response()->json($item);
	}
	
	public function store(Request $request)
	{
        $validator = Validator::make($request->all(), [
            'processing_seq' => 'required|integer|unique:dqs_grade',
			'grade' => 'required|max:50|unique:dqs_grade',
			'grade_name' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'data' => $validator->errors()]);
        } else {
			$item = new Grade;
			$item->fill($request->all());
			$item->created_by = Auth::user()->personnel_id;
			$item->updated_by = Auth::user()->personnel_id;
			$item->save();
		}
		
		return response()->json(['status' => 200, 'data' => $item]);	
	}
	
	public function update(Request $request, $rule_id)
	{
		try {
			$item = Rule::findOrFail($rule_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Rule not found.']);
		}
		
        $validator = Validator::make($request->all(), [
            'rule_name' => 'required|max:255|unique:dqs_rule',
			'rule_group' => 'required|max:50',
			'data_flow_id' => 'required|integer',
			'initial_flag' => 'required|boolean',
			'update_flag' => 'required|boolean',
			'last_contact_flag' => 'required|boolean',
			'inform_flag' => 'required|boolean',
			'edit_rule_release_flag' => 'required|boolean'
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
	
	public function destroy($rule_id)
	{
		try {
			$item = Rule::findOrFail($rule_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Rule not found.']);
		}	

		try {
			$item->delete();
		} catch (QueryException $e) {
			if ($e->errorInfo[1] == 547) {
				return response()->json(['status' => 400, 'data' => 'Foreign key conflict error. Please ensure that this Rule is not referenced in another module.']);
			} else {
				return response()->json($e);
			}
		}
		
		return response()->json(['status' => 200]);
		
	}	
	
}