<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/
if (isset($_SERVER['HTTP_ORIGIN'])) {
	header('Access-Control-Allow-Credentials: true');
	header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
	header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
	header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, useXDomain, withCredentials');
}
// Route::get('/', function () {
    // return Response::json(array('hello' => 'hehe'));
// });

//Route::resource('authenticate', 'AuthenticateController', ['only' => ['index']]);
Route::group(['middleware' => 'cors'], function()
{	
	// Session //
	Route::get('session','AuthenticateController@index');
	Route::post('session', 'AuthenticateController@authenticate');
	Route::delete('session', 'AuthenticateController@destroy');
	
	// Branch //
	Route::get('dqs_branch', 'BranchController@index');
	Route::patch('dqs_branch/', 'BranchController@update');
	Route::get('dqs_branch/export', 'BranchController@export');
	
	// Branch Operation //
	Route::resource('dqs_branch_operation', 'BranchOperationController');
	
	// File Management //
	Route::get('dqs_file', 'FileController@index');
	Route::get('dqs_file/contact_type', 'FileController@contact_type_list');
	Route::patch('dqs_file', 'FileController@update');
	Route::get('dqs_file/{file_id}', 'FileController@show');

	// Role //
	Route::get('dqs_role/{role_id}/authorize', 'RoleController@roleauth');
	Route::post('dqs_role/{role_id}/authorize', 'RoleController@authorization');
	Route::resource('dqs_role', 'RoleController');
	
	// Menu //
	Route::get('dqs_menu/{menu_id}/authorize', 'MenuController@menuauth');
	Route::post('dqs_menu/{menu_id}/authorize', 'MenuController@authorization');
	Route::resource('dqs_menu', 'MenuController');	
	
	// System Configuration //
	Route::get('dqs_system_config', 'SystemConfigController@index');
	Route::post('dqs_system_config/kpi_date', 'SystemConfigController@kpi_date');
	Route::post('dqs_system_config/export_file', 'SystemConfigController@export_file');
	Route::post('dqs_system_config/import_file', 'SystemConfigController@import_file');
	Route::post('dqs_system_config/warning_branch', 'SystemConfigController@warning_branch');
	Route::post('dqs_system_config/grade_date', 'SystemConfigController@grade_date');
	
	// User //
	Route::get('dqs_user/cost_center', 'UserController@auto_cost_center');
	Route::get('dqs_user/revised_cost_center', 'UserController@list_revised_cost_center');
	Route::get('dqs_user/personnel_id', 'UserController@auto_personnel');
	Route::patch('dqs_user', 'UserController@update');
	Route::get('dqs_user', 'UserController@index');
	
	// Rule //
	Route::delete('dqs_rule/{rule_id}', 'RuleController@destroy');
	Route::post('dqs_rule', 'RuleController@store');
	Route::patch('dqs_rule/{rule_id}', 'RuleController@update');
	Route::get('dqs_rule/{rule_name}', 'RuleController@auto_rule');
	Route::get('dqs_rule/{rule_id}', 'RuleController@show');
	Route::get('dqs_rule', 'RuleController@index');
	Route::get('dqs_data_flow', 'RuleController@list_data_flow');
	
	// Region //
	Route::get('dqs_branch/region/{region_code}', 'RegionController@getRegionName');
	Route::resource('dqs_region', 'RegionController');	
	
	// Grade //
	Route::get('dqs_grade/{grade_id}/condition', 'GradeController@list_condition');
	Route::post('dqs_grade/{grade_id}/condition', 'GradeController@add_condition');
	Route::patch('dqs_grade/{grade_id}/condition/{condition_id}', 'GradeController@update_condition');
	Route::delete('dqs_grade/{grade_id}/condition/{condition_id}', 'GradeController@delete_condition');
	Route::resource('dqs_grade', 'GradeController');
	
	// Import/Export //
	Route::post('dqs_import_export', 'ImportExportController@upload');
	
	Route::get('404', ['as' => 'notfound', function () {
		return response()->json(['status' => '404']);
	}]);

	Route::get('405', ['as' => 'notallow', function () {
		return response()->json(['status' => '405']);
	}]);	
});



