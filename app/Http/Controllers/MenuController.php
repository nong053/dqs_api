<?php

namespace App\Http\Controllers;

use App\Menu;
use App\DQSAuth;

use DB;
use Validator;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class MenuController extends Controller
{

	public function __construct()
	{

	   $this->middleware('jwt.auth');
	}
   
    public function index()
    {
        $items = DB::select("
			select menu_id, menu_name, app_url, menu_category
			from dqs_menu
		");
		return response()->json($items);
    }
	
	public function show($menu_id)
	{
		try {
			$item = Menu::findOrFail($menu_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Menu not found.']);
		}
		return response()->json($item);
	}
	
	public function store(Request $request)
	{
        $validator = Validator::make($request->all(), [
            'menu_name' => 'required|max:255|unique:dqs_menu',
			'app_url' => 'required|max:255',
			'menu_category' => 'required|size:2'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'data' => $validator->errors()]);
        } else {
			$item = new Menu;
			$item->menu_name = $request->menu_name;
			$item->app_url = $request->app_url;
			$item->menu_category = $request->menu_category;
			$item->created_by = Auth::user()->personnel_id;
			$item->updated_by = Auth::user()->personnel_id;
			$item->save();
		}
		
		return response()->json(['status' => 200, 'data' => $item]);	
	}
	
	public function update(Request $request, $menu_id)
	{
		try {
			$item = Menu::findOrFail($menu_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Menu not found.']);
		}
		
        $validator = Validator::make($request->all(), [
            'menu_name' => 'required|max:255|unique:dqs_menu,menu_name,'.$item->menu_id . ',menu_id',
			'app_url' => 'required|max:255',
			'menu_category' => 'required|size:2'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'data' => $validator->errors()]);
        } else {
			$item->fill($request->all());
			$item->updated_by = Auth::user()->personnel_id;
			$item->save();
		}
		
		return response()->json(['status' => 200, 'data' => $item]);
				
	}
	
	public function menuauth($menu_id)
	{
		try {
			$item = Menu::findOrFail($menu_id);
		} catch (ModelNotFoundException $e) {
			//return response()->json(['status' => 404, 'data' => 'Role not found.']);
			return response()->json();
		}
		
		$items = DB::select("
			select a.role_id, a.role_name, 
			  (
				case when b.menu_id is null then 0
				else 1
				end
			  ) menu_active
			from dqs_role a 
			left outer join dqs_authorization b
			on a.role_id = b.role_id
			and b.menu_id = ?
		", array($menu_id));
		
		return response()->json($items);
	}	
	
	public function authorization(Request $request, $menu_id)
	{
	
		try {
			$item = Menu::findOrFail($menu_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Menu not found.']);
		}
		
		DB::table('dqs_authorization')->where('menu_id',$menu_id)->delete();
		
		if (empty($request->roles)) {
			
			
		} else {
			foreach ($request->roles as $role) {
				$auth = new DQSAuth;
				$auth->role_id = $role;
				$auth->menu_id = $menu_id;
				$auth->created_by = Auth::user()->personnel_id;
				$auth->save();
			}
		}
			
		$authorizes = DB::select("
			select *
			from dqs_authorization
			where menu_id = ?
		", array($menu_id));
		
		return response()->json(['status' => 200, 'data' => $authorizes]);
	}
	
	public function destroy($menu_id)
	{
		try {
			$item = Menu::findOrFail($menu_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Menu not found.']);
		}	

		try {
			$item->delete();
		} catch (QueryException $e) {
			if ($e->errorInfo[1] == 547) {
				return response()->json(['status' => 400, 'data' => 'Foreign key conflict error. Please remove this Menu from Authorization first.']);
			} else {
				return response()->json($e);
			}
		}
		
		return response()->json(['status' => 200]);
		
	}
	
}