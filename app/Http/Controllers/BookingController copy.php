<?php

namespace App\Http\Controllers;


use App\Booking;
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
use Illuminate\Support\Facades\DB as FacadesDB;

class BookingController extends Controller
{

	public function __construct()
	{

	   $this->middleware('jwt.auth');
	}
	
	public function index(Request $request)
	{		
		//select * from booking order by booking_id 
		$items = DB::select("
			

			select b.*,(select count(*) from booking bb
			inner join chauffeur_and_vehicle cv on bb.booking_id=cv.booking_id
			where bb.booking_id=b.booking_id) as count_assign
			from booking b  order by booking_id
		");
		return response()->json($items);
	}
	public function booking_search(Request $request)
	{		
		
		$items = DB::select("
			select b.*,(select count(*) from booking bb
			inner join chauffeur_and_vehicle cv on bb.booking_id=cv.booking_id
			where bb.booking_id=b.booking_id) as count_assign
			from booking b  
			where DATE_FORMAT(date_from, '%Y')=? 
            and (DATE_FORMAT(date_from, '%m')=?  or '00' = ?)
            and  b.booking_status='N' and b.assign_status='N'   and b.approved_status='P' 
			order by booking_id
		", array($request->param_year,$request->param_month,$request->param_month));

		return response()->json($items);
	}

	public function confirm_booking_search(Request $request)
	{		
		
		$items = DB::select("
			select b.*,(select count(*) from booking bb
			inner join chauffeur_and_vehicle cv on bb.booking_id=cv.booking_id
			where bb.booking_id=b.booking_id) as count_assign
			from booking b  
			where DATE_FORMAT(date_from, '%Y')=? 
            and (DATE_FORMAT(date_from, '%m')=?  or '00' = ?)
            and  b.booking_status='Y' and b.assign_status='N'   and b.approved_status='P' 
			order by booking_id
		", array($request->param_year,$request->param_month,$request->param_month));

		return response()->json($items);
	}

	public function booking_approved_search(Request $request)
	{		
		
		$items = DB::select("
			select b.*,(select count(*) from booking bb
			inner join chauffeur_and_vehicle cv on bb.booking_id=cv.booking_id
			where bb.booking_id=b.booking_id) as count_assign
			from booking b  
			where DATE_FORMAT(date_from, '%Y')=? 
            -- and DATE_FORMAT(date_from, '%m')=?
            and (DATE_FORMAT(date_from, '%m')=?  or '00' = ?)
            and b.booking_status='Y'   and b.assign_status='Y'  and b.approved_status='P' 
			order by booking_id
		", array($request->param_year,$request->param_month,$request->param_month));

		return response()->json($items);
	}
	public function booking_search_by_user(Request $request)
	{		
		
		$items = DB::select("
			select b.*,(select count(*) from booking bb
			inner join chauffeur_and_vehicle cv on bb.booking_id=cv.booking_id
			where bb.booking_id=b.booking_id) as count_assign
			from booking b  
             inner join profile p on b.user_id=p.profile_id
			where 
            DATE_FORMAT(date_from, '%Y')=? 
            and DATE_FORMAT(date_from, '%m')=?
            and p.email=?
            and b.booking_status='N'   and b.assign_status='N'  and b.approved_status='P' 
			order by booking_id
		",array($request->param_year,$request->param_month,Auth::id()));
		return response()->json($items);
	}


	public function booking_sum_booking_assigned_approved_by_user(Request $request)
	{		
		
		$items = DB::select("
		SELECT count(*)   as 'booking_by_user' ,
		(SELECT count(*) 
		FROM booking b 
		inner join profile p
		on b.user_id=p.profile_id
		where b.booking_status='Y'
		and b.assign_status='Y' and b.approved_status='P'
		and p.email=?) as 'assigned_by_user',


		(SELECT count(*) 
		FROM booking b 
		inner join profile p
		on b.user_id=p.profile_id
		where b.booking_status='Y'
		and b.assign_status='N' and b.approved_status='P'
		and p.email=?) as 'wait_for_assign_by_user',



		(SELECT count(*) 
		FROM booking b 
		inner join profile p
		on b.user_id=p.profile_id
		where  b.approved_status='Y'
		and p.email=?) as 'approved_by_user',

		(SELECT count(*) 
		FROM booking b 
		inner join profile p
		on b.user_id=p.profile_id
		where b.approved_status='N' 
		and p.email=?) as 'not_approved_by_user'


		FROM booking b 
		inner join profile p
		on b.user_id=p.profile_id
		where b.booking_status='N'
		and b.assign_status='N' and b.approved_status='P'
		and p.email=?
		",array(Auth::id(),Auth::id(),Auth::id(),Auth::id(),Auth::id()));
		return response()->json($items);
	}
	public function booking_sum_booking_assigned_approved_by_all_user(Request $request)
	{		
		
		$items = DB::select("
		SELECT count(*)   as 'booking_all_user' ,
		(SELECT count(*) 
		FROM booking b 
		inner join profile p
		on b.user_id=p.profile_id
		where b.booking_status='Y'
		and b.assign_status='Y' and b.approved_status='P'
		) as 'assigned_all_user',


		(SELECT count(*) 
		FROM booking b 
		left join profile p
		on b.user_id=p.profile_id
		where b.booking_status='Y'
		and b.assign_status='N' and b.approved_status='P'
		) as 'wait_for_assign_by_user',



		(SELECT count(*) 
		FROM booking b 
		inner join profile p
		on b.user_id=p.profile_id
		where b.approved_status='Y'
		) as 'approved_all_user',

		(SELECT count(*) 
		FROM booking b 
		inner join profile p
		on b.user_id=p.profile_id
		where b.approved_status='N' and b.approved_status_reason!=''
		) as 'not_approved_all_user',

		(SELECT count(vehicle_id) as vehicle_use FROM chauffeur_and_vehicle  cav
inner join booking b on cav.booking_id=b.booking_id
where 
b.date_to >= CURDATE()
	  	and b.booking_status='Y' 
	  	and b.approved_status!='N'
	  	) as vehicle_use,

	  	(SELECT 
		count(chauffeur_id) as chauffeur_use  FROM chauffeur_and_vehicle  cav
inner join booking b on cav.booking_id=b.booking_id
where 
b.date_to >= CURDATE()
	  	and b.booking_status='Y' 
	  	and b.approved_status!='N'
	  	) as chauffeur_use,

	  	(select count(*) as total_vehicle from vehicle) as total_vehicle,
	  	(select count(*) as total_chauffeur from chauffeur) as total_chauffeur





		FROM booking b 
		inner join profile p
		on b.user_id=p.profile_id
		where b.booking_status='N'
		and b.assign_status='N' and b.approved_status='P'
		
		");
		return response()->json($items);
	}



    public function list_vehicle_image(Request $request)
	{		
		
		$items = DB::select("
			SELECT doc_path FROM booking_car_db.chauffeur_and_vehicle cav
left join appraisal_item_result_doc aird on cav.vehicle_id=aird.vehicle_doc_id
where booking_id=? limit 1


		",array($request->booking_id));
		return response()->json($items);


	}

	public function booking_list(Request $request)
	{		
		
		$items = DB::select("
			select b.*,(select count(*) from booking bb
			inner join chauffeur_and_vehicle cv on bb.booking_id=cv.booking_id
			where bb.booking_id=b.booking_id) as count_assign
			from booking b  

			where b.booking_status='Y',
			and b.assign_status='N',
			and b.approved_status='N'

			order by booking_id
		");

		

		//return response()->json(['data' => $items, 'check_status' => $check_status]);	
		return response()->json($items);
	}

	public function booking_confrim_assign_status(Request $request,$booking_id)
	{		
		
		$items = DB::select("
			select count(*) from booking b
			inner join chauffeur_and_vehicle cv on b.booking_id=cv.booking_id
			where b.booking_id=?
			 order by b.booking_id
		", array($booking_id));
		return response()->json($items);
	}



	public function booking_confrim_detail(Request $request,$booking_id)
	{		
		
		$items = DB::select("
			SELECT b.* ,p.*
FROM booking_car_db.booking b 
left join profile p on b.user_id=p.profile_id
where b.booking_id=?

		", array($booking_id));
		return response()->json($items);
	}

	public function booking_approved_list(Request $request)
	{		
		
		$items = DB::select("
			select * from booking where stage=3 order by booking_id
		");
		return response()->json($items);
	}

	public function confirm_booking(Request $request,$booking_id)
	{		
		
		$items = DB::select("
			UPDATE booking
			SET assign_status='Y', approved_status_reason=?

			WHERE booking_id=? 
		",array($request->approved_status_reason,$booking_id));
		//return response()->json($items);
		return response()->json(['status' => 200]);
	}

	public function not_confirm_booking(Request $request,$booking_id)
	{		
		
		$items = DB::select("
			UPDATE booking
			SET assign_status='N'
			WHERE booking_id=? 
		",array($booking_id));
		//return response()->json($items);
		return response()->json(['status' => 200]);
	}

	public function send_to_draft_booking(Request $request,$booking_id)
	{		
		
		$items = DB::select("
			UPDATE booking
			SET 
			booking_status='N',
			assign_status='N',
			approved_status='P',
			booking_status_reason=?
			WHERE booking_id=? 
		",array($request->booking_status_reason,$booking_id));
		//return response()->json($items);
		return response()->json(['status' => 200]);
	}

	
	

	

	public function booking_approved_status(Request $request,$booking_id)
	{		
		
		// $items = DB::select("
		// 	UPDATE booking
		// 	SET approved_status=? ,approved_status_reason=?
		// 	WHERE booking_id=?  
		// ",array($request->approved_status),array($request->approved_status_reason),array($request->booking_id));
		// return response()->json($items);

		if($request->approved_status=='N'){

			$items = DB::select("
			UPDATE booking
			SET booking_status='N', assign_status='N', approved_status='N',booking_status_reason=?
			,assign_status_reason=?
			WHERE booking_id=?  
			",array($request->approved_status_reason,$request->approved_status_reason,$booking_id));
			//return response()->json($items);
			return response()->json(['status' => 200, 'data' => $items]);


		} if($request->approved_status=='B'){

			$items = DB::select("
			UPDATE booking
			SET booking_status='N', assign_status='N', approved_status='P' ,booking_status_reason=?
			WHERE booking_id=?  
			",array($request->approved_status_reason,$booking_id));
			//return response()->json($items);
			return response()->json(['status' => 200, 'data' => $items]);


		} if($request->approved_status=='A'){

			$items = DB::select("
			UPDATE booking
			SET booking_status='Y', assign_status='N', approved_status='P' ,assign_status_reason=?
			WHERE booking_id=?  
			",array($request->approved_status_reason,$booking_id));
			//return response()->json($items);
			return response()->json(['status' => 200, 'data' => $items]);


		}if($request->approved_status=='Y'){

			$items = DB::select("
			UPDATE booking
			SET booking_status='Y', assign_status='Y', approved_status='Y' ,approved_status_reason=?
			WHERE booking_id=?  
			",array($request->approved_status_reason,$booking_id));
			//return response()->json($items);
			return response()->json(['status' => 200, 'data' => $items]);


		}
		// else{


		// 	$items = DB::select("
		// 	UPDATE booking
		// 	SET approved_status=? ,approved_status_reason=''
		// 	WHERE booking_id=?  
		// 	",array($request->approved_status,$booking_id));
		// 	//return response()->json($items);
		//     return response()->json(['status' => 200, 'data' => $items]);


			

		// }


	}

	
	
	public function upload_files(Request $request,$cv_id )
	{
		
		
		
		$result = array();	

		$validator = Validator::make($request->all(), [
			'cv_id' => 'required|max:255|unique:appraisal_item_result_doc'
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 400, 'data' => $validator->errors()]);
		} else {
			
			$path = $_SERVER['DOCUMENT_ROOT'] . '/booking-car/api/public/attach_files/' . $cv_id . '/';
			foreach ($request->file() as $f) {
				$filename = iconv('UTF-8','windows-874',time().$f->getClientOriginalName());
				//$f->move($path,$filename);
				$f->move($path,time().$f->getClientOriginalName());
				//echo $filename;
				
				 $item = AttachFile::firstOrNew(array('doc_path' => 'attach_files/' . $cv_id . '/'.time().''. $f->getClientOriginalName()));
				
				 $item->cv_id = $cv_id;
				 $item->fuel_type = $request->fuel_type;
				 $item->fuel_amount = $request->fuel_amount;
				 $item->created_by = Auth::id();
				
				// //print_r($item);
				$item->save();
				$result[] = $item;
				//echo "hello".$f->getClientOriginalName();
			}
		}
		
		return response()->json(['status' => 200, 'data' => $result]);
		//return response()->json(['status' => 200]);
	}


	public function upload_files_list(Request $request,$booking_id)
	{
			$items = DB::select("
			SELECT v.vehicle_number,vt.vehicle_type,cav.vehicle_id,cav.cv_id, result_doc_id,doc_path,aird.fuel_type,aird.fuel_amount
			FROM appraisal_item_result_doc aird
            inner join chauffeur_and_vehicle cav on cav.cv_id=aird.cv_id
            inner join vehicle v on cav.vehicle_id= v.vehicle_id
            inner join vehicle_type vt on v.vehicle_type_id= vt.vehicle_type_id
            where cav.booking_id=?
			order by result_doc_id;



			-- SELECT result_doc_id,doc_path 
			-- FROM appraisal_item_result_doc
			-- where  attach_files_id=?
			-- order by result_doc_id;


		", array($booking_id));

		return response()->json($items);
	}


	public function delete_file(Request $request){

		//try {

			//$item = AttachFile::findOrFail($request->result_doc_id);
			$items = DB::select("
			SELECT result_doc_id,doc_path 
			FROM appraisal_item_result_doc
			where  cv_id=?
			order by result_doc_id;
		", array($request->cv_id));


		// } catch (ModelNotFoundException $e) {
		// 	return response()->json(['status' => 404, 'data' => 'File not found.']);
		// }
		           //$_SERVER['DOCUMENT_ROOT'] . '/see_api/public/attach_files/' . $item_result_id . '/';

		// foreach ($items as $i) {
		// 	File::Delete($_SERVER['DOCUMENT_ROOT'] . '/booking-car/api/public/'.$i->doc_path);		
		// 	$delPicture = DB::select("
		// 	DELETE  
		// 	FROM appraisal_item_result_doc
		// 	where  attach_files_id=?
		// 	", array($i->result_doc_id));
		// }

		foreach ($items as $i) {
			File::Delete($_SERVER['DOCUMENT_ROOT'] . '/booking-car/api/public/'.$i->doc_path);		
			$delPicture = DB::select("
			DELETE  
			FROM appraisal_item_result_doc
			where  cv_id=?
			", array($request->cv_id));
		}
		 File::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/booking-car/api/public/attach_files/'.$request->cv_id);

		return response()->json(['status' => 200]);

	}
	
	
	public function store(Request $request)
	{
	

	
		$seat_go_number=$request->seat_go;
		$seat_back_number=$request->seat_back;
		$booking_id="";
		$validator = Validator::make($request->all(), [
			//'purpose' => 'required|max:255|unique:booking'
			
		]);

		
		if ($validator->fails()) {
			return response()->json(['status' => 400, 'data' => $validator->errors()]);
		} else {
			$item = new Booking;
			$item->fill($request->all());
			$item->save();

			$booking_id=$item->booking_id;
			
			
			
		}
		
		//echo "booking_id=".$booking_id."<br>";
		//echo "seat_go_number=".$seat_go_number."<br>";

		// echo "boat=".$request->boat."<br>";
		// echo "around_boat_go=".$request->around_boat_go."<br>";
		// echo "around_boat_back=".$request->around_boat_back."<br>";
		// echo "seat_go=".$request->seat_go."<br>";
		// echo "seat_back=".$request->seat_back."<br>";
		// //echo "booking_date=".$request->booking_date."<br>";
		// echo "go_back_booking_flag".$request->go_back_booking_flag."<br>";
		// echo "date_from".$request->date_from."<br>";
		// echo "date_to".$request->date_to."<br>";
		//seat_go_number
		//seat_back_number
		//booking_date
		//booking_id

		if($request->go_back_booking_flag=='1'){
			//echo "go_back_booking_flag=1";
			$seats_check = DB::select("
			SELECT bv.*,s.* FROM booking_vehicle bv
			left join seat s on bv.booking_id=s.booking_id
			where 
			
			(bv.boat_go=? or bv.boat_back=?)
			and (around_boat_go=? or around_boat_back=?)
			and (s.seat_go_number=? or s.seat_back_number =?)
			and (s.booking_date between  ? and ?)

			",array(
			 $request->boat_go
			,$request->boat_back
			,$request->around_boat_go
			,$request->around_boat_back
			,$request->seat_go
			,$request->seat_back
			,$request->date_from
			,$request->date_to
			
			
		));
		}else{
			//echo "go_back_booking_flag=2";
			$seats_check = DB::select("
			SELECT bv.*,s.* FROM booking_vehicle bv
			left join seat s on bv.booking_id=s.booking_id
			where 
			
			bv.boat_go=?
			and around_boat_go=?
			and s.seat_go_number=?
			and (s.booking_date between  ? and ?)
			",array(
			 $request->boat_go
			,$request->around_boat_go
			,$request->seat_go
			,$request->date_from
			,$request->date_to
			));

		}
		


		//loop for booing seat here.
		if (empty($seats_check)) {
			//echo "insert ok";
			
			foreach ($request->booking_date as $booking_date) {
				$seats = DB::select("
				INSERT INTO SEAT(seat_go_number,seat_back_number,booking_id,booking_date,created_date,updated_date)VALUES(?,?,?,?,NOW(),NOW());
				", array($seat_go_number,$seat_back_number,$booking_id,$booking_date));
			}
			return response()->json(['status' => 200, 'data' => $item]);
			
			
		}else{
			echo "not insert";
			return response()->json(['status' => 200, 'data' => 'duplicate_seat_number']);	
		}
	
		
		
		

	
		
	}
	
	public function show($booking_id)
	{
		try {
			$item = Booking::findOrFail($booking_id);

			
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Booking not found.']);
		}
		return response()->json($item);
	}
	
	public function update(Request $request, $booking_id)
	{

		$seat_go_number=$request->seat_go;
		$seat_back_number=$request->seat_back;
		

		try {
			$item = Booking::findOrFail($booking_id);
			
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Booking not found.']);
		}
		//'org_code' => 'required|max:15|unique:org,org_name,' . $org_id . ',org_id',
		$validator = Validator::make($request->all(), [
			'fullname' => 'required|max:255|unique:booking_vehicle,fullname,' . $booking_id . ',booking_id'
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 400, 'data' => $validator->errors()]);
		} else {

			$item->fill($request->all());
			$item->save();

			$delete_seats = DB::select("
			DELETE FROM SEAT WHERE booking_id=?;
				", array($booking_id));

				
			if($request->go_back_booking_flag=='1'){
				//echo "go_back_booking_flag=1";
				$seats_check = DB::select("
				SELECT bv.*,s.* FROM booking_vehicle bv
				left join seat s on bv.booking_id=s.booking_id
				where 
				
				(bv.boat_go=? or bv.boat_back=?)
				and (around_boat_go=? or around_boat_back=?)
				and (s.seat_go_number=? or s.seat_back_number =?)
				and (s.booking_date between  ? and ?)

				",array(
				$request->boat_go
				,$request->boat_back
				,$request->around_boat_go
				,$request->around_boat_back
				,$request->seat_go
				,$request->seat_back
				,$request->date_from
				,$request->date_to
				
				
			));
			}else{
				//echo "go_back_booking_flag=2";
				$seats_check = DB::select("
				SELECT bv.*,s.* FROM booking_vehicle bv
				left join seat s on bv.booking_id=s.booking_id
				where 
				
				bv.boat_go=?
				and around_boat_go=?
				and s.seat_go_number=?
				and (s.booking_date between  ? and ?)
				",array(
				$request->boat_go
				,$request->around_boat_go
				,$request->seat_go
				,$request->date_from
				,$request->date_to
				));

			}
			


			//loop for booing seat here.
			if (empty($seats_check)) {
				//echo "insert ok";
				

				foreach ($request->booking_date as $booking_date) {
					$seats = DB::select("
					INSERT INTO SEAT(seat_go_number,seat_back_number,booking_id,booking_date,created_date,updated_date)VALUES(?,?,?,?,NOW(),NOW());
					", array($seat_go_number,$seat_back_number,$booking_id,$booking_date));
				}
				return response()->json(['status' => 200, 'data' => $item]);
				
				
			}else{
				
				return response()->json(['status' => 200, 'data' => 'duplicate_seat_number']);	
			}
		
		}
	
		//return response()->json(['status' => 200, 'data' => $item]);
				
	}
	
	public function destroy($booking_id)
	{
		try {
			$item = Booking::findOrFail($booking_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Booking not found.']);
		}	

		try {
			$item->delete();

			$items = DB::select("
			DELETE  FROM seat WHERE booking_id=?

			

		", array($booking_id));


		} catch (Exception $e) {
			//print_r($e);
			if ($e->errorInfo[1] == 1451) {
				return response()->json(['status' => 400, 'data' => 'Cannot delete because this booking is in use.']);
			} else {
				return response()->json($e->errorInfo);
			}
		}
		
		return response()->json(['status' => 200]);
		
	}

	public function report_overview_booking_car(Request $request){


		if($request->status=='All'){
		$items = DB::select("
			SELECT b.booking_id,b.date_from,b.time_from,b.date_to,b.time_to,
			p.title,p.FIRST_NAME,p.LAST_NAME,b.purpose,b.number_of_people,b.booking_status,b.assign_status,b.approved_status
			FROM booking b
			left join profile p on p.profile_id= b.user_id

			where b.date_from between ? and ?

			

		", array($request->date_from,$request->date_to));

		}else if($request->status=='Y'){
			$items = DB::select("
			SELECT b.booking_id,b.date_from,b.time_from,b.date_to,b.time_to,
			p.title,p.FIRST_NAME,p.LAST_NAME,b.purpose,b.number_of_people,b.booking_status,b.assign_status,b.approved_status
			FROM booking b
			left join profile p on p.profile_id= b.user_id

			where b.date_from between ? and ?

			
            and b.approved_status='Y'

		", array($request->date_from,$request->date_to));
		}else if($request->status=='N'){
			$items = DB::select("
			SELECT b.booking_id,b.date_from,b.time_from,b.date_to,b.time_to,
			p.title,p.FIRST_NAME,p.LAST_NAME,b.purpose,b.number_of_people,b.booking_status,b.assign_status,b.approved_status
			FROM booking b
			left join profile p on p.profile_id= b.user_id

			where b.date_from between ? and ?

			
            and b.approved_status='N'

		", array($request->date_from,$request->date_to));
		}else if($request->status=='A'){
			$items = DB::select("
			SELECT b.booking_id,b.date_from,b.time_from,b.date_to,b.time_to,
			p.title,p.FIRST_NAME,p.LAST_NAME,b.purpose,b.number_of_people,b.booking_status,b.assign_status,b.approved_status
			FROM booking b
			left join profile p on p.profile_id= b.user_id

			where b.date_from between ? and ?

			and b.booking_status ='Y'
            and b.assign_status='Y'
            and b.approved_status='P'

		", array($request->date_from,$request->date_to));
		}else if($request->status=='B'){
			$items = DB::select("
			SELECT b.booking_id,b.date_from,b.time_from,b.date_to,b.time_to,
			p.title,p.FIRST_NAME,p.LAST_NAME,b.purpose,b.number_of_people,b.booking_status,b.assign_status,b.approved_status
			FROM booking b
			left join profile p on p.profile_id= b.user_id

			where b.date_from between ? and ?

			and b.booking_status ='N'
            and b.assign_status='N'
            and b.approved_status='P'

		", array($request->date_from,$request->date_to));
		}else if($request->status=='WA'){
			$items = DB::select("
			SELECT b.booking_id,b.date_from,b.time_from,b.date_to,b.time_to,
			p.title,p.FIRST_NAME,p.LAST_NAME,b.purpose,b.number_of_people,b.booking_status,b.assign_status,b.approved_status
			FROM booking b
			left join profile p on p.profile_id= b.user_id

			where b.date_from between ? and ?

			and b.booking_status ='Y'
            and b.assign_status='N'
            and b.approved_status='P'

		", array($request->date_from,$request->date_to));
		}


		return response()->json($items);
	}

	public function report_overview_booking_vehicle(Request $request){


		if($request->status=='All'){
		$items = DB::select("
		select 
		booking_id,
		fullname,
		tel,
		stay,
		go_back_booking_flag,
		date_from,
		time_from,
		date_to,
		time_to,
		booking_price_total,
		note 
		from booking_vehicle
		where date_from between ? and ?
		order by created_date asc


			

			

		", array($request->date_from,$request->date_to));

		}


		return response()->json($items);
	}


	public function report_calendar_booking_car(Request $request){
		$items = DB::select("
			select b.purpose,b.date_from,b.time_from,b.date_to,b.time_to from booking b  where  b.booking_status='Y'   and b.assign_status='Y' and b.approved_status='Y'   limit 360

		");
		return response()->json($items);
	}
	public function report_calendar_booking_vehicle(Request $request){
		$items = DB::select("

		select booking_id,fullname,date_from,time_from,date_to,time_to
		from booking_vehicle
		order by date_from asc
		");
		return response()->json($items);
	}

	public function around_vehicle(Request $request){
		$items = DB::select("

		SELECT * FROM around_vehicle order by around_name asc
		");
		return response()->json($items);
	}

	public function vehicle_seat_by_boat($boat_id){
		$items = DB::select("

		SELECT number_of_seats FROM vehicle where vehicle_id=?;
		",array($boat_id));
		return response()->json($items);
	}

	public function vehicle(Request $request){
		$items = DB::select("
		SELECT * FROM vehicle 
		");
		return response()->json($items);
	}

	public function seat_go_back_reserve(Request $request){
		$items = DB::select("
		SELECT s.seat_go_number ,s.seat_back_number,booking_date    FROM booking_vehicle bv
			left join seat s on bv.booking_id=s.booking_id
			where bv.boat_go=? and bv.around_boat_go=?
		",array(
			$request->boat_go
		   ,$request->around_boat_go
		  // ,$request->date_from
		  // ,$request->date_to
		   
		   ));
		return response()->json($items);
	}

	public function seat_back_reserve(Request $request){
		$items = DB::select("
		SELECT DISTINCT(s.seat_back_number) as seat_back_number   FROM booking_vehicle bv
			left join seat s on bv.booking_id=s.booking_id
			where 
			
			bv.boat_back=?
			and around_boat_back=?
		
			and (s.booking_date between  ? and ?) order by  seat_back_number
		",array(
			$request->boat_back
		   ,$request->around_boat_back
		   ,$request->seat_back
		   ,$request->date_from
		   ,$request->date_to
		   
		   ));
		return response()->json($items);
	}

	public function get_seat_reserve_by_booking_date($booking_date,$booking_id){
		//echo "booking_id=$booking_date";
		$items = DB::select("
		select seat_go_number,seat_back_number,booking_date
		from seat
		where booking_date=? and booking_id=?
		",array(
			$booking_date,$booking_id
		   ));
		return response()->json($items);
	}
	public function get_seat_reserve_by_booking_id($booking_id){
		//echo "booking_id=$booking_date";
		$items = DB::select("
		select seat_go_number,seat_back_number,booking_date
		from seat
		where booking_id=?
		",array(
			$booking_id
		   ));
		return response()->json($items);
	}


	
}
