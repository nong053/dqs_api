<?php

namespace App\Http\Controllers;

use App\ChauffeurAndVehicle;
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

class ChauffeurAndVehicleController extends Controller
{

	public function __construct()
	{

	   $this->middleware('jwt.auth');
	}
	
	public function index(Request $request, $booking_id)
	{		
		
		$items = DB::select("
			SELECT cav.*, v.vehicle_number,vt.vehicle_type,c.chauffeur_name,c.driver_license FROM chauffeur_and_vehicle cav
left join vehicle v on cav.vehicle_id=v.vehicle_id
left join vehicle_type vt on v.vehicle_type_id=vt.vehicle_type_id
left join chauffeur c on cav.chauffeur_id=c.chauffeur_id
where booking_id=?;
		", array($booking_id));
		return response()->json($items);
	}
	
	public function index_ee(Request $request)
	{		
		
		$items = DB::select("
			SELECT * FROM chauffeur_and_vehicle
		");
		return response()->json($items);
	}

	public function store(Request $request)
	{
		//'email' => 'required|unique:profile,' . $id . ',profile_id',

		$count_vehicle = DB::select("
			SELECT count(*) as 'count_vehicle' FROM chauffeur_and_vehicle cav
			where cav.booking_id = ?
			and cav.vehicle_id=?
		", array($request->booking_id,$request->vehicle_id));

		$count_chauffeur = DB::select("
			SELECT count(*) as 'count_chauffeur' FROM chauffeur_and_vehicle cav
			where cav.booking_id = ? and cav.chauffeur_id=?
		", array($request->booking_id,$request->chauffeur_id));


		//echo $item_cav[0]->countData;
		//print_r($item_cav->countData);


		if($count_vehicle[0]->count_vehicle>=1){
			return response()->json(['status' => 400, 'data' => 'Vehicle assigned is alreay on this booking.']);
		}else if($count_chauffeur[0]->count_chauffeur>=1){
			return response()->json(['status' => 400, 'data' => 'Chauffeur assigned is alreay on this booking.']);
		}else{

			$item = new ChauffeurAndVehicle;
			$item->fill($request->all());
			$item->save();
		 return response()->json(['status' => 200, 'data' => $item]);	
		}
	}
	
	public function show($chauffeur_and_vehicle_id)
	{
		try {
			$item = ChauffeurAndVehicle::findOrFail($chauffeur_and_vehicle_id);

			
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'ChauffeurAndVehicle not found.']);
		}
		return response()->json($item);
	}
	
	public function update(Request $request, $chauffeur_and_vehicle_id)
	{
		try {
			$item = ChauffeurAndVehicle::findOrFail($chauffeur_and_vehicle_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'ChauffeurAndVehicle not found.']);
		}
		//'org_code' => 'required|max:15|unique:org,org_name,' . $org_id . ',org_id',
		$validator = Validator::make($request->all(), [
			'booking_id' => 'required|max:255|unique:chauffeur_and_vehicle,booking_id,' . $chauffeur_and_vehicle_id . ',cv_id'
			
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 400, 'data' => $validator->errors()]);
		} else {
			$item->fill($request->all());
			$item->save();
		}
	
		return response()->json(['status' => 200, 'data' => $item]);
				
	}

	public function check_vehicle_chauffeur_alreay(Request $request)
	{		
		
	$items = DB::select("
		SELECT cav.cv_id,cav.booking_id,cav.vehicle_id,cav.chauffeur_id,b.booking_status,b.assign_status,b.approved_status
		FROM booking b 
		inner join chauffeur_and_vehicle cav on b.booking_id=cav.booking_id
		where 
	  	date_to >= CURDATE()
	  	and b.booking_status='Y' 
	  	and b.assign_status='N' 
	  	and b.approved_status!='N'
		");
		//return response()->json($items);
		return response()->json(['status' => 200, 'data' => $items]);
		
		//return response()->json(['status' => 200]);
	}

	
	public function destroy($chauffeur_and_vehicle_id)
	{
		try {
			$item = ChauffeurAndVehicle::findOrFail($chauffeur_and_vehicle_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'ChauffeurAndVehicle not found.']);
		}	

		try {
			$item->delete();
		} catch (Exception $e) {
			//print_r($e);
			if ($e->errorInfo[1] == 1451) {
				return response()->json(['status' => 400, 'data' => 'Cannot delete because this ChauffeurAndVehicle is in use.']);
			} else {
				return response()->json($e->errorInfo);
			}
		}
		
		return response()->json(['status' => 200]);
		
	}	
}
