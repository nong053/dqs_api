<?php

namespace App\Http\Controllers;

use App\Branch;

use Auth;
//use Adldap;
//use JWTAuth;
use DB;
use File;
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
	
	public function recal_kpi(Request $request)
	{
		set_time_limit(300);
		$param = ' -PLocaleUTF8 -R"Repo_WIN-MPNE686ADQV.txt"  -G"a38aebe8_e108_4ea5_9ed0_ee55bfd8837b" -t5 -T14 -LocaleGV -GV"$JobName=J0tQSUNhbGN1bGF0aW9uUHJvY2Vzcyc;$G_BranchCode='. base64_encode($request->ccdef) .'" -GV"MDEwMTAxMDEwMTA;"   -CtBatch -CmWIN-MPNE686ADQV -CaAdministrator -CjWIN-MPNE686ADQV -Cp3500';
		$batch = 'C:/Development/BatchScript/OP_KPICalculation_BJ.bat';
		$file_param = 'C:/Development/BatchScript/OP_KPICalculation_BJ.txt';
		File::Delete($file_param);	
		File::put($file_param, $param);
		$result = exec($batch);
		return response()->json(['status' => 200, 'data' => $result]);
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
