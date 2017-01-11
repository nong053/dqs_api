<?php

namespace App\Http\Controllers;

use App\Customer;
use App\SystemConfig;
use App\CitizenImport;
use App\ImportLog;

use DB;
use Validator;
use Auth;
use Excel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\ImportCitizenJob;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class ImportExportController extends Controller
{

	public function __construct()
	{

	   $this->middleware('jwt.auth');
	}
   
   public function list_cust_type()
   {
		$items = DB::select("
			select gsbccode, [desc]
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
		$limit = $config->export_citizen_max_record;
		$include_date = $config->export_include_date_flag;
		$filenamemaster = "N" . date('dm') .  substr(date('Y') + 543,2,2);
		$filecount = 1;
		$filenum = "_" . str_pad($filecount,2,"0",STR_PAD_LEFT);
		
		if ($request->record_type == 1) {
			$query = "
				select distinct acn REFNO, zcizid PID, fname FNAME, lnm LNAME, dob DOB, sex SEX
				from dqs_cust
				where 1 = 1
			";		
			$qinput = array();
			empty($request->cust_type) ?: ($query .= " and zktbccode = ? " AND $qinput[] = $request->cust_type);
			empty($request->year) ?: ($query .= " and datepart(yyyy, dao) = ? " AND $qinput[] = $request->year);
			$items = DB::select($query, $qinput);

		} else {
			$query = "
				select distinct acn REFNO, zcizid PID, fname FNAME, lnm LNAME, dob DOB, sex SEX
				from dqs_cust
				where citizen_export_flag = 0 
			";		
			$qinput = array();
			empty($request->cust_type) ?: ($query .= " and zktbccode = ? " AND $qinput[] = $request->cust_type);		
			$items = DB::select($query, $qinput);
		}

		$page = 1;
		$perPage = $limit;
		$offSet = ($page * $perPage) - $perPage;
		$itemsForCurrentPage = array_slice($items, $offSet, $perPage, false);	
		
		if (empty($items)) {
			return 'No matching record found';
		}
		
		while (!empty($itemsForCurrentPage)) {
			$filename = $filenamemaster . "_" . str_pad($filecount,2,"0",STR_PAD_LEFT);
			$x = Excel::create($filename, function($excel) use($itemsForCurrentPage, $filename) {

				$excel->sheet($filename, function($sheet) use($itemsForCurrentPage) {
					$sheet->appendRow(array('REFNO', 'PID', 'FNAME', 'LNAME', 'DOB', 'SEX'));
					foreach ($itemsForCurrentPage as $i) {
						$sheet->appendRow(array(
							$i->REFNO, //iconv("UTF-8", "Windows-1252", $i->REFNO), 
							$i->PID, //iconv("UTF-8", "Windows-1252", $i->PID), 
							$i->FNAME, //iconv("UTF-8", "Windows-1252", $i->FNAME), 
							$i->LNAME, //iconv("UTF-8", "Windows-1252", $i->LNAME), 
							$i->DOB, //iconv("UTF-8", "Windows-1252", $i->DOB), 
							$i->SEX //iconv("UTF-8", "Windows-1252", $i->SEX)
							));
					}
				});
			//})->export('xls');
			})->store('xls',$outpath);	
			$filecount += 1;
			$page += 1;
			$perPage = $limit;
			$offSet = ($page * $perPage) - $perPage;
			$itemsForCurrentPage = array_slice($items, $offSet, $perPage, false);			
		}
		
		$clearold = exec('del "'.$outpath . $filenamemaster . '.zip"');
		$result = exec('"C:\\Program Files (x86)\\7-Zip\\7z.exe" a -tzip -sdel "' . $outpath . $filenamemaster . '.zip" "' . $outpath . 'N*.xls"');
		
		foreach ($items as $i) {
			Customer::where('acn', $i->REFNO)
				->update([
					'citizen_export_flag' => 1,
					'citizen_import_flag' => 0
				]);
		}
		
		//return response()->json(["status" => 200]);
		return response()->download($outpath . $filenamemaster . '.zip', $filenamemaster . '.zip');
   }
   
   public function import_citizen(Request $request)
   {
		
		try {
			$config = SystemConfig::firstOrFail();
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Please set System Configuration first.']);
		}		   
		
		$import_max_size = $config->import_max_file_size * 1024 * 1024;
		$importpath = $config->import_file_path;
		
		$successes = array();
		$errors = array();
		foreach ($request->file() as $f) {
			$start_at = date('Ymd H:i:s');
			if ($f->getClientSize() > $import_max_size) {
				$errors[] = [
					"filename" => $f->getClientOriginalName(),
					"size" => $f->getClientSize(),
					"errorMessage" => "This file size is greater than maximum file size limit."
				];
			} else {

				$f->move($importpath,$f->getClientOriginalName());				
				//set_time_limit(300);
				$filelocation = $importpath.$f->getClientOriginalName();
				$this->dispatch(new ImportCitizenJob(Auth::user()->personnel_id, $importpath, $f->getClientOriginalName(), $filelocation, $start_at));
		
			}
		}
		


		return response()->json(["status" => 200, "error" => $errors]);
   }
   
   public function export_sms(Request $request)
   {	
		try {
			$config = SystemConfig::firstOrFail();
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Please set System Configuration first.']);
		}		
		
		$outpath = $config->export_file_path;
		$limit = $config->export_citizen_max_record;
		$include_date = $config->export_include_date_flag;
		if ($include_date == 1) {
			$filenamemaster = "exp_cif_mobile_" . date('dm') .  substr(date('Y') + 543,2,2) . date('His');
		} else {
			$filenamemaster = "exp_cif_mobile_" . date('dm') .  substr(date('Y') + 543,2,2);
		}
		
		$filecount = 1;
		$filenum = "_" . str_pad($filecount,2,"0",STR_PAD_LEFT);
		
		if (empty($request->conditions)) {
			$items = DB::select("
				select distinct ACN, XNAME, APH
				from dqs_cust
				order by ACN asc
			");
		} else {
			$query = "
				select distinct ACN, XNAME, APH
				from dqs_cust
				where " . $request->conditions . " order by ACN asc";
			try {
				$items = DB::select($query);			
			} catch (QueryException $e) {
				//return response()->json(['status' => 400, 'data' => 'There is an error in query syntax.']);
				return 'There is an error in your query syntax.';
			}
		}
		
		$page = 1;
		$perPage = $limit;
		$offSet = ($page * $perPage) - $perPage;
		$itemsForCurrentPage = array_slice($items, $offSet, $perPage, false);		
		
		if (empty($items)) {
			return 'No matching record found';
		}		
		
		while (!empty($itemsForCurrentPage)) {
			$filename = $filenamemaster . "_" . str_pad($filecount,2,"0",STR_PAD_LEFT);
			$x = Excel::create($filename, function($excel) use($itemsForCurrentPage, $filename) {

				$excel->sheet($filename, function($sheet) use($itemsForCurrentPage) {
					$sheet->appendRow(array('ACN', 'XNAME', 'APH'));
					foreach ($itemsForCurrentPage as $i) {
						$sheet->appendRow(array(
							$i->ACN, //iconv("UTF-8", "Windows-1252", $i->ACN), 
							$i->XNAME, //iconv("UTF-8", "Windows-1252", $i->XNAME), 
							$i->APH //iconv("UTF-8", "Windows-1252", $i->APH)
							));
					}
				});

			})->store('xls',$outpath);	
			//})->export('xls');
			$filecount += 1;
			$page += 1;
			$perPage = $limit;
			$offSet = ($page * $perPage) - $perPage;
			$itemsForCurrentPage = array_slice($items, $offSet, $perPage, false);			
		}
		$clearold = exec('del "'.$outpath . $filenamemaster . '.zip"');
		$result = exec('"C:\\Program Files (x86)\\7-Zip\\7z.exe" a -tzip -sdel "' . $outpath . $filenamemaster . '.zip" "' . $outpath . 'exp*.xls"');
		
		//return response()->json(["status" => 200]);
		return response()->download($outpath . $filenamemaster . '.zip', $filenamemaster . '.zip');
   }   
   
   
   public function import_sms(Request $request)
   {
		try {
			$config = SystemConfig::firstOrFail();
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Please set System Configuration first.']);
		}		   
		
		$import_max_size = $config->import_max_file_size * 1024 * 1024;
		$importpath = $config->import_file_path;
		
		$successes = array();
		$errors = array();
		foreach ($request->file() as $f) {
			if ($f->getClientSize() > $import_max_size) {
				$errors[] = [
					"filename" => $f->getClientOriginalName(),
					"size" => $f->getClientSize(),
					"errorMessage" => "This file size is greater than maximum file size limit."
				];
			} else {

				$f->move($importpath,$f->getClientOriginalName());				
				
				foreach(file($importpath.$f->getClientOriginalName()) as $line) {
					//$item = explode('|',$line);
					 Customer::where("aph",trim($line))->update(["aph_use_flag" => 0]);
				}				
				rename($importpath.$f->getClientOriginalName(), $importpath."archive/".$f->getClientOriginalName());
				$successes[] = [
					"filename" => $f->getClientOriginalName()
				];
			}
		}
		

		return response()->json(["status" => 200, "success" => $successes, "error" => $errors]);
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