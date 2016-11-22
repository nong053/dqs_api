<?php

namespace App\Http\Controllers;

use App\Branch;

use Auth;
//use Adldap;
//use JWTAuth;
use DB;
use Validator;
use Excel;
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
        $branches = DB::table('dqs_branch')->paginate($rpp);
		return response()->json($branches);
    }
	
	public function update(Request $request)
	{
		// try {
			// $item = Branch::findOrFail($brcd);
		// } catch (ModelNotFoundException $e) {
			// return response()->json(['status' => 404, 'data' => 'Branch not found.']);
		// }
		
        // $validator = Validator::make($request->all(), [
            // 'brcd' => 'required|numeric',
            // 'desc' => 'required|max:255',
			// 'ccdef' => 'required|numeric',
			// 'region' => 'required|numeric',
			// 'regdesc' => 'required|max:255',
			// 'regcode' => 'required|numeric',
			// 'dist' => 'required|numeric',
			// 'distdesc' => 'required|max:255',
			// 'brstate' => 'required|numeric',
			// 'statedesc' => 'required|max:255',
			// 'close_flag' => 'required|boolean',
        // ]);

        // if ($validator->fails()) {
            // return response()->json(['status' => 400, 'data' => $validator->errors()]);
        // } else {
			// $item->fill($request->all());
			// $item->save();
		// }
		
		// return response()->json(['status' => 200, 'data' => $item]);
		
		$errors = array();
		$successes = array();
		
		$branches = $request->branches;
		
		if (empty($branches)) {
			return response()->json(['status' => 200, 'data' => ["success" => [], "error" => []]]);
		}
		
		foreach ($branches as $b) {
			$item = Branch::find($b["brcd"]);
			if (empty($item)) {
				$errors[] = ["brcd" => $b["brcd"]];
			} else {
				$item->close_flag = $b["close_flag"];
				$item->updated_by = Auth::user()->personnel_id;
				$item->save();
				$sitem = ["brcd" => $item->brcd, "close_flag" => $item->close_flag];
				$successes[] = $sitem;
			}
		}
		
		return response()->json(['status' => 200, 'data' => ["success" => $successes, "error" => $errors]]);
			
	}
	
	public function export()
	{
		$x = Excel::create('Filename', function($excel) {

			$excel->sheet('Sheetname', function($sheet) {

				$sheet->fromArray(array('data1', 'data2'));
				$sheet->appendRow(array('data1', 'data23','data4'));
				$sheet->appendRow(array('data1', 'data23','data4'));

			});

		})->export('xls');	
		return response()->json($x);
	}
	
}
