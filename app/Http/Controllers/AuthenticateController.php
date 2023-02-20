<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\User;
use App\AppraisalCriteria;
use App\SystemConfiguration;
use App\UsageLog;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use JWTAuth;
use Auth;
use Validator;
use DB;
use Mail;
use Hash;
use Adldap;
use Exception;
use SoapClient;
use Tymon\JWTAuth\Exceptions\JWTException;
use Adldap\Exceptions\Auth\BindException;
//use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;


class AuthenticateController extends Controller
{

	public function __construct()
	{
		$this->middleware('jwt.auth', ['only' => ['index']]);
	}
   
    public function index(Request $request)
    {
			
		
		// $emp = DB::select("
		// 	select b.is_hr,a.emp_id
		// 	from employee a
		// 	inner join appraisal_level b
		// 	on a.level_id = b.level_id
		// 	where a.emp_code = 'admin'
		// ", array(Auth::id()));
		
		
		// if (empty($emp)) {
		// 	return response()->json(['error' => 'User not found.'], 500);
		// }

		// $profile = DB::select("
		// 	select profile_id,email,title,first_name,last_name,role 
		// 	from profile 
		// 	where email=?
		// ", array(Auth::id()));

		
		

		
		//return response()->json(['status' => 200, 'theme_color' => $config->theme_color, 'is_hr' => $emp[0]->is_hr, 'emp_id' => $emp[0]->emp_id]);


		//return response()->json(['status' => 200, 'profile' =>$profile]);

		return response()->json(['status' => 200]);
    }    
	
	public function debug(Request $request)
	{
		// $a = [['a' => 1, 'b' => 2], ['a' => 2, 'b' => 1], ['a' => 1, 'b' => 2], ['a' => 3, 'b' => 2]];
		return 'done';
		// $a = array_unique($a,SORT_REGULAR);
		
		// return $a;
		// // Get array keys
		// $arrayKeys = array_keys($a);
		// // Fetch last array key
		// $lastArrayKey = array_pop($arrayKeys);
		// //iterate array
		// $string = '';
		// foreach($a as $k => $v) {
			// if($k == $lastArrayKey) {
				// //during array iteration this condition states the last element.
				// $string .= $v;
			// } else {
				// $string .= $v . ',';
			// }
		// }		
	//	$hash = Hash::make('secret');
		//return $hash;
		$mail_body = "
Hello from TYW KPI,

You have been appraised please click https://www.google.com

Best Regards,

From Going Jesse Team
		";
		$error = '';
		try {
		Mail::raw($mail_body, function($message)
		{
			$message->from('msuksang@gmail.com', 'TYW Team');

			$message->to('methee@goingjesse.com')->subject('You have been Appraised :-)');
		});
		} catch (Exception $e)
		{
			$error = $e->getMessage();
		}
		
		// Mail::later(5,'emails.welcome', array('msg' => $mail_body), function($message)
		// {
			// $message->from('msuksang@gmail.com', 'TYW Team');

			// $message->to('methee@goingjesse.com')->subject('You have been Appraised :-)');
		// });	
		
		return response()->json(['error' => $error]);
		
		
		//return response()->json($test);
	}

	public function authenticate(Request $request)
    {	

    
				

        try {
            // verify the credentials and create a token for the user
            if (! $token = JWTAuth::attempt(array('email' => $request->username, 'password' => $request->password))) {

    //         	$user = new User;
				// $user->email = 'xxx';
				// $user->password = Hash::make('xxx');	
				// $user->save(); 

                return response()->json(['error' => 'invalid_credentials'], 401);


            }
        } catch (JWTException $e) {
            // something went wrong
            return response()->json(['error' => 'could_not_create_token'], 500);
        } catch (BindException $e) {
			return response()->json(['error' => 'Cannot connect to Server.'], 500);
		}
		
        // if no errors are encountered we can return a JWT
		
		$emp = DB::select("
			SELECT * FROM profile 
			where email=? and ACTIVE_FLAG=1
		", array($request->username));
		
		if (empty($emp)) {
			return response()->json(['error' => 'User not found.'], 500);
		}
		
		/*
		$u = new UsageLog;
		//$u->emp_code = Auth::id();
		$u->profile_id = $emp[0]->profile_id;
		$u->save();
		*/
         return response()->json(['token' => $token,'status' => $emp[0]->STATUS,'profile_id' => $emp[0]->profile_id,'title' => $emp[0]->TITLE,'first_name' => $emp[0]->FIRST_NAME,'last_name' => $emp[0]->LAST_NAME,'position' => $emp[0]->POSITION,'role' => $emp[0]->role]);
        //  return response()->json(['token' => $token]);
		
    }


    public function authenticate_ldap(Request $request)
    {	
  
        //check ldap here start..

		/* Initialize webservice with your WSDL */
		$client = new SoapClient("http://10.107.1.123/ldap/service.asmx?wsdl");

		/* Set your parameters for the request */
		// $userNameArray= (explode("@",$request->username));
		// $username=$userNameArray[0];
		$params = array(
		  // "user" => $username,
		  "user" => $request->username,
		  "pass" => $request->password,
		  "SecCode" => "gJ9JOGMF+f+64mEmgN4xMqMiCHH5+anE",
		);

		/* Invoke webservice method with your parameters, in this case: Function1 */
		$response = $client->__soapCall("ldap_login", array($params));

		/* Convert Object to Array */
		$txt = $response->ldap_loginResult;
		//echo $txt,"<hr>";
		$cols = explode(":", $txt);

		//Input array to variable
		$uname = $cols[0];
		$fullname = $cols[1];
		$fullnameArray= (explode(" ",$fullname));
		$title=$fullnameArray[0];
		$first_name=$fullnameArray[1];
		$last_name=$fullnameArray[2];
		$position = $cols[2];

		//$email = $cols[3];
		//echo $uname," ",$tname," ",$position," ",$email, "<hr>";
		if($uname != "null"){
			//echo "ok";
        	//if($request->username=='userTest@rtaf.mi.th' and  $request->password=='1234'){
        		//echo "ok";

				$user = DB::select("
							SELECT * FROM profile 
							where email=?
						", array($request->username."@rtaf.mi.th"));
						
						if (empty($user)) {
							//not has user insert
							$insert_user = DB::select("
							INSERT INTO profile (TITLE,FIRST_NAME,LAST_NAME,email,position, password)
							VALUES (?,?,?,?,?,?);
							", array($title,$first_name,$last_name,$request->username."@rtaf.mi.th",$position,bcrypt($request->password)));

							

						}else{
							//has user update
							$update_user = DB::select("
							UPDATE profile
							SET password = ?,TITLE = ?,FIRST_NAME = ?,LAST_NAME = ?,POSITION = ?
							WHERE email = ?;
							", array(bcrypt($request->password),$title,$first_name,$last_name,$position,$request->username."@rtaf.mi.th"));
							

						}


						 try {





					            // verify the credentials and create a token for the user
					            if (! $token = JWTAuth::attempt(array('email' => $request->username."@rtaf.mi.th", 'password' => $request->password))) {


					                return response()->json(['error' => 'invalid_credentials'], 401);


					            }
					        } catch (JWTException $e) {
					            // something went wrong
					            return response()->json(['error' => 'could_not_create_token'], 500);
					        } catch (BindException $e) {
								return response()->json(['error' => 'Cannot connect to Server.'], 500);
							}
							
					        // if no errors are encountered we can return a JWT
							
							$emp = DB::select("
								SELECT * FROM profile 
								where email=?


							", array($request->username."@rtaf.mi.th"));
							
							if (empty($emp)) {
								return response()->json(['error' => 'User not found.'], 500);
							}


							
							
					         return response()->json(['token' => $token,'status' => $emp[0]->STATUS,'profile_id' => $emp[0]->profile_id,'title' => $emp[0]->TITLE,'first_name' => $emp[0]->FIRST_NAME,'last_name' => $emp[0]->LAST_NAME,'position' => $emp[0]->POSITION,'role' => $emp[0]->role]);

					        //  return response()->json(['token' => $token]);
        


		}else{
			return response()->json(['error' => 'Username or Password is Incorrect'], 401);
		}



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