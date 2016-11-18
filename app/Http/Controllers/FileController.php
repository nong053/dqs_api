<?php

namespace App\Http\Controllers;

use App\File;

use DB;
use Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BranchController extends Controller
{

	public function __construct()
	{

	   $this->middleware('jwt.auth');
	}
   
    public function index(Request $request)
    {
		$rpp = $request->rpp;
		if (empty($rpp)) {
			$rpp = 10;
		}
        $items = DB::table('dqs_file')->paginate($rpp);
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
            'file_name' => 'required|max:255',
			'source_file_path' => 'required|max:255',
			'target_file_path' => 'required|max:255',
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