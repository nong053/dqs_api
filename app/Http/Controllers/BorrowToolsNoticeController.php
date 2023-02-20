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

class BorrowToolsNoticeController extends Controller
{

	public function __construct()
	{

	   $this->middleware('jwt.auth');
	}


	public function auto_fullname(Request $request)
	{		
		$items = DB::select("
			SELECT  distinct CONCAT(p.title,' ',p.first_name,' ',p.last_name) as fullname
			FROM picking_order po
			inner join profile p
			on po.profile_id=p.profile_id 
			where p.first_name like ?
		"
		,array('%'.$request->full_name.'%')
	);
		return response()->json($items);
	}
					
	public function list_user_borrow_notice(Request $request)
	{		
		$items = DB::select("
			

   SELECT  distinct po.profile_id,p.first_name,p.last_name,p.position,
-- IF((select count(po.status)  from  picking_order po where po.profile_id = p.profile_id and status=2)>0,2,0) as wait_status,
            (select count(po.status)  from  picking_order po where po.profile_id = p.profile_id and status=2) as wait_status,
            (select count(po.status)  from  picking_order po where po.profile_id = p.profile_id and status=3) as borrow_status,
             (select count(po.status)  from  picking_order po where po.profile_id = p.profile_id and status=4) as returned_status
			FROM picking_order po
			inner join profile p
			on po.profile_id=p.profile_id 
            order by po.loan_date desc

		"
		
		);
		return response()->json($items);
	}

	public function list_tools_borrow_notice(Request $request)
	{		
		// and po.status=2 
		$items = DB::select("
			SELECT st.id as stock_id,st.category,st.pn,st.mpn,st.sappn,st.description,st.amount as total_amount ,
			st.active,po.id,po.amount as borrow_amount,po.status,po.reason,po.loan_date,po.returned_date
			FROM picking_order po
			inner join stock_tools st
			on po.stock_id= st.id
			where po.profile_id=?
			and po.status!=1 and  po.status!=4
			
		"
		,array($request->profile_id)
		);
		return response()->json($items);
	}
	public function list_myloan_tools_borrow_notice(Request $request)
	{		
		// and po.status=2 
		$items = DB::select("
			SELECT st.id as stock_id,st.category,st.pn,st.mpn,st.sappn,st.description,st.amount as total_amount ,
			st.active,po.id,po.amount as borrow_amount,po.status,po.reason,po.loan_date,po.returned_date
			FROM picking_order po
			inner join stock_tools st
			on po.stock_id= st.id
			where po.profile_id=?
			and (po.status!=1 and po.status!=4)

			
		"
		,array($request->profile_id)
		);
		return response()->json($items);
	}
/*
	public function tools_borrow_notice(Request $request)
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
	*/

	public function tools_ready(Request $request)
	{		
		
		foreach ($request->orders as $o) {
			//echo $o['order_id'];
			$items = DB::select("
			UPDATE picking_order
					SET status=3
					WHERE id=?
			"
			,array($o['order_id'])
			);
		}
		return response()->json(['status' => 200]);

	}

	public function tools_wait(Request $request)
	{		
		
		foreach ($request->orders as $o) {
			//echo $o['order_id'];
			$items = DB::select("
			UPDATE picking_order
					SET status=2
					WHERE id=?
			"
			,array($o['order_id'])
			);
		}
		return response()->json(['status' => 200]);

	}
	public function tools_out_of_stock(Request $request)
	{		
		
		foreach ($request->orders as $o) {
			//echo $o['order_id'];
			$items = DB::select("
			UPDATE picking_order
					SET status=5
					WHERE id=?
			"
			,array($o['order_id'])
			);
		}
		return response()->json(['status' => 200]);

	}
	public function tools_returned(Request $request)
	{		
		
		foreach ($request->orders as $o) {
			//echo $o['order_id'];
			$items = DB::select("
			UPDATE picking_order
					SET status=4
					WHERE id=?
			"
			,array($o['order_id'])
			);

			$updateStock = DB::select("
			UPDATE stock_tools
					SET amount=(amount+?)
					WHERE id=?
			"
			,array($o['borrow_amount'],$o['stock_id'])
			);


		}



		//borrow_amount


		return response()->json(['status' => 200]);

	}

	


	
}
