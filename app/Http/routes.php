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
	//header('Keep-Alive: timeout=10, max=100');
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
	Route::post('session/log', 'AuthenticateController@usage_log');
	Route::delete('session', 'AuthenticateController@destroy');
	
	// Branch //
	Route::get('dqs_branch', 'BranchController@index');
	Route::patch('dqs_branch', 'BranchController@update');
	Route::post('dqs_branch/export', 'BranchController@export');
	Route::post('dqs_branch/recal_kpi', 'BranchController@recal_kpi');
	
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
	Route::post('dqs_system_config/default_role', 'SystemConfigController@default_role');
	
	// User //
	Route::get('dqs_user/cost_center', 'UserController@auto_cost_center');
	Route::get('dqs_user/revised_cost_center', 'UserController@list_revised_cost_center');
	Route::get('dqs_user/personnel_id', 'UserController@auto_personnel');
	Route::patch('dqs_user', 'UserController@update');
	Route::get('dqs_user', 'UserController@index');
	Route::post('dqs_user/export', 'UserController@export');
	
	// Rule //
	Route::delete('dqs_rule/{rule_id}', 'RuleController@destroy');
	Route::post('dqs_rule', 'RuleController@store');
	Route::patch('dqs_rule/{rule_id}', 'RuleController@update');
	Route::get('dqs_rule/rule_name', 'RuleController@auto_rule');
	Route::get('dqs_rule/{rule_id}', 'RuleController@show');
	Route::get('dqs_rule', 'RuleController@index');
	Route::get('dqs_data_flow', 'RuleController@list_data_flow'); // To be moved
	Route::patch('dqs_rule' ,'RuleController@update_flags');
	
	// Region //
	Route::post('dqs_branch/region/getRegionName', 'RegionController@getRegionName');
	Route::resource('dqs_region', 'RegionController');	
	
	// Grade //
	Route::get('dqs_grade/rule_list', 'GradeController@list_rule');
	Route::get('dqs_grade/{grade_id}/condition', 'GradeController@list_condition');
	Route::post('dqs_grade/{grade_id}/condition', 'GradeController@add_condition');
	Route::patch('dqs_grade/{grade_id}/condition', 'GradeController@update_condition');
	Route::delete('dqs_grade/{grade_id}/condition/{condition_id}', 'GradeController@delete_condition');
	Route::resource('dqs_grade', 'GradeController');
	
	// Import/Export //
	Route::get('dqs_import_export/cust_type', 'ImportExportController@list_cust_type');
	Route::post('dqs_import_export/export', 'ImportExportController@export_citizen');
	Route::post('dqs_import_export/export_sms', 'ImportExportController@export_sms');
	Route::post('dqs_import_export/import', 'ImportExportController@import_citizen');
	Route::post('dqs_import_export/import_sms', 'ImportExportController@import_sms');
	
	// Citizen Import //
	Route::post('dqs_citizen_import/npid', 'CitizenImportController@auto_npid');
	Route::resource('dqs_citizen_import', 'CitizenImportController');
	
	// Monitoring //
	Route::get('dqs_monitoring/cust_type', 'MonitoringController@list_cust_type');
	Route::get('dqs_monitoring/branch_list', 'MonitoringController@list_branch');
	Route::get('dqs_monitoring/rule', 'MonitoringController@list_rule');
	Route::post('dqs_monitoring/cdmd/export', 'MonitoringController@cdmd_export');		// add to doc
	Route::get('dqs_monitoring/cdmd/{header_id}', 'MonitoringController@cdmd_details');
	Route::patch('dqs_monitoring/cdmd/{header_id}', 'MonitoringController@cdmd_update');
	Route::get('dqs_monitoring/cdmd/{header_id}/explain', 'MonitoringController@cdmd_explain_details');
	Route::patch('dqs_monitoring/cdmd/{header_id}/explain', 'MonitoringController@cdmd_update_explain');
	Route::get('dqs_monitoring/cdmd', 'MonitoringController@cdmd_index');
	Route::post('dqs_monitoring/branch/export', 'MonitoringController@branch_export');		// add to doc
	Route::patch('dqs_monitoring/branch/{header_id}', 'MonitoringController@branch_update');
	Route::get('dqs_monitoring/branch/{header_id}', 'MonitoringController@branch_details');
	Route::get('dqs_monitoring/branch/{header_id}/explain', 'MonitoringController@branch_explain_details');
	Route::patch('dqs_monitoring/branch/{header_id}/explain', 'MonitoringController@branch_update_explain');
	Route::get('dqs_monitoring/branch', 'MonitoringController@branch_index');
	Route::post('dqs_monitoring/branch/{header_id}/explain', 'MonitoringController@branch_upload_explain');
	Route::delete('dqs_monitoring/branch/{header_id}/explain/{file_id}', 'MonitoringController@branch_delete_explain');
	
	// Maintenance //
	Route::get('dqs_maintenance/contact_type', 'MaintenanceController@contact_type');
	Route::get('dqs_maintenance/import_log', 'MaintenanceController@import_log');
	Route::post('dqs_maintenance/import_log/export', 'MaintenanceController@export_import_log');
	Route::get('dqs_maintenance/reject_log', 'MaintenanceController@reject_log');
	Route::post('dqs_maintenance/reject_log/export', 'MaintenanceController@export_reject_log');
	Route::get('dqs_maintenance/usage_log', 'MaintenanceController@usage_log');
	Route::post('dqs_maintenance/usage_log/export', 'MaintenanceController@export_usage_log');
	Route::post('dqs_maintenance/personnel_name', 'MaintenanceController@auto_personnel_name');
	
	// Operation Report//
	Route::get('dqs_operation_report/province_list', 'OperationReportController@list_province');
	Route::post('dqs_operation_report/auto_name', 'OperationReportController@auto_name');
	Route::post('dqs_operation_report/auto_surname', 'OperationReportController@auto_surname');
	Route::get('dqs_operation_report/operation_list', 'OperationReportController@list_operation');
	Route::get('dqs_operation_report/region_list', 'OperationReportController@list_region');
	Route::get('dqs_operation_report/district_list', 'OperationReportController@list_district');
	Route::get('dqs_operation_report/branch_list', 'OperationReportController@list_branch');
	Route::get('dqs_operation_report/no_progress', 'OperationReportController@no_progress');
	Route::post('dqs_operation_report/no_progress/export', 'OperationReportController@no_progress_export');
	Route::get('dqs_operation_report/progressed', 'OperationReportController@progressed');
	Route::post('dqs_operation_report/progressed/export', 'OperationReportController@progressed_export');
	Route::get('dqs_operation_report/customer', 'OperationReportController@customer');	
	Route::post('dqs_operation_report/customer/export', 'OperationReportController@customer_export');	
	Route::get('dqs_operation_report/overdue_kpi', 'OperationReportController@overdue_kpi');	
	Route::post('dqs_operation_report/overdue_kpi/export', 'OperationReportController@overdue_kpi_export');	
	Route::get('dqs_operation_report/merge_cif', 'OperationReportController@merge_cif');
	Route::post('dqs_operation_report/merge_cif/export', 'OperationReportController@merge_cif_export');
	Route::get('dqs_operation_report/kpi_result', 'OperationReportController@kpi_result');
	Route::post('dqs_operation_report/kpi_result/export', 'OperationReportController@kpi_result_export');	
	
	Route::get('404', ['as' => 'notfound', function () {
		return response()->json(['status' => '404']);
	}]);

	Route::get('405', ['as' => 'notallow', function () {
		return response()->json(['status' => '405']);
	}]);	
});



