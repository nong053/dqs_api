<?php

namespace App\Http\Controllers;


use App\SystemConfig;
use DB;
use Validator;
use Auth;
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