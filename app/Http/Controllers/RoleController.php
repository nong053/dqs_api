<?php

namespace App\Http\Controllers;

use App\DQSRole;
use App\DQSAuth;

use DB;
use Validator;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class RoleController extends Controller
{

	public function __construct()
	{

	   $this->middleware('jwt.auth');
	}
   
    public function index()
    {
        $items = DB::select("
			select role_id, role_name, authority_flag, all_branch_flag
			from dqs_role
		");
		return response()->json($items);
    }
	
	public function store(Request $request)
	{
        $validator = Validator::make($request->all(), [
            'role_name' => 'required|max:255',
			'authority_flag' => 'required|boolean',
			'all_branch_flag' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'data' => $validator->errors()]);
        } else {
			$item = new DQSRole;
			$item->role_name = $request->role_name;
			$item->authority_flag = $request->authority_flag;
			$item->all_branch_flag = $request->all_branch_flag;
			$item->created_by = Auth::user()->personnel_id;
			$item->updated_by = Auth::user()->personnel_id;
			$item->save();
		}
		
		return response()->json(['status' => 200, 'data' => $item]);	
	}
	
	public function update(Request $request, $role_id)
	{
		try {
			$item = DQSRole::findOrFail($role_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Role not found.']);
		}
		
        $validator = Validator::make($request->all(), [
            'role_name' => 'required|max:255',
			'authority_flag' => 'required|boolean',
			'all_branch_flag' => 'required|boolean'
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
	
	public function destroy($role_id)
	{	
		try {
			$item = DQSRole::findOrFail($role_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Role not found.']);
		}	

		try {
			$item->delete();
		} catch (QueryException $e) {
			if ($e->errorInfo[1] == 547) {
				return response()->json(['status' => 400, 'data' => 'Foreign key conflict error. Please remove this Role from Authorization first.']);
			} else {
				return response()->json($e);
			}
		}
		
		return response()->json(['status' => 200]);		
		
	}
	
	public function roleauth($role_id)
	{
		try {
			$item = DQSRole::findOrFail($role_id);
		} catch (ModelNotFoundException $e) {
			//return response()->json(['status' => 404, 'data' => 'Role not found.']);
			return response()->json();
		}
		
		$items = DB::select("
			select a.menu_id, a.menu_name, 
			  (
				case when b.role_id is null then 0
				else 1
				end
			  ) role_active
			from dqs_menu a 
			left outer join dqs_authorization b
			on a.menu_id = b.menu_id
			and b.role_id = ?
		", array($role_id));
		
		return response()->json($items);
	}
	
	public function authorization(Request $request, $role_id)
	{
	
		try {
			$item = DQSRole::findOrFail($role_id);
		} catch (ModelNotFoundException $e) {
			return response()->json(['status' => 404, 'data' => 'Role not found.']);
		}
		
		DB::table('dqs_authorization')->where('role_id',$role_id)->delete();
		
		foreach ($request->menus as $menu) {
			$auth = new DQSAuth;
			$auth->role_id = $role_id;
			$auth->menu_id = $menu;
			$auth->created_by = Auth::user()->personnel_id;
			$auth->save();
		}
		
		$authorizes = DB::select("
			select *
			from dqs_authorization
			where role_id = ?
		", array($role_id));
		
		return response()->json(['status' => 200, 'data' => $authorizes]);
	}
		
	
}