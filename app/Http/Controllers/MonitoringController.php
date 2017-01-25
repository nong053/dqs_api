<?php

namespace App\Http\Controllers;

use App\DQSValidate;
use App\DQSInitialValidate;
use App\DQSValidateHeader;
use App\DQSInitialValidateHeader;
use App\DQSRole;
use App\DQSUser;
use App\ExplainFile;

use DB;
use Validator;
use Auth;
use File;
use Excel;
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
		$user = DQSUser::find(Auth::user()->personnel_id);
		$role = DQSRole::find($user->role_id);
		if (empty($role)) {
			return response()->json(['status' => 400, 'Role not found for current user.']);
		}
		
		if ($role->all_branch_flag == '1') {
			$items = DB::select("
				select brcd, concat(brcd,' ',[desc]) [desc]
				from dqs_branch
				order by brcd asc
			");
			
			// $items = DB::select("
				// select distinct c.brcd, c.[desc]
				// from dqs_usage_log a
				// left outer join dqs_user b
				// on a.personnel_id = b.personnel_id
				// left outer join dqs_branch c
				// on b.branch_code = c.brcd			
			// ");
		} else {
			// $items = DB::select("
				// select brcd, [desc]
				// from dqs_branch
				// where ccdef = ?
			// ", array($user->revised_cost_center));		
			$checkop = DB::select("
				select operation_id, operation_name
				from dqs_branch_operation
				where cost_center = ?
			", array($user->revised_cost_center));
			
			if (empty($checkop)) {
				$checkregion = DB::select("
					select region, regdesc
					from dqs_branch
					where region = ?
				", array($user->revised_cost_center));
				
				if (empty($checkregion)) {
					$checkdist = DB::select("
						select dist, distdesc
						from dqs_branch
						where dist = ?
					", array($user->revised_cost_center));
					
					if (empty($checkdist)) {
						$items = DB::select("
							select distinct c.brcd, concat(c.brcd,' ',c.[desc]) [desc]
							from dqs_branch_operation a
							left outer join dqs_region b
							on a.operation_id = b.operation_id
							left outer join dqs_branch c
							on b.region_code = c.region	
							where c.ccdef = ?		
							order by c.brcd asc
						", array($user->revised_cost_center));						
					} else {	
						$items = DB::select("
							select distinct c.brcd, concat(c.brcd,' ',c.[desc]) [desc]
							from dqs_branch_operation a
							left outer join dqs_region b
							on a.operation_id = b.operation_id
							left outer join dqs_branch c
							on b.region_code = c.region	
							where c.dist = ?
							order by c.brcd asc
						", array($checkdist[0]->dist));						
					}				
				} else {		
					$items = DB::select("
						select distinct c.brcd, concat(c.brcd,' ',c.[desc]) [desc]
						from dqs_branch_operation a
						left outer join dqs_region b
						on a.operation_id = b.operation_id
						left outer join dqs_branch c
						on b.region_code = c.region	
						where c.region = ?			
						order by c.brcd asc
					", array($checkregion[0]->region));					
				}			
			} else {
				$items = DB::select("
					select distinct c.brcd, concat(c.brcd,' ',c.[desc]) [desc]
					from dqs_branch_operation a
					left outer join dqs_region b
					on a.operation_id = b.operation_id
					left outer join dqs_branch c
					on b.region_code = c.region	
					where a.operation_id = ?				
					order by c.brcd asc
				", array($checkop[0]->operation_id));		
			}				
		}
		return response()->json($items);
	}
	
	public function list_cust_type()
	{
		$items = DB::select("
			select gsbccode, [desc]
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
		
		$user = DQSUser::find(Auth::user()->personnel_id);
		$role = DQSRole::find($user->role_id);
		if (empty($role)) {
			return response()->json(['status' => 400, 'Role not found for current user.']);
		}
				
		if ($request->process_type == 'Initial') {
			$query = "			
				select a.validate_initial_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date) maxdays, a.kpi_flag, a.complete_flag,
				count(b.initial_validate_id) rules 
				from dqs_initial_validate_header a
				left outer join dqs_initial_validate b
				on a.validate_initial_header_id = b.validate_initial_header_id
				left outer join dqs_cust c
				on a.cif_no = c.acn
				where 1 = 1
			";
					
			$qfooter = "
				group by a.validate_initial_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date), a.kpi_flag, a.complete_flag
				order by a.validate_date desc, a.explain_status asc, a.contact_date desc, a.contact_branch_name asc, cast(a.cif_no as int) asc
			";
						
			$qinput = array();
			
			if ($role->all_branch_flag == '1') {
				empty($request->contact_branch_code) ?: ($query .= " and a.contact_branch_code = ? " AND $qinput[] = $request->contact_branch_code);
			} else {
				$query .= " and a.contact_branch_code = ? ";
				$qinput[] = $user->branch_code;
			}
				
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
			//!isset($request->customer_flag) ?: ($query .= " and c.customer_flag = ? " AND $qinput[] = $request->customer_flag);
			if ($request->customer_flag == '') {
			} else {
				$query .= " and c.customer_flag = ? ";
				$qinput[] = $request->customer_flag;
			}
			//!isset($request->death_flag) ?: ($query .= " and c.death_flag = ? " AND $qinput[] = $request->death_flag);
			if ($request->death_flag == '') {
			} else {
				$query .= " and c.death_flag = ? ";
				$qinput[] = $request->death_flag;
			}
			//!isset($request->personnel_flag) ?: ($query .= " and c.personnel_flag = ? " AND $qinput[] = $request->personnel_flag);
			if ($request->personnel_flag == '') {
			} else {
				$query .= " and c.personnel_flag = ? ";
				$qinput[] = $request->personnel_flag;
			}
			//!isset($request->employee_flag) ?: ($query .= " and c.employee_flag = ? " AND $qinput[] = $request->employee_flag);
			if ($request->employee_flag == '') {
			} else {
				$query .= " and c.employee_flag = ? ";
				$qinput[] = $request->employee_flag;
			}
			empty($request->explain_status) ?: ($query .= " and a.explain_status = ? " AND $qinput[] = $request->explain_status);
			//!isset($request->affiliation_flag) ?: ($query .= " and c.affiliation_flag = ? " AND $qinput[] = $request->affiliation_flag);
			if ($request->affiliation_flag == '') {
			} else {
				$query .= " and c.affiliation_flag = ? ";
				$qinput[] = $request->affiliation_flag;
			}
			//!isset($request->inform_flag) ?: ($query .= " and b.inform_flag = ? " AND $qinput[] = $request->inform_flag);
			if ($request->inform_flag == '') {
			} else {
				$query .= " and b.inform_flag = ? ";
				$qinput[] = $request->inform_flag;
			}
			//!isset($request->release_flag) ?: ($query .= " and b.release_flag = ? " AND $qinput[] = $request->release_flag);
			if ($request->release_flag == '') {
			} else {
				$query .= " and b.release_flag = ? ";
				$qinput[] = $request->release_flag;
			}
		

			// Get all items you want
			$items = DB::select($query . $qfooter, $qinput);		
		} else {
			$query = "			
				select a.validate_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date) maxdays, a.kpi_flag, a.complete_flag,
				count(b.validate_id) rules 
				from dqs_validate_header a
				left outer join dqs_validate b
				on a.validate_header_id = b.validate_header_id
				left outer join dqs_cust c
				on a.cif_no = c.acn
				where 1 = 1
			";
					
			$qfooter = "
				group by a.validate_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date), a.kpi_flag, a.complete_flag
				order by a.validate_date desc, a.explain_status asc, a.contact_date desc, a.contact_branch_name asc, cast(a.cif_no as int) asc
			";
						
			$qinput = array();
			
			if ($role->all_branch_flag == '1') {
				empty($request->contact_branch_code) ?: ($query .= " and a.contact_branch_code = ? " AND $qinput[] = $request->contact_branch_code);
			} else {
				$query .= " and a.contact_branch_code = ? ";
				$qinput[] = $user->branch_code;
			}
				
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
			//!isset($request->customer_flag) ?: ($query .= " and c.customer_flag = ? " AND $qinput[] = $request->customer_flag);
			if ($request->customer_flag == '') {
			} else {
				$query .= " and c.customer_flag = ? ";
				$qinput[] = $request->customer_flag;
			}
			//!isset($request->death_flag) ?: ($query .= " and c.death_flag = ? " AND $qinput[] = $request->death_flag);
			if ($request->death_flag == '') {
			} else {
				$query .= " and c.death_flag = ? ";
				$qinput[] = $request->death_flag;
			}
			//!isset($request->personnel_flag) ?: ($query .= " and c.personnel_flag = ? " AND $qinput[] = $request->personnel_flag);
			if ($request->personnel_flag == '') {
			} else {
				$query .= " and c.personnel_flag = ? ";
				$qinput[] = $request->personnel_flag;
			}
			//!isset($request->employee_flag) ?: ($query .= " and c.employee_flag = ? " AND $qinput[] = $request->employee_flag);
			if ($request->employee_flag == '') {
			} else {
				$query .= " and c.employee_flag = ? ";
				$qinput[] = $request->employee_flag;
			}
			empty($request->explain_status) ?: ($query .= " and a.explain_status = ? " AND $qinput[] = $request->explain_status);
			//!isset($request->affiliation_flag) ?: ($query .= " and c.affiliation_flag = ? " AND $qinput[] = $request->affiliation_flag);
			if ($request->affiliation_flag == '') {
			} else {
				$query .= " and c.affiliation_flag = ? ";
				$qinput[] = $request->affiliation_flag;
			}
			//!isset($request->inform_flag) ?: ($query .= " and b.inform_flag = ? " AND $qinput[] = $request->inform_flag);
			if ($request->inform_flag == '') {
			} else {
				$query .= " and b.inform_flag = ? ";
				$qinput[] = $request->inform_flag;
			}
			//!isset($request->release_flag) ?: ($query .= " and b.release_flag = ? " AND $qinput[] = $request->release_flag);
			if ($request->release_flag == '') {
			} else {
				$query .= " and b.release_flag = ? ";
				$qinput[] = $request->release_flag;
			}
		

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
	
	public function cdmd_export(Request $request)
	{
				
		if ($request->process_type == 'Initial') {
			$query = "			
				select a.validate_initial_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, b.rule_start_date, b.rule_end_date) days, 
				c.customer_flag, c.death_flag, c.type, c.affiliation_flag, a.contact_type, b.rule_group, b.rule_name, b.validate_status, a.risk, 
				count(d.explain_file_id) is_attached
				from dqs_initial_validate_header a
				left outer join dqs_initial_validate b
				on a.validate_initial_header_id = b.validate_initial_header_id
				left outer join dqs_cust c
				on a.cif_no = c.acn
				left outer join dqs_explain_file d
				on a.validate_initial_header_id = d.validate_initial_header_id
				where 1 = 1
			";
					
			$qfooter = "
				group by a.validate_initial_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, b.rule_start_date, b.rule_end_date), 
				c.customer_flag, c.death_flag, c.type, c.affiliation_flag, a.contact_type, b.rule_group, b.rule_name, b.validate_status, a.risk
				order by a.validate_date desc, a.explain_status asc, a.contact_date desc, a.contact_branch_name asc, cast(a.cif_no as int) asc
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
			//!isset($request->customer_flag) ?: ($query .= " and c.customer_flag = ? " AND $qinput[] = $request->customer_flag);
			if ($request->customer_flag == '') {
			} else {
				$query .= " and c.customer_flag = ? ";
				$qinput[] = $request->customer_flag;
			}
			//!isset($request->death_flag) ?: ($query .= " and c.death_flag = ? " AND $qinput[] = $request->death_flag);
			if ($request->death_flag == '') {
			} else {
				$query .= " and c.death_flag = ? ";
				$qinput[] = $request->death_flag;
			}
			//!isset($request->personnel_flag) ?: ($query .= " and c.personnel_flag = ? " AND $qinput[] = $request->personnel_flag);
			if ($request->personnel_flag == '') {
			} else {
				$query .= " and c.personnel_flag = ? ";
				$qinput[] = $request->personnel_flag;
			}
			//!isset($request->employee_flag) ?: ($query .= " and c.employee_flag = ? " AND $qinput[] = $request->employee_flag);
			if ($request->employee_flag == '') {
			} else {
				$query .= " and c.employee_flag = ? ";
				$qinput[] = $request->employee_flag;
			}
			empty($request->explain_status) ?: ($query .= " and a.explain_status = ? " AND $qinput[] = $request->explain_status);
			//!isset($request->affiliation_flag) ?: ($query .= " and c.affiliation_flag = ? " AND $qinput[] = $request->affiliation_flag);
			if ($request->affiliation_flag == '') {
			} else {
				$query .= " and c.affiliation_flag = ? ";
				$qinput[] = $request->affiliation_flag;
			}
			//!isset($request->inform_flag) ?: ($query .= " and b.inform_flag = ? " AND $qinput[] = $request->inform_flag);
			if ($request->inform_flag == '') {
			} else {
				$query .= " and b.inform_flag = ? ";
				$qinput[] = $request->inform_flag;
			}
			//!isset($request->release_flag) ?: ($query .= " and b.release_flag = ? " AND $qinput[] = $request->release_flag);
			if ($request->release_flag == '') {
			} else {
				$query .= " and b.release_flag = ? ";
				$qinput[] = $request->release_flag;
			}
		

			// Get all items you want
			$items = DB::select($query . $qfooter, $qinput);		
		} else {
			$query = "			
				select a.validate_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, b.rule_start_date, b.rule_end_date) days, 
				c.customer_flag, c.death_flag, c.type, c.affiliation_flag, a.contact_type, b.rule_group, b.rule_name, b.validate_status, a.risk, 
				count(d.explain_file_id) is_attached
				from dqs_validate_header a
				left outer join dqs_validate b
				on a.validate_header_id = b.validate_header_id
				left outer join dqs_cust c
				on a.cif_no = c.acn
				left outer join dqs_explain_file d
				on a.validate_header_id = d.validate_header_id
				where 1 = 1
			";
					
			$qfooter = "
				group by a.validate_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, b.rule_start_date, b.rule_end_date), 
				c.customer_flag, c.death_flag, c.type, c.affiliation_flag, a.contact_type, b.rule_group, b.rule_name, b.validate_status, a.risk
				order by a.validate_date desc, a.explain_status asc, a.contact_date desc, a.contact_branch_name asc, cast(a.cif_no as int) asc
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
			//!isset($request->customer_flag) ?: ($query .= " and c.customer_flag = ? " AND $qinput[] = $request->customer_flag);
			if ($request->customer_flag == '') {
			} else {
				$query .= " and c.customer_flag = ? ";
				$qinput[] = $request->customer_flag;
			}
			//!isset($request->death_flag) ?: ($query .= " and c.death_flag = ? " AND $qinput[] = $request->death_flag);
			if ($request->death_flag == '') {
			} else {
				$query .= " and c.death_flag = ? ";
				$qinput[] = $request->death_flag;
			}
			//!isset($request->personnel_flag) ?: ($query .= " and c.personnel_flag = ? " AND $qinput[] = $request->personnel_flag);
			if ($request->personnel_flag == '') {
			} else {
				$query .= " and c.personnel_flag = ? ";
				$qinput[] = $request->personnel_flag;
			}
			//!isset($request->employee_flag) ?: ($query .= " and c.employee_flag = ? " AND $qinput[] = $request->employee_flag);
			if ($request->employee_flag == '') {
			} else {
				$query .= " and c.employee_flag = ? ";
				$qinput[] = $request->employee_flag;
			}
			empty($request->explain_status) ?: ($query .= " and a.explain_status = ? " AND $qinput[] = $request->explain_status);
			//!isset($request->affiliation_flag) ?: ($query .= " and c.affiliation_flag = ? " AND $qinput[] = $request->affiliation_flag);
			if ($request->affiliation_flag == '') {
			} else {
				$query .= " and c.affiliation_flag = ? ";
				$qinput[] = $request->affiliation_flag;
			}
			//!isset($request->inform_flag) ?: ($query .= " and b.inform_flag = ? " AND $qinput[] = $request->inform_flag);
			if ($request->inform_flag == '') {
			} else {
				$query .= " and b.inform_flag = ? ";
				$qinput[] = $request->inform_flag;
			}
			//!isset($request->release_flag) ?: ($query .= " and b.release_flag = ? " AND $qinput[] = $request->release_flag);
			if ($request->release_flag == '') {
			} else {
				$query .= " and b.release_flag = ? ";
				$qinput[] = $request->release_flag;
			}
		

			// Get all items you want
			$items = DB::select($query . $qfooter, $qinput);
		}

		$filename = "CDMD_Monitoring_" . date('dm') .  substr(date('Y') + 543,2,2);
		$x = Excel::create($filename, function($excel) use($items, $filename) {
			$excel->sheet($filename, function($sheet) use($items) {
				
				$sheet->appendRow(array('Seq No', 'CIF No', 'Customer Name', 'Customer Flag', 'Death Flag', 'Customer Type', 'Affiliation Flag', 'Last Contact Branch', 'Last Contact Date', 'Last Contact Type', 'Rule Group', 'Rule', 'Validate Status', 'Is Attached', 'Explain Status', '#Days', 'Risk'));
				$seq = 1;
		
				foreach ($items as $i) {
					$sheet->appendRow(array(
						$seq, 
						$i->cif_no, 
						$i->cust_full_name, 
						$i->customer_flag, 
						$i->death_flag, 
						$i->type,
						$i->affiliation_flag,
						$i->contact_branch_name,
						$i->contact_type,
						$i->rule_group,
						$i->rule_name,
						$i->validate_status,
						$i->is_attached,
						$i->explain_status,
						$i->days,
						$i->risk
						));
					$seq += 1;
				}
			});

		})->export('xls');				
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
				group by a.own_branch_name, a.cif_no, a.cust_full_name, a.cust_type_desc, a.validate_date, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date)				
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
			return ['header' => $header, 'rule_list' => $result->toArray()];
			
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
	
	public function cdmd_explain_details(Request $request, $header_id)
	{
		if ($request->process_type == 'Initial') {
			$item = DB::select("
				select explain_remark, explain_status, explain_user, explain_dttm, approve_user, approve_dttm
				from dqs_initial_validate_header
				where validate_initial_header_id = ?
			", array($header_id));
			if (empty($item)) {
				return response()->json(['status' => 404, 'data' => 'Initial Validate Header not found.']);
			} else {
				$files = DB::select("
					select explain_file_id, validate_initial_header_id, file_path
					from dqs_explain_file
					where validate_initial_header_id = ?
				", array($header_id));
				$item[0]->explain_files = $files;
			}
		} else {
			$item = DB::select("
				select explain_remark, explain_status, explain_user, explain_dttm, approve_user, approve_dttm
				from dqs_validate_header
				where validate_header_id = ?
			", array($header_id));
			if (empty($item)) {
				return response()->json(['status' => 404, 'data' => 'Validate Header not found.']);
			} else {
				$files = DB::select("
					select explain_file_id, validate_header_id, file_path
					from dqs_explain_file
					where validate_header_id = ?
				", array($header_id));
				$item[0]->explain_files = $files;
			}		
		}
		
		return response()->json($item[0]);
	}
	
	public function cdmd_update_explain(Request $request, $header_id)
	{
		$checkuser = DQSUser::find(Auth::user()->personnel_id);
		$checkrole = DQSRole::find($checkuser->role_id);
		$warning = '';
		if (empty($checkrole)) {
			return response()->json(['status' => 400, 'data' => 'Role not found for current user']);
		}	
		
		if ($request->process_type == 'Initial') {
			$header = DB::select("
				select validate_initial_header_id
				from dqs_initial_validate_header
				where validate_initial_header_id = ?
			", array($header_id));
			if (empty($header)) {
				return response()->json(['status' => 404, 'data' => 'Initial Validate Header not found.']);
			}
				
			$validator = Validator::make($request->all(), [
				'explain_status' => 'required|max:50'
			]);
			if ($validator->fails()) {
				return response()->json(['status' => 400, 'data' => $validator->errors()]);
			} else {
				$item = DQSInitialValidateHeader::find($header_id);
				$item->explain_remark = $request->explain_remark;
				
				if ($item->explain_status == '1-Waiting' && $checkrole->authority_flag == 1 && ($request->explain_status == '2-Approved' || $request->explain_status == '3-Not Approved')) {
					$item->explain_status = $request->explain_status;
					$item->approve_user = Auth::user()->personnel_id;
					$item->approve_dttm = date('Y-m-d H:i:s');			
					$item->explain_user = Auth::user()->personnel_id;
					$item->explain_dttm = date('Y-m-d H:i:s');					
				} else {
					$warning = "You do not have permission to update Explain Status.";
				}
				$item->save();
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
				
			$validator = Validator::make($request->all(), [
				'explain_status' => 'required|max:50'
			]);
			if ($validator->fails()) {
				return response()->json(['status' => 400, 'data' => $validator->errors()]);
			} else {
				$item = DQSValidateHeader::find($header_id);
				$item->explain_remark = $request->explain_remark;
				
				if ($item->explain_status == '1-Waiting' && $checkrole->authority_flag == 1 && ($request->explain_status == '2-Approved' || $request->explain_status == '3-Not Approved')) {
					$item->explain_status = $request->explain_status;
					$item->approve_user = Auth::user()->personnel_id;
					$item->approve_dttm = date('Y-m-d H:i:s');
					$item->explain_user = Auth::user()->personnel_id;
					$item->explain_dttm = date('Y-m-d H:i:s');
				} else {
					$warning = "You do not have permission to update Explain Status.";
				}
				$item->save();
			}	
		}
		
		return response()->json(['status' => 200, 'data' => $item, 'warning' => $warning]);	
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
		
		$user = DQSUser::find(Auth::user()->personnel_id);
		$role = DQSRole::find($user->role_id);
		if (empty($role)) {
			return response()->json(['status' => 400, 'Role not found for current user.']);
		}
		
		if ($request->process_type == 'Initial') {
			$query = "			
				select a.validate_initial_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date) maxdays, a.kpi_flag, a.complete_flag, 
				count(b.initial_validate_id) rules 
				from dqs_initial_validate_header a
				left outer join dqs_initial_validate b
				on a.validate_initial_header_id = b.validate_initial_header_id
				left outer join dqs_cust c
				on a.cif_no = c.acn
				where b.inform_flag = 1
			";
					
			$qfooter = "
				group by a.validate_initial_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date), a.kpi_flag, a.complete_flag
				order by maxdays desc, rules desc, cast(a.cif_no as int) asc
			";
						
			$qinput = array();
			
			if ($role->all_branch_flag == 1) {
				empty($request->contact_branch_code) ?: ($query .= " and a.contact_branch_code = ? " AND $qinput[] = $request->contact_branch_code);
			} else {
				$query .= " and a.contact_branch_code in (select brcd from dqs_branch where ccdef = ?) ";
				$qinput[] = $user->revised_cost_center;
				empty($request->contact_branch_code) ?: ($query .= " and a.contact_branch_code = ? " AND $qinput[] = $request->contact_branch_code);
			}
			
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
			//!isset($request->customer_flag) ?: ($query .= " and c.customer_flag = ? " AND $qinput[] = $request->customer_flag);
			if ($request->customer_flag == '') {
			} else {
				$query .= " and c.customer_flag = ? ";
				$qinput[] = $request->customer_flag;
			}
			empty($request->explain_status) ?: ($query .= " and a.explain_status = ? " AND $qinput[] = $request->explain_status);
			//!isset($request->affiliation_flag) ?: ($query .= " and c.affiliation_flag = ? " AND $qinput[] = $request->affiliation_flag);
			if ($request->affiliation_flag == '') {
			} else {
				$query .= " and c.affiliation_flag = ? ";
				$qinput[] = $request->affiliation_flag;
			}
		

			// Get all items you want
			$items = DB::select($query . $qfooter, $qinput);		
		} else {
			$query = "			
				select a.validate_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date) maxdays, a.kpi_flag, a.complete_flag, 
				count(b.validate_id) rules 
				from dqs_validate_header a
				left outer join dqs_validate b
				on a.validate_header_id = b.validate_header_id
				left outer join dqs_cust c
				on a.cif_no = c.acn
				where b.inform_flag = 1
			";
					
			$qfooter = "
				group by a.validate_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date), a.kpi_flag, a.complete_flag
				order by maxdays desc, rules desc, cast(a.cif_no as int)
			";
						
			$qinput = array();
			
			if ($role->all_branch_flag == 1) {
				empty($request->contact_branch_code) ?: ($query .= " and a.contact_branch_code = ? " AND $qinput[] = $request->contact_branch_code);
			} else {
				$query .= " and a.contact_branch_code in (select brcd from dqs_branch where ccdef = ?) ";
				$qinput[] = $user->revised_cost_center;
				empty($request->contact_branch_code) ?: ($query .= " and a.contact_branch_code = ? " AND $qinput[] = $request->contact_branch_code);
			}
				
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
			//!isset($request->customer_flag) ?: ($query .= " and c.customer_flag = ? " AND $qinput[] = $request->customer_flag);
			if ($request->customer_flag == '') {
			} else {
				$query .= " and c.customer_flag = ? ";
				$qinput[] = $request->customer_flag;
			}
			empty($request->explain_status) ?: ($query .= " and a.explain_status = ? " AND $qinput[] = $request->explain_status);
			//!isset($request->affiliation_flag) ?: ($query .= " and c.affiliation_flag = ? " AND $qinput[] = $request->affiliation_flag);
			if ($request->affiliation_flag) {
			} else {
				$query .= " and c.affiliation_flag = ? ";
				$qinput[] = $request->affiliation_flag;
			}
		

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
	
	public function branch_export(Request $request)
	{
		$user = DQSUser::find(Auth::user()->personnel_id);
		$role = DQSRole::find($user->role_id);
		if (empty($role)) {
			return response()->json(['status' => 400, 'Role not found for current user.']);
		}	
		if ($request->process_type == 'Initial') {
			$query = "			
				select a.validate_initial_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, b.rule_start_date, b.rule_end_date) days, datediff(day, sysdatetime(), a.validate_date) maxdays,
				c.customer_flag, c.death_flag, c.type, c.affiliation_flag, a.contact_type, b.rule_group, b.rule_name, b.validate_status, a.risk, 
				count(d.explain_file_id) is_attached
				from dqs_initial_validate_header a
				left outer join dqs_initial_validate b
				on a.validate_initial_header_id = b.validate_initial_header_id
				left outer join dqs_cust c
				on a.cif_no = c.acn
				left outer join dqs_explain_file d
				on a.validate_initial_header_id = d.validate_initial_header_id
				where 1 = 1
			";
					
			$qfooter = "
				group by a.validate_initial_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, b.rule_start_date, b.rule_end_date), datediff(day, sysdatetime(), a.validate_date),
				c.customer_flag, c.death_flag, c.type, c.affiliation_flag, a.contact_type, b.rule_group, b.rule_name, b.validate_status, a.risk
				order by maxdays desc, cast(a.cif_no as int) asc
			";
						
			$qinput = array();
			
			if ($role->all_branch_flag == 1) {
				empty($request->contact_branch_code) ?: ($query .= " and a.contact_branch_code = ? " AND $qinput[] = $request->contact_branch_code);
			} else {
				$query .= " and a.contact_branch_code in (select brcd from dqs_branch where ccdef = ?) ";
				$qinput[] = $user->revised_cost_center;
				empty($request->contact_branch_code) ?: ($query .= " and a.contact_branch_code = ? " AND $qinput[] = $request->contact_branch_code);
			}
				
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
			//!isset($request->customer_flag) ?: ($query .= " and c.customer_flag = ? " AND $qinput[] = $request->customer_flag);
			if ($request->customer_flag == '') {
			} else {
				$query .= " and c.customer_flag = ? ";
				$qinput[] = $request->customer_flag;
			}
			empty($request->explain_status) ?: ($query .= " and a.explain_status = ? " AND $qinput[] = $request->explain_status);
			//!isset($request->affiliation_flag) ?: ($query .= " and c.affiliation_flag = ? " AND $qinput[] = $request->affiliation_flag);
			if ($request->affiliation_flag == '') {
			} else {
				$query .= " and c.affiliation_flag = ? ";
				$qinput[] = $request->affiliation_flag;
			}
		

			// Get all items you want
			$items = DB::select($query . $qfooter, $qinput);		
		} else {
			$query = "			
				select a.validate_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, b.rule_start_date, b.rule_end_date) days, datediff(day, sysdatetime(), a.validate_date) maxdays,
				c.customer_flag, c.death_flag, c.type, c.affiliation_flag, a.contact_type, b.rule_group, b.rule_name rules, b.validate_status, a.risk, 
				count(d.explain_file_id) is_attached
				from dqs_validate_header a
				left outer join dqs_validate b
				on a.validate_header_id = b.validate_header_id
				left outer join dqs_cust c
				on a.cif_no = c.acn
				left outer join dqs_explain_file d
				on a.validate_header_id = d.validate_header_id
				where 1 = 1
			";
					
			$qfooter = "
				group by a.validate_header_id, a.cif_no, a.cust_full_name, a.validate_date, a.explain_status, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, b.rule_start_date, b.rule_end_date), datediff(day, sysdatetime(), a.validate_date),
				c.customer_flag, c.death_flag, c.type, c.affiliation_flag, a.contact_type, b.rule_group, b.rule_name, b.validate_status, a.risk
				order by maxdays desc, rules desc, cast(a.cif_no as int) asc
			";
						
			$qinput = array();
			
			if ($role->all_branch_flag == 1) {
				empty($request->contact_branch_code) ?: ($query .= " and a.contact_branch_code = ? " AND $qinput[] = $request->contact_branch_code);
			} else {
				$query .= " and a.contact_branch_code in (select brcd from dqs_branch where ccdef = ?) ";
				$qinput[] = $user->revised_cost_center;
				empty($request->contact_branch_code) ?: ($query .= " and a.contact_branch_code = ? " AND $qinput[] = $request->contact_branch_code);
			}
				
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
			//!isset($request->customer_flag) ?: ($query .= " and c.customer_flag = ? " AND $qinput[] = $request->customer_flag);
			if ($request->customer_flag == '') {
			} else {
				$query .= " and c.customer_flag = ? ";
				$qinput[] = $request->customer_flag;
			}
			empty($request->explain_status) ?: ($query .= " and a.explain_status = ? " AND $qinput[] = $request->explain_status);
			//!isset($request->affiliation_flag) ?: ($query .= " and c.affiliation_flag = ? " AND $qinput[] = $request->affiliation_flag);
			if ($request->affiliation_flag) {
			} else {
				$query .= " and c.affiliation_flag = ? ";
				$qinput[] = $request->affiliation_flag;
			}
		

			// Get all items you want
			$items = DB::select($query . $qfooter, $qinput);
		}	
		$filename = "CDMD_Branch_" . date('dm') .  substr(date('Y') + 543,2,2);
		$x = Excel::create($filename, function($excel) use($items, $filename) {
			$excel->sheet($filename, function($sheet) use($items) {
				
				$sheet->appendRow(array('Seq No', 'CIF No', 'Customer Name', 'Customer Flag', 'Death Flag', 'Customer Type', 'Affiliation Flag', 'Last Contact Branch', 'Last Contact Date', 'Last Contact Type', 'Rule Group', 'Rule', 'Validate Status', 'Is Attached', 'Explain Status', '#Days', 'Risk'));
				$seq = 1;
		
				foreach ($items as $i) {
					$sheet->appendRow(array(
						$seq, 
						$i->cif_no, 
						$i->cust_full_name, 
						$i->customer_flag, 
						$i->death_flag, 
						$i->type,
						$i->affiliation_flag,
						$i->contact_branch_name,
						$i->contact_type,
						$i->rule_group,
						$i->rule_name,
						$i->validate_status,
						$i->is_attached,
						$i->explain_status,
						$i->days,
						$i->risk
						));
					$seq += 1;
				}
			});

		})->export('xls');				
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
				group by a.own_branch_name, a.cif_no, a.cust_full_name, a.cust_type_desc, a.validate_date, a.contact_branch_name, a.contact_date, a.transaction_date, datediff(day, sysdatetime(), a.validate_date)				
			", array($header_id));
			$header = $query[0];
			
			$items = DB::select("
				select a.cif_no, initial_validate_id, rule_id, rule_group, rule_name, a.kpi_flag, datediff(day, rule_start_date, rule_end_date) days, validate_status, no_doc_flag, iif(datediff(day, b.cifclcd, sysdatetime()) <= c.nof_contact_date,1,0) warning
				from dqs_initial_validate a
				left outer join dqs_cust b
				on a.cif_no = b.acn        
				cross join dqs_system_config c
				where a.validate_initial_header_id = ?
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
			return response()->json(['header' => $header, 'rule_list' => $result->toArray()]);
			
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
				select a.cif_no, validate_id, rule_id, rule_group, rule_name, a.kpi_flag, datediff(day, rule_start_date, rule_end_date) days, validate_status, no_doc_flag, iif(datediff(day, b.cifclcd, sysdatetime()) <= c.nof_contact_date,1,0) warning
				from dqs_validate a
				left outer join dqs_cust b
				on a.cif_no = b.acn        
				cross join dqs_system_config c
				where a.validate_header_id = ?
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
	
	public function branch_explain_details(Request $request, $header_id)
	{
		if ($request->process_type == 'Initial') {
			$item = DB::select("
				select explain_remark, explain_status, explain_user, explain_dttm, approve_user, approve_dttm
				from dqs_initial_validate_header
				where validate_initial_header_id = ?
			", array($header_id));
			if (empty($item)) {
				return response()->json(['status' => 404, 'data' => 'Initial Validate Header not found.']);
			} else {
				$files = DB::select("
					select explain_file_id, validate_initial_header_id, file_path
					from dqs_explain_file
					where validate_initial_header_id = ?
				", array($header_id));
				$item[0]->explain_files = $files;
			}
		} else {
			$item = DB::select("
				select explain_remark, explain_status, explain_user, explain_dttm, approve_user, approve_dttm
				from dqs_validate_header
				where validate_header_id = ?
			", array($header_id));
			if (empty($item)) {
				return response()->json(['status' => 404, 'data' => 'Validate Header not found.']);
			} else {
				$files = DB::select("
					select explain_file_id, validate_header_id, file_path
					from dqs_explain_file
					where validate_header_id = ?
				", array($header_id));
				$item[0]->explain_files = $files;
			}		
		}
		
		return response()->json($item[0]);
	}	
	
	public function branch_update_explain(Request $request, $header_id)
	{
	
		$checkuser = DQSUser::find(Auth::user()->personnel_id);
		$checkrole = DQSRole::find($checkuser->role_id);	
		
		if (empty($checkrole)) {
			return response()->json(['status' => 400, 'data' => 'Role not found for current user']);
		}		
				
		
		if ($request->process_type == 'Initial') {
			$header = DB::select("
				select validate_initial_header_id
				from dqs_initial_validate_header
				where validate_initial_header_id = ?
			", array($header_id));
			if (empty($header)) {
				return response()->json(['status' => 404, 'data' => 'Initial Validate Header not found.']);
			}
				
			$validator = Validator::make($request->all(), [
			]);
			if ($validator->fails()) {
				return response()->json(['status' => 400, 'data' => $validator->errors()]);
			} else {
				$item = DQSInitialValidateHeader::find($header_id);
				if ($checkrole->authority_flag == 1) {
					$item->explain_remark = $request->explain_remark;		
					$item->explain_user = Auth::user()->personnel_id;
					$item->explain_dttm = date('Y-m-d H:i:s');					
				} else {
				}
				$item->save();
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
				
			$validator = Validator::make($request->all(), [

			]);
			if ($validator->fails()) {
				return response()->json(['status' => 400, 'data' => $validator->errors()]);
			} else {
				$item = DQSValidateHeader::find($header_id);
				if ($checkrole->authority_flag == 1) {
					$item->explain_remark = $request->explain_remark;
					$item->explain_user = Auth::user()->personnel_id;
					$item->explain_dttm = date('Y-m-d H:i:s');
				} else {
				}
				$item->save();
			}	
		}
		
		return response()->json(['status' => 200, 'data' => $item]);	
	}	
	
	public function branch_upload_explain(Request $request, $header_id)
	{
		$checkuser = DQSUser::find(Auth::user()->personnel_id);
		$checkrole = DQSRole::find($checkuser->role_id);	

		if (empty($checkrole)) {
			return response()->json(['status' => 400, 'data' => 'Role not found for current user']);
		}		
		
		if ($checkrole->authority_flag == 0) {
			return response()->json(['status' => 405, 'data' => 'No permission to upload for this user.']);
		}
		
		$result = array();	
		if ($request->process_type == 'Initial') {		
			$path = $_SERVER['DOCUMENT_ROOT'] . '/dqs_api/public/explain_files/initial_validate/' . $header_id . '/';
			foreach ($request->file() as $f) {
				$filename = iconv('UTF-8','windows-874',$f->getClientOriginalName());
				$f->move($path,$filename);
				$item = ExplainFile::firstOrNew(array('file_path' => 'explain_files/initial_validate/' . $header_id . '/' . $f->getClientOriginalName()));
				$item->validate_initial_header_id = $header_id;
			//	$item->file_path = 'explain_files/initial_validate/' . $header_id . '/' . $f->getClientOriginalName();
				$item->save();
				$result[] = $item;
				$header = DQSInitialValidateHeader::find($header_id);
				if (empty($header)) {
					return response()->json(['status' => 400, 'data' => 'Initial Validate Header not found.']);
				}						
				$header->explain_status = '1-Waiting';
				$header->save();
			}
		} else {
			$path = $_SERVER['DOCUMENT_ROOT'] . '/dqs_api/public/explain_files/validate/' . $header_id . '/';
			foreach ($request->file() as $f) {
				$filename = iconv('UTF-8','windows-874',$f->getClientOriginalName());
				$f->move($path,$filename);
				$item = ExplainFile::firstOrNew(array('file_path' => 'explain_files/validate/' . $header_id . '/' . $f->getClientOriginalName()));
				$item->validate_header_id = $header_id;
				//$item->file_path = 'explain_files/validate/' . $header_id . '/' . $f->getClientOriginalName();
				$item->save();
				$result[] = $item;
				$header = DQSValidateHeader::find($header_id);
				if (empty($header)) {
					return response()->json(['status' => 400, 'data' => 'Validate Header not found.']);
				}						
				$header->explain_status = '1-Waiting';
				$header->save();				
			}		
		}
		return response()->json(['status' => 200, 'data' => $result]);
	}
	
	public function branch_delete_explain(Request $request, $header_id, $file_id)
	{
		$checkuser = DQSUser::find(Auth::user()->personnel_id);
		$checkrole = DQSRole::find($checkuser->role_id);	

		if (empty($checkrole)) {
			return response()->json(['status' => 400, 'data' => 'Role not found for current user']);
		}		
		
		if ($checkrole->authority_flag == 0) {
			return response()->json(['status' => 405, 'data' => 'No permission to delete for this user.']);
		}
		
		try {
			$item = ExplainFile::findOrFail($file_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'File not found.']);
		}
		File::Delete($_SERVER['DOCUMENT_ROOT'] . '/dqs_api/public/' . $item->file_path);		
		$item->delete();
		
		if ($request->process_type == 'Initial') {
			$header = DQSInitialValidateHeader::find($header_id);
			if (empty($header)) {
				return response()->json(['status' => 400, 'data' => 'Initial Validate Header not found.']);
			}						
			$header->explain_status = '4-Not Explain';
			$header->save();		
		} else {
			$header = DQSValidateHeader::find($header_id);
			if (empty($header)) {
				return response()->json(['status' => 400, 'data' => 'Validate Header not found.']);
			}						
			$header->explain_status = '4-Not Explain';
			$header->save();		
		}
		
		return response()->json(['status' => 200]);
		
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