<?php

namespace App\Http\Controllers;

use App\Chauffeur;
use App\AttachFile;

use Auth;
use DB;
use File;
use Validator;
use Excel;
use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ChauffeurController extends Controller
{

	public function __construct()
	{

	   $this->middleware('jwt.auth');
	}
	
	public function index(Request $request)
	{		
		
		$items = DB::select("
			select * from chauffeur cf
			left join appraisal_item_result_doc aird 
			on cf.chauffeur_id=aird.chauffeur_doc_id
			order by cf.chauffeur_id
			
		");
		return response()->json($items);
	}
	
	

	
	
	
	public function upload_files(Request $request,$item_result_id )
	{
		
		
		
		$result = array();	
			
			$path = $_SERVER['DOCUMENT_ROOT'] . '/booking-car/api/public/chauffeur_files/' . $item_result_id . '/';
			foreach ($request->file() as $f) {
				$filename = iconv('UTF-8','windows-874',$f->getClientOriginalName());
				//$f->move($path,$filename);
				$f->move($path,$f->getClientOriginalName());
				//echo $filename;
				
				 $item = AttachFile::firstOrNew(array('doc_path' => 'chauffeur_files/' . $item_result_id . '/' . $f->getClientOriginalName()));
				
				 $item->chauffeur_doc_id = $item_result_id;
				 $item->created_by = Auth::id();
				
				// //print_r($item);
				 $item->save();
				$result[] = $item;
				//echo "hello".$f->getClientOriginalName();



			}
		
		return response()->json(['status' => 200, 'data' => $result]);
		//return response()->json(['status' => 200]);
	}


	public function upload_files_list(Request $request)
	{
		$items = DB::select("
			SELECT result_doc_id,doc_path 
			FROM appraisal_item_result_doc
			where  chauffeur_doc_id=?
			order by result_doc_id;
		", array($request->item_result_id));

		return response()->json($items);
	}


	public function delete_file(Request $request){

		//try {

			//$item = AttachFile::findOrFail($request->result_doc_id);
			$items = DB::select("
			SELECT result_doc_id,doc_path 
			FROM appraisal_item_result_doc
			where  chauffeur_doc_id=?
			order by result_doc_id;
		", array($request->item_result_id));


		// } catch (ModelNotFoundException $e) {
		// 	return response()->json(['status' => 404, 'data' => 'File not found.']);
		// }
		           //$_SERVER['DOCUMENT_ROOT'] . '/see_api/public/attach_files/' . $item_result_id . '/';

		foreach ($items as $i) {
			File::Delete($_SERVER['DOCUMENT_ROOT'] . '/booking-car/api/public/'.$i->doc_path);		
			$delPicture = DB::select("
			DELETE  
			FROM appraisal_item_result_doc
			where  result_doc_id=?
			", array($i->result_doc_id));
		}
		 File::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/booking-car/api/public/chauffeur_files/'.$request->item_result_id);

		return response()->json(['status' => 200]);

	}

	
	public function store(Request $request)
	{
	
		$validator = Validator::make($request->all(), [
			'chauffeur_name' => 'required|max:255|unique:chauffeur'
			
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 400, 'data' => $validator->errors()]);
		} else {
			$item = new Chauffeur;
			$item->fill($request->all());
			$item->save();
		}
	
		return response()->json(['status' => 200, 'data' => $item]);	
	}
	
	public function show($chauffeur_id)
	{
		try {
			$item = Chauffeur::findOrFail($chauffeur_id);

			
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Chauffeur not found.']);
		}
		return response()->json($item);
	}
	
	public function update(Request $request, $chauffeur_id)
	{
		try {
			$item = Chauffeur::findOrFail($chauffeur_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Chauffeur not found.']);
		}
		//'org_code' => 'required|max:15|unique:org,org_name,' . $org_id . ',org_id',
		$validator = Validator::make($request->all(), [

			'chauffeur_name' => 'required|max:255|unique:chauffeur,chauffeur_name,' . $chauffeur_id . ',chauffeur_id'
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 400, 'data' => $validator->errors()]);
		} else {

			//delete image file start.
		// 	$items = DB::select("
		// 	SELECT result_doc_id,doc_path 
		// 	FROM appraisal_item_result_doc
		// 	where  chauffeur_doc_id=?
		// 	order by result_doc_id;
		// ", array($request->item_result_id));

		// 	foreach ($items as $i) {
		// 	File::Delete($_SERVER['DOCUMENT_ROOT'] . '/booking-car/api/public/'.$i->doc_path);		
		// 	$delPicture = DB::select("
		// 	DELETE  
		// 	FROM appraisal_item_result_doc
		// 	where  chauffeur_doc_id=?
		// 	", array($i->result_doc_id));
		// 	}
		 	

		 	//delete image file end.
			$item->fill($request->all());
			$item->save();
		}
	
		return response()->json(['status' => 200, 'data' => $item]);
				
	}
	
	public function destroy($chauffeur_id)
	{
		try {
			$item = Chauffeur::findOrFail($chauffeur_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Chauffeur not found.']);
		}	

		try {
			$item->delete();
		} catch (Exception $e) {
			//print_r($e);
			if ($e->errorInfo[1] == 1451) {
				return response()->json(['status' => 400, 'data' => 'Cannot delete because this Chauffeur is in use.']);
			} else {
				return response()->json($e->errorInfo);
			}
		}
		
		return response()->json(['status' => 200]);
		
	}	
	public function report_chauffeur(Request $request){
		$items = DB::select("
			SELECT c.chauffeur_name,sum(datediff(b.date_to, b.date_from))as sum_trip,count(*) as count_trip
			FROM chauffeur_and_vehicle cav
			inner join chauffeur c on cav.chauffeur_id=c.chauffeur_id
			inner join booking b on cav.booking_id=b.booking_id
			where b.date_from between ? and ?
			group by cav.chauffeur_id

		", array($request->date_from,$request->date_to));
		return response()->json($items);
	}
}
