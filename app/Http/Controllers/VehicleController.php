<?php

namespace App\Http\Controllers;

use App\Vehicle;
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

class VehicleController extends Controller
{

	public function __construct()
	{

	   $this->middleware('jwt.auth');
	}
	
	public function index(Request $request)
	{		
		
		$items = DB::select("
			select * from vehicle v
			left join appraisal_item_result_doc aird 
			on v.vehicle_id=aird.vehicle_doc_id
            left join vehicle_type vt
            on v.vehicle_type_id=vt.vehicle_type_id
			order by v.vehicle_id

		");
		return response()->json($items);
	}
	
	
	public function upload_files(Request $request,$item_result_id )
	{
		
		$result = array();	
			
			$path = $_SERVER['DOCUMENT_ROOT'] . '/boats_booking/api/public/vehicle_files/' . $item_result_id . '/';
			foreach ($request->file() as $f) {

				$filename = iconv('UTF-8','windows-874',$f->getClientOriginalName());
				$f->move($path,$f->getClientOriginalName());
				
				 $item = AttachFile::firstOrNew(array('doc_path' => 'vehicle_files/' . $item_result_id . '/' . $f->getClientOriginalName()));
				
				 $item->vehicle_doc_id = $item_result_id;
				 $item->created_by = Auth::id();
				 $item->save();
				$result[] = $item;



			}

			
		
		return response()->json(['status' => 200, 'data' => $result]);
		//return response()->json(['status' => 200]);
	}


	public function upload_files_list(Request $request)
	{
		$items = DB::select("
			SELECT result_doc_id,doc_path 
			FROM appraisal_item_result_doc
			where  vehicle_doc_id=?
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
			where  vehicle_doc_id=?
			order by result_doc_id;
		", array($request->item_result_id));


		// } catch (ModelNotFoundException $e) {
		// 	return response()->json(['status' => 404, 'data' => 'File not found.']);
		// }
		           //$_SERVER['DOCUMENT_ROOT'] . '/see_api/public/attach_files/' . $item_result_id . '/';

		foreach ($items as $i) {
			File::Delete($_SERVER['DOCUMENT_ROOT'] . '/boats_booking/api/public/'.$i->doc_path);		
			$delPicture = DB::select("
			DELETE  
			FROM appraisal_item_result_doc
			where  result_doc_id=?
			", array($i->result_doc_id));
		}
		 File::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/boats_booking/api/public/vehicle_files/'.$request->item_result_id);
		return response()->json(['status' => 200]);

	}

	public function store(Request $request)
	{
	
		$validator = Validator::make($request->all(), [
			'vehicle_number' => 'required|max:255|unique:vehicle'
			
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 400, 'data' => $validator->errors()]);
		} else {
			$item = new Vehicle;
			$item->fill($request->all());
			$item->save();
		}
	
		return response()->json(['status' => 200, 'data' => $item]);	
	}
	
	public function show($vehicle_id)
	{
		try {
			$item = Vehicle::findOrFail($vehicle_id);

			
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Vehicle not found.']);
		}
		return response()->json($item);
	}
	
	public function update(Request $request, $vehicle_id)
	{
		try {
			$item = Vehicle::findOrFail($vehicle_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Vehicle not found.']);
		}
		//'org_code' => 'required|max:15|unique:org,org_name,' . $org_id . ',org_id',
		$validator = Validator::make($request->all(), [

			'vehicle_number' => 'required|max:255|unique:vehicle,vehicle_number,' . $vehicle_id . ',vehicle_id'
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 400, 'data' => $validator->errors()]);
		} else {
			$item->fill($request->all());
			$item->save();
		}
	
		return response()->json(['status' => 200, 'data' => $item]);
				
	}
	
	public function destroy($vehicle_id)
	{
		try {
			$item = Vehicle::findOrFail($vehicle_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Vehicle not found.']);
		}	

		try {
			$item->delete();
		} catch (Exception $e) {
			//print_r($e);
			if ($e->errorInfo[1] == 1451) {
				return response()->json(['status' => 400, 'data' => 'Cannot delete because this Vehicle is in use.']);
			} else {
				return response()->json($e->errorInfo);
			}
		}
		
		return response()->json(['status' => 200]);
		
	}	

	
	public function report_vehicle(Request $request){
		$items = DB::select("
			SELECT v.vehicle_number,vt.vehicle_type,sum(datediff(b.date_to, b.date_from))as sum_trip,count(*) as count_trip
			FROM chauffeur_and_vehicle cav
			inner join vehicle v on cav.vehicle_id=v.vehicle_id
			inner join booking b on cav.booking_id=b.booking_id
            inner join vehicle_type vt on v.vehicle_type_id=vt.vehicle_type_id
			where b.date_from between ? and ?
			group by cav.vehicle_id

		", array($request->date_from,$request->date_to));
		return response()->json($items);
	}
}
