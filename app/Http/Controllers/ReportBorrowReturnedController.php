<?php

namespace App\Http\Controllers;

use App\PickingOrder;

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

class ReportBorrowReturnedController extends Controller
{

	public function __construct()
	{

	   $this->middleware('jwt.auth');
	}


	
	public function list_user_borrow_returned(Request $request)
	{		
		
		if(empty($request->profile_id)){
			//echo "0=".$request->profile_id;
			$items = DB::select("
					SELECT  distinct po.profile_id,p.first_name,p.last_name,p.position,p.email,p.gender,p.title
					-- ,po.loan_date,po.returned_date,po.status
					FROM picking_order po
					inner join profile p
					on po.profile_id=p.profile_id 
					where po.status in(3,4)

				"
				);
				
		}else{
			//echo "1=".$request->profile_id;
				$items = DB::select("
				SELECT  distinct po.profile_id,p.first_name,p.last_name,p.position,p.email,po.loan_date,po.returned_date,po.status
				FROM picking_order po
				inner join profile p
				on po.profile_id=p.profile_id 
				where po.status in(3,4)
				and p.profile_id = ?

				",
				array($request->profile_id)
				);
			}
		return response()->json($items);
	}
	// public function list_user_borrow_returned_by_search(Request $request)
	// {		
	// 	$items = DB::select("
	// 		SELECT  distinct po.profile_id,p.first_name,p.last_name,p.position,p.email,po.loan_date,po.returned_date,po.status
	// 		FROM picking_order po
	// 		inner join profile p
	// 		on po.profile_id=p.profile_id 
	// 		where po.status in(3,4)
	// 		and (p.first_name like ? or p.last_name like ?)

	// 	"
	// 	array('%'.$request->full_name.'%','%'.$request->full_name.'%')
		
	// 	);
	// 	return response()->json($items);
	// }


	public function list_tools_borrow_returned_by_profile(Request $request)
	{		
		$items = DB::select("
			SELECT st.category,st.pn,st.mpn,st.sappn,st.description,st.amount as total_amount ,
			st.active,po.id,po.amount as load_amount,po.status,po.reason,po.loan_date,po.returned_date
			FROM picking_order po
			left join stock_tools st
			on po.stock_id= st.id
			where po.profile_id=?
			 and po.status!=1
		"
		,array($request->profile_id)
		);
		return response()->json($items);
	}

	// public function auto_fullname(Request $request)
	// {		
	// 	$items = DB::select("
	// 		SELECT  distinct CONCAT(p.title,' ',p.first_name,' ',p.last_name) as fullname
	// 		FROM picking_order po
	// 		inner join profile p
	// 		on po.profile_id=p.profile_id 
	// 		where (p.first_name like ? or p.last_name like ?)
	// 	"
	// 	,array('%'.$request->full_name.'%','%'.$request->full_name.'%')
	// );
	// 	return response()->json($items);
	// }

	


	
}
