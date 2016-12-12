<?php

namespace App\Http\Controllers;

use App\DQSValidate;
use App\DQSInitialValidate;

use DB;
use Validator;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class MonitoringController extends Controller
{

	public function __construct()
	{

	   $this->middleware('jwt.auth');
	}
   
	public function list_branch()
	{
		$items = DB::select("
			select brcd, desc_1
			from dqs_branch
		"); // to be filtered by role
		return response()->json($items);
	}
	
	public function list_cust_type()
	{
		$items = DB::select("
			select gsbccode, desc_1
			from dqs_cust_type
		"); // to be filtered by role
		return response()->json($items);
	}
		
	public function list_rule()
	{
		$items = DB::select("
			select rule_id, rule_name, rule_group
			from dqs_rule
		");
		return response()->json($items);
	}
	
    public function cdmd_index(Request $request)
    {
		// TEMPLATE QUERY 
		// select a.validate_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, a.validate_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date) maxdays, count(b.validate_id) rules 
		// from dqs_validate_header a
		// left outer join dqs_validate b
		// on a.validate_header_id = b.validate_header_id
		// left outer join dqs_cust c
		// on a.cif_no = c.acn
		// where 1 = 1
		// and a.contact_branch_code = 123
		// and a.validate_date between sysdatetime() and sysdatetime()
		// and a.cif_no = 123
		// and a.cust_full_name = 1
		// and a.cust_type_code = 123
		// and b.rule_group = 1
		// and b.rule_id = 1
		// and a.risk = 12
		// and b.validate_status = 3
		// and c.customer_flag = 1
		// and c.death_flag = 1
		// and c.personnel_flag = 1
		// and c.employee_flag = 1
		// and a.explain_status = 1
		// and c.affiliation_flag = 1
		// and b.inform_flag = 1
		// and b.release_flag = 1
		// group by a.validate_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date)
		// order by a.validate_date desc, a.explain_status asc, a.contact_date desc, a.contact_branch_name asc, a.cif_no asc	
		
		if ($request->process_type == 'Initial') {
			$query = "			
				select a.validate_initial_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date) maxdays, count(b.validate_id) rules 
				from dqs_initial_validate_header a
				left outer join dqs_initial_validate b
				on a.validate_initial_header_id = b.validate_initial_header_id
				left outer join dqs_cust c
				on a.cif_no = c.acn
				where 1 = 1
			";
					
			$qfooter = "
				group by a.validate_initial_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date)
				order by a.validate_date desc, a.explain_status asc, a.contact_date desc, a.contact_branch_name asc, a.cif_no asc
			";
						
			$qinput = array();
			
			empty($request->contact_branch_code) ?: ($query .= " and a.contact_branch_code = ? " AND $qinput[] = $request->contact_branch_code);
				
				if (empty($request->start_validate_date) || empty($request->end_validate_date)) {
				} else {
					$query .= " and a.validate_date between cast(? as date) and cast(? as date) ";
					$qinput[] = $request->start_validate_date;
					$qinput[] = $request->end_validate_date;				
				}
				
			empty($request->cif_no) ?: ($query .= " and a.cif_no = ? " AND $qinput[] = $request->cif_no);
			empty($request->cust_full_name) ?: ($query .= " and a.cust_full_name = ? " AND $qinput[] = $request->cust_full_name);
			empty($request->cust_type_code) ?: ($query .= " and a.cust_type_code = ? " AND $qinput[] = $request->cust_type_code);
			empty($request->rule_group) ?: ($query .= " and b.rule_group = ? " AND $qinput[] = $request->rule_group);
			empty($request->rule_id) ?: ($query .= " and b.rule_id = ? " AND $qinput[] = $request->rule_id);
			empty($request->risk) ?: ($query .= " and a.risk = ? " AND $qinput[] = $request->risk);
			empty($request->validate_status) ?: ($query .= " and b.validate_status = ? " AND $qinput[] = $request->validate_status);
			!isset($request->customer_flag) ?: ($query .= " and c.customer_flag = ? " AND $qinput[] = $request->customer_flag);
			!isset($request->death_flag) ?: ($query .= " and c.death_flag = ? " AND $qinput[] = $request->death_flag);
			!isset($request->personnel_flag) ?: ($query .= " and c.personnel_flag = ? " AND $qinput[] = $request->personnel_flag);
			!isset($request->employee_flag) ?: ($query .= " and c.employee_flag = ? " AND $qinput[] = $request->employee_flag);
			empty($request->explain_status) ?: ($query .= " and a.explain_status = ? " AND $qinput[] = $request->explain_status);
			!isset($request->affiliation_flag) ?: ($query .= " and c.affiliation_flag = ? " AND $qinput[] = $request->affiliation_flag);
			!isset($request->inform_flag) ?: ($query .= " and b.inform_flag = ? " AND $qinput[] = $request->inform_flag);
			!isset($request->release_flag) ?: ($query .= " and b.release_flag = ? " AND $qinput[] = $request->release_flag);
		

			// Get all items you want
			$items = DB::select($query . $qfooter, $qinput);		
		} else {
			$query = "			
				select a.validate_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date) maxdays, count(b.validate_id) rules 
				from dqs_validate_header a
				left outer join dqs_validate b
				on a.validate_header_id = b.validate_header_id
				left outer join dqs_cust c
				on a.cif_no = c.acn
				where 1 = 1
			";
					
			$qfooter = "
				group by a.validate_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date)
				order by a.validate_date desc, a.explain_status asc, a.contact_date desc, a.contact_branch_name asc, a.cif_no asc
			";
						
			$qinput = array();
			
			empty($request->contact_branch_code) ?: ($query .= " and a.contact_branch_code = ? " AND $qinput[] = $request->contact_branch_code);
				
				if (empty($request->start_validate_date) || empty($request->end_validate_date)) {
				} else {
					$query .= " and a.validate_date between cast(? as date) and cast(? as date) ";
					$qinput[] = $request->start_validate_date;
					$qinput[] = $request->end_validate_date;				
				}
				
			empty($request->cif_no) ?: ($query .= " and a.cif_no = ? " AND $qinput[] = $request->cif_no);
			empty($request->cust_full_name) ?: ($query .= " and a.cust_full_name = ? " AND $qinput[] = $request->cust_full_name);
			empty($request->cust_type_code) ?: ($query .= " and a.cust_type_code = ? " AND $qinput[] = $request->cust_type_code);
			empty($request->rule_group) ?: ($query .= " and b.rule_group = ? " AND $qinput[] = $request->rule_group);
			empty($request->rule_id) ?: ($query .= " and b.rule_id = ? " AND $qinput[] = $request->rule_id);
			empty($request->risk) ?: ($query .= " and a.risk = ? " AND $qinput[] = $request->risk);
			empty($request->validate_status) ?: ($query .= " and b.validate_status = ? " AND $qinput[] = $request->validate_status);
			!isset($request->customer_flag) ?: ($query .= " and c.customer_flag = ? " AND $qinput[] = $request->customer_flag);
			!isset($request->death_flag) ?: ($query .= " and c.death_flag = ? " AND $qinput[] = $request->death_flag);
			!isset($request->personnel_flag) ?: ($query .= " and c.personnel_flag = ? " AND $qinput[] = $request->personnel_flag);
			!isset($request->employee_flag) ?: ($query .= " and c.employee_flag = ? " AND $qinput[] = $request->employee_flag);
			empty($request->explain_status) ?: ($query .= " and a.explain_status = ? " AND $qinput[] = $request->explain_status);
			!isset($request->affiliation_flag) ?: ($query .= " and c.affiliation_flag = ? " AND $qinput[] = $request->affiliation_flag);
			!isset($request->inform_flag) ?: ($query .= " and b.inform_flag = ? " AND $qinput[] = $request->inform_flag);
			!isset($request->release_flag) ?: ($query .= " and b.release_flag = ? " AND $qinput[] = $request->release_flag);
		

			// Get all items you want
			$items = DB::select($query . $qfooter, $qinput);
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
	
	public function cdmd_details(Request $request, $header_id)
	{
		if ($request->process_type == 'Initial') {
			$query = DB::select("
				select a.own_branch_name, a.cif_no, a.cust_full_name, a.cust_type_desc, a.validate_date, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date) maxdays,
				count(b.initial_validate_id) rules
				from dqs_initial_validate_header a
				left outer join dqs_initial_validate b
				on a.validate_initial_header_id = b.validate_initial_header_id
				where a.validate_initial_header_id = ?
				group by a.own_branch_name, a.cif_no, a.cust_full_name, a.cust_type_desc, a.validate_date, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date) maxdays				
			", array($header_id));
			$header = $query[0];
			
			$items = DB::select("
				select initial_validate_id, rule_id, rule_group, rule_name, kpi_flag, datediff(day, rule_start_date, rule_end_date) days, validate_status, no_doc_flag
				from dqs_initial_validate
				where validate_initial_header_id = ?
			", array($header_id));
			
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
			$header->rule_list = $result;
			return response()->json($header);
			
		} else {
			$query = DB::select("
				select a.own_branch_name, a.cif_no, a.cust_full_name, a.cust_type_desc, a.validate_date, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date) maxdays,
				count(b.validate_id) rules
				from dqs_validate_header a
				left outer join dqs_validate b
				on a.validate_header_id = b.validate_header_id
				where a.validate_header_id = ?
				group by a.own_branch_name, a.cif_no, a.cust_full_name, a.cust_type_desc, a.validate_date, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date)				
			", array($header_id));
			$header = $query[0];
			
			$items = DB::select("
				select validate_id, rule_id, rule_group, rule_name, kpi_flag, datediff(day, rule_start_date, rule_end_date) days, validate_status, no_doc_flag
				from dqs_validate
				where validate_header_id = ?
			", array($header_id));
			
			// Get the current page from the url if it's not set default to 1
			empty($request->page) ? $page = 1 : $page = $request->page;
			
			// Number of items per page
			empty($request->rpp) ? $perPage = 10 : $perPage = $request->rpp;

			// Start displaying items from this number;
			$offSet = ($page * $perPage) - $perPage; // Start displaying items from this number

			// Get only the items you need using array_slice (only get 10 items since that's what you need)
			$itemsForCurrentPage = array_slice($items, $offSet, $perPage, false);

			// Return the paginator with only 10 items but with the count of all items and set the it on the correct page
			$result = new LengthAwarePaginator($itemsForCurrentPage, count($items), $perPage, $page, ['path' => $request->url()]);	

			return ['header' => $header, 'rule_list' => $result->toArray()];
			
		}

		
	}
	
	public function cdmd_update(Request $request, $header_id) {	
		
		$rules = $request->rules;
		
		$errors = array();
		$successes = array();		

		if ($request->process_type == 'Initial') {
			$header = DB::select("
				select validate_initial_header_id
				from dqs_initial_validate_header
				where validate_initial_header_id = ?
			", array($header_id));
			if (empty($header)) {
				return response()->json(['status' => 404, 'data' => 'Initial Validate Header not found.']);
			}
				
			foreach ($rules as $c) {
				$validator = Validator::make($c, [
					'kpi_flag' => 'boolean',
					'validate_status' => 'max:50'
				]);
				if ($validator->fails()) {
					$errors[] = ['initial_validate_id' => $c['initial_validate_id'], 'error' => $validator->errors()];
				} else {
					$item = DQSInitialValidate::find($c['initial_validate_id']);
					$item->fill($c);
					if ($c['kpi_flag'] == 0 && $c['validate_status'] == 'correct') {
						$item->release_user = Auth::user()->personnel_id;
						$item->release_dttm = date('Ymd H:i:s');
					}
					$item->save();
					$successes[] = ['initial_validate_id' => $c['initial_validate_id']];
				}			
			}			
		} else {
			$header = DB::select("
				select validate_header_id
				from dqs_validate_header
				where validate_header_id = ?
			", array($header_id));

			if (empty($header)) {
				return response()->json(['status' => 404, 'data' => 'Validate Header not found.']);
			}		
			
			foreach ($rules as $c) {
				$validator = Validator::make($c, [
					'kpi_flag' => 'boolean',
					'validate_status' => 'max:50'
				]);
				if ($validator->fails()) {
					$errors[] = ['initial_validate_id' => $c['validate_id'], 'error' => $validator->errors()];
				} else {
					$item = DQSValidate::find($c['validate_id']);
					$item->fill($c);
					if ($c['kpi_flag'] == 0 && $c['validate_status'] == 'correct') {
						$item->release_user = Auth::user()->personnel_id;
						$item->release_dttm = date('Ymd H:i:s');
					}
					$item->save();
					$successes[] = ['validate_id' => $c['validate_id']];
				}			
			}					
		}
		
		return response()->json(['status' => 200, 'data' => ["success" => $successes, "error" => $errors]]);		
	}
	
    public function branch_index(Request $request)
    {
		// TEMPLATE QUERY 
		// select a.validate_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, a.validate_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date) maxdays, count(b.validate_id) rules 
		// from dqs_validate_header a
		// left outer join dqs_validate b
		// on a.validate_header_id = b.validate_header_id
		// left outer join dqs_cust c
		// on a.cif_no = c.acn
		// where 1 = 1
		// and a.contact_branch_code = 123
		// and a.validate_date between sysdatetime() and sysdatetime()
		// and a.cif_no = 123
		// and a.cust_full_name = 1
		// and a.cust_type_code = 123
		// and b.rule_group = 1
		// and b.rule_id = 1
		// and a.risk = 12
		// and b.validate_status = 3
		// and c.customer_flag = 1
		// and c.death_flag = 1
		// and c.personnel_flag = 1
		// and c.employee_flag = 1
		// and a.explain_status = 1
		// and c.affiliation_flag = 1
		// and b.inform_flag = 1
		// and b.release_flag = 1
		// group by a.validate_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date)
		// order by a.validate_date desc, a.explain_status asc, a.contact_date desc, a.contact_branch_name asc, a.cif_no asc	
		
		if ($request->process_type == 'Initial') {
			$query = "			
				select a.validate_initial_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date) maxdays, count(b.validate_id) rules 
				from dqs_initial_validate_header a
				left outer join dqs_initial_validate b
				on a.validate_initial_header_id = b.validate_initial_header_id
				left outer join dqs_cust c
				on a.cif_no = c.acn
				where 1 = 1
			";
					
			$qfooter = "
				group by a.validate_initial_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date)
				order by maxdays desc, rules desc, cif_no asc
			";
						
			$qinput = array();
			
			empty($request->contact_branch_code) ?: ($query .= " and a.contact_branch_code = ? " AND $qinput[] = $request->contact_branch_code);
				
				if (empty($request->start_validate_date) || empty($request->end_validate_date)) {
				} else {
					$query .= " and a.validate_date between cast(? as date) and cast(? as date) ";
					$qinput[] = $request->start_validate_date;
					$qinput[] = $request->end_validate_date;				
				}
				
			empty($request->cif_no) ?: ($query .= " and a.cif_no = ? " AND $qinput[] = $request->cif_no);
			empty($request->cust_type_code) ?: ($query .= " and a.cust_type_code = ? " AND $qinput[] = $request->cust_type_code);
			empty($request->rule_group) ?: ($query .= " and b.rule_group = ? " AND $qinput[] = $request->rule_group);
			empty($request->rule_id) ?: ($query .= " and b.rule_id = ? " AND $qinput[] = $request->rule_id);
			empty($request->validate_status) ?: ($query .= " and b.validate_status = ? " AND $qinput[] = $request->validate_status);
			!isset($request->customer_flag) ?: ($query .= " and c.customer_flag = ? " AND $qinput[] = $request->customer_flag);
			empty($request->explain_status) ?: ($query .= " and a.explain_status = ? " AND $qinput[] = $request->explain_status);
			!isset($request->affiliation_flag) ?: ($query .= " and c.affiliation_flag = ? " AND $qinput[] = $request->affiliation_flag);
		

			// Get all items you want
			$items = DB::select($query . $qfooter, $qinput);		
		} else {
			$query = "			
				select a.validate_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date) maxdays, count(b.validate_id) rules 
				from dqs_validate_header a
				left outer join dqs_validate b
				on a.validate_header_id = b.validate_header_id
				left outer join dqs_cust c
				on a.cif_no = c.acn
				where 1 = 1
			";
					
			$qfooter = "
				group by a.validate_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date)
				order by maxdays desc, rules desc, cif_no asc
			";
						
			$qinput = array();
			
			empty($request->contact_branch_code) ?: ($query .= " and a.contact_branch_code = ? " AND $qinput[] = $request->contact_branch_code);
				
				if (empty($request->start_validate_date) || empty($request->end_validate_date)) {
				} else {
					$query .= " and a.validate_date between cast(? as date) and cast(? as date) ";
					$qinput[] = $request->start_validate_date;
					$qinput[] = $request->end_validate_date;				
				}
				
			empty($request->cif_no) ?: ($query .= " and a.cif_no = ? " AND $qinput[] = $request->cif_no);
			empty($request->cust_type_code) ?: ($query .= " and a.cust_type_code = ? " AND $qinput[] = $request->cust_type_code);
			empty($request->rule_group) ?: ($query .= " and b.rule_group = ? " AND $qinput[] = $request->rule_group);
			empty($request->rule_id) ?: ($query .= " and b.rule_id = ? " AND $qinput[] = $request->rule_id);
			empty($request->validate_status) ?: ($query .= " and b.validate_status = ? " AND $qinput[] = $request->validate_status);
			!isset($request->customer_flag) ?: ($query .= " and c.customer_flag = ? " AND $qinput[] = $request->customer_flag);
			empty($request->explain_status) ?: ($query .= " and a.explain_status = ? " AND $qinput[] = $request->explain_status);
			!isset($request->affiliation_flag) ?: ($query .= " and c.affiliation_flag = ? " AND $qinput[] = $request->affiliation_flag);
		

			// Get all items you want
			$items = DB::select($query . $qfooter, $qinput);
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
	
	public function branch_details(Request $request, $header_id)
	{
		if ($request->process_type == 'Initial') {
			$query = DB::select("
				select a.own_branch_name, a.cif_no, a.cust_full_name, a.cust_type_desc, a.validate_date, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date) maxdays,
				count(b.initial_validate_id) rules
				from dqs_initial_validate_header a
				left outer join dqs_initial_validate b
				on a.validate_initial_header_id = b.validate_initial_header_id
				where a.validate_initial_header_id = ?
				group by a.own_branch_name, a.cif_no, a.cust_full_name, a.cust_type_desc, a.validate_date, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date) maxdays				
			", array($header_id));
			$header = $query[0];
			
			$items = DB::select("
				select initial_validate_id, rule_id, rule_group, rule_name, kpi_flag, datediff(day, rule_start_date, rule_end_date) days, validate_status, no_doc_flag
				from dqs_initial_validate
				where validate_initial_header_id = ?
			", array($header_id));
			
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
			$header->rule_list = $result;
			return response()->json($header);
			
		} else {
			$query = DB::select("
				select a.own_branch_name, a.cif_no, a.cust_full_name, a.cust_type_desc, a.validate_date, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date) maxdays,
				count(b.validate_id) rules
				from dqs_validate_header a
				left outer join dqs_validate b
				on a.validate_header_id = b.validate_header_id
				where a.validate_header_id = ?
				group by a.own_branch_name, a.cif_no, a.cust_full_name, a.cust_type_desc, a.validate_date, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date)				
			", array($header_id));
			$header = $query[0];
			
			$items = DB::select("
				select validate_id, rule_id, rule_group, rule_name, kpi_flag, datediff(day, rule_start_date, rule_end_date) days, validate_status, no_doc_flag
				from dqs_validate
				where validate_header_id = ?
			", array($header_id));
			
			// Get the current page from the url if it's not set default to 1
			empty($request->page) ? $page = 1 : $page = $request->page;
			
			// Number of items per page
			empty($request->rpp) ? $perPage = 10 : $perPage = $request->rpp;

			// Start displaying items from this number;
			$offSet = ($page * $perPage) - $perPage; // Start displaying items from this number

			// Get only the items you need using array_slice (only get 10 items since that's what you need)
			$itemsForCurrentPage = array_slice($items, $offSet, $perPage, false);

			// Return the paginator with only 10 items but with the count of all items and set the it on the correct page
			$result = new LengthAwarePaginator($itemsForCurrentPage, count($items), $perPage, $page, ['path' => $request->url()]);	

			return ['header' => $header, 'rule_list' => $result->toArray()];
			
		}

		
	}	
	
	public function branch_update(Request $request, $header_id) {	
		
		$rules = $request->rules;
		
		$errors = array();
		$successes = array();		

		if ($request->process_type == 'Initial') {
			$header = DB::select("
				select validate_initial_header_id
				from dqs_initial_validate_header
				where validate_initial_header_id = ?
			", array($header_id));
			if (empty($header)) {
				return response()->json(['status' => 404, 'data' => 'Initial Validate Header not found.']);
			}
				
			foreach ($rules as $c) {
				$validator = Validator::make($c, [
					'no_doc_flag' => 'boolean'
				]);
				if ($validator->fails()) {
					$errors[] = ['initial_validate_id' => $c['initial_validate_id'], 'error' => $validator->errors()];
				} else {
					$item = DQSInitialValidate::find($c['initial_validate_id']);
					$item->fill($c);
					$item->save();
					$successes[] = ['initial_validate_id' => $c['initial_validate_id']];
				}			
			}			
		} else {
			$header = DB::select("
				select validate_header_id
				from dqs_validate_header
				where validate_header_id = ?
			", array($header_id));

			if (empty($header)) {
				return response()->json(['status' => 404, 'data' => 'Validate Header not found.']);
			}		
			
			foreach ($rules as $c) {
				$validator = Validator::make($c, [
					'no_doc_flag' => 'boolean'
				]);
				if ($validator->fails()) {
					$errors[] = ['initial_validate_id' => $c['validate_id'], 'error' => $validator->errors()];
				} else {
					$item = DQSValidate::find($c['validate_id']);
					$item->fill($c);
					$item->save();
					$successes[] = ['validate_id' => $c['validate_id']];
				}			
			}					
		}
		
		return response()->json(['status' => 200, 'data' => ["success" => $successes, "error" => $errors]]);		
	}	
}