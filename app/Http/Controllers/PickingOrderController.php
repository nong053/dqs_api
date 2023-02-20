<?php

namespace App\Http\Controllers;

use App\PickingOrder;

use Auth;
use DB;
use File;
use Validator;
use Excel;
use Exception;
use DateTime;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PickingOrderController extends Controller
{

	public function __construct()
	{

	   $this->middleware('jwt.auth');
	}
	
	// Stock_ID	CARD_ID	PASSPORT_ID	TITLE	FIRST_NAME	LAST_NAME	GENDER	NATIONALITY	DATE_OF_BIRTH	RELIGION	ADDRESS	CREATED_DATE	CREATED_BY	UPDATED_DATE	UPDATED_BY	ACTIVE_FLAG
	public function index(Request $request)
	{		
		$items = DB::select("
			SELECT po.id as order_id, st.id as stock_id,st.category,st.pn,st.mpn,st.sappn,st.description,
st.amount_total as total_amount, po.amount as borrow_amount, (st.amount_total-po.amount) as balance_amount,
po.loan_date,po.returned_date,aird.doc_path
FROM stock_tools st
			left join picking_order po
			on st.id=po.stock_id
			left join appraisal_item_result_doc aird
            on st.id = aird.item_result_id
			where po.status=1
			and po.profile_id=?
		"
		,array($request->profile_id)
	);
		return response()->json($items);
	}


	public function auto_pn(Request $request)
	{		
		$items = DB::select("
			select pn from stock_tools
			where pn like ?
			and active=1

		"
		,array('%'.$request->pn_name.'%')
	);
		return response()->json($items);
	}

	public function get_inventory(Request $request)
	{		
		$items = DB::select("
			SELECT st.*, aird.doc_path FROM stock_tools st
left join appraisal_item_result_doc aird
on st.id=aird.item_result_id
			where st.pn=?

		"
		,array($request->pn_name)
		);
		return response()->json($items);

	}


	public function check_unique_stock(Request $request)
	{		
		$items = DB::select("
			SELECT * FROM picking_order
			where profile_id=?
			and stock_id=?
		    and (status=2 or status=3 or status=1)

		"
		,array($request->profile_id,$request->stock_id)
		);
		return response()->json($items);

	}

	
	

	public function delete_all_by_profile(Request $request)
	{		


		try {
			$items = DB::select("
						DELETE FROM picking_order
						where profile_id=?

					"
					,array($request->profile_id)
			);
			return response()->json(['status' => 200]);
			
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'PickingOrder not found.']);
		}


		

		
	}
	
	
	
	public function store(Request $request)
	{
	$amount="";
	$total_amount="";
		$validator = Validator::make($request->all(), [
			'profile_id' => 'required',
			'stock_id' => 'required'
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 400, 'data' => $validator->errors()]);
		} else {
			$now = new DateTime();
			$item = new PickingOrder;
			$item->fill($request->all());
			$item->loan_date = $now;
			// $item->updated_by = Auth::id();
			  
			 $item->save();
			
			 //updated start ...
			 /*
			$item2= DB::select("
				SELECT amount FROM stock_tools 
				where id=?
			"
			,array($request->stock_id)
			);			   


			foreach ($item2 as $i) {
				$amount = $i->amount;
			}
			$total_amount= $amount-$request->amount;
			
			try {
				$update = DB::select("
							UPDATE stock_tools
							SET amount = ?
							WHERE id = ?

						"
						,array($total_amount,$request->stock_id)
				);
				
			} catch (ModelNotFoundException $e) {
				return response()->json(['status' => 404, 'data' => 'error update stock_tools.']);
			}
			*/
			//update end...


		}
	
		return response()->json(['status' => 200, 'data' => $item]);	
	}
	
	public function show(Request $request,$id)
	{
	
		/*
		try {

			$item = PickingOrder::findOrFail($id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'PickingOrder not found.']);
		}
		return response()->json($item);
		*/

		$items = DB::select("
			SELECT po.id as order_id, st.id as stock_id,st.category,st.pn,st.mpn,st.sappn,st.description,
--(st.amount+po.amount) as total_amount, po.amount as borrow_amount, ((st.amount+po.amount)-po.amount) as balance_amount,
(st.amount) as total_amount, po.amount as borrow_amount, (st.amount-po.amount) as balance_amount,
po.loan_date,po.returned_date,aird.doc_path
FROM stock_tools st
			left join picking_order po
			on st.id=po.stock_id
			left join appraisal_item_result_doc aird
            on st.id = aird.item_result_id
			where po.status=1
			and po.profile_id=?
            and po.id=?

		"
		,array($request->profile_id,$request->id)
		);
		return response()->json($items);

		
	}

	public function confirm_all_by_profile(Request $request){

		
		try {
			


			//updated start ...
			 
			foreach ($request->inventory_update as $i) {
			//	 print($i['order_id']);
			//	 print_r($i->stock_id);
			//	 print_r($i->borrow_amount);



				$items = DB::select("
						UPDATE picking_order
						SET status = 2
						WHERE profile_id = ?
						and id=	?

					"
					,array($request->profile_id,$i['order_id'])
				);


				 $inventoryUpdate = DB::select("
						UPDATE stock_tools
						SET amount = (amount-?)
						WHERE id = ?
					"
					,array($i['borrow_amount'],$i['stock_id'])
				);

			}
			
			
			//update end...

			return response()->json(['status' => 200]);
			
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'PickingOrder not found.']);
		}



	}
	
	

	public function update(Request $request, $id)
	{
		try {
			$item = PickingOrder::findOrFail($id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'PickingOrder not found.']);
		}
		
		$validator = Validator::make($request->all(), [
			'profile_id' => 'required',
			'stock_id' => 'required'
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 400, 'data' => $validator->errors()]);
		} else {
			$item->fill($request->all());
			//$item->updated_by = Auth::id();
			$item->save();
		}
	
		return response()->json(['status' => 200, 'data' => $item]);
				
	}
	
	public function destroy($id)
	{
		try {
			$item = PickingOrder::findOrFail($id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'PickingOrder not found.']);
		}	

		try {
			$item->delete();
		} catch (Exception $e) {
			if ($e->errorInfo[1] == 1451) {
				return response()->json(['status' => 400, 'data' => 'Cannot delete because this Position is in use.']);
			} else {
				return response()->json($e->errorInfo);
			}
		}
		
		return response()->json(['status' => 200]);
		
	}	
}
