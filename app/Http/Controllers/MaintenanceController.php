<?php

namespace App\Http\Controllers;


use DB;
use Validator;
use Auth;
use Excel;
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
			select row_number() over (order by contact_type asc, file_name asc) seq, contact_type, file_instance, total_record_footer_file, total_record_read_file, total_record_insert_table, convert(varchar(10),start_date_time,126) as import_date, convert(varchar(20),start_date_time,120) as start_date_time, convert(varchar(20),end_date_time,120) end_date_time,
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
	
	public function export_import_log(Request $request)
	{
		$query ="			
			select contact_type, file_instance, total_record_footer_file, total_record_read_file, total_record_insert_table, convert(varchar(10),start_date_time,126) as import_date, convert(varchar(20),start_date_time,120) as start_date_time, convert(varchar(20),end_date_time,120) end_date_time,
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
		$filename = "Import_Log_" . date('dm') .  substr(date('Y') + 543,2,2);
		$x = Excel::create($filename, function($excel) use($items, $filename) {
			$excel->sheet($filename, function($sheet) use($items) {
				
				$sheet->appendRow(array('Contact Type', 'File Name', '#Footer Rows', '#Read Rows', '#Write Rows', 'Import Date', 'Start Date Time', 'End Date Time', 'Processing Time'));
				foreach ($items as $i) {
					$sheet->appendRow(array(
						$i->contact_type, 
						$i->file_instance, 
						$i->total_record_footer_file, 
						$i->total_record_read_file, 
						$i->total_record_insert_table, 
						$i->import_date,
						$i->start_date_time,
						$i->end_date_time,
						$i->processing_time,
						));
				}
			});

		})->export('xls');			
	}
	
	public function reject_log(Request $request)
	{
		$query ="			
			select row_number() over (order by b.contact_type asc, a.file_instance asc, cif_no asc) seq, concat(b.contact_type,' - ',a.file_instance) file_name, a.reject_date, cif_no, own_branch_code, c.[desc] own_branch, a.contact_branch_code, d.[desc] contact_branch, citizen_id, birth_date, reject_desc
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
			order by contact_type asc, a.file_instance asc, cif_no asc
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
	
	public function export_reject_log(Request $request)
	{
		$query ="			
			select contact_type, file_instance, a.reject_date, cif_no, own_branch_code, c.[desc] own_branch, a.contact_branch_code, d.[desc] contact_branch, citizen_id, birth_date, reject_desc
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
		$filename = "Reject_Log_" . date('dm') .  substr(date('Y') + 543,2,2);
		$x = Excel::create($filename, function($excel) use($items, $filename) {
			$excel->sheet($filename, function($sheet) use($items) {
			
				$sheet->appendRow(array('Contact Type - File Name', 'CIF No', 'Own Branch', 'Last Contact Branch', 'Citizen ID', 'Birth Date', 'Reject Detail'));
				foreach ($items as $i) {
					$sheet->appendRow(array(
						$i->contact_type . ' - ' . $i->file_instance, 
						$i->cif_no, 
						$i->own_branch, 
						$i->contact_branch, 
						$i->citizen_id, 
						$i->birth_date,
						$i->reject_desc
						));
				}
			});

		})->export('xls');				
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
			select row_number() over (order by d.[desc] asc, a.usage_dttm asc, a.personnel_id asc) seq, convert(varchar, a.usage_dttm, 120) usage_dttm, a.personnel_id, b.thai_full_name, c.menu_name, d.[desc] branch_name
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
			order by branch_name asc, a.usage_dttm asc, personnel_id asc
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
	public function export_usage_log (Request $request)
	{
		$query ="			
			select a.usage_dttm, a.personnel_id, b.thai_full_name, c.menu_name, d.[desc] branch_name
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
		$filename = "Usage_Log_" . date('dm') .  substr(date('Y') + 543,2,2);
		$x = Excel::create($filename, function($excel) use($items, $filename) {
			$excel->sheet($filename, function($sheet) use($items) {

				$sheet->appendRow(array('Branch', 'Usage Date', 'Personnel ID', 'Personnel Name', 'Menu'));
				foreach ($items as $i) {
					$sheet->appendRow(array(
						$i->branch_name, 
						$i->usage_dttm, 
						$i->personnel_id, 
						$i->thai_full_name, 
						$i->menu_name
						));
				}
			});

		})->export('xls');				
	}
}