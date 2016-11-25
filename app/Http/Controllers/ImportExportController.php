<?php

namespace App\Http\Controllers;


use App\SystemConfig;
use DB;
use Validator;
use Auth;
use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class ImportExportController extends Controller
{

	// public function __construct()
	// {

	   // $this->middleware('jwt.auth');
	// }
   
   public function list_cust_type()
   {
		$items = DB::select("
			select gsbccode, desc_1
			from dqs_cust_type
			where persflg = 1
		");
		return response()->json($items);
   }
   
   public function export_citizen(Request $request)
   {	
		try {
			$config = SystemConfig::firstOrFail();
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Please set System Configuration first.']);
		}		
		
		$outpath = $config->export_file_path;
		$limit = "select top ". $config->export_citizen_max_record . " ";
		$include_date = $config->export_include_date_flag;
		
		
		$items = DB::select($limit . "
			acn REFNO, zcizid PID, fname FNAME, lnm LNAME, dob DOB, sex SEX
			from dqs_cust
			order by REFNO desc		
		");
		
		
		return response()->json(date('dmY'));
		
		// $x = Excel::create('Filename', function($excel) {

			// $excel->sheet('Sheetname', function($sheet) {

				// $sheet->fromArray(array('data1', 'data2'));
				// $sheet->appendRow(array('data1', 'data23','data4'));
				// $sheet->appendRow(array('data1', 'data23','data4'));

			// });

		// })->export('xls');			
   }
   
    public function upload(Request $request)
    {
		$item = SystemConfig::firstOrFail();
		$item->kpi_date_m1 = 28;
		$item->save();
		$counter = 0;
		// foreach ($request->file as $f)
		// {
			// $counter += 1;
		// }
		//$test = $request->file()[0]->move('C:/dqsfiles/','mobile1.txt');
		// $test = [
			// "name" => $request->file()[0]->getClientOriginalName(),
			// "size" => $request->file()[0]->getClientSize(),
		// ];
		$read = array();
		foreach(file('C:/dqsfiles/mobile1.txt') as $line) {
			// loop with $line for each line of yourfile.txt
			$read[] = explode('|',$line);
		}		
		//$headers = array('Content-Type: application/text');		
		return response()->download('C:/dqsfiles/mobile1.txt','test.txt');
    }
	
	
}