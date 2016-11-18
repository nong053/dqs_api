<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use JWTAuth;
use App\User;
use Auth;
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
		$full_name = Auth::user()->full_name;
		return response()->json(['status' => 200, 'data' => ['full_name' => $full_name]]);
    }    
  
    public function authenticate(Request $request)
    {
        $credentials = $request->only('user_name', 'password');

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
		$full_name = Auth::user()->full_name;
        return response()->json(['token' => $token, 'data' => ['full_name' => $full_name]]);
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