<?php

namespace App\Http\Controllers;

use App\CitizenImport;
use App\ImportLog;

use DB;
use Validator;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class CitizenImportController extends Controller
{

	public function __construct()
	{

	   $this->middleware('jwt.auth');
	}
	
	public function auto_npid(Request $request)
	{
		$items = DB::select("
			select top 10 npid
			from dqs_citizen_import
			where npid like ?
		", array("%" . $request->q . "%"));
		return response()->json($items);
	}
   
    public function index(Request $request)
    {
		$query ="			
			select ref_no cif, npid, ntitle, fname, lname, dob, sex, manual_add_flag
			from dqs_citizen_import
			where 1=1";			
		
		$qinput = array();
		
		empty($request->cif_no) ?: ($query .= " and ref_no like ? " AND $qinput[] = "%" . $request->cif_no . "%");
		empty($request->npid) ?: ($query .= " and npid = ? " AND $qinput[] = $request->npid);
		empty($request->flag_2) ?: ($query .= " and flag_1 = ? " AND $qinput[] = $request->flag_2);	
		empty($request->manual_add_flag) ?: ($query .= " and manual_add_flag = ? " AND $qinput[] = $request->manual_add_flag);	

		// Get all items you want
		$items = DB::select($query, $qinput);


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
	
	public function show($ref_no)
	{
		try {
			$item = CitizenImport::findOrFail($ref_no);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Citizen not found.']);
		}
		return response()->json($item);
	
	}
	
	public function store(Request $request)
	{
        $validator = Validator::make($request->all(), [
            'ref_no' => 'required|max:11|unique:dqs_citizen_import',
			'pid' => 'required|max:13',
			'fname' => 'required|max:30',
			'lname' => 'required|max:30',
			'dob' => 'required|numeric|digits_between:1,8',
			'sex' => 'required|numeric|digits_between:1,1',
			'ntitle' => 'required|max:30',
			'hno' => 'required|max:16',
			'moo' => 'numeric|digits_between:1,6',
			'trok' => 'max:40',
			'soi' => 'max:40',
			'thanon' => 'max:40',
			'thumbol' => 'required|max:40',
			'amphur' => 'required|max:40',
			'province' => 'required|max:40',
			'flag' => 'required|numeric|digits_between:1,2',
			'flag_1' => 'required|numeric|digits_between:1,2',
			'thai_flag' => 'required|boolean',
			'manual_add_flag' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'data' => $validator->errors()]);
        } else {
			$item = new CitizenImport;
			$item->fill($request->all());
			$item->npid = $request->pid;
			$item->nfname = $request->fname;
			$item->nlname = $request->lname;
			$item->ndob = $request->dob;
			$item->nsex = $request->sex;
			$item->manual_add_flag = 1;
			$item->created_by = Auth::user()->personnel_id;
			$item->updated_by = Auth::user()->personnel_id;
			$item->save();
		}
		
		return response()->json(['status' => 200, 'data' => $item]);	
	}
	
	public function update(Request $request, $ref_no)
	{
		try {
			$item = CitizenImport::findOrFail($ref_no);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Citizen not found.']);
		}	

		if ($item->manual_add_flag == 1) {
			$validator = Validator::make($request->all(), [
				'ref_no' => 'required|max:11|unique:dqs_citizen_import,ref_no,'.$request->ref_no.',ref_no',
				'pid' => 'required|max:13',
				'fname' => 'required|max:30',
				'lname' => 'required|max:30',
				'dob' => 'required|numeric|digits_between:1,8',
				'sex' => 'required|numeric|digits_between:1,1',
				'ntitle' => 'required|max:30',
				'hno' => 'required|max:16',
				'moo' => 'numeric|digits_between:1,6',
				'trok' => 'max:40',
				'soi' => 'max:40',
				'thanon' => 'max:40',
				'thumbol' => 'required|max:40',
				'amphur' => 'required|max:40',
				'province' => 'required|max:40',
				'flag' => 'required|numeric|digits_between:1,2',
				'flag_1' => 'required|numeric|digits_between:1,2',
				'thai_flag' => 'required|boolean',
				'manual_add_flag' => 'required|boolean'
			]);

			if ($validator->fails()) {
				return response()->json(['status' => 400, 'data' => $validator->errors()]);
			} else {
				$item->fill($request->all());
				$item->npid = $request->pid;
				$item->nfname = $request->fname;
				$item->nlname = $request->lname;
				$item->ndob = $request->dob;
				$item->nsex = $request->sex;
				$item->manual_add_flag = 1;
				$item->updated_by = Auth::user()->personnel_id;
				$item->save();
			}
					
		} else {
			$validator = Validator::make($request->all(), [
				'pid' => 'required|max:13',
				'dob' => 'required|numeric|digits_between:1,8',
				'sex' => 'required|numeric|digits_between:1,1',
				'thai_flag' => 'required|boolean'
			]);

			if ($validator->fails()) {
				return response()->json(['status' => 400, 'data' => $validator->errors()]);
			} else {
				$item->pid = $request->pid;
				$item->npid = $request->pid;
				$item->dob = $request->dob;
				$item->ndob = $request->dob;
				$item->sex = $request->sex;
				$item->nsex = $request->sex;
				$item->thai_flag = $request->thai_flag;
				$item->updated_by = Auth::user()->personnel_id;
				$item->save();
			}
							
		}

		return response()->json(['status' => 200, 'data' => $item]);	
				
	}
	
	
	public function destroy($ref_no)
	{
		try {
			$item = CitizenImport::findOrFail($ref_no);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Citizen not found.']);
		}	

		try {
			$item->delete();
		} catch (QueryException $e) {
			if ($e->errorInfo[1] == 547) {
				return response()->json(['status' => 400, 'data' => 'Foreign key conflict error. Please ensure that this Citizen is not referenced in another module.']);
			} else {
				return response()->json($e);
			}
		}
		
		return response()->json(['status' => 200]);
		
	}	
	
}