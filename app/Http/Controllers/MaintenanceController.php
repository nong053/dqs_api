<?php

namespace App\Http\Controllers;


use DB;
use Validator;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class MaintenanceController extends Controller
{

	public function __construct()
	{

	   $this->middleware('jwt.auth');
	}
	
	function _group_by($array, $key) {
		$return = array();
		foreach($array as $val) {
			$return[$val[$key]][] = $val;
		}
		return $return;
	}	
	
	public function contact_type()
	{
		$items = DB::select("
			select distinct contact_type
			from dqs_file
		");
		return response()->json($items);
	}
	
	public function import_log(Request $request)
	{
		$query ="			
			select contact_type, file_name, total_record_footer_file, total_record_read_file, total_record_insert_table, start_date_time, end_date_time,
			cast(datediff(minute,start_date_time,end_date_time)/60 as varchar)+'h ' + cast(datediff(minute,start_date_time,end_date_time)%60 as varchar)+'m' processing_time
			from dqs_staging.dbo.stg_import_log
			where 1=1";			
		
		$qfooter = "
			order by contact_type asc, file_name asc
		";		
		
		$qinput = array();
		
		empty($request->contact_type) ?: ($query .= " and contact_type = ? " AND $qinput[] =  $request->contact_type);
		empty($request->import_date) ?: ($query .= " and cast(start_date_time as date) = cast(? as date) " AND $qinput[] = $request->import_date);
	

		// Get all items you want
		$items = DB::select($query . $qfooter, $qinput);

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
	
	public function reject_log(Request $request)
	{
		$query ="			
			select file_name, a.reject_date, cif_no, own_branch_code, c.desc_1 own_branch, a.contact_branch_code, d.desc_1 contact_branch, citizen_id, birth_date, reject_desc
			from dqs_reject_log a
			left outer join dqs_file b
			on a.file_id = b.file_id
			left outer join dqs_branch c
			on a.own_branch_code = c.brcd
			left outer join dqs_branch d
			on a.contact_branch_code = d.brcd
			where 1=1
		";			
			
		$qfooter = "
			order by contact_type asc, file_name asc, cif_no asc
		";		
		$qinput = array();
		
		
		empty($request->contact_type) ?: ($query .= " and contact_type = ? " AND $qinput[] =  $request->contact_type);
		if (empty($request->reject_start_date) || empty($request->reject_end_date)) {
		} else {
			$query .= " and a.reject_date between cast(? as date) and cast(? as date) ";
			$qinput[] = $request->reject_start_date;
			$qinput[] = $request->reject_end_date;				
		}
	

		// Get all items you want
		$items = DB::select($query . $qfooter, $qinput);

		// Get the current page from the url if it's not set default to 1
		empty($request->page) ? $page = 1 : $page = $request->page;
		
		// Number of items per page
		empty($request->rpp) ? $perPage = 10 : $perPage = $request->rpp;

		// Start displaying items from this number;
		$offSet = ($page * $perPage) - $perPage; // Start displaying items from this number

		// Get only the items you need using array_slice (only get 10 items since that's what you need)
		$itemsForCurrentPage = array_slice($items, $offSet, $perPage, false);

		//$group = $this->_group_by($itemsForCurrentPage, 'file_name');
		// Return the paginator with only 10 items but with the count of all items and set the it on the correct page
		$result = new LengthAwarePaginator($itemsForCurrentPage, count($items), $perPage, $page);			
		$groups = array();
		foreach ($itemsForCurrentPage as $item) {
			$key = $item->file_name;
			if (!isset($groups[$key])) {
				$groups[$key] = array(
					'items' => array($item),
					'count' => 1,
				);
			} else {
				$groups[$key]['items'][] = $item;
				$groups[$key]['count'] += 1;
			}
		}		
		$resultT = $result->toArray();
		$resultT['group'] = $groups;
		return response()->json($resultT);	
	}
	
	public function auto_personnel_name(Request $request)
	{
		$items = DB::select("
			select top 10 thai_full_name
			from dqs_user
			where thai_full_name like ?
		", array('%' . $request->q .'%'));
		return response()->json($items);
	}
	
	public function usage_log(Request $request)
	{
		$query ="			
			select a.usage_dttm, a.personnel_id, b.thai_full_name, c.menu_name, d.desc_1 branch_name
			from dqs_usage_log a
			left outer join dqs_user b
			on a.personnel_id = b.personnel_id
			left outer join dqs_menu c
			on a.menu_id = c.menu_id
			left outer join dqs_branch d
			on b.branch_code = d.brcd
			where 1=1
		";			
			
		$qfooter = "
			order by branch_name asc, usage_dttm asc, personnel_id asc
		";		
		$qinput = array();
		
		
		empty($request->branch_code) ?: ($query .= " and b.branch_code = ? " AND $qinput[] =  $request->branch_code);
		empty($request->personnel_name) ?: ($query .= " and b.thai_full_name like ? " AND  $qinput[] = '%' . $request->personnel_name . '%');
		if (empty($request->usage_start_date) || empty($request->usage_end_date)) {
		} else {
			$query .= " and cast(a.usage_dttm as date) between cast(? as date) and cast(? as date) ";
			$qinput[] = $request->usage_start_date;
			$qinput[] = $request->usage_end_date;				
		}
	

		// Get all items you want
		$items = DB::select($query . $qfooter, $qinput);

		// Get the current page from the url if it's not set default to 1
		empty($request->page) ? $page = 1 : $page = $request->page;
		
		// Number of items per page
		empty($request->rpp) ? $perPage = 10 : $perPage = $request->rpp;

		// Start displaying items from this number;
		$offSet = ($page * $perPage) - $perPage; // Start displaying items from this number

		// Get only the items you need using array_slice (only get 10 items since that's what you need)
		$itemsForCurrentPage = array_slice($items, $offSet, $perPage, false);

		//$group = $this->_group_by($itemsForCurrentPage, 'file_name');
		// Return the paginator with only 10 items but with the count of all items and set the it on the correct page
		$result = new LengthAwarePaginator($itemsForCurrentPage, count($items), $perPage, $page);			
		$groups = array();
		foreach ($itemsForCurrentPage as $item) {
			$key = $item->branch_name;
			if (!isset($groups[$key])) {
				$groups[$key] = array(
					'items' => array($item),
					'count' => 1,
				);
			} else {
				$groups[$key]['items'][] = $item;
				$groups[$key]['count'] += 1;
			}
		}		
		$resultT = $result->toArray();
		$resultT['group'] = $groups;
		return response()->json($resultT);	
	}	
}