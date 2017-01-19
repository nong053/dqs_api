<?php

namespace App\Http\Controllers;

use App\Branch;

use Auth;
//use Adldap;
//use JWTAuth;
use DB;
use File;
use Validator;
use Excel;
use SoapClient;
use SoapHeader;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BranchController extends Controller
{

	public function __construct()
	{

	   $this->middleware('jwt.auth');
	}
   
    public function index(Request $request)
    {
		$rpp = $request->rpp;
		if (empty($rpp)) {
			$rpp = 10;
		}
        $branches = DB::table('dqs_branch')->paginate($rpp);
		return response()->json($branches);
    }
	
	public function update(Request $request)
	{
		// try {
			// $item = Branch::findOrFail($brcd);
		// } catch (ModelNotFoundException $e) {
			// return response()->json(['status' => 404, 'data' => 'Branch not found.']);
		// }
		
        // $validator = Validator::make($request->all(), [
            // 'brcd' => 'required|numeric',
            // 'desc' => 'required|max:255',
			// 'ccdef' => 'required|numeric',
			// 'region' => 'required|numeric',
			// 'regdesc' => 'required|max:255',
			// 'regcode' => 'required|numeric',
			// 'dist' => 'required|numeric',
			// 'distdesc' => 'required|max:255',
			// 'brstate' => 'required|numeric',
			// 'statedesc' => 'required|max:255',
			// 'close_flag' => 'required|boolean',
        // ]);

        // if ($validator->fails()) {
            // return response()->json(['status' => 400, 'data' => $validator->errors()]);
        // } else {
			// $item->fill($request->all());
			// $item->save();
		// }
		
		// return response()->json(['status' => 200, 'data' => $item]);
		
		$errors = array();
		$successes = array();
		
		$branches = $request->branches;
		
		if (empty($branches)) {
			return response()->json(['status' => 200, 'data' => ["success" => [], "error" => []]]);
		}
		
		foreach ($branches as $b) {
			$item = Branch::find($b["brcd"]);
			if (empty($item)) {
				$errors[] = ["brcd" => $b["brcd"], "message" => "Branch not found."];
			} else {
				if ($b["brcd"] == 1) {
					$checkbranch = DB::select("
						select count(1) count_no
						from
						(
						  select 'initial' table_type, own_branch_code
						  from dqs_initial_validate_header
						  union all
						  select 'validate' table_type, own_branch_code
						  from dqs_validate_header
						) a
						where own_branch_code = ?			
					", array($b["brcd"]));
					if ($checkbranch[0]->count_no > 0) {
						$errors[] = ["brcd" => $b["brcd"], "message" => "ไม่สามารถบันทึกข้อมูลได้เนื่องจากสาขา " . $b["brcd"] . " ตรงกับสาขาที่สร้าง CIF"];
					} else {
						// DB::statement("
							// update dqs_initial_validate
							// set contact_branch_code = b.own_branch_code,
							// contact_branch_name = b.own_branch_name
							// from dqs_initial_validate a
							// inner join dqs_initial_validate_header b 
							// on a.validate_initial_header_id = b.validate_initial_header_id
							// where a.contact_branch_code = ?		
							// and a.rule_end_date is null
						// ", array($item->brcd));
						// DB::statement("
							// update dqs_initial_validate_header 
							// set contact_branch_code = own_branch_code,
							// contact_branch_name = own_branch_name
							// where contact_branch_code = ?				
						// ", array($item->brcd));
						// DB::statement("
							// update dqs_validate
							// set contact_branch_code = b.own_branch_code,
							// contact_branch_name = b.own_branch_name
							// from dqs_validate a
							// inner join dqs_validate_header b 
							// on a.validate_header_id = b.validate_header_id
							// where a.contact_branch_code = ?		
							// and a.rule_end_date is null
						// ", array($item->brcd));
						// DB::statement("
							// update dqs_validate_header 
							// set contact_branch_code = own_branch_code,
							// contact_branch_name = own_branch_name
							// where contact_branch_code = ?				
						// ", array($item->brcd));		
						
						$init_validate = DB::select("
							select a.initial_validate_id, a.validate_initial_header_id, b.own_branch_code, b.own_branch_name
							from dqs_initial_validate a
							inner join dqs_initial_validate_header b 
							on a.validate_initial_header_id = b.validate_initial_header_id
							where a.contact_branch_code = ?
							and a.rule_end_date is null
							and a.validate_status in ('wrong','incomplete')						
						", array($b["brcd"]));
						
						$validate = DB::select("
							select a.validate_id, a.validate_header_id, b.own_branch_code, b.own_branch_name
							from dqs_validate a
							inner join dqs_validate_header b 
							on a.validate_header_id = b.validate_header_id
							where a.contact_branch_code = ?
							and a.rule_end_date is null
							and a.validate_status in ('wrong','incomplete')								
						", array($b["brcd"]));
						
						foreach ($init_validate as $i) {
							DQSInitialValidate::where("initial_validate_id",$i->initial_validate_id)->update(['contact_branch_code' => $i->own_branch_code, 'contact_branch_name' => $i->own_branch_name]);
							DQSInitialValidateHeader::where("validate_initial_header_id",$i->validate_initial_header_id)->update(['contact_branch_code' => $i->own_branch_code, 'contact_branch_name' => $i->own_branch_name]);							
						}
						
						foreach ($validate as $i) {
							DQSValidate::where("validate_id",$i->validate_id)->update(['contact_branch_code' => $i->own_branch_code, 'contact_branch_name' => $i->own_branch_name]);
							DQSValidateHeader::where("validate_header_id",$i->validate_header_id)->update(['contact_branch_code' => $i->own_branch_code, 'contact_branch_name' => $i->own_branch_name]);							
						}						
						
						$item->close_flag = 1;
						$item->updated_by = Auth::user()->personnel_id;
						$item->save();
						$sitem = ["brcd" => $item->brcd];
						$successes[] = $sitem;			
					}
				} else {
					$item->close_flag = 0;
					$item->updated_by = Auth::user()->personnel_id;
					$item->save();					
				}

			}
		}
		
		return response()->json(['status' => 200, 'data' => ["success" => $successes, "error" => $errors]]);
			
	}

	public function recal_kpi(Request $request)
	{
		set_time_limit(300);

		// $param = ' -PLocaleUTF8 -R"Repo_WIN-MPNE686ADQV.txt"  -G"a38aebe8_e108_4ea5_9ed0_ee55bfd8837b" -t5 -T14 -LocaleGV -GV"$JobName=J0tQSUNhbGN1bGF0aW9uUHJvY2Vzcyc;$G_BranchCode=" -GV"' . str_replace("=","",base64_encode($request->ccdef)) . ';"   -CtBatch -CmWIN-MPNE686ADQV -CaAdministrator -CjWIN-MPNE686ADQV -Cp3500';
		// $batch = 'C:/Development/BatchScript/OP_KPICalculation_BJ.bat';
		// $file_param = 'C:/Development/BatchScript/OP_KPICalculation_BJ.txt';
		// File::Delete($file_param);	
		// File::put($file_param, $param);
		// $result = exec($batch);
		 $client = new SoapClient("http://10.22.51.138:8080/DataServices/servlet/webservices?ver=2.1&label=run_batch&wsdlxml");
		// //$job = new OP_KPICalculation_BJ_GlobalVariables('OP_KPICalculation_BJ','8901');
		// //return $client->OP_KPICalculation_BJ(array("G_BranchCode" => 8901));
		//return var_dump($client->__getTypes()); 

//return var_dump($client->__getFunctions()); 
		// //return $client->OP_KPICalculation_BJ_GlobalVariables();
		// //$params = array("OP_KPICalculation_BJ_GlobalVariables" => $job);
		// $params = array("OP_KPICalculation_BJ_GlobalVariables" => array("jobname" => 'OP_KPICalculation_BJ', "g_branchcode" => '8901'));
		$getSession = $client->__soapCall("Logon", array("LogonRequest" => array(
			 "username" => "Administrator",
			 "password" => "Tok8kivv,lbo",
			 "cms_system" => "DEVWS12R2WEB138",
			 "cms_authentication" => "secEnterprise"
		 )));
		 $sessionID =  $getSession->SessionID;
		 
		$security = array('SessionID' => $sessionID);

		$header = new SoapHeader('http://www.businessobjects.com/DataServices/ServerX.xsd','session',$security, false);

		$setHeader = $client->__setSoapHeaders($header);		 
		// $client->OP_TestWeb_BJ();	
 		
		$runJob = $client->__soapCall("OP_KPICalculation_BJ", array(
			"OP_KPICalculation_BJ_GlobalVariables" => array(
				"JobName" => "OP_KPICalculation_BJ",
				"G_BranchCode" => $request->ccdef
			)
		));
		return response()->json(['status' => 200, 'data' => (array)$runJob]);
		
	
				 
		 
		  // string jobName;
 // string repoName;
 // string jobServer;
 // string serverGroup;
		//return $client->OP_TestWeb_BJ(array("OP_TestWeb_BJ_Input" => ""));
		// echo $client->__getLastResponse();
		// return 1;

		return response()->json();
	}
	

	public function export()
	{
		$x = Excel::create('Filename', function($excel) {

			$excel->sheet('Sheetname', function($sheet) {

				$sheet->fromArray(array('data1', 'data2'));
				$sheet->appendRow(array('data1', 'data23','data4'));
				$sheet->appendRow(array('data1', 'data23','data4'));

			});

		})->export('xls');	
		return response()->json($x);
	}
	
}
