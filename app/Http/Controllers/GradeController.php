<?php

namespace App\Http\Controllers;

use App\Grade;
use App\GradeCondition;

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
	
	public function update(Request $request, $grade_id)
	{
		try {
			$item = Grade::findOrFail($grade_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Grade not found.']);
		}
		
        $validator = Validator::make($request->all(), [
            'processing_seq' => 'required|integer|unique:dqs_grade,processing_seq,' . $grade_id . ',grade_id',
			'grade' => 'required|max:50|unique:dqs_grade,grade,' . $grade_id . ',grade_id',
			'grade_name' => 'required|max:255',
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
	
	public function destroy($grade_id)
	{
		try {
			$item = Grade::findOrFail($grade_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Grade not found.']);
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
	
	public function list_condition($grade_id)
	{
		try {
			$grade = Grade::findOrFail($grade_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Grade not found.']);
		}	
		
		$grade->conditions = DB::select("
			select a.condition_id, a.processing_seq, a.operator, a.rule_id, b.rule_name, a.complete_flag
			from dqs_grade_condition a
			left outer join dqs_rule b
			on a.rule_id = b.rule_id
			where a.grade_id = ?
			order by a.processing_seq asc
		", array($grade->grade_id));

		return response()->json($grade);
	}
	
	public function add_condition(Request $request, $grade_id)
	{
		try {
			$grade = Grade::findOrFail($grade_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Grade not found.']);
		}		
		
		$conditions = $request->conditions;
		
		if (empty($conditions)) {
			return response()->json(['status' => 400, 'data' => "Require at least 1 Rule"]);		
		}
		
		$errors = array();
		$successes = array();
		
		foreach ($conditions as $c) {
			$validator = Validator::make($c, [
				'processing_seq' => 'required|integer',
				'operator' => 'max:10',
				'rule_id' => 'required|integer|unique:dqs_grade_condition,rule_id,null,condition_id,operator,' . $c['operator'],
				'complete_flag' => 'required|boolean',
			]);
			if ($validator->fails()) {
				$errors[] = ['rule_id' => $c['rule_id'], 'error' => $validator->errors()];
			} else {
				$item = new GradeCondition;
				$item->fill($c);
				$item->grade_id = $grade_id;
				$item->created_by = Auth::user()->personnel_id;
				$item->updated_by = Auth::user()->personnel_id;
				$item->save();
				$successes[] = ['rule_id' => $c['rule_id']];
			}			
		}
		return response()->json(['status' => 200, 'data' => ["success" => $successes, "error" => $errors]]);
		
	}
	
	public function update_condition(Request $request, $grade_id, $condition_id)
	{
		try {
			$item = GradeCondition::findOrFail($condition_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Grade Condition not found.']);
		}	
		
		$validator = Validator::make($request->all(), [
			'processing_seq' => 'required|integer',
			'operator' => 'max:10',
			'rule_id' => 'required|integer|unique:dqs_grade_condition,rule_id,'. $condition_id .',condition_id,operator,' . $request->operator,
			'complete_flag' => 'required|boolean',
		]);
		
		if ($validator->fails()) {
			return response()->json(['status' => 400, 'data' =>  $validator->errors()]);
		} else {
			$item->fill($request->all());
			$item->grade_id = $grade_id;
			$item->updated_by = Auth::user()->personnel_id;
			$item->save();		
			return response()->json(['status' => 200, 'data' => $item]);
		}
		
	}	
	
	public function delete_condition($grade_id, $condition_id)
	{
		try {
			$item = GradeCondition::findOrFail($condition_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Grade Condition not found.']);
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