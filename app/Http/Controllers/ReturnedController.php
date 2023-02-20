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

class ReturnedController extends Controller
{

	public function __construct()
	{

	   $this->middleware('jwt.auth');
	}


	public function auto_fullname(Request $request)
	{		
		$items = DB::select("
			SELECT  distinct p.profile_id,CONCAT(p.title,' ',p.first_name,' ',p.last_name) as fullname
			FROM picking_order po
			inner join profile p
			on po.profile_id=p.profile_id 
			where (p.first_name like ? or p.last_name like ?)
		"
		,array('%'.$request->full_name.'%','%'.$request->full_name.'%')
	);
		return response()->json($items);
	}

	public function list_user_returned(Request $request)
	{		
		$items = DB::select("
			SELECT  distinct po.profile_id,p.first_name,p.last_name,p.position
			FROM picking_order po
			inner join profile p
			on po.profile_id=p.profile_id 

		"
		
		);
		return response()->json($items);
	}

	public function list_tools_returned(Request $request)
	{		
		$items = DB::select("
			SELECT st.category,st.pn,st.mpn,st.sappn,st.description,st.amount as total_amount ,
			st.active,po.id,po.amount as load_amount,po.status,po.reason,po.loan_date,po.returned_date
			FROM picking_order po
			inner join stock_tools st
			on po.stock_id= st.id
			where po.profile_id=?
		"
		,array($request->profile_id)
		);
		return response()->json($items);
	}

	public function tools_returned(Request $request)
	{		
		$items = DB::select("
			UPDATE picking_order
					SET status=3
					WHERE id=?
		"
		,array($request->id)
		);
		return response()->json(['status' => 200]);
	}
	


	
}
