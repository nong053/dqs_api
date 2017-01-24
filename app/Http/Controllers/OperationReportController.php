<?php

namespace App\Http\Controllers;


use DB;
use Validator;
use Auth;
use Excel;
use App\DQSRole;
use App\DQSUser;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class OperationReportController extends Controller
{

	public function __construct()
	{
	   $this->middleware('jwt.auth');
	}
	
	public function list_province()
	{
		$items = DB::select("
			select province_code, province_name
			from dqs_province
			order by province_name asc
		");
		return response()->json($items);
	}
	
	public function auto_name(Request $request)
	{
		$items = DB::select("
			select distinct cust_name
			from dqs_merge_cif
			where cust_name like ?
		", array('%' . $request->q . '%'));
		return response()->json($items);
	}
	
	public function auto_surname(Request $request)
	{
		$items = DB::select("
			select distinct cust_surname
			from dqs_merge_cif
			where cust_surname like ?
		", array('%' . $request->q . '%'));
		return response()->json($items);	
	}
	
	public function list_operation()
	{
		$user = DQSUser::find(Auth::user()->personnel_id);
		$role = DQSRole::find($user->role_id);
		if (empty($role)) {
			return response()->json(['status' => 400, 'Role not found for current user.']);
		}
		
		if ($role->all_branch_flag == 1) {
			$items = DB::select("
				select distinct a.operation_id, a.operation_name
				from dqs_branch_operation a
				left outer join dqs_region b
				on a.operation_id = b.operation_id
				left outer join dqs_branch c
				on b.region_code = c.region		
			");
		} else {
			$items = DB::select("
				select distinct a.operation_id, a.operation_name
				from dqs_branch_operation a
				left outer join dqs_region b
				on a.operation_id = b.operation_id
				left outer join dqs_branch c
				on b.region_code = c.region		
				where a.cost_center = ?
			", array($user->revised_cost_center));
		}
		
		return response()->json($items);
	}
	
	public function list_region(Request $request)
	{
		$user = DQSUser::find(Auth::user()->personnel_id);
		$role = DQSRole::find($user->role_id);
		if (empty($role)) {
			return response()->json(['status' => 400, 'Role not found for current user.']);
		}
		
		if ($role->all_branch_flag == 1) {
			$items = DB::select("
				select distinct c.region, c.regdesc
				from dqs_branch_operation a
				left outer join dqs_region b
				on a.operation_id = b.operation_id
				left outer join dqs_branch c
				on b.region_code = c.region		
				where a.operation_id = ?
			", array($request->operation_id));
		} else {
			$checkop = DB::select("
				select operation_id, operation_name
				from dqs_branch_operation
				where cost_center = ?
			", array($user->revised_cost_center));
			
			if (empty($checkop)) {
				$items = DB::select("
					select distinct c.region, c.regdesc
					from dqs_branch_operation a
					left outer join dqs_region b
					on a.operation_id = b.operation_id
					left outer join dqs_branch c
					on b.region_code = c.region	
					where c.ccdef = ?
				", array($user->revised_cost_center));					
			} else {
				$items = DB::select("
					select distinct c.region, c.regdesc
					from dqs_branch_operation a
					left outer join dqs_region b
					on a.operation_id = b.operation_id
					left outer join dqs_branch c
					on b.region_code = c.region	
					where a.operation_id = ?
				", array($checkop[0]->operation_id));
			}		
		}
		
		return response()->json($items);	
	}
	
	public function list_district(Request $request)
	{
		$user = DQSUser::find(Auth::user()->personnel_id);
		$role = DQSRole::find($user->role_id);
		if (empty($role)) {
			return response()->json(['status' => 400, 'Role not found for current user.']);
		}
		
		if ($role->all_branch_flag == 1) {
			$items = DB::select("
				select distinct c.dist, c.distdesc
				from dqs_branch_operation a
				left outer join dqs_region b
				on a.operation_id = b.operation_id
				left outer join dqs_branch c
				on b.region_code = c.region		
				where c.region = ?
			", array($request->region));
		} else {
			// $checkregion = DB::select("
				// select region, regdesc
				// from dqs_branch
				// where region = ?
			// ", array($request->revised_cost_center));
			
			// if (empty($checkregion)) {
				// $items = DB::select("
					// select distinct c.dist, c.distdesc
					// from dqs_branch_operation a
					// left outer join dqs_region b
					// on a.operation_id = b.operation_id
					// left outer join dqs_branch c
					// on b.region_code = c.region	
					// where c.ccdef = ?
				// ", array($user->revised_cost_center));				
			// } else {
				// $items = DB::select("
					// select distinct c.dist, c.distdesc
					// from dqs_branch_operation a
					// left outer join dqs_region b
					// on a.operation_id = b.operation_id
					// left outer join dqs_branch c
					// on b.region_code = c.region	
					// where c.region = ?
				// ", array($checkregion[0]->region));			
			// }
			
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
					$items = DB::select("
						select distinct dist, distdesc
						from dqs_branch
						where ccdef = ?
					", array($user->revised_cost_center));
		
				} else {		
					$items = DB::select("
						select distinct c.dist, c.distdesc
						from dqs_branch_operation a
						left outer join dqs_region b
						on a.operation_id = b.operation_id
						left outer join dqs_branch c
						on b.region_code = c.region	
						where a.cost_center = ?
						and c.region = ?
					",array($user->revised_cost_center, $request->region));			
				}			
			} else {
				$items = DB::select("
					select distinct c.dist, c.distdesc
					from dqs_branch_operation a
					left outer join dqs_region b
					on a.operation_id = b.operation_id
					left outer join dqs_branch c
					on b.region_code = c.region	
					where a.cost_center = ?
					and c.region = ?
				",array($user->revised_cost_center, $request->region));	
			}								
				
		}
		
		return response()->json($items);
		
	}
	
	public function list_branch(Request $request)
	{
		$user = DQSUser::find(Auth::user()->personnel_id);
		$role = DQSRole::find($user->role_id);
		if (empty($role)) {
			return response()->json(['status' => 400, 'Role not found for current user.']);
		}
		
		if ($role->all_branch_flag == 1) {
			$items = DB::select("
				select distinct c.brcd, concat(c.brcd,' ',c.[desc]) [desc]
				from dqs_branch_operation a
				left outer join dqs_region b
				on a.operation_id = b.operation_id
				left outer join dqs_branch c
				on b.region_code = c.region		
				where c.dist = ?
				order by c.brcd asc
			", array($request->dist));
		} else {
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
						",array($user->revised_cost_center));						
					} else {	
						$items = DB::select("
							select distinct c.brcd, concat(c.brcd,' ',c.[desc]) [desc]
							from dqs_branch_operation a
							left outer join dqs_region b
							on a.operation_id = b.operation_id
							left outer join dqs_branch c
							on b.region_code = c.region	
							where a.cost_center = ?
							and c.dist = ?
							order by c.brcd asc
						",array($user->revised_cost_center, $request->dist));					
					}				
				} else {		
					$items = DB::select("
						select distinct c.brcd, concat(c.brcd,' ',c.[desc]) [desc]
						from dqs_branch_operation a
						left outer join dqs_region b
						on a.operation_id = b.operation_id
						left outer join dqs_branch c
						on b.region_code = c.region	
						where a.cost_center = ?
						and c.dist = ?
						order by c.brcd asc
					",array($user->revised_cost_center, $request->dist));					
				}			
			} else {
				$items = DB::select("
					select distinct c.brcd, concat(c.brcd,' ',c.[desc]) [desc]
					from dqs_branch_operation a
					left outer join dqs_region b
					on a.operation_id = b.operation_id
					left outer join dqs_branch c
					on b.region_code = c.region	
					where a.cost_center = ?
					and c.dist = ?
					order by c.brcd asc
				",array($user->revised_cost_center, $request->dist));
	
			}				
			
		}
		
		return response()->json($items);
		
	}
	
	public function no_progress(Request $request)
	{
		$user = DQSUser::find(Auth::user()->personnel_id);
		$role = DQSRole::find($user->role_id);
		if (empty($role)) {
			return response()->json(['status' => 400, 'Role not found for current user.']);
		}
		
		$operations_in = array();
		$op_query_string = '';
		empty($request->operation_code) ?: ($op_query_string .= ' and operation_code = ? ' AND $operations_in[] = $request->operation_code);
		empty($request->region_code) ?: ($op_query_string .= ' and region_code = ? ' AND $operations_in[] = $request->region_code);	
		empty($request->district_code) ?: ($op_query_string .= ' and district_code = ? ' AND $operations_in[] = $request->district_code);	
		if ($role->all_branch_flag == 1) {
			empty($request->contact_branch_code) ?: ($op_query_string .= ' and contact_branch_code = ? ' AND $operations_in[] = $request->contact_branch_code);	
		} else {
			// $op_query_string .= ' and contact_branch_code in (select brcd from dqs_branch where ccdef = ?) ';
			// $operations_in[] = $user->revised_cost_center;			
			
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
						$op_query_string .= ' and contact_branch_code in (
							select distinct c.brcd
							from dqs_branch_operation a
							left outer join dqs_region b
							on a.operation_id = b.operation_id
							left outer join dqs_branch c
							on b.region_code = c.region	
							where c.ccdef = ?		
						)';
						$operations_in[] = $user->revised_cost_center;							
					} else {	
						$op_query_string .= ' and contact_branch_code in (
							select distinct c.brcd
							from dqs_branch_operation a
							left outer join dqs_region b
							on a.operation_id = b.operation_id
							left outer join dqs_branch c
							on b.region_code = c.region	
							where c.dist = ?		
						)';
						$operations_in[] = $checkdist[0]->dist;							
					}				
				} else {		
					$op_query_string .= ' and contact_branch_code in (
						select distinct c.brcd
						from dqs_branch_operation a
						left outer join dqs_region b
						on a.operation_id = b.operation_id
						left outer join dqs_branch c
						on b.region_code = c.region	
						where c.region = ?				
					)';
					$operations_in[] = $checkregion[0]->region;						
				}			
			} else {
				$op_query_string .= ' and contact_branch_code in (
					select distinct c.brcd
					from dqs_branch_operation a
					left outer join dqs_region b
					on a.operation_id = b.operation_id
					left outer join dqs_branch c
					on b.region_code = c.region	
					where a.operation_id = ?				
				)';
				$operations_in[] = $checkop[0]->operation_id;			
			}					
			
			empty($request->contact_branch_code) ?: ($op_query_string .= ' and contact_branch_code = ? ' AND $operations_in[] = $request->contact_branch_code);			
		}
		empty($request->year) ?: ($op_query_string .= ' and year = ? ' AND $operations_in[] = $request->year);	
		empty($request->month) ?: ($op_query_string .= ' and month_no = ? ' AND $operations_in[] = $request->month);	

		if ($request->status == '') {
		} else {
			$op_query_string .= ' and no_doc_flag = ? ';
			$operations_in[] = $request->status;
		}
		
		
		$operations_query = "
			select operation_code, operation_name, 
			sum(iif(rule_group = 'Cleansing',1,0)) cleansing,
			sum(iif(rule_group = 'Mapping',1,0)) mapping,
			sum(iif(rule_group = 'Matching',1,0)) matching,
			sum(iif(rule_group = 'Edit',1,0)) edit,
			sum(iif(rule_group = 'KPI',1,0)) kpi,
			count(1) total
			from (
			  select distinct operation_name, operation_code, cif_no, rule_group
			  from dqs_validate
			  where validate_status in ('incomplete','wrong')
			  {$op_query_string}
			) a
			group by operation_code, operation_name
		";

		$operations = DB::select($operations_query, $operations_in);
		//return $operations;
		foreach ($operations as $o) {
			$regions_in = $operations_in;
			$regions_query = "
				select region_code, region_name, 
				sum(iif(rule_group = 'Cleansing',1,0)) cleansing,
				sum(iif(rule_group = 'Mapping',1,0)) mapping,
				sum(iif(rule_group = 'Matching',1,0)) matching,
				sum(iif(rule_group = 'Edit',1,0)) edit,
				sum(iif(rule_group = 'KPI',1,0)) kpi,
				count(1) total
				from (
				  select distinct region_code, region_name, cif_no, rule_group
				  from dqs_validate
				  where validate_status in ('incomplete','wrong')
				  and operation_code = ?
				  {$op_query_string}				  
				) a
				group by region_code, region_name				
			";
			array_unshift($regions_in, $o->operation_code);
			$regions = DB::select($regions_query, $regions_in);
			foreach ($regions as $r) {
				$districts_in = $operations_in;
				$districts_query = "
					select district_code, district_name, 
					sum(iif(rule_group = 'Cleansing',1,0)) cleansing,
					sum(iif(rule_group = 'Mapping',1,0)) mapping,
					sum(iif(rule_group = 'Matching',1,0)) matching,
					sum(iif(rule_group = 'Edit',1,0)) edit,
					sum(iif(rule_group = 'KPI',1,0)) kpi,
					count(1) total
					from (
					  select distinct district_code, district_name, cif_no, rule_group
					  from dqs_validate
					  where validate_status in ('incomplete','wrong')
					  and region_code = ?
					  {$op_query_string}
					) a
					group by district_code, district_name								
				";
				array_unshift($districts_in, $r->region_code);
				$districts = DB::select($districts_query, $districts_in);
				foreach ($districts as $d) {
					$branches_in = $operations_in;
					$branches_query = "
						select contact_branch_code, contact_branch_name, 
						sum(iif(rule_group = 'Cleansing',1,0)) cleansing,
						sum(iif(rule_group = 'Mapping',1,0)) mapping,
						sum(iif(rule_group = 'Matching',1,0)) matching,
						sum(iif(rule_group = 'Edit',1,0)) edit,
						sum(iif(rule_group = 'KPI',1,0)) kpi,
						count(1) total
						from (
						  select distinct contact_branch_code, contact_branch_name, cif_no, rule_group
						  from dqs_validate
						  where validate_status in ('incomplete','wrong')
						  and district_code = ?
						  {$op_query_string}
						) a
						group by contact_branch_code, contact_branch_name								
					";
					array_unshift($branches_in, $d->district_code);
					$branches = DB::select($branches_query, $branches_in);		
					$d->branches = $branches;
				}
				$r->districts = $districts;
			}
			$o->regions = $regions;
		}
		return response()->json($operations);
	}
	
	public function no_progress_export(Request $request)
	{
		$user = DQSUser::find(Auth::user()->personnel_id);
		$role = DQSRole::find($user->role_id);
		if (empty($role)) {
			return response()->json(['status' => 400, 'Role not found for current user.']);
		}
		
		$operations_in = array();
		$op_query_string = '';
		empty($request->operation_code) ?: ($op_query_string .= ' and operation_code = ? ' AND $operations_in[] = $request->operation_code);
		empty($request->region_code) ?: ($op_query_string .= ' and region_code = ? ' AND $operations_in[] = $request->region_code);	
		empty($request->district_code) ?: ($op_query_string .= ' and district_code = ? ' AND $operations_in[] = $request->district_code);	
		if ($role->all_branch_flag == 1) {
			empty($request->contact_branch_code) ?: ($op_query_string .= ' and contact_branch_code = ? ' AND $operations_in[] = $request->contact_branch_code);	
		} else {
			// $op_query_string .= ' and contact_branch_code in (select brcd from dqs_branch where ccdef = ?) ';
			// $operations_in[] = $user->revised_cost_center;	
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
						$op_query_string .= ' and contact_branch_code in (
							select distinct c.brcd
							from dqs_branch_operation a
							left outer join dqs_region b
							on a.operation_id = b.operation_id
							left outer join dqs_branch c
							on b.region_code = c.region	
							where c.ccdef = ?		
						)';
						$operations_in[] = $user->revised_cost_center;							
					} else {	
						$op_query_string .= ' and contact_branch_code in (
							select distinct c.brcd
							from dqs_branch_operation a
							left outer join dqs_region b
							on a.operation_id = b.operation_id
							left outer join dqs_branch c
							on b.region_code = c.region	
							where c.dist = ?		
						)';
						$operations_in[] = $checkdist[0]->dist;							
					}				
				} else {		
					$op_query_string .= ' and contact_branch_code in (
						select distinct c.brcd
						from dqs_branch_operation a
						left outer join dqs_region b
						on a.operation_id = b.operation_id
						left outer join dqs_branch c
						on b.region_code = c.region	
						where c.region = ?				
					)';
					$operations_in[] = $checkregion[0]->region;						
				}			
			} else {
				$op_query_string .= ' and contact_branch_code in (
					select distinct c.brcd
					from dqs_branch_operation a
					left outer join dqs_region b
					on a.operation_id = b.operation_id
					left outer join dqs_branch c
					on b.region_code = c.region	
					where a.operation_id = ?				
				)';
				$operations_in[] = $checkop[0]->operation_id;			
			}								
			empty($request->contact_branch_code) ?: ($op_query_string .= ' and contact_branch_code = ? ' AND $operations_in[] = $request->contact_branch_code);		
		}
		empty($request->year) ?: ($op_query_string .= ' and year = ? ' AND $operations_in[] = $request->year);	
		empty($request->month) ?: ($op_query_string .= ' and month_no = ? ' AND $operations_in[] = $request->month);	

		if ($request->status == '') {
		} else {
			$op_query_string .= ' and no_doc_flag = ? ';
			$operations_in[] = $request->status;
		}
		$operations_query = "
			select operation_code, operation_name, 
			sum(iif(rule_group = 'Cleansing',1,0)) cleansing,
			sum(iif(rule_group = 'Mapping',1,0)) mapping,
			sum(iif(rule_group = 'Matching',1,0)) matching,
			sum(iif(rule_group = 'Edit',1,0)) edit,
			sum(iif(rule_group = 'KPI',1,0)) kpi,
			count(1) total
			from (
			  select distinct operation_name, operation_code, cif_no, rule_group
			  from dqs_validate
			  where validate_status in ('incomplete','wrong')
			  {$op_query_string}
			) a
			group by operation_code, operation_name
		";

		$operations = DB::select($operations_query, $operations_in);
		//return $operations;
		foreach ($operations as $o) {
			$regions_in = $operations_in;
			$regions_query = "
				select region_code, region_name, 
				sum(iif(rule_group = 'Cleansing',1,0)) cleansing,
				sum(iif(rule_group = 'Mapping',1,0)) mapping,
				sum(iif(rule_group = 'Matching',1,0)) matching,
				sum(iif(rule_group = 'Edit',1,0)) edit,
				sum(iif(rule_group = 'KPI',1,0)) kpi,
				count(1) total
				from (
				  select distinct region_code, region_name, cif_no, rule_group
				  from dqs_validate
				  where validate_status in ('incomplete','wrong')
				  and operation_code = ?
				  {$op_query_string}				  
				) a
				group by region_code, region_name				
			";
			array_unshift($regions_in, $o->operation_code);
			$regions = DB::select($regions_query, $regions_in);
			foreach ($regions as $r) {
				$districts_in = $operations_in;
				$districts_query = "
					select district_code, district_name, 
					sum(iif(rule_group = 'Cleansing',1,0)) cleansing,
					sum(iif(rule_group = 'Mapping',1,0)) mapping,
					sum(iif(rule_group = 'Matching',1,0)) matching,
					sum(iif(rule_group = 'Edit',1,0)) edit,
					sum(iif(rule_group = 'KPI',1,0)) kpi,
					count(1) total
					from (
					  select distinct district_code, district_name, cif_no, rule_group
					  from dqs_validate
					  where validate_status in ('incomplete','wrong')
					  and region_code = ?
					  {$op_query_string}
					) a
					group by district_code, district_name								
				";
				array_unshift($districts_in, $r->region_code);
				$districts = DB::select($districts_query, $districts_in);
				foreach ($districts as $d) {
					$branches_in = $operations_in;
					$branches_query = "
						select contact_branch_code, contact_branch_name, 
						sum(iif(rule_group = 'Cleansing',1,0)) cleansing,
						sum(iif(rule_group = 'Mapping',1,0)) mapping,
						sum(iif(rule_group = 'Matching',1,0)) matching,
						sum(iif(rule_group = 'Edit',1,0)) edit,
						sum(iif(rule_group = 'KPI',1,0)) kpi,
						count(1) total
						from (
						  select distinct contact_branch_code, contact_branch_name, cif_no, rule_group
						  from dqs_validate
						  where validate_status in ('incomplete','wrong')
						  and district_code = ?
						  {$op_query_string}
						) a
						group by contact_branch_code, contact_branch_name								
					";
					array_unshift($branches_in, $d->district_code);
					$branches = DB::select($branches_query, $branches_in);		
					$d->branches = $branches;
				}
				$r->districts = $districts;
			}
			$o->regions = $regions;
		}
		$filename = "No_Progress_Report_" . date('dm') .  substr(date('Y') + 543,2,2);
		$x = Excel::create($filename, function($excel) use($operations, $filename) {
			$excel->sheet($filename, function($sheet) use($operations) {
				$sheet->appendRow(array('', '', 'Cleansing', 'Mapping', 'Matching', 'Edit', 'KPI', 'Total (Distinct)'));				
				foreach ($operations as $o) {
					$sheet->appendRow(array(
						$o->operation_name,
						'ผลรวม',
						$o->cleansing,
						$o->mapping,
						$o->matching,
						$o->edit,
						$o->kpi,
						$o->total
					));
					foreach ($o->regions as $r) {
						$sheet->appendRow(array(
							'--' . $r->region_name,
							'ผลรวม',
							$r->cleansing,
							$r->mapping,
							$r->matching,
							$r->edit,
							$r->kpi,
							$r->total
						));					
						foreach ($r->districts as $d) {
							$sheet->appendRow(array(
								'----' . $d->district_name,
								'ผลรวม',
								$d->cleansing,
								$d->mapping,
								$d->matching,
								$d->edit,
								$d->kpi,
								$d->total
							));
							$sheet->appendRow(array('------รหัสสาขา', 'ชื่อสาขา'));
							foreach ($d->branches as $b) {
								$sheet->appendRow(array(
									'------' . $b->contact_branch_code,
									$b->contact_branch_name,
									$b->cleansing,
									$b->mapping,
									$b->matching,
									$b->edit,
									$b->kpi,
									$b->total
								));									
							}
						}
					}
				}

			});

		})->export('xls');			
		
	}	
	
	public function progressed(Request $request)
	{
		$user = DQSUser::find(Auth::user()->personnel_id);
		$role = DQSRole::find($user->role_id);
		if (empty($role)) {
			return response()->json(['status' => 400, 'Role not found for current user.']);
		}
		
		$operations_in = array();
		$op_query_string = '';
		empty($request->operation_code) ?: ($op_query_string .= ' and operation_code = ? ' AND $operations_in[] = $request->operation_code);
		empty($request->region_code) ?: ($op_query_string .= ' and region_code = ? ' AND $operations_in[] = $request->region_code);	
		empty($request->district_code) ?: ($op_query_string .= ' and district_code = ? ' AND $operations_in[] = $request->district_code);	
		if ($role->all_branch_flag == 1) {
			empty($request->contact_branch_code) ?: ($op_query_string .= ' and contact_branch_code = ? ' AND $operations_in[] = $request->contact_branch_code);	
		} else {
			// $op_query_string .= ' and contact_branch_code in (select brcd from dqs_branch where ccdef = ?) ';
			// $operations_in[] = $user->revised_cost_center;		
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
						$op_query_string .= ' and contact_branch_code in (
							select distinct c.brcd
							from dqs_branch_operation a
							left outer join dqs_region b
							on a.operation_id = b.operation_id
							left outer join dqs_branch c
							on b.region_code = c.region	
							where c.ccdef = ?		
						)';
						$operations_in[] = $user->revised_cost_center;							
					} else {	
						$op_query_string .= ' and contact_branch_code in (
							select distinct c.brcd
							from dqs_branch_operation a
							left outer join dqs_region b
							on a.operation_id = b.operation_id
							left outer join dqs_branch c
							on b.region_code = c.region	
							where c.dist = ?		
						)';
						$operations_in[] = $checkdist[0]->dist;							
					}				
				} else {		
					$op_query_string .= ' and contact_branch_code in (
						select distinct c.brcd
						from dqs_branch_operation a
						left outer join dqs_region b
						on a.operation_id = b.operation_id
						left outer join dqs_branch c
						on b.region_code = c.region	
						where c.region = ?				
					)';
					$operations_in[] = $checkregion[0]->region;						
				}			
			} else {
				$op_query_string .= ' and contact_branch_code in (
					select distinct c.brcd
					from dqs_branch_operation a
					left outer join dqs_region b
					on a.operation_id = b.operation_id
					left outer join dqs_branch c
					on b.region_code = c.region	
					where a.operation_id = ?				
				)';
				$operations_in[] = $checkop[0]->operation_id;			
			}								
			empty($request->contact_branch_code) ?: ($op_query_string .= ' and contact_branch_code = ? ' AND $operations_in[] = $request->contact_branch_code);	
		}
		empty($request->year) ?: ($op_query_string .= ' and year = ? ' AND $operations_in[] = $request->year);	
		empty($request->month) ?: ($op_query_string .= ' and month_no = ? ' AND $operations_in[] = $request->month);	

		// if ($request->status == '') {
		// } else {
			// $op_query_string = ' and no_doc_flag = ? ';
			// $operations_in[] = $request->status;
		// }
		$operations_query = "
			select operation_code, operation_name, 
			sum(iif(rule_group = 'Cleansing',1,0)) cleansing,
			sum(iif(rule_group = 'Mapping',1,0)) mapping,
			sum(iif(rule_group = 'Matching',1,0)) matching,
			sum(iif(rule_group = 'Edit',1,0)) edit,
			sum(iif(rule_group = 'KPI',1,0)) kpi,
			count(1) total
			from (
			  select distinct operation_name, operation_code, cif_no, rule_group
			  from dqs_validate
			  where validate_status in ('complete','correct','transfer')
			  {$op_query_string}
			) a
			group by operation_code, operation_name
		";

		$operations = DB::select($operations_query, $operations_in);
		//return $operations;
		foreach ($operations as $o) {
			$regions_in = $operations_in;
			$regions_query = "
				select region_code, region_name, 
				sum(iif(rule_group = 'Cleansing',1,0)) cleansing,
				sum(iif(rule_group = 'Mapping',1,0)) mapping,
				sum(iif(rule_group = 'Matching',1,0)) matching,
				sum(iif(rule_group = 'Edit',1,0)) edit,
				sum(iif(rule_group = 'KPI',1,0)) kpi,
				count(1) total
				from (
				  select distinct region_code, region_name, cif_no, rule_group
				  from dqs_validate
				  where validate_status in ('complete','correct','transfer')
				  and operation_code = ?
				  {$op_query_string}				  
				) a
				group by region_code, region_name				
			";
			array_unshift($regions_in, $o->operation_code);
			$regions = DB::select($regions_query, $regions_in);
			foreach ($regions as $r) {
				$districts_in = $operations_in;
				$districts_query = "
					select district_code, district_name, 
					sum(iif(rule_group = 'Cleansing',1,0)) cleansing,
					sum(iif(rule_group = 'Mapping',1,0)) mapping,
					sum(iif(rule_group = 'Matching',1,0)) matching,
					sum(iif(rule_group = 'Edit',1,0)) edit,
					sum(iif(rule_group = 'KPI',1,0)) kpi,
					count(1) total
					from (
					  select distinct district_code, district_name, cif_no, rule_group
					  from dqs_validate
					  where validate_status in ('complete','correct','transfer')
					  and region_code = ?
					  {$op_query_string}
					) a
					group by district_code, district_name								
				";
				array_unshift($districts_in, $r->region_code);
				$districts = DB::select($districts_query, $districts_in);
				foreach ($districts as $d) {
					$branches_in = $operations_in;
					$branches_query = "
						select contact_branch_code, contact_branch_name, 
						sum(iif(rule_group = 'Cleansing',1,0)) cleansing,
						sum(iif(rule_group = 'Mapping',1,0)) mapping,
						sum(iif(rule_group = 'Matching',1,0)) matching,
						sum(iif(rule_group = 'Edit',1,0)) edit,
						sum(iif(rule_group = 'KPI',1,0)) kpi,
						count(1) total
						from (
						  select distinct contact_branch_code, contact_branch_name, cif_no, rule_group
						  from dqs_validate
						  where validate_status in ('complete','correct','transfer')
						  and district_code = ?
						  {$op_query_string}
						) a
						group by contact_branch_code, contact_branch_name								
					";
					array_unshift($branches_in, $d->district_code);
					$branches = DB::select($branches_query, $branches_in);		
					$d->branches = $branches;
				}
				$r->districts = $districts;
			}
			$o->regions = $regions;
		}
		return response()->json($operations);
	}
	
	public function progressed_export(Request $request)
	{
		$user = DQSUser::find(Auth::user()->personnel_id);
		$role = DQSRole::find($user->role_id);
		if (empty($role)) {
			return response()->json(['status' => 400, 'Role not found for current user.']);
		}
		
		$operations_in = array();
		$op_query_string = '';
		empty($request->operation_code) ?: ($op_query_string .= ' and operation_code = ? ' AND $operations_in[] = $request->operation_code);
		empty($request->region_code) ?: ($op_query_string .= ' and region_code = ? ' AND $operations_in[] = $request->region_code);	
		empty($request->district_code) ?: ($op_query_string .= ' and district_code = ? ' AND $operations_in[] = $request->district_code);	
		if ($role->all_branch_flag == 1) {
			empty($request->contact_branch_code) ?: ($op_query_string .= ' and contact_branch_code = ? ' AND $operations_in[] = $request->contact_branch_code);	
		} else {
			// $op_query_string .= ' and contact_branch_code in (select brcd from dqs_branch where ccdef = ?) ';
			// $operations_in[] = $user->revised_cost_center;			
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
						$op_query_string .= ' and contact_branch_code in (
							select distinct c.brcd
							from dqs_branch_operation a
							left outer join dqs_region b
							on a.operation_id = b.operation_id
							left outer join dqs_branch c
							on b.region_code = c.region	
							where c.ccdef = ?		
						)';
						$operations_in[] = $user->revised_cost_center;							
					} else {	
						$op_query_string .= ' and contact_branch_code in (
							select distinct c.brcd
							from dqs_branch_operation a
							left outer join dqs_region b
							on a.operation_id = b.operation_id
							left outer join dqs_branch c
							on b.region_code = c.region	
							where c.dist = ?		
						)';
						$operations_in[] = $checkdist[0]->dist;							
					}				
				} else {		
					$op_query_string .= ' and contact_branch_code in (
						select distinct c.brcd
						from dqs_branch_operation a
						left outer join dqs_region b
						on a.operation_id = b.operation_id
						left outer join dqs_branch c
						on b.region_code = c.region	
						where c.region = ?				
					)';
					$operations_in[] = $checkregion[0]->region;						
				}			
			} else {
				$op_query_string .= ' and contact_branch_code in (
					select distinct c.brcd
					from dqs_branch_operation a
					left outer join dqs_region b
					on a.operation_id = b.operation_id
					left outer join dqs_branch c
					on b.region_code = c.region	
					where a.operation_id = ?				
				)';
				$operations_in[] = $checkop[0]->operation_id;			
			}								
			empty($request->contact_branch_code) ?: ($op_query_string .= ' and contact_branch_code = ? ' AND $operations_in[] = $request->contact_branch_code);			
		}
		empty($request->year) ?: ($op_query_string .= ' and year = ? ' AND $operations_in[] = $request->year);	
		empty($request->month) ?: ($op_query_string .= ' and month_no = ? ' AND $operations_in[] = $request->month);	

		// if ($request->status == '') {
		// } else {
			// $op_query_string = ' and no_doc_flag = ? ';
			// $operations_in[] = $request->status;
		// }
		$operations_query = "
			select operation_code, operation_name, 
			sum(iif(rule_group = 'Cleansing',1,0)) cleansing,
			sum(iif(rule_group = 'Mapping',1,0)) mapping,
			sum(iif(rule_group = 'Matching',1,0)) matching,
			sum(iif(rule_group = 'Edit',1,0)) edit,
			sum(iif(rule_group = 'KPI',1,0)) kpi,
			count(1) total
			from (
			  select distinct operation_name, operation_code, cif_no, rule_group
			  from dqs_validate
			  where validate_status in ('complete','correct','transfer')
			  {$op_query_string}
			) a
			group by operation_code, operation_name
		";

		$operations = DB::select($operations_query, $operations_in);
		//return $operations;
		foreach ($operations as $o) {
			$regions_in = $operations_in;
			$regions_query = "
				select region_code, region_name, 
				sum(iif(rule_group = 'Cleansing',1,0)) cleansing,
				sum(iif(rule_group = 'Mapping',1,0)) mapping,
				sum(iif(rule_group = 'Matching',1,0)) matching,
				sum(iif(rule_group = 'Edit',1,0)) edit,
				sum(iif(rule_group = 'KPI',1,0)) kpi,
				count(1) total
				from (
				  select distinct region_code, region_name, cif_no, rule_group
				  from dqs_validate
				  where validate_status in ('complete','correct','transfer')
				  and operation_code = ?
				  {$op_query_string}				  
				) a
				group by region_code, region_name				
			";
			array_unshift($regions_in, $o->operation_code);
			$regions = DB::select($regions_query, $regions_in);
			foreach ($regions as $r) {
				$districts_in = $operations_in;
				$districts_query = "
					select district_code, district_name, 
					sum(iif(rule_group = 'Cleansing',1,0)) cleansing,
					sum(iif(rule_group = 'Mapping',1,0)) mapping,
					sum(iif(rule_group = 'Matching',1,0)) matching,
					sum(iif(rule_group = 'Edit',1,0)) edit,
					sum(iif(rule_group = 'KPI',1,0)) kpi,
					count(1) total
					from (
					  select distinct district_code, district_name, cif_no, rule_group
					  from dqs_validate
					  where validate_status in ('complete','correct','transfer')
					  and region_code = ?
					  {$op_query_string}
					) a
					group by district_code, district_name								
				";
				array_unshift($districts_in, $r->region_code);
				$districts = DB::select($districts_query, $districts_in);
				foreach ($districts as $d) {
					$branches_in = $operations_in;
					$branches_query = "
						select contact_branch_code, contact_branch_name, 
						sum(iif(rule_group = 'Cleansing',1,0)) cleansing,
						sum(iif(rule_group = 'Mapping',1,0)) mapping,
						sum(iif(rule_group = 'Matching',1,0)) matching,
						sum(iif(rule_group = 'Edit',1,0)) edit,
						sum(iif(rule_group = 'KPI',1,0)) kpi,
						count(1) total
						from (
						  select distinct contact_branch_code, contact_branch_name, cif_no, rule_group
						  from dqs_validate
						  where validate_status in ('complete','correct','transfer')
						  and district_code = ?
						  {$op_query_string}
						) a
						group by contact_branch_code, contact_branch_name								
					";
					array_unshift($branches_in, $d->district_code);
					$branches = DB::select($branches_query, $branches_in);		
					$d->branches = $branches;
				}
				$r->districts = $districts;
			}
			$o->regions = $regions;
		}
		$filename = "Progressed_Report_" . date('dm') .  substr(date('Y') + 543,2,2);
		$x = Excel::create($filename, function($excel) use($operations, $filename) {
			$excel->sheet($filename, function($sheet) use($operations) {
				$sheet->appendRow(array('', '', 'Cleansing', 'Mapping', 'Matching', 'Edit', 'KPI', 'Total (Distinct)'));				
				foreach ($operations as $o) {
					$sheet->appendRow(array(
						$o->operation_name,
						'ผลรวม',
						$o->cleansing,
						$o->mapping,
						$o->matching,
						$o->edit,
						$o->kpi,
						$o->total
					));
					foreach ($o->regions as $r) {
						$sheet->appendRow(array(
							'--' . $r->region_name,
							'ผลรวม',
							$r->cleansing,
							$r->mapping,
							$r->matching,
							$r->edit,
							$r->kpi,
							$r->total
						));					
						foreach ($r->districts as $d) {
							$sheet->appendRow(array(
								'----' . $d->district_name,
								'ผลรวม',
								$d->cleansing,
								$d->mapping,
								$d->matching,
								$d->edit,
								$d->kpi,
								$d->total
							));
							$sheet->appendRow(array('------รหัสสาขา', 'ชื่อสาขา'));
							foreach ($d->branches as $b) {
								$sheet->appendRow(array(
									'------' . $b->contact_branch_code,
									$b->contact_branch_name,
									$b->cleansing,
									$b->mapping,
									$b->matching,
									$b->edit,
									$b->kpi,
									$b->total
								));									
							}
						}
					}
				}

			});

		})->export('xls');			
		
	}		
	
	public function customer(Request $request)
	{
	
		$user = DQSUser::find(Auth::user()->personnel_id);
		$role = DQSRole::find($user->role_id);
		if (empty($role)) {
			return response()->json(['status' => 400, 'Role not found for current user.']);
		}
		
		$operations_in = array();
		$op_query_string = '';
		empty($request->operation_code) ?: ($op_query_string .= ' and d.operation_id = ? ' AND $operations_in[] = $request->operation_code);
		empty($request->region_code) ?: ($op_query_string .= ' and b.region = ? ' AND $operations_in[] = $request->region_code);	
		empty($request->district_code) ?: ($op_query_string .= ' and b.dist = ? ' AND $operations_in[] = $request->district_code);	
		if ($role->all_branch_flag == 1) {
			empty($request->contact_branch_code) ?: ($op_query_string .= ' and b.brcd = ? ' AND $operations_in[] = $request->contact_branch_code);	
		} else {
			// $op_query_string .= ' and contact_branch_code in (select brcd from dqs_branch where ccdef = ?) ';
			// $operations_in[] = $user->revised_cost_center;			
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
						$op_query_string .= ' and a.last_contact_branch in (
							select distinct c.brcd
							from dqs_branch_operation a
							left outer join dqs_region b
							on a.operation_id = b.operation_id
							left outer join dqs_branch c
							on b.region_code = c.region	
							where c.ccdef = ?		
						)';
						$operations_in[] = $user->revised_cost_center;							
					} else {	
						$op_query_string .= ' and a.last_contact_branch in (
							select distinct c.brcd
							from dqs_branch_operation a
							left outer join dqs_region b
							on a.operation_id = b.operation_id
							left outer join dqs_branch c
							on b.region_code = c.region	
							where c.dist = ?		
						)';
						$operations_in[] = $checkdist[0]->dist;							
					}				
				} else {		
					$op_query_string .= ' and a.last_contact_branch in (
						select distinct c.brcd
						from dqs_branch_operation a
						left outer join dqs_region b
						on a.operation_id = b.operation_id
						left outer join dqs_branch c
						on b.region_code = c.region	
						where c.region = ?				
					)';
					$operations_in[] = $checkregion[0]->region;						
				}			
			} else {
				$op_query_string .= ' and a.last_contact_branch in (
					select distinct c.brcd
					from dqs_branch_operation a
					left outer join dqs_region b
					on a.operation_id = b.operation_id
					left outer join dqs_branch c
					on b.region_code = c.region	
					where a.operation_id = ?				
				)';
				$operations_in[] = $checkop[0]->operation_id;			
			}								
			empty($request->contact_branch_code) ?: ($op_query_string .= ' and a.last_contact_branch = ? ' AND $operations_in[] = $request->contact_branch_code);			
		}

		$operations_query = "
			select d.operation_id operation_code, d.operation_name, sum(iif(type = 0 and grade = 'A',1,0)) idv_complete,
			sum(iif(type = 0 and isnull(grade,0) != 'A',1,0)) idv_incomplete,
			sum(iif(type = 0,1,0)) total_idv,
			sum(iif(type = 1 and grade = 'A',1,0)) corp_complete,
			sum(iif(type = 1 and isnull(grade,0) != 'A',1,0)) corp_incomplete,
			sum(iif(type = 1,1,0)) total_corp,
			sum(iif(type in (0,1) and grade = 'A',1,0)) all_complete,
			sum(iif(type in (0,1) and isnull(grade,0) != 'A',1,0)) all_incomplete,
			sum(iif(type in (0,1),1,0)) total_all
			from dqs_cust a
			left outer join dqs_branch b
			on a.last_contact_branch = b.brcd
			left outer join dqs_region c
			on b.region = c.region_code
			left outer join dqs_branch_operation d
			on c.operation_id = d.operation_id
			where cbs_flag = 1
			{$op_query_string}
			group by d.operation_id, d.operation_name	
		";

		$operations = DB::select($operations_query, $operations_in);
		//return $operations;
		foreach ($operations as $o) {
			$regions_in = $operations_in;
			$regions_query = "
				select b.region region_code, b.regdesc region_name, sum(iif(type = 0 and grade = 'A',1,0)) idv_complete,
				sum(iif(type = 0 and isnull(grade,0) != 'A',1,0)) idv_incomplete,
				sum(iif(type = 0,1,0)) total_idv,
				sum(iif(type = 1 and grade = 'A',1,0)) corp_complete,
				sum(iif(type = 1 and isnull(grade,0) != 'A',1,0)) corp_incomplete,
				sum(iif(type = 1,1,0)) total_corp,
				sum(iif(type in (0,1) and grade = 'A',1,0)) all_complete,
				sum(iif(type in (0,1) and isnull(grade,0) != 'A',1,0)) all_incomplete,
				sum(iif(type in (0,1),1,0)) total_all
				from dqs_cust a
				left outer join dqs_branch b
				on a.last_contact_branch = b.brcd
				left outer join dqs_region c
				on b.region = c.region_code
				left outer join dqs_branch_operation d
				on c.operation_id = d.operation_id
				where cbs_flag = 1
				and d.operation_id = ?
				{$op_query_string}
				group by b.region, b.regdesc			
			";
			array_unshift($regions_in, $o->operation_code);
			$regions = DB::select($regions_query, $regions_in);
			foreach ($regions as $r) {
				$districts_in = $operations_in;
				$districts_query = "
					select b.dist district_code, b.distdesc district_name, sum(iif(type = 0 and grade = 'A',1,0)) idv_complete,
					sum(iif(type = 0 and isnull(grade,0) != 'A',1,0)) idv_incomplete,
					sum(iif(type = 0,1,0)) total_idv,
					sum(iif(type = 1 and grade = 'A',1,0)) corp_complete,
					sum(iif(type = 1 and isnull(grade,0) != 'A',1,0)) corp_incomplete,
					sum(iif(type = 1,1,0)) total_corp,
					sum(iif(type in (0,1) and grade = 'A',1,0)) all_complete,
					sum(iif(type in (0,1) and isnull(grade,0) != 'A',1,0)) all_incomplete,
					sum(iif(type in (0,1),1,0)) total_all
					from dqs_cust a
					left outer join dqs_branch b
					on a.last_contact_branch = b.brcd
					left outer join dqs_region c
					on b.region = c.region_code
					left outer join dqs_branch_operation d
					on c.operation_id = d.operation_id
					where cbs_flag = 1
					and b.region = ?
					{$op_query_string}
					group by b.dist, b.distdesc								
				";
				array_unshift($districts_in, $r->region_code);
				$districts = DB::select($districts_query, $districts_in);
				foreach ($districts as $d) {
					$branches_in = $operations_in;
					$branches_query = "
						select b.brcd contact_branch_code, b.[desc] contact_branch_name, sum(iif(type = 0 and grade = 'A',1,0)) idv_complete,
						sum(iif(type = 0 and isnull(grade,0) != 'A',1,0)) idv_incomplete,
						sum(iif(type = 0,1,0)) total_idv,
						sum(iif(type = 1 and grade = 'A',1,0)) corp_complete,
						sum(iif(type = 1 and isnull(grade,0) != 'A',1,0)) corp_incomplete,
						sum(iif(type = 1,1,0)) total_corp,
						sum(iif(type in (0,1) and grade = 'A',1,0)) all_complete,
						sum(iif(type in (0,1) and isnull(grade,0) != 'A',1,0)) all_incomplete,
						sum(iif(type in (0,1),1,0)) total_all
						from dqs_cust a
						left outer join dqs_branch b
						on a.last_contact_branch = b.brcd
						left outer join dqs_region c
						on b.region = c.region_code
						left outer join dqs_branch_operation d
						on c.operation_id = d.operation_id
						where cbs_flag = 1
						and b.dist = ?
						{$op_query_string}
						group by b.brcd, b.[desc]									
					";
					array_unshift($branches_in, $d->district_code);
					$branches = DB::select($branches_query, $branches_in);		
					$d->branches = $branches;
				}
				$r->districts = $districts;
			}
			$o->regions = $regions;
		}
		return response()->json($operations);		
	}
	
	public function customer_export(Request $request)
	{
	
		$user = DQSUser::find(Auth::user()->personnel_id);
		$role = DQSRole::find($user->role_id);
		if (empty($role)) {
			return response()->json(['status' => 400, 'Role not found for current user.']);
		}
		
		$operations_in = array();
		$op_query_string = '';
		empty($request->operation_code) ?: ($op_query_string .= ' and d.operation_id = ? ' AND $operations_in[] = $request->operation_code);
		empty($request->region_code) ?: ($op_query_string .= ' and b.region = ? ' AND $operations_in[] = $request->region_code);	
		empty($request->district_code) ?: ($op_query_string .= ' and b.dist = ? ' AND $operations_in[] = $request->district_code);	
		if ($role->all_branch_flag == 1) {
			empty($request->contact_branch_code) ?: ($op_query_string .= ' and b.brcd = ? ' AND $operations_in[] = $request->contact_branch_code);	
		} else {
			// $op_query_string .= ' and contact_branch_code in (select brcd from dqs_branch where ccdef = ?) ';
			// $operations_in[] = $user->revised_cost_center;			
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
						$op_query_string .= ' and a.last_contact_branch in (
							select distinct c.brcd
							from dqs_branch_operation a
							left outer join dqs_region b
							on a.operation_id = b.operation_id
							left outer join dqs_branch c
							on b.region_code = c.region	
							where c.ccdef = ?		
						)';
						$operations_in[] = $user->revised_cost_center;							
					} else {	
						$op_query_string .= ' and a.last_contact_branch in (
							select distinct c.brcd
							from dqs_branch_operation a
							left outer join dqs_region b
							on a.operation_id = b.operation_id
							left outer join dqs_branch c
							on b.region_code = c.region	
							where c.dist = ?		
						)';
						$operations_in[] = $checkdist[0]->dist;							
					}				
				} else {		
					$op_query_string .= ' and a.last_contact_branch in (
						select distinct c.brcd
						from dqs_branch_operation a
						left outer join dqs_region b
						on a.operation_id = b.operation_id
						left outer join dqs_branch c
						on b.region_code = c.region	
						where c.region = ?				
					)';
					$operations_in[] = $checkregion[0]->region;						
				}			
			} else {
				$op_query_string .= ' and a.last_contact_branch in (
					select distinct c.brcd
					from dqs_branch_operation a
					left outer join dqs_region b
					on a.operation_id = b.operation_id
					left outer join dqs_branch c
					on b.region_code = c.region	
					where a.operation_id = ?				
				)';
				$operations_in[] = $checkop[0]->operation_id;			
			}								
			empty($request->contact_branch_code) ?: ($op_query_string .= ' and a.last_contact_branch = ? ' AND $operations_in[] = $request->contact_branch_code);			
		}

		$operations_query = "
			select d.operation_id operation_code, d.operation_name, sum(iif(type = 0 and grade = 'A',1,0)) idv_complete,
			sum(iif(type = 0 and isnull(grade,0) != 'A',1,0)) idv_incomplete,
			sum(iif(type = 0,1,0)) total_idv,
			sum(iif(type = 1 and grade = 'A',1,0)) corp_complete,
			sum(iif(type = 1 and isnull(grade,0) != 'A',1,0)) corp_incomplete,
			sum(iif(type = 1,1,0)) total_corp,
			sum(iif(type in (0,1) and grade = 'A',1,0)) all_complete,
			sum(iif(type in (0,1) and isnull(grade,0) != 'A',1,0)) all_incomplete,
			sum(iif(type in (0,1),1,0)) total_all
			from dqs_cust a
			left outer join dqs_branch b
			on a.last_contact_branch = b.brcd
			left outer join dqs_region c
			on b.region = c.region_code
			left outer join dqs_branch_operation d
			on c.operation_id = d.operation_id
			where cbs_flag = 1
			{$op_query_string}
			group by d.operation_id, d.operation_name	
		";

		$operations = DB::select($operations_query, $operations_in);
		//return $operations;
		foreach ($operations as $o) {
			$regions_in = $operations_in;
			$regions_query = "
				select b.region region_code, b.regdesc region_name, sum(iif(type = 0 and grade = 'A',1,0)) idv_complete,
				sum(iif(type = 0 and isnull(grade,0) != 'A',1,0)) idv_incomplete,
				sum(iif(type = 0,1,0)) total_idv,
				sum(iif(type = 1 and grade = 'A',1,0)) corp_complete,
				sum(iif(type = 1 and isnull(grade,0) != 'A',1,0)) corp_incomplete,
				sum(iif(type = 1,1,0)) total_corp,
				sum(iif(type in (0,1) and grade = 'A',1,0)) all_complete,
				sum(iif(type in (0,1) and isnull(grade,0) != 'A',1,0)) all_incomplete,
				sum(iif(type in (0,1),1,0)) total_all
				from dqs_cust a
				left outer join dqs_branch b
				on a.last_contact_branch = b.brcd
				left outer join dqs_region c
				on b.region = c.region_code
				left outer join dqs_branch_operation d
				on c.operation_id = d.operation_id
				where cbs_flag = 1
				and d.operation_id = ?
				{$op_query_string}
				group by b.region, b.regdesc			
			";
			array_unshift($regions_in, $o->operation_code);
			$regions = DB::select($regions_query, $regions_in);
			foreach ($regions as $r) {
				$districts_in = $operations_in;
				$districts_query = "
					select b.dist district_code, b.distdesc district_name, sum(iif(type = 0 and grade = 'A',1,0)) idv_complete,
					sum(iif(type = 0 and isnull(grade,0) != 'A',1,0)) idv_incomplete,
					sum(iif(type = 0,1,0)) total_idv,
					sum(iif(type = 1 and grade = 'A',1,0)) corp_complete,
					sum(iif(type = 1 and isnull(grade,0) != 'A',1,0)) corp_incomplete,
					sum(iif(type = 1,1,0)) total_corp,
					sum(iif(type in (0,1) and grade = 'A',1,0)) all_complete,
					sum(iif(type in (0,1) and isnull(grade,0) != 'A',1,0)) all_incomplete,
					sum(iif(type in (0,1),1,0)) total_all
					from dqs_cust a
					left outer join dqs_branch b
					on a.last_contact_branch = b.brcd
					left outer join dqs_region c
					on b.region = c.region_code
					left outer join dqs_branch_operation d
					on c.operation_id = d.operation_id
					where cbs_flag = 1
					and b.region = ?
					{$op_query_string}
					group by b.dist, b.distdesc								
				";
				array_unshift($districts_in, $r->region_code);
				$districts = DB::select($districts_query, $districts_in);
				foreach ($districts as $d) {
					$branches_in = $operations_in;
					$branches_query = "
						select b.brcd contact_branch_code, b.[desc] contact_branch_name, sum(iif(type = 0 and grade = 'A',1,0)) idv_complete,
						sum(iif(type = 0 and isnull(grade,0) != 'A',1,0)) idv_incomplete,
						sum(iif(type = 0,1,0)) total_idv,
						sum(iif(type = 1 and grade = 'A',1,0)) corp_complete,
						sum(iif(type = 1 and isnull(grade,0) != 'A',1,0)) corp_incomplete,
						sum(iif(type = 1,1,0)) total_corp,
						sum(iif(type in (0,1) and grade = 'A',1,0)) all_complete,
						sum(iif(type in (0,1) and isnull(grade,0) != 'A',1,0)) all_incomplete,
						sum(iif(type in (0,1),1,0)) total_all
						from dqs_cust a
						left outer join dqs_branch b
						on a.last_contact_branch = b.brcd
						left outer join dqs_region c
						on b.region = c.region_code
						left outer join dqs_branch_operation d
						on c.operation_id = d.operation_id
						where cbs_flag = 1
						and b.dist = ?
						{$op_query_string}
						group by b.brcd, b.[desc]									
					";
					array_unshift($branches_in, $d->district_code);
					$branches = DB::select($branches_query, $branches_in);		
					$d->branches = $branches;
				}
				$r->districts = $districts;
			}
			$o->regions = $regions;
		}
		
		$filename = "Customer_Report_" . date('dm') .  substr(date('Y') + 543,2,2);
		$x = Excel::create($filename, function($excel) use($operations, $filename) {
			$excel->sheet($filename, function($sheet) use($operations) {
				$sheet->appendRow(array('ประเภทลูกค้า', '', 'ลูกค้าบุคคล', '','', 'ลูกค้านิติบุคคล', '', '', 'รวม', '', ''));
				$sheet->appendRow(array('', '', 'สมบูรณ์', 'ไม่สมบูรณ์', 'ทั้งหมด', 'สมบูรณ์', 'ไม่สมบูรณ์', 'ทั้งหมด', 'สมบูรณ์', 'ไม่สมบูรณ์', 'ทั้งหมด'));		
				foreach ($operations as $o) {
					$sheet->appendRow(array(
						$o->operation_name,
						'ผลรวม',
						$o->idv_complete,
						$o->idv_incomplete,
						$o->total_idv,
						$o->corp_complete,
						$o->corp_incomplete,
						$o->total_corp,
						$o->all_complete,
						$o->all_incomplete,
						$o->total_all
					));
					foreach ($o->regions as $r) {
						$sheet->appendRow(array(
							'--' . $r->region_name,
							'ผลรวม',
							$r->idv_complete,
							$r->idv_incomplete,
							$r->total_idv,
							$r->corp_complete,
							$r->corp_incomplete,
							$r->total_corp,
							$r->all_complete,
							$r->all_incomplete,
							$r->total_all
						));					
						foreach ($r->districts as $d) {
							$sheet->appendRow(array(
								'----' . $d->district_name,
								'ผลรวม',
								$d->idv_complete,
								$d->idv_incomplete,
								$d->total_idv,
								$d->corp_complete,
								$d->corp_incomplete,
								$d->total_corp,
								$d->all_complete,
								$d->all_incomplete,
								$d->total_all
							));
							$sheet->appendRow(array('------รหัสสาขา', 'ชื่อสาขา'));
							foreach ($d->branches as $b) {
								$sheet->appendRow(array(
								'------' . $b->contact_branch_code,
								$b->contact_branch_name,
								$b->idv_complete,
								$b->idv_incomplete,
								$b->total_idv,
								$b->corp_complete,
								$b->corp_incomplete,
								$b->total_corp,
								$b->all_complete,
								$b->all_incomplete,
								$b->total_all
								));									
							}
						}
					}
				}

			});

		})->export('xls');					
	}	
	
	public function overdue_kpi(Request $request)
	{
		$user = DQSUser::find(Auth::user()->personnel_id);
		$role = DQSRole::find($user->role_id);
		if (empty($role)) {
			return response()->json(['status' => 400, 'Role not found for current user.']);
		}
		
		$operations_in = array();
		$op_query_string = '';
		empty($request->operation_code) ?: ($op_query_string .= ' and operation_code = ? ' AND $operations_in[] = $request->operation_code);
		empty($request->region_code) ?: ($op_query_string .= ' and region_code = ? ' AND $operations_in[] = $request->region_code);	
		empty($request->district_code) ?: ($op_query_string .= ' and district_code = ? ' AND $operations_in[] = $request->district_code);	
		if ($role->all_branch_flag == 1) {
			empty($request->contact_branch_code) ?: ($op_query_string .= ' and contact_branch_code = ? ' AND $operations_in[] = $request->contact_branch_code);	
		} else {
			// $op_query_string .= ' and contact_branch_code in (select brcd from dqs_branch where ccdef = ?) ';
			// $operations_in[] = $user->revised_cost_center;			
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
						$op_query_string .= ' and contact_branch_code in (
							select distinct c.brcd
							from dqs_branch_operation a
							left outer join dqs_region b
							on a.operation_id = b.operation_id
							left outer join dqs_branch c
							on b.region_code = c.region	
							where c.ccdef = ?		
						)';
						$operations_in[] = $user->revised_cost_center;							
					} else {	
						$op_query_string .= ' and contact_branch_code in (
							select distinct c.brcd
							from dqs_branch_operation a
							left outer join dqs_region b
							on a.operation_id = b.operation_id
							left outer join dqs_branch c
							on b.region_code = c.region	
							where c.dist = ?		
						)';
						$operations_in[] = $checkdist[0]->dist;							
					}				
				} else {		
					$op_query_string .= ' and contact_branch_code in (
						select distinct c.brcd
						from dqs_branch_operation a
						left outer join dqs_region b
						on a.operation_id = b.operation_id
						left outer join dqs_branch c
						on b.region_code = c.region	
						where c.region = ?				
					)';
					$operations_in[] = $checkregion[0]->region;						
				}			
			} else {
				$op_query_string .= ' and contact_branch_code in (
					select distinct c.brcd
					from dqs_branch_operation a
					left outer join dqs_region b
					on a.operation_id = b.operation_id
					left outer join dqs_branch c
					on b.region_code = c.region	
					where a.operation_id = ?				
				)';
				$operations_in[] = $checkop[0]->operation_id;			
			}								
			empty($request->contact_branch_code) ?: ($op_query_string .= ' and contact_branch_code = ? ' AND $operations_in[] = $request->contact_branch_code);			
		}

		$operations_query = "
			select operation_code, operation_name,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) = 1, 1, 0)) month_1,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) = 2, 1, 0)) month_2,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) = 3, 1, 0)) month_3,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) = 4, 1, 0)) month_4,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) = 5, 1, 0)) month_5,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) = 6, 1, 0)) month_6,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) = 7, 1, 0)) month_7,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) = 8, 1, 0)) month_8,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) = 9, 1, 0)) month_9,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) = 10, 1, 0)) month_10,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) = 11, 1, 0)) month_11,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) = 12, 1, 0)) month_12,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) > 12, 1, 0)) month_13,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) > 0, 1, 0)) total
			from dqs_validate_header
			where kpi_flag = 1 
			and complete_flag = 0
			{$op_query_string}
			group by operation_name, operation_code
		";

		$operations = DB::select($operations_query, $operations_in);
		//return $operations;
		foreach ($operations as $o) {
			$regions_in = $operations_in;
			$regions_query = "
				select region_code, region_name,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) = 1, 1, 0)) month_1,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) = 2, 1, 0)) month_2,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) = 3, 1, 0)) month_3,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) = 4, 1, 0)) month_4,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) = 5, 1, 0)) month_5,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) = 6, 1, 0)) month_6,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) = 7, 1, 0)) month_7,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) = 8, 1, 0)) month_8,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) = 9, 1, 0)) month_9,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) = 10, 1, 0)) month_10,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) = 11, 1, 0)) month_11,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) = 12, 1, 0)) month_12,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) > 12, 1, 0)) month_13,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) > 0, 1, 0)) total
				from dqs_validate_header
				where kpi_flag = 1 
				and complete_flag = 0
				and operation_code = ?
				{$op_query_string}
				group by region_code, region_name		
			";
			array_unshift($regions_in, $o->operation_code);
			$regions = DB::select($regions_query, $regions_in);
			foreach ($regions as $r) {
				$districts_in = $operations_in;
				$districts_query = "
					select district_code, district_name,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) = 1, 1, 0)) month_1,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) = 2, 1, 0)) month_2,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) = 3, 1, 0)) month_3,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) = 4, 1, 0)) month_4,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) = 5, 1, 0)) month_5,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) = 6, 1, 0)) month_6,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) = 7, 1, 0)) month_7,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) = 8, 1, 0)) month_8,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) = 9, 1, 0)) month_9,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) = 10, 1, 0)) month_10,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) = 11, 1, 0)) month_11,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) = 12, 1, 0)) month_12,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) > 12, 1, 0)) month_13,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) > 0, 1, 0)) total
					from dqs_validate_header
					where kpi_flag = 1 
					and complete_flag = 0
					and region_code = ?
					{$op_query_string}
					group by district_code, district_name						
				";
				array_unshift($districts_in, $r->region_code);
				$districts = DB::select($districts_query, $districts_in);
				foreach ($districts as $d) {
					$branches_in = $operations_in;
					$branches_query = "
						select contact_branch_code, contact_branch_name,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) = 1, 1, 0)) month_1,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) = 2, 1, 0)) month_2,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) = 3, 1, 0)) month_3,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) = 4, 1, 0)) month_4,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) = 5, 1, 0)) month_5,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) = 6, 1, 0)) month_6,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) = 7, 1, 0)) month_7,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) = 8, 1, 0)) month_8,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) = 9, 1, 0)) month_9,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) = 10, 1, 0)) month_10,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) = 11, 1, 0)) month_11,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) = 12, 1, 0)) month_12,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) > 12, 1, 0)) month_13,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) > 0, 1, 0)) total
						from dqs_validate_header
						where kpi_flag = 1 
						and complete_flag = 0
						and district_code = ?
						{$op_query_string}
						group by contact_branch_code, contact_branch_name							
					";
					array_unshift($branches_in, $d->district_code);
					$branches = DB::select($branches_query, $branches_in);		
					$d->branches = $branches;
				}
				$r->districts = $districts;
			}
			$o->regions = $regions;
		}
		return response()->json($operations);
	}	
	
	public function overdue_kpi_export(Request $request)
	{
		$user = DQSUser::find(Auth::user()->personnel_id);
		$role = DQSRole::find($user->role_id);
		if (empty($role)) {
			return response()->json(['status' => 400, 'Role not found for current user.']);
		}
		
		$operations_in = array();
		$op_query_string = '';
		empty($request->operation_code) ?: ($op_query_string .= ' and operation_code = ? ' AND $operations_in[] = $request->operation_code);
		empty($request->region_code) ?: ($op_query_string .= ' and region_code = ? ' AND $operations_in[] = $request->region_code);	
		empty($request->district_code) ?: ($op_query_string .= ' and district_code = ? ' AND $operations_in[] = $request->district_code);	
		if ($role->all_branch_flag == 1) {
			empty($request->contact_branch_code) ?: ($op_query_string .= ' and contact_branch_code = ? ' AND $operations_in[] = $request->contact_branch_code);	
		} else {
			// $op_query_string .= ' and contact_branch_code in (select brcd from dqs_branch where ccdef = ?) ';
			// $operations_in[] = $user->revised_cost_center;			
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
						$op_query_string .= ' and contact_branch_code in (
							select distinct c.brcd
							from dqs_branch_operation a
							left outer join dqs_region b
							on a.operation_id = b.operation_id
							left outer join dqs_branch c
							on b.region_code = c.region	
							where c.ccdef = ?		
						)';
						$operations_in[] = $user->revised_cost_center;							
					} else {	
						$op_query_string .= ' and contact_branch_code in (
							select distinct c.brcd
							from dqs_branch_operation a
							left outer join dqs_region b
							on a.operation_id = b.operation_id
							left outer join dqs_branch c
							on b.region_code = c.region	
							where c.dist = ?		
						)';
						$operations_in[] = $checkdist[0]->dist;							
					}				
				} else {		
					$op_query_string .= ' and contact_branch_code in (
						select distinct c.brcd
						from dqs_branch_operation a
						left outer join dqs_region b
						on a.operation_id = b.operation_id
						left outer join dqs_branch c
						on b.region_code = c.region	
						where c.region = ?				
					)';
					$operations_in[] = $checkregion[0]->region;						
				}			
			} else {
				$op_query_string .= ' and contact_branch_code in (
					select distinct c.brcd
					from dqs_branch_operation a
					left outer join dqs_region b
					on a.operation_id = b.operation_id
					left outer join dqs_branch c
					on b.region_code = c.region	
					where a.operation_id = ?				
				)';
				$operations_in[] = $checkop[0]->operation_id;			
			}								
			empty($request->contact_branch_code) ?: ($op_query_string .= ' and contact_branch_code = ? ' AND $operations_in[] = $request->contact_branch_code);		
		}

		$operations_query = "
			select operation_code, operation_name,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) = 1, 1, 0)) month_1,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) = 2, 1, 0)) month_2,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) = 3, 1, 0)) month_3,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) = 4, 1, 0)) month_4,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) = 5, 1, 0)) month_5,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) = 6, 1, 0)) month_6,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) = 7, 1, 0)) month_7,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) = 8, 1, 0)) month_8,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) = 9, 1, 0)) month_9,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) = 10, 1, 0)) month_10,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) = 11, 1, 0)) month_11,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) = 12, 1, 0)) month_12,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) > 12, 1, 0)) month_13,
			sum(iif(datediff(month,validate_date,SYSDATETIME()) > 0, 1, 0)) total
			from dqs_validate_header
			where kpi_flag = 1 
			and complete_flag = 0
			{$op_query_string}
			group by operation_name, operation_code
		";

		$operations = DB::select($operations_query, $operations_in);
		//return $operations;
		foreach ($operations as $o) {
			$regions_in = $operations_in;
			$regions_query = "
				select region_code, region_name,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) = 1, 1, 0)) month_1,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) = 2, 1, 0)) month_2,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) = 3, 1, 0)) month_3,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) = 4, 1, 0)) month_4,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) = 5, 1, 0)) month_5,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) = 6, 1, 0)) month_6,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) = 7, 1, 0)) month_7,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) = 8, 1, 0)) month_8,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) = 9, 1, 0)) month_9,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) = 10, 1, 0)) month_10,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) = 11, 1, 0)) month_11,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) = 12, 1, 0)) month_12,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) > 12, 1, 0)) month_13,
				sum(iif(datediff(month,validate_date,SYSDATETIME()) > 0, 1, 0)) total
				from dqs_validate_header
				where kpi_flag = 1 
				and complete_flag = 0
				and operation_code = ?
				{$op_query_string}
				group by region_code, region_name		
			";
			array_unshift($regions_in, $o->operation_code);
			$regions = DB::select($regions_query, $regions_in);
			foreach ($regions as $r) {
				$districts_in = $operations_in;
				$districts_query = "
					select district_code, district_name,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) = 1, 1, 0)) month_1,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) = 2, 1, 0)) month_2,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) = 3, 1, 0)) month_3,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) = 4, 1, 0)) month_4,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) = 5, 1, 0)) month_5,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) = 6, 1, 0)) month_6,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) = 7, 1, 0)) month_7,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) = 8, 1, 0)) month_8,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) = 9, 1, 0)) month_9,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) = 10, 1, 0)) month_10,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) = 11, 1, 0)) month_11,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) = 12, 1, 0)) month_12,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) > 12, 1, 0)) month_13,
					sum(iif(datediff(month,validate_date,SYSDATETIME()) > 0, 1, 0)) total
					from dqs_validate_header
					where kpi_flag = 1 
					and complete_flag = 0
					and region_code = ?
					{$op_query_string}
					group by district_code, district_name						
				";
				array_unshift($districts_in, $r->region_code);
				$districts = DB::select($districts_query, $districts_in);
				foreach ($districts as $d) {
					$branches_in = $operations_in;
					$branches_query = "
						select contact_branch_code, contact_branch_name,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) = 1, 1, 0)) month_1,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) = 2, 1, 0)) month_2,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) = 3, 1, 0)) month_3,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) = 4, 1, 0)) month_4,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) = 5, 1, 0)) month_5,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) = 6, 1, 0)) month_6,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) = 7, 1, 0)) month_7,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) = 8, 1, 0)) month_8,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) = 9, 1, 0)) month_9,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) = 10, 1, 0)) month_10,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) = 11, 1, 0)) month_11,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) = 12, 1, 0)) month_12,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) > 12, 1, 0)) month_13,
						sum(iif(datediff(month,validate_date,SYSDATETIME()) > 0, 1, 0)) total
						from dqs_validate_header
						where kpi_flag = 1 
						and complete_flag = 0
						and district_code = ?
						{$op_query_string}
						group by contact_branch_code, contact_branch_name							
					";
					array_unshift($branches_in, $d->district_code);
					$branches = DB::select($branches_query, $branches_in);		
					$d->branches = $branches;
				}
				$r->districts = $districts;
			}
			$o->regions = $regions;
		}
		$filename = "Overdue_KPI_Report_" . date('dm') .  substr(date('Y') + 543,2,2);
		$x = Excel::create($filename, function($excel) use($operations, $filename) {
			$excel->sheet($filename, function($sheet) use($operations) {
				$sheet->appendRow(array('งวดที่ค้าง (เดือน)', '', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '>12', 'รวม'));	
				foreach ($operations as $o) {
					$sheet->appendRow(array(
						$o->operation_name,
						'ผลรวม',
						$o->month_1,
						$o->month_2,
						$o->month_3,
						$o->month_4,
						$o->month_5,
						$o->month_6,
						$o->month_7,
						$o->month_8,
						$o->month_9,
						$o->month_10,
						$o->month_11,
						$o->month_12,
						$o->month_13,
						$o->total
					));
					foreach ($o->regions as $r) {
						$sheet->appendRow(array(
							'--' . $r->region_name,
							'ผลรวม',
							$r->month_1,
							$r->month_2,
							$r->month_3,
							$r->month_4,
							$r->month_5,
							$r->month_6,
							$r->month_7,
							$r->month_8,
							$r->month_9,
							$r->month_10,
							$r->month_11,
							$r->month_12,
							$r->month_13,
							$r->total
						));					
						foreach ($r->districts as $d) {
							$sheet->appendRow(array(
								'----' . $d->district_name,
								'ผลรวม',
								$d->month_1,
								$d->month_2,
								$d->month_3,
								$d->month_4,
								$d->month_5,
								$d->month_6,
								$d->month_7,
								$d->month_8,
								$d->month_9,
								$d->month_10,
								$d->month_11,
								$d->month_12,
								$d->month_13,
								$d->total
							));
							$sheet->appendRow(array('------รหัสสาขา', 'ชื่อสาขา'));
							foreach ($d->branches as $b) {
								$sheet->appendRow(array(
								'------' . $b->contact_branch_code,
								$b->contact_branch_name,
								$b->month_1,
								$b->month_2,
								$b->month_3,
								$b->month_4,
								$b->month_5,
								$b->month_6,
								$b->month_7,
								$b->month_8,
								$b->month_9,
								$b->month_10,
								$b->month_11,
								$b->month_12,
								$b->month_13,
								$b->total
								));									
							}
						}
					}
				}

			});

		})->export('xls');			
	}		
	
	public function merge_cif(Request $request)
	{
		$query = "
			select a.merge_id, a.cif_no, b.[desc] cust_type, a.citizen_id, a.own_branch_code, c.[desc] own_branch_name, a.contact_branch_code, e.[desc] contact_branch_name, d.province_name, a.cust_name, a.cust_surname
			from dqs_merge_cif a
			left outer join dqs_cust_type b
			on a.cust_type_code = b.gsbccode
			left outer join dqs_branch c
			on a.own_branch_code = c.brcd
			left outer join dqs_province d
			on a.provice_code = d.province_code	
			left outer join dqs_branch e
			on a.contact_branch_code = e.brcd
			where 1=1
		";
		
		$query_footer = "
			order by merge_id asc
		";
		
		$qinput = array();
		
		empty($request->cust_type) ?: ($query .= " and a.cust_type_code = ? " AND $qinput[] = $request->cust_type);
		empty($request->province) ?: ($query .= " and a.provice_code = ? " AND $qinput[] = $request->province);
		empty($request->name) ?: ($query .= " and a.cust_name like ? " AND $qinput[] = '%' . $request->name . '%');
		empty($request->surname) ?: ($query .= " and a.cust_surname like ? " AND $qinput[] = '%' . $request->surname . '%');
		
		$items = DB::select($query . $query_footer, $qinput);
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
	
	public function merge_cif_export(Request $request)
	{
		$query = "
			select a.merge_id, a.cif_no, b.[desc] cust_type, a.citizen_id, a.own_branch_code, c.[desc] own_branch_name, a.contact_branch_code, e.[desc] contact_branch_name, d.province_name, a.cust_name, a.cust_surname
			from dqs_merge_cif a
			left outer join dqs_cust_type b
			on a.cust_type_code = b.gsbccode
			left outer join dqs_branch c
			on a.own_branch_code = c.brcd
			left outer join dqs_province d
			on a.provice_code = d.province_code	
			left outer join dqs_branch e
			on a.contact_branch_code = e.brcd
			where 1=1
		";
		$query_footer = "
			order by merge_id asc
		";		
		$qinput = array();
		
		empty($request->cust_type) ?: ($query .= " and a.cust_type_code = ? " AND $qinput[] = $request->cust_type);
		empty($request->province) ?: ($query .= " and a.provice_code = ? " AND $qinput[] = $request->province);
		empty($request->name) ?: ($query .= " and a.cust_name like ? " AND $qinput[] = '%' . $request->name . '%');
		empty($request->surname) ?: ($query .= " and a.cust_surname like ? " AND $qinput[] = '%' . $request->surname . '%');
		
		$items = DB::select($query . $query_footer, $qinput);
		
		$filename = "Merge_CIF_" . date('dm') .  substr(date('Y') + 543,2,2);
		$x = Excel::create($filename, function($excel) use($items, $filename) {
			$excel->sheet($filename, function($sheet) use($items) {
				
				$sheet->appendRow(array('Merge ID', 'CIF No.', 'Cust Type', 'ID', 'Own Branch', 'Last Contact Branch', 'Province', 'Name', 'Surname'));
		
				foreach ($items as $i) {
					$sheet->appendRow(array(
						$i->merge_id,
						$i->cif_no, 
						$i->cust_type, 
						$i->citizen_id, 
						$i->own_branch_name, 
						$i->contact_branch_name,
						$i->province_name,
						$i->cust_name,
						$i->cust_surname
						));
				}
			});

		})->export('xls');		
	}

	public function kpi_result(Request $request)
	{
		$user = DQSUser::find(Auth::user()->personnel_id);
		$role = DQSRole::find($user->role_id);
		if (empty($role)) {
			return response()->json(['status' => 400, 'Role not found for current user.']);
		}
		
		$operations_in = array();
		$op_query_string = '';
		empty($request->operation_code) ?: ($op_query_string .= ' and operation_code = ? ' AND $operations_in[] = $request->operation_code);
		empty($request->region_code) ?: ($op_query_string .= ' and region_code = ? ' AND $operations_in[] = $request->region_code);	
		empty($request->district_code) ?: ($op_query_string .= ' and district_code = ? ' AND $operations_in[] = $request->district_code);	
		if ($role->all_branch_flag == 1) {
			empty($request->contact_branch_code) ?: ($op_query_string .= ' and contact_branch_code = ? ' AND $operations_in[] = $request->contact_branch_code);	
		} else {
			// $op_query_string .= ' and contact_branch_code in (select brcd from dqs_branch where ccdef = ?) ';
			// $operations_in[] = $user->revised_cost_center;	
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
						$op_query_string .= ' and contact_branch_code in (
							select distinct c.brcd
							from dqs_branch_operation a
							left outer join dqs_region b
							on a.operation_id = b.operation_id
							left outer join dqs_branch c
							on b.region_code = c.region	
							where c.ccdef = ?		
						)';
						$operations_in[] = $user->revised_cost_center;							
					} else {	
						$op_query_string .= ' and contact_branch_code in (
							select distinct c.brcd
							from dqs_branch_operation a
							left outer join dqs_region b
							on a.operation_id = b.operation_id
							left outer join dqs_branch c
							on b.region_code = c.region	
							where c.dist = ?		
						)';
						$operations_in[] = $checkdist[0]->dist;							
					}				
				} else {		
					$op_query_string .= ' and contact_branch_code in (
						select distinct c.brcd
						from dqs_branch_operation a
						left outer join dqs_region b
						on a.operation_id = b.operation_id
						left outer join dqs_branch c
						on b.region_code = c.region	
						where c.region = ?				
					)';
					$operations_in[] = $checkregion[0]->region;						
				}			
			} else {
				$op_query_string .= ' and contact_branch_code in (
					select distinct c.brcd
					from dqs_branch_operation a
					left outer join dqs_region b
					on a.operation_id = b.operation_id
					left outer join dqs_branch c
					on b.region_code = c.region	
					where a.operation_id = ?				
				)';
				$operations_in[] = $checkop[0]->operation_id;			
			}								
			empty($request->contact_branch_code) ?: ($op_query_string .= ' and contact_branch_code = ? ' AND $operations_in[] = $request->contact_branch_code);				
		}
		empty($request->year) ?: ($op_query_string .= ' and year = ? ' AND $operations_in[] = $request->year);	
		empty($request->month) ?: ($op_query_string .= ' and month_no <= ? ' AND $operations_in[] = $request->month);	

		$country_query = "
			select sum(nof_person_incomplete_cif) nof_person_incomplete_cif, 
			sum(nof_nodoc_incomplete_cif) nof_nodoc_incomplete_cif, 
			sum(nof_all_cif) nof_all_cif, 
			cast(round(cast(sum(nof_complete_cif) as numeric(15,2))/cast(sum(nof_all_cif) as numeric(15,2)) * 100, 2) as numeric(15,2)) percent_complete,
			cast(cast(round(cast(sum(nof_complete_cif) as numeric(15,2))/cast(sum(nof_all_cif) as numeric(15,2)) * 100, 2) as numeric(15,2))/
			(select count(distinct concat(year,month_no))
			from dqs_kpi_result
			where year = ?
			and month_no <= ?) as numeric(15,2)) as average_kpi
			from dqs_kpi_result
			where year = ?
			and month_no <= ?
		";		
		
		$country = DB::select($country_query, array($request->year, $request->month, $request->year, $request->month));
		
		$operations_query = "
			select operation_code, operation_name, 
			sum(nof_person_incomplete_cif) nof_person_incomplete_cif, 
			sum(nof_nodoc_incomplete_cif) nof_nodoc_incomplete_cif, 
			sum(nof_all_cif) nof_all_cif, 
			cast(round(cast(sum(nof_complete_cif) as numeric(15,2))/cast(sum(nof_all_cif) as numeric(15,2)) * 100, 2) as numeric(15,2)) percent_complete,
			cast(cast(round(cast(sum(nof_complete_cif) as numeric(15,2))/cast(sum(nof_all_cif) as numeric(15,2)) * 100, 2) as numeric(15,2))/
			(select count(distinct concat(year,month_no))
			from dqs_kpi_result
			where year = ?
			and month_no <= ?) as numeric(15,2)) as average_kpi
			from dqs_kpi_result
			where 1=1
			{$op_query_string}
			group by operation_code, operation_name	
		";
		array_unshift($operations_in, $request->month);
		array_unshift($operations_in, $request->year);
		$operations = DB::select($operations_query, $operations_in);
		//return $operations;
		foreach ($operations as $o) {
			$regions_in = $operations_in;
			$regions_query = "
				select region_code, region_name, 
				sum(nof_person_incomplete_cif) nof_person_incomplete_cif, 
				sum(nof_nodoc_incomplete_cif) nof_nodoc_incomplete_cif, 
				sum(nof_all_cif) nof_all_cif, 
				cast(round(cast(sum(nof_complete_cif) as numeric(15,2))/cast(sum(nof_all_cif) as numeric(15,2)) * 100, 2) as numeric(15,2)) percent_complete,
				cast(cast(round(cast(sum(nof_complete_cif) as numeric(15,2))/cast(sum(nof_all_cif) as numeric(15,2)) * 100, 2) as numeric(15,2))/
				(select count(distinct concat(year,month_no))
				from dqs_kpi_result
				where year = ?
				and month_no <= ?) as numeric(15,2)) as average_kpi
				from dqs_kpi_result
				where operation_code = ?
				{$op_query_string}
				group by region_code, region_name				
			";
			array_unshift($regions_in, $o->operation_code);
			array_unshift($regions_in, $request->month);
			array_unshift($regions_in, $request->year);			
			$regions = DB::select($regions_query, $regions_in);
			foreach ($regions as $r) {
				$districts_in = $operations_in;
				$districts_query = "
					select district_code, district_name, 
					sum(nof_person_incomplete_cif) nof_person_incomplete_cif, 
					sum(nof_nodoc_incomplete_cif) nof_nodoc_incomplete_cif, 
					sum(nof_all_cif) nof_all_cif, 
					cast(round(cast(sum(nof_complete_cif) as numeric(15,2))/cast(sum(nof_all_cif) as numeric(15,2)) * 100, 2) as numeric(15,2)) percent_complete,
					cast(cast(round(cast(sum(nof_complete_cif) as numeric(15,2))/cast(sum(nof_all_cif) as numeric(15,2)) * 100, 2) as numeric(15,2))/
					(select count(distinct concat(year,month_no))
					from dqs_kpi_result
					where year = ?
					and month_no <= ?) as numeric(15,2)) as average_kpi
					from dqs_kpi_result
					where region_code = ?
					{$op_query_string}
					group by district_code, district_name										
				";
				array_unshift($districts_in, $r->region_code);
				array_unshift($districts_in, $request->month);
				array_unshift($districts_in, $request->year);					
				$districts = DB::select($districts_query, $districts_in);
				foreach ($districts as $d) {
					$branches_in = $operations_in;
					$branches_query = "
						select contact_branch_code, contact_branch_name, 
						sum(nof_person_incomplete_cif) nof_person_incomplete_cif, 
						sum(nof_nodoc_incomplete_cif) nof_nodoc_incomplete_cif, 
						sum(nof_all_cif) nof_all_cif, 
						cast(round(cast(sum(nof_complete_cif) as numeric(15,2))/cast(sum(nof_all_cif) as numeric(15,2)) * 100, 2) as numeric(15,2)) percent_complete,
						cast(cast(round(cast(sum(nof_complete_cif) as numeric(15,2))/cast(sum(nof_all_cif) as numeric(15,2)) * 100, 2) as numeric(15,2))/
						(select count(distinct concat(year,month_no))
						from dqs_kpi_result
						where year = ?
						and month_no <= ?) as numeric(15,2)) as average_kpi
						from dqs_kpi_result
						where district_code = ?
						{$op_query_string}
						group by contact_branch_code, contact_branch_name								
					";
					array_unshift($branches_in, $d->district_code);
					array_unshift($branches_in, $request->month);
					array_unshift($branches_in, $request->year);						
					$branches = DB::select($branches_query, $branches_in);		
					$d->branches = $branches;
				}
				$r->districts = $districts;
			}
			$o->regions = $regions;
		}
		return response()->json(["country" => $country, "operations" => $operations]);
	}
	
	public function kpi_result_export(Request $request)
	{
		$user = DQSUser::find(Auth::user()->personnel_id);
		$role = DQSRole::find($user->role_id);
		if (empty($role)) {
			return response()->json(['status' => 400, 'Role not found for current user.']);
		}
		
		$operations_in = array();
		$op_query_string = '';
		empty($request->operation_code) ?: ($op_query_string .= ' and operation_code = ? ' AND $operations_in[] = $request->operation_code);
		empty($request->region_code) ?: ($op_query_string .= ' and region_code = ? ' AND $operations_in[] = $request->region_code);	
		empty($request->district_code) ?: ($op_query_string .= ' and district_code = ? ' AND $operations_in[] = $request->district_code);	
		if ($role->all_branch_flag == 1) {
			empty($request->contact_branch_code) ?: ($op_query_string .= ' and contact_branch_code = ? ' AND $operations_in[] = $request->contact_branch_code);	
		} else {
			// $op_query_string .= ' and contact_branch_code in (select brcd from dqs_branch where ccdef = ?) ';
			// $operations_in[] = $user->revised_cost_center;		
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
						$op_query_string .= ' and contact_branch_code in (
							select distinct c.brcd
							from dqs_branch_operation a
							left outer join dqs_region b
							on a.operation_id = b.operation_id
							left outer join dqs_branch c
							on b.region_code = c.region	
							where c.ccdef = ?		
						)';
						$operations_in[] = $user->revised_cost_center;							
					} else {	
						$op_query_string .= ' and contact_branch_code in (
							select distinct c.brcd
							from dqs_branch_operation a
							left outer join dqs_region b
							on a.operation_id = b.operation_id
							left outer join dqs_branch c
							on b.region_code = c.region	
							where c.dist = ?		
						)';
						$operations_in[] = $checkdist[0]->dist;							
					}				
				} else {		
					$op_query_string .= ' and contact_branch_code in (
						select distinct c.brcd
						from dqs_branch_operation a
						left outer join dqs_region b
						on a.operation_id = b.operation_id
						left outer join dqs_branch c
						on b.region_code = c.region	
						where c.region = ?				
					)';
					$operations_in[] = $checkregion[0]->region;						
				}			
			} else {
				$op_query_string .= ' and contact_branch_code in (
					select distinct c.brcd
					from dqs_branch_operation a
					left outer join dqs_region b
					on a.operation_id = b.operation_id
					left outer join dqs_branch c
					on b.region_code = c.region	
					where a.operation_id = ?				
				)';
				$operations_in[] = $checkop[0]->operation_id;			
			}								
			empty($request->contact_branch_code) ?: ($op_query_string .= ' and contact_branch_code = ? ' AND $operations_in[] = $request->contact_branch_code);			
		}
		empty($request->year) ?: ($op_query_string .= ' and year = ? ' AND $operations_in[] = $request->year);	
		empty($request->month) ?: ($op_query_string .= ' and month_no <= ? ' AND $operations_in[] = $request->month);	

		$country_query = "
			select sum(nof_person_incomplete_cif) nof_person_incomplete_cif, 
			sum(nof_nodoc_incomplete_cif) nof_nodoc_incomplete_cif, 
			sum(nof_all_cif) nof_all_cif, 
			cast(round(cast(sum(nof_complete_cif) as numeric(15,2))/cast(sum(nof_all_cif) as numeric(15,2)) * 100, 2) as numeric(15,2)) percent_complete,
			cast(cast(round(cast(sum(nof_complete_cif) as numeric(15,2))/cast(sum(nof_all_cif) as numeric(15,2)) * 100, 2) as numeric(15,2))/
			(select count(distinct concat(year,month_no))
			from dqs_kpi_result
			where year = ?
			and month_no <= ?) as numeric(15,2)) as average_kpi
			from dqs_kpi_result
			where year = ?
			and month_no <= ?
		";		
		
		$country = DB::select($country_query, array($request->year, $request->month, $request->year, $request->month));
		
		$operations_query = "
			select operation_code, operation_name, 
			sum(nof_person_incomplete_cif) nof_person_incomplete_cif, 
			sum(nof_nodoc_incomplete_cif) nof_nodoc_incomplete_cif, 
			sum(nof_all_cif) nof_all_cif, 
			cast(round(cast(sum(nof_complete_cif) as numeric(15,2))/cast(sum(nof_all_cif) as numeric(15,2)) * 100, 2) as numeric(15,2)) percent_complete,
			cast(cast(round(cast(sum(nof_complete_cif) as numeric(15,2))/cast(sum(nof_all_cif) as numeric(15,2)) * 100, 2) as numeric(15,2))/
			(select count(distinct concat(year,month_no))
			from dqs_kpi_result
			where year = ?
			and month_no <= ?) as numeric(15,2)) as average_kpi
			from dqs_kpi_result
			where 1=1
			{$op_query_string}
			group by operation_code, operation_name	
		";
		array_unshift($operations_in, $request->month);
		array_unshift($operations_in, $request->year);
		$operations = DB::select($operations_query, $operations_in);
		//return $operations;
		foreach ($operations as $o) {
			$regions_in = $operations_in;
			$regions_query = "
				select region_code, region_name, 
				sum(nof_person_incomplete_cif) nof_person_incomplete_cif, 
				sum(nof_nodoc_incomplete_cif) nof_nodoc_incomplete_cif, 
				sum(nof_all_cif) nof_all_cif, 
				cast(round(cast(sum(nof_complete_cif) as numeric(15,2))/cast(sum(nof_all_cif) as numeric(15,2)) * 100, 2) as numeric(15,2)) percent_complete,
				cast(cast(round(cast(sum(nof_complete_cif) as numeric(15,2))/cast(sum(nof_all_cif) as numeric(15,2)) * 100, 2) as numeric(15,2))/
				(select count(distinct concat(year,month_no))
				from dqs_kpi_result
				where year = ?
				and month_no <= ?) as numeric(15,2)) as average_kpi
				from dqs_kpi_result
				where operation_code = ?
				{$op_query_string}
				group by region_code, region_name				
			";
			array_unshift($regions_in, $o->operation_code);
			array_unshift($regions_in, $request->month);
			array_unshift($regions_in, $request->year);			
			$regions = DB::select($regions_query, $regions_in);
			foreach ($regions as $r) {
				$districts_in = $operations_in;
				$districts_query = "
					select district_code, district_name, 
					sum(nof_person_incomplete_cif) nof_person_incomplete_cif, 
					sum(nof_nodoc_incomplete_cif) nof_nodoc_incomplete_cif, 
					sum(nof_all_cif) nof_all_cif, 
					cast(round(cast(sum(nof_complete_cif) as numeric(15,2))/cast(sum(nof_all_cif) as numeric(15,2)) * 100, 2) as numeric(15,2)) percent_complete,
					cast(cast(round(cast(sum(nof_complete_cif) as numeric(15,2))/cast(sum(nof_all_cif) as numeric(15,2)) * 100, 2) as numeric(15,2))/
					(select count(distinct concat(year,month_no))
					from dqs_kpi_result
					where year = ?
					and month_no <= ?) as numeric(15,2)) as average_kpi
					from dqs_kpi_result
					where region_code = ?
					{$op_query_string}
					group by district_code, district_name										
				";
				array_unshift($districts_in, $r->region_code);
				array_unshift($districts_in, $request->month);
				array_unshift($districts_in, $request->year);					
				$districts = DB::select($districts_query, $districts_in);
				foreach ($districts as $d) {
					$branches_in = $operations_in;
					$branches_query = "
						select contact_branch_code, contact_branch_name, 
						sum(nof_person_incomplete_cif) nof_person_incomplete_cif, 
						sum(nof_nodoc_incomplete_cif) nof_nodoc_incomplete_cif, 
						sum(nof_all_cif) nof_all_cif, 
						cast(round(cast(sum(nof_complete_cif) as numeric(15,2))/cast(sum(nof_all_cif) as numeric(15,2)) * 100, 2) as numeric(15,2)) percent_complete,
						cast(cast(round(cast(sum(nof_complete_cif) as numeric(15,2))/cast(sum(nof_all_cif) as numeric(15,2)) * 100, 2) as numeric(15,2))/
						(select count(distinct concat(year,month_no))
						from dqs_kpi_result
						where year = ?
						and month_no <= ?) as numeric(15,2)) as average_kpi
						from dqs_kpi_result
						where district_code = ?
						{$op_query_string}
						group by contact_branch_code, contact_branch_name								
					";
					array_unshift($branches_in, $d->district_code);
					array_unshift($branches_in, $request->month);
					array_unshift($branches_in, $request->year);						
					$branches = DB::select($branches_query, $branches_in);		
					$d->branches = $branches;
				}
				$r->districts = $districts;
			}
			$o->regions = $regions;
		}
		$filename = "KPI_Result_Report_" . date('dm') .  substr(date('Y') + 543,2,2);
		$x = Excel::create($filename, function($excel) use($country, $operations, $filename) {
			$excel->sheet($filename, function($sheet) use($country, $operations) {
				$sheet->appendRow(array('', '', 'ไม่สมบูรณ์(บุคคล)', 'ไม่ส่งเอกสาร(นิติบุคคล)', 'ทั้งหมด', '%ความถูกต้อง', 'KPI เฉลี่ย'));	
				$sheet->appendRow(array('ทั้งประเทศ', 'ผลรวม', $country[0]->nof_person_incomplete_cif, $country[0]->nof_nodoc_incomplete_cif, $country[0]->nof_all_cif, $country[0]->percent_complete, $country[0]->average_kpi));	
				foreach ($operations as $o) {
					$sheet->appendRow(array(
						$o->operation_name,
						'ผลรวม',
						$o->nof_person_incomplete_cif,
						$o->nof_nodoc_incomplete_cif,
						$o->nof_all_cif,
						$o->percent_complete,
						$o->average_kpi
					));
					foreach ($o->regions as $r) {
						$sheet->appendRow(array(
							'--' . $r->region_name,
							'ผลรวม',
							$r->nof_person_incomplete_cif,
							$r->nof_nodoc_incomplete_cif,
							$r->nof_all_cif,
							$r->percent_complete,
							$r->average_kpi
						));					
						foreach ($r->districts as $d) {
							$sheet->appendRow(array(
								'----' . $d->district_name,
								'ผลรวม',
								$d->nof_person_incomplete_cif,
								$d->nof_nodoc_incomplete_cif,
								$d->nof_all_cif,
								$d->percent_complete,
								$d->average_kpi
							));
							$sheet->appendRow(array('------รหัสสาขา', 'ชื่อสาขา'));
							foreach ($d->branches as $b) {
								$sheet->appendRow(array(
								'------' . $b->contact_branch_code,
								$b->contact_branch_name,
								$b->nof_person_incomplete_cif,
								$b->nof_nodoc_incomplete_cif,
								$b->nof_all_cif,
								$b->percent_complete,
								$b->average_kpi
								));									
							}
						}
					}
				}

			});

		})->export('xls');	
	}	
	
}