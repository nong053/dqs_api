<?php

namespace App\Http\Controllers;

use App\DQSUser;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use JWTAuth;
use App\User;
use Auth;
use DB;
use Adldap;
use Tymon\JWTAuth\Exceptions\JWTException;
use Adldap\Exceptions\Auth\BindException;
//use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;


class AuthenticateController extends Controller
{

	public function __construct()
	{
	   $this->middleware('jwt.auth', ['only' => ['index']]);
	}
   
    public function index()
    {
		$dqs_user = DQSUser::find(Auth::user()->personnel_id);
		if (empty($dqs_user)) {
			return response()->json(['error' => 'User not found.'], 403);
		}		
		$full_name = $dqs_user->thai_full_name;
		$menus = DB::select("
			select b.menu_id, b.menu_name, b.app_url, b.menu_category
			from dqs_authorization a
			left outer join dqs_menu b
			on a.menu_id = b.menu_id
			where a.role_id = ?
		", array($dqs_user->role_id));
		return response()->json(['status' => 200, 'data' => ['full_name' => $full_name, 'menu' => $menus]]);
    }    
  
    public function authenticate(Request $request)
    {
		// if (Adldap::auth()->attempt($request->username, $request->password)) {
			// return 'pass';
		// } else {
			// return 'fail';
		// }
		
        $credentials = $request->only('user_name', 'password');
		// if (Auth::attempt($credentials)) {
			// return 'pass';
		// } else {
			// return 'fail';
		// }	
    

        try {
            // verify the credentials and create a token for the user
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
        } catch (JWTException $e) {
            // something went wrong
            return response()->json(['error' => 'could_not_create_token'], 500);
        } catch (BindException $e) {
			return response()->json(['error' => 'Cannot connect to LDAP Server.'], 500);
		}
		
        // if no errors are encountered we can return a JWT
		
		//$user = User::find($request->user_name);
		//return response()->json($user);
		$dqs_user = DQSUser::find(Auth::user()->personnel_id);
		
		if (empty($dqs_user)) {
			return response()->json(['error' => 'User not found.'], 403);
		}
		
		$full_name = $dqs_user->thai_full_name;
		
		$menus = DB::select("
			select b.menu_id, b.menu_name, b.app_url, b.menu_category
			from dqs_authorization a
			left outer join dqs_menu b
			on a.menu_id = b.menu_id
			where a.role_id = ?
		", array($dqs_user->role_id));		
        return response()->json(['token' => $token, 'data' => ['full_name' => $full_name, 'menu' => $menus]]);
    }
	
	public function destroy()
	{
		$token = JWTAuth::getToken();
		if (empty($token)) {
			return response()->json(['status' => 401,'message' => 'no token provided']);
		} else {
			try
			{
				$response = JWTAuth::invalidate(JWTAuth::getToken());
				return response()->json(['status' => 200,'t_stat' => $response]);
				
			} catch (JWTException $e) {
				return response()->json(['status' => 401, 'message' => $e->getMessage()], $e->getStatusCode());
			}
			
		}
	}
}