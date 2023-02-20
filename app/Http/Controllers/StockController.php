<?php

namespace App\Http\Controllers;

use App\Stock;
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

class StockController extends Controller
{

	public function __construct()
	{

	   $this->middleware('jwt.auth');
	}
	
	// Stock_ID	CARD_ID	PASSPORT_ID	TITLE	FIRST_NAME	LAST_NAME	GENDER	NATIONALITY	DATE_OF_BIRTH	RELIGION	ADDRESS	CREATED_DATE	CREATED_BY	UPDATED_DATE	UPDATED_BY	ACTIVE_FLAG
	public function index(Request $request)
	{		
		$items = DB::select("
			SELECT * FROM stock_tools  st
left join appraisal_item_result_doc aird 
on st.id=aird.item_result_id
order by st.id
		"
		//,array('%'.$request->Stock_id.'%')
	);
		return response()->json($items);
	}
	
	
	public function store(Request $request)
	{
	
		$validator = Validator::make($request->all(), [
			'category' => 'required',
			// 'LAST_NAME' => 'required',
			// 'POSITION' => 'required',
			// 'ACTIVE_FLAG' => 'required|integer',
		]);

		if ($validator->fails()) {
			return response()->json(['status' => 400, 'data' => $validator->errors()]);
		} else {
			$item = new Stock;
			$item->fill($request->all());
			// $item->created_by = Auth::id();
			// $item->updated_by = Auth::id();
			 $item->save();
		}
	
		return response()->json(['status' => 200, 'data' => $item,'id'=>$item->id]);	
	}
	
	public function show(Request $request,$id)
	{
	
		
		try {

			$item = Stock::findOrFail($id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Stock not found.']);
		}
		return response()->json($item);
		
	}
	
	public function update(Request $request, $id)
	{
		try {
			$item = Stock::findOrFail($id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Stock not found.']);
		}
		
		$validator = Validator::make($request->all(), [
			'category' => 'required'
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
			$item = Stock::findOrFail($id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Stock not found.']);
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

	public function returned_tools_amount_in_stock(Request $request){

		
		try {
			$items = DB::select("
						UPDATE stock_tools
						SET amount = (amount+?)
						WHERE id = ?

					"
					,array($request->amount,$request->stock_id)
			);
			return response()->json(['status' => 200]);
			
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'PickingOrder not found.']);
		}



	}


	public function upload_files(Request $request,$item_result_id )
	{
		
		
		
		$result = array();	
			
			$path = $_SERVER['DOCUMENT_ROOT'] . '/ssi/api/public/stock_files/' . $item_result_id . '/';
			foreach ($request->file() as $f) {
				$filename = iconv('UTF-8','windows-874',$f->getClientOriginalName());
				//$f->move($path,$filename);
				$f->move($path,$f->getClientOriginalName());
				//echo $filename;
				
				 $item = AttachFile::firstOrNew(array('doc_path' => 'stock_files/' . $item_result_id . '/' . $f->getClientOriginalName()));
				
				 $item->item_result_id = $item_result_id;
				 $item->created_by = Auth::id();
				
				// //print_r($item);
				 $item->save();
				$result[] = $item;
				//echo "hello".$f->getClientOriginalName();



			}
		
		return response()->json(['status' => 200, 'data' => $result]);
		//return response()->json(['status' => 200]);
	}

	public function import(Request $request)
	{
		set_time_limit(0);
		$errors = array();
		foreach ($request->file() as $f) {
			$items = Excel::load($f, function($reader){})->get();	
			DB::select("delete from stock_tools");		
			foreach ($items as $i) {
					
					
					$validator = Validator::make($i->toArray(), [
						'pn' => 'required'
						
						
					]);

					if ($validator->fails()) {
						$errors[] = ['errors' => $validator->errors()];
					} else{
						// echo($i->pn);
						// echo($i->mpn);
						// echo($i->sappn);

						$items = DB::select("
							insert into stock_tools(category,pn,mpn,sappn,amount,active,description,created_date,updated_date,nsn,inventory)
											 values(?,?,?,?,?,?,?,now(),now(),?,?)
						", array($i->category,$i->pn,$i->mpn,$i->sappn,$i->amount,$i->active,$i->description,$i->nsn,$i->inventory));

						


					}
			}	
		}
		return response()->json(['status' => 200, 'errors' => $errors]);
	}	
	public function upload_files_list(Request $request)
	{
		$items = DB::select("
			SELECT result_doc_id,doc_path 
			FROM appraisal_item_result_doc
			where  item_result_id=?
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
			where  item_result_id=?
			order by result_doc_id;
		", array($request->item_result_id));


		// } catch (ModelNotFoundException $e) {
		// 	return response()->json(['status' => 404, 'data' => 'File not found.']);
		// }
		           //$_SERVER['DOCUMENT_ROOT'] . '/see_api/public/attach_files/' . $item_result_id . '/';

		foreach ($items as $i) {
			File::Delete($_SERVER['DOCUMENT_ROOT'] . '/ssi/api/public/'.$i->doc_path);		
			$delPicture = DB::select("
			DELETE  
			FROM appraisal_item_result_doc
			where  result_doc_id=?
			", array($i->result_doc_id));
		}
		 File::deleteDirectory($_SERVER['DOCUMENT_ROOT'] . '/ssi/api/public/stock_files/'.$request->item_result_id);

		return response()->json(['status' => 200]);

	}

	
	
		
}
