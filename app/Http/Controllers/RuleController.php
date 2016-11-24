<?php

namespace App\Http\Controllers;

use App\Rule;

use DB;
use Validator;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class RuleController extends Controller
{

	public function __construct()
	{

	   $this->middleware('jwt.auth');
	}
   
    public function index(Request $request)
    {
		if (empty($request->search_all)) {
			$query ="			
				select a.rule_id, a.rule_group, a.rule_name, b.data_flow_name, a.initial_flag, a.update_flag, a.last_contact_flag, a.inform_flag, a.edit_rule_release_flag
				from dqs_rule a
				left outer join dqs_data_flow b
				on a.data_flow_id = b.data_flow_id
				where 1=1";			
			
			$qinput = array();
			
			empty($request->rule_group) ?: ($query .= " and a.rule_group like ? " AND $qinput[] = "%" . $request->rule_group . "%");
			empty($request->rule_name) ?: ($query .= " and a.rule_name like ? " AND $qinput[] = "%" . $request->rule_name . "%");
			!isset($request->initial_flag) ?: ($query .= " and a.initial_flag = ? " AND $qinput[] = $request->initial_flag);
			!isset($request->update_flag) ?: ($query .= " and a.update_flag = ? " AND $qinput[] = $request->update_flag);
			!isset($request->last_contact_flag) ?: ($query .= " and a.last_contact_flag = ? " AND $qinput[] = $request->last_contact_flag);			

			// Get all items you want
			$items = DB::select($query, $qinput);
		} else {
			$q = "%" . $request->search_all . "%";
		//	$qflag = $request->search_all;
			$items = DB::select("
				select a.rule_id, a.rule_group, a.rule_name, b.data_flow_name, a.initial_flag, a.update_flag, a.last_contact_flag, a.inform_flag, a.edit_rule_release_flag
				from dqs_rule a
				left outer join dqs_data_flow b
				on a.data_flow_id = b.data_flow_id
				where a.rule_group like ?
				or a.rule_name like ?
				or b.data_flow_name like ?
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
	
	public function show($rule_id)
	{
		try {
			$item = Rule::findOrFail($rule_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Rule not found.']);
		}
		return response()->json($item);
	}
	
	public function store(Request $request)
	{
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
			$item = new Rule;
			$item->fill($request->all());
			$item->created_by = Auth::user()->personnel_id;
			$item->updated_by = Auth::user()->personnel_id;
			$item->save();
		}
		
		return response()->json(['status' => 200, 'data' => $item]);	
	}
	
	public function update_flags(Request $request)
	{
		$errors = array();
		$successes = array();
		
		$rules = $request->rules;
		
		
		if (empty($rules)) {
			return response()->json(['status' => 200, 'data' => ["success" => [], "error" => []]]);
		}
		
		foreach ($rules as $r) {
			$item = Rule::find($r["rule_id"]);
			if (empty($item)) {
				$errors[] = ["rule_id" => $r["rule_id"]];
			} else {
				$validator = Validator::make($r, [
					'initial_flag' => 'required|boolean',
					'update_flag' => 'required|boolean',
					'last_contact_flag' => 'required|boolean',
					'inform_flag' => 'required|boolean',
					'edit_rule_release_flag' => 'required|boolean'
				]);

				if ($validator->fails()) {
					$errors[] = ["rule_id" => $r["rule_id"], "error" => $validator->errors()];
				} else {
					$item->fill($r);
					$item->save();
					$sitem = ["rule_id" => $item->rule_id];
					$successes[] = $sitem;					
				}			

			}
		}
		
		return response()->json(['status' => 200, 'data' => ["success" => $successes, "error" => $errors]]);				
	}
	
	public function update(Request $request, $rule_id)
	{
		try {
			$item = Rule::findOrFail($rule_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Rule not found.']);
		}
		
        $validator = Validator::make($request->all(), [
            'rule_name' => 'required|max:255|unique:dqs_rule,rule_name,' . $rule_id . ',rule_id',
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
	
	public function auto_rule(Request $request)
	{
		$q = '%' . $request->q . '%';
		$items = DB::select("
			select top 10 rule_id, rule_name
			from dqs_rule
			where rule_name like ?
		", array($q));
		return response()->json($items);
	}
	
	public function list_data_flow()
	{
		$items = DB::select("
			select *
			from dqs_data_flow
		");
		return response()->json($items);		
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