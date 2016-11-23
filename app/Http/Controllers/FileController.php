<?php

namespace App\Http\Controllers;

use App\File;

use DB;
use Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class FileController extends Controller
{

	public function __construct()
	{

	   $this->middleware('jwt.auth');
	}
   
    public function index(Request $request)
    {
		if (empty($request->search_all)) {
			$query = "			
				select file_id, processing_seq, file_name, source_file_path, target_file_path, contact_type, kpi_flag, last_contact_flag, source_file_delete_flag, nof_date_delete
				from dqs_file
				order by processing_seq asc
			";					

			// Get all items you want
			$items = DB::select($query);
		} else {
			$q = "%" . $request->search_all . "%";
		//	$qflag = $request->search_all;
			$items = DB::select("
				select file_id, processing_seq, file_name, source_file_path, target_file_path, contact_type, kpi_flag, last_contact_flag, source_file_delete_flag, nof_date_delete
				from dqs_file
				where processing_seq like ?
				or file_name like ?
				or source_file_path like ?
				or target_file_path like ?
				or contact_type like ?
				or nof_date_delete like ?
				order by processing_seq asc
			", array($q, $q, $q, $q, $q, $q));

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
	
	public function show($file_id)
	{
		try {
			$item = File::findOrFail($file_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'File not found.']);
		}
		return response()->json($item);	
	}
	
	public function contact_type_list()
	{
		$items = DB::select("
			select distinct contact_type
			from dqs_file
		");
		return response()->json($items);	
	}
	
	public function update(Request $request, $file_id)
	{
		try {
			$item = File::findOrFail($file_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'File not found.']);
		}
		
        $validator = Validator::make($request->all(), [
            'processing_seq' => 'required|integer',
			'contact_type' => 'required|max:255',
			'kpi_flag' => 'required|boolean',
			'last_contact_flag' => 'required|boolean',
			'source_file_delete_flag' => 'required|boolean',
			'nof_date_delete' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'data' => $validator->errors()]);
        } else {
			$item->fill($request->all());
			$item->save();
		}
		
		return response()->json(['status' => 200, 'data' => $item]);
		
	}
}