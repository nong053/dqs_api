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
	Route::get('session/debug', 'AuthenticateController@debug');
	Route::delete('session', 'AuthenticateController@destroy');
	
	// Common Data Set //
	Route::get('cds/al_list','CommonDataSetController@al_list');
	Route::get('cds/connection_list','CommonDataSetController@connection_list');
	Route::post('cds/auto_cds','CommonDataSetController@auto_cds_name');
	Route::patch('cds/{id}','CommonDataSetController@update');
	Route::get('cds/{id}','CommonDataSetController@show');
	Route::delete('cds/{id}','CommonDataSetController@destroy');
	Route::post('cds','CommonDataSetController@store');
	Route::get('cds','CommonDataSetController@index');
	Route::post('cds/test_sql','CommonDataSetController@test_sql');
	Route::post('cds/copy','CommonDataSetController@copy');
	
	// Appraisal Item //
	Route::post('appraisal_item','AppraisalItemController@store');
	Route::get('appraisal_item/al_list','AppraisalItemController@al_list');
	Route::get('appraisal_item/remind_list','AppraisalItemController@remind_list');
	Route::get('appraisal_item/value_type_list','AppraisalItemController@value_type_list');
	Route::get('appraisal_item/department_list','AppraisalItemController@department_list');
	Route::get('appraisal_item/uom_list','AppraisalItemController@uom_list');	
	Route::get('appraisal_item/cds_list','AppraisalItemController@cds_list');
	Route::get('appraisal_item/perspective_list','AppraisalItemController@perspective_list');
	Route::get('appraisal_item/structure_list','AppraisalItemController@structure_list');
	Route::post('appraisal_item/auto_appraisal_name','AppraisalItemController@auto_appraisal_name');
	Route::post('appraisal_item/copy','AppraisalItemController@copy');
	Route::get('appraisal_item','AppraisalItemController@index');
	Route::get('appraisal_item/{item_id}','AppraisalItemController@show');
	Route::patch('appraisal_item/{item_id}','AppraisalItemController@update');
	Route::delete('appraisal_item/{item_id}','AppraisalItemController@destroy');
	
	// Import Employee //
	Route::get('import_employee/role_list','ImportEmployeeController@role_list');
	Route::get('import_employee/dep_list','ImportEmployeeController@dep_list');
	Route::get('import_employee/sec_list','ImportEmployeeController@sec_list');
	Route::get('import_employee/auto_position_name','ImportEmployeeController@auto_position_name');
	Route::post('import_employee/auto_employee_name','ImportEmployeeController@auto_employee_name');
	Route::get('import_employee/{emp_code}/role', 'ImportEmployeeController@show_role');
	Route::patch('import_employee/{emp_code}/role', 'ImportEmployeeController@assign_role');
	Route::patch('import_employee/role', 'ImportEmployeeController@batch_role');
	Route::get('import_employee','ImportEmployeeController@index');
	Route::get('import_employee/{emp_id}', 'ImportEmployeeController@show');
	Route::patch('import_employee/{emp_id}', 'ImportEmployeeController@update');
	Route::delete('import_employee/{emp_id}', 'ImportEmployeeController@destroy');
	Route::post('import_employee', 'ImportEmployeeController@import');
	
	// CDS Result //
	Route::get('cds_result/al_list','CDSResultController@al_list');
	Route::get('cds_result/year_list', 'CDSResultController@year_list');
	Route::get('cds_result/month_list', 'CDSResultController@month_list');
	Route::post('cds_result/auto_position_name', 'CDSResultController@auto_position_name');
	Route::post('cds_result/auto_emp_name', 'CDSResultController@auto_emp_name');
	Route::get('cds_result', 'CDSResultController@index');
	Route::post('cds_result/export', 'CDSResultController@export');
	Route::post('cds_result', 'CDSResultController@import');
	Route::delete('cds_result/{cds_result_id}','CDSResultController@destroy');
	
	// Appraisal Data //
	Route::get('appraisal_data/structure_list','AppraisalDataController@structure_list');
	Route::get('appraisal_data/al_list','AppraisalDataController@al_list');
	Route::get('appraisal_data/period_list','AppraisalDataController@period_list');
	Route::get('appraisal_data/appraisal_type_list','AppraisalDataController@appraisal_type_list');
	Route::post('appraisal_data/auto_appraisal_item','AppraisalDataController@auto_appraisal_item');
	Route::post('appraisal_data/auto_emp_name','AppraisalDataController@auto_emp_name');
	Route::post('appraisal_data/calculate_weight','AppraisalDataController@calculate_weight');
	Route::get('appraisal_data','AppraisalDataController@index');
	Route::post('appraisal_data/export','AppraisalDataController@export');
	Route::post('appraisal_data','AppraisalDataController@import');
	
	// Appraisal Assignment //
	Route::get('appraisal_assignment/appraisal_type_list', 'AppraisalAssignmentController@appraisal_type_list');
	Route::post('appraisal_assignment/auto_position_name', 'AppraisalAssignmentController@auto_position_name');
	Route::get('appraisal_assignment/al_list', 'AppraisalAssignmentController@al_list');
	Route::get('appraisal_assignment/period_list', 'AppraisalAssignmentController@period_list');
	Route::get('appraisal_assignment/frequency_list', 'AppraisalAssignmentController@frequency_list');
	Route::post('appraisal_assignment/auto_employee_name', 'AppraisalAssignmentController@auto_employee_name');
	Route::get('appraisal_assignment', 'AppraisalAssignmentController@index');
	Route::get('appraisal_assignment/template', 'AppraisalAssignmentController@assign_template');
	Route::get('appraisal_assignment/new_assign_to', 'AppraisalAssignmentController@new_assign_to');
	Route::get('appraisal_assignment/new_action_to', 'AppraisalAssignmentController@new_action_to');
	Route::get('appraisal_assignment/edit_assign_to', 'AppraisalAssignmentController@edit_assign_to');
	Route::get('appraisal_assignment/edit_action_to', 'AppraisalAssignmentController@edit_action_to');	
	Route::get('appraisal_assignment/{emp_result_id}', 'AppraisalAssignmentController@show');	
	Route::patch('appraisal_assignment/{emp_result_id}', 'AppraisalAssignmentController@update');	
	Route::delete('appraisal_assignment/{emp_result_id}', 'AppraisalAssignmentController@destroy');	
	Route::post('appraisal_assignment', 'AppraisalAssignmentController@store');	
	
	// Appraisal //
	Route::get('appraisal/year_list', 'AppraisalController@year_list');
	Route::get('appraisal/period_list', 'AppraisalController@period_list');
	Route::get('appraisal/al_list', 'AppraisalController@al_list');
	Route::get('appraisal/phase_list','AppraisalController@phase_list');
	Route::get('appraisal/auto_org_name','AppraisalController@auto_org_name');
	Route::get('appraisal/auto_position_name','AppraisalController@auto_position_name');
	Route::get('appraisal/auto_employee_name','AppraisalController@auto_employee_name');
	Route::post('appraisal/calculate_weight','AppraisalController@calculate_weight');
	Route::get('appraisal','AppraisalController@index');
	Route::get('appraisal/edit_assign_to', 'AppraisalController@edit_assign_to');
	Route::get('appraisal/edit_action_to', 'AppraisalController@edit_action_to');		
	Route::get('appraisal/{emp_result_id}','AppraisalController@show');	
	Route::patch('appraisal/{emp_result_id}','AppraisalController@update');	
	Route::get('appraisal/action_plan/auto_employee_name','AppraisalController@auto_action_employee_name');
	Route::get('appraisal/action_plan/{item_result_id}','AppraisalController@show_action');
	Route::post('appraisal/action_plan/{item_result_id}','AppraisalController@add_action');	
	Route::patch('appraisal/action_plan/{item_result_id}','AppraisalController@update_action');	
	Route::delete('appraisal/action_plan/{item_result_id}','AppraisalController@delete_action');	
	Route::get('appraisal/reason/{item_result_id}','AppraisalController@list_reason');
	Route::get('appraisal/reason/{item_result_id}/{reason_id}','AppraisalController@show_reason');
	Route::post('appraisal/reason/{item_result_id}','AppraisalController@add_reason');	
	Route::patch('appraisal/reason/{item_result_id}','AppraisalController@update_reason');	
	Route::delete('appraisal/reason/{item_result_id}','AppraisalController@delete_reason');		
	
	// Database Connection //
	Route::get('database_connection', 'DatabaseConnectionController@index');
	Route::get('database_connection/db_type_list', 'DatabaseConnectionController@db_type_list');	
	Route::post('database_connection', 'DatabaseConnectionController@store');
	Route::get('database_connection/{connection_id}', 'DatabaseConnectionController@show');
	Route::patch('database_connection/{connection_id}', 'DatabaseConnectionController@update');
	Route::delete('database_connection/{connection_id}', 'DatabaseConnectionController@destroy');
	
	// System Config //
	Route::get('system_config', 'SystemConfigController@index');
	Route::patch('system_config', 'SystemConfigController@update');
	Route::get('system_config/month_list', 'SystemConfigController@month_list');
	Route::get('system_config/frequency_list', 'SystemConfigController@frequency_list');
	
	// Perspective //
	Route::get('perspective', 'PerspectiveController@index');
	Route::post('perspective', 'PerspectiveController@store');
	Route::get('perspective/{perspective_id}', 'PerspectiveController@show');
	Route::patch('perspective/{perspective_id}', 'PerspectiveController@update');
	Route::delete('perspective/{perspective_id}', 'PerspectiveController@destroy');	
	
	// UOM //
	Route::get('uom', 'UOMController@index');
	Route::post('uom', 'UOMController@store');
	Route::get('uom/{uom_id}', 'UOMController@show');
	Route::patch('uom/{uom_id}', 'UOMController@update');
	Route::delete('uom/{uom_id}', 'UOMController@destroy');		
	
	// Position //
	Route::get('position', 'PositionController@index');
	Route::post('position/auto', 'PositionController@auto');
	Route::post('position', 'PositionController@store');
	Route::post('position/import', 'PositionController@import');
	Route::get('position/{position_id}', 'PositionController@show');
	Route::patch('position/{position_id}', 'PositionController@update');
	Route::delete('position/{position_id}', 'PositionController@destroy');		
	
	// Phase //
	Route::get('phase', 'PhaseController@index');
	Route::post('phase', 'PhaseController@store');
	Route::get('phase/{phase_id}', 'PhaseController@show');
	Route::patch('phase/{phase_id}', 'PhaseController@update');
	Route::delete('phase/{phase_id}', 'PhaseController@destroy');		
	
	// KPI Type //
	Route::get('kpi_type', 'KPITypeController@index');
	Route::post('kpi_type', 'KPITypeController@store');
	Route::get('kpi_type/{kpi_type_id}', 'KPITypeController@show');
	Route::patch('kpi_type/{kpi_type_id}', 'KPITypeController@update');
	Route::delete('kpi_type/{kpi_type_id}', 'KPITypeController@destroy');	

	// Org //
	Route::get('org', 'OrgController@index');
	Route::get('org/parent_list', 'OrgController@parent_list');
	Route::get('org/province_list', 'OrgController@province_list');
	Route::get('org/al_list', 'OrgController@al_list');
	Route::post('org', 'OrgController@store');
	Route::post('org/import', 'OrgController@import');
	Route::post('org/auto_org_name', 'OrgController@auto_org_name');
	Route::get('org/{org_id}', 'OrgController@show');
	Route::patch('org/{org_id}', 'OrgController@update');
	Route::delete('org/{org_id}', 'OrgController@destroy');			
	
	// Appraisal Structure //
	Route::get('appraisal_structure', 'AppraisalStructureController@index');
	Route::get('appraisal_structure/form_list', 'AppraisalStructureController@form_list');
	Route::post('appraisal_structure', 'AppraisalStructureController@store');
	Route::get('appraisal_structure/{structure_id}', 'AppraisalStructureController@show');
	Route::patch('appraisal_structure/{structure_id}', 'AppraisalStructureController@update');
	Route::delete('appraisal_structure/{structure_id}', 'AppraisalStructureController@destroy');
	
	// Threshold Group //
	Route::get('threshold/group', 'ThresholdController@group_list');
	Route::post('threshold/group', 'ThresholdController@add_group');
	Route::get('threshold/group/{threshold_group_id}', 'ThresholdController@show_group');
	Route::patch('threshold/group/{threshold_group_id}', 'ThresholdController@edit_group');
	Route::delete('threshold/group/{threshold_group_id}', 'ThresholdController@delete_group');	
	
	// Threshold //
	Route::get('threshold', 'ThresholdController@index');
	Route::get('threshold/structure_list', 'ThresholdController@structure_list');
	Route::post('threshold', 'ThresholdController@store');
	Route::get('threshold/{threshold_id}', 'ThresholdController@show');
	Route::patch('threshold/{threshold_id}', 'ThresholdController@update');
	Route::delete('threshold/{threshold_id}', 'ThresholdController@destroy');	
	
	// Result Threshold Group //
	Route::get('result_threshold/group', 'ResultThresholdController@group_list');
	Route::post('result_threshold/group', 'ResultThresholdController@add_group');
	Route::get('result_threshold/group/{result_threshold_group_id}', 'ResultThresholdController@show_group');
	Route::patch('result_threshold/group/{result_threshold_group_id}', 'ResultThresholdController@edit_group');
	Route::delete('result_threshold/group/{result_threshold_group_id}', 'ResultThresholdController@delete_group');	
	
	// Result Threshold //
	Route::get('result_threshold', 'ResultThresholdController@index');
	Route::post('result_threshold', 'ResultThresholdController@store');
	Route::get('result_threshold/{result_threshold_id}', 'ResultThresholdController@show');
	Route::patch('result_threshold/{result_threshold_id}', 'ResultThresholdController@update');
	Route::delete('result_threshold/{result_threshold_id}', 'ResultThresholdController@destroy');		
	
	// Appraisal Level //
	Route::get('appraisal_level', 'AppraisalLevelController@index');
	Route::post('appraisal_level', 'AppraisalLevelController@store');
	Route::get('appraisal_level/{level_id}', 'AppraisalLevelController@show');
	Route::patch('appraisal_level/{level_id}', 'AppraisalLevelController@update');
	Route::delete('appraisal_level/{level_id}', 'AppraisalLevelController@destroy');	
	Route::get('appraisal_level/{level_id}/criteria', 'AppraisalLevelController@appraisal_criteria');	
	Route::patch('appraisal_level/{level_id}/criteria', 'AppraisalLevelController@update_criteria');

	// Appraisal Grade //
	Route::get('appraisal_grade', 'AppraisalGradeController@index');
	Route::get('appraisal_grade/al_list', 'AppraisalGradeController@al_list');
	Route::post('appraisal_grade', 'AppraisalGradeController@store');
	Route::get('appraisal_grade/{grade_id}', 'AppraisalGradeController@show');
	Route::patch('appraisal_grade/{grade_id}', 'AppraisalGradeController@update');
	Route::delete('appraisal_grade/{grade_id}', 'AppraisalGradeController@destroy');	
	
	// Appraisal Period //
	Route::get('appraisal_period', 'AppraisalPeriodController@index');
	Route::get('appraisal_period/appraisal_year_list', 'AppraisalPeriodController@appraisal_year_list');
	Route::get('appraisal_period/start_month_list', 'AppraisalPeriodController@start_month_list');
	Route::get('appraisal_period/frequency_list', 'AppraisalPeriodController@frequency_list');
	Route::get('appraisal_period/add_frequency_list', 'AppraisalPeriodController@add_frequency_list');
	Route::post('appraisal_period/auto_desc', 'AppraisalPeriodController@auto_desc');
	Route::post('appraisal_period/create', 'AppraisalPeriodController@create');
	Route::post('appraisal_period', 'AppraisalPeriodController@store');
	Route::get('appraisal_period/{period_id}', 'AppraisalPeriodController@show');
	Route::patch('appraisal_period/{period_id}', 'AppraisalPeriodController@update');
	Route::delete('appraisal_period/{period_id}', 'AppraisalPeriodController@destroy');		
	
	//Dashboard //
	/*Route::get('dashboard/year_list', 'DashboardController@year_list');
	Route::post('dashboard/month_list', 'DashboardController@month_list');
	Route::post('dashboard/balance_scorecard', 'DashboardController@balance_scorecard');
	Route::post('dashboard/monthly_variance', 'DashboardController@monthly_variance');
	Route::post('dashboard/monthly_growth', 'DashboardController@monthly_growth');
	Route::post('dashboard/ytd_monthly_variance', 'DashboardController@ytd_monthly_variance');
	Route::post('dashboard/ytd_monthly_growth', 'DashboardController@ytd_monthly_growth');	
	Route::post('dashboard/emp_list', 'DashboardController@emp_list');*/
	Route::get('dashboard/year_list', 'DashboardController@year_list');
	Route::post('dashboard/period_list', 'DashboardController@period_list');
	Route::get('dashboard/region_list', 'DashboardController@region_list');
	Route::get('dashboard/district_list', 'DashboardController@district_list');
	Route::get('dashboard/appraisal_level', 'DashboardController@appraisal_level');
	Route::post('dashboard/org_list', 'DashboardController@org_list');
	Route::post('dashboard/kpi_map_list', 'DashboardController@kpi_map_list');
	Route::post('dashboard/kpi_list', 'DashboardController@kpi_list');
	Route::post('dashboard/content', 'DashboardController@dashboard_content'); //Post Method
	Route::post('dashboard/all_content', 'DashboardController@all_dashboard_content'); //Post Method
	Route::get('dashboard/kpi_overall', 'DashboardController@kpi_overall');
	Route::get('dashboard/kpi_overall_pie', 'DashboardController@kpi_overall_pie');
	Route::get('dashboard/kpi_overall_bubble', 'DashboardController@kpi_overall_bubble');
	Route::get('dashboard/performance_trend', 'DashboardController@performance_trend');
	Route::get('dashboard/gantt', 'DashboardController@gantt');
	Route::get('dashboard/branch_performance', 'DashboardController@branch_performance');
	Route::get('dashboard/branch_details', 'DashboardController@branch_details');	
	
	//Dashbaord Emp
	Route::get('dashboard_emp/year_list', 'DashboardEmpController@year_list');
	Route::post('dashboard_emp/month_list', 'DashboardEmpController@month_list');
	Route::post('dashboard_emp/result_emp_by_structure', 'DashboardEmpController@result_emp_by_structure');	
	
	//Result Bonus //
	Route::get('result_bonus/appraisal_year', 'ResultBonusController@appraisal_year');
	Route::get('result_bonus/bonus_period', 'ResultBonusController@bonus_period');
	Route::post('result_bonus/result_bonus', 'ResultBonusController@result_bonus');

	//Result Raise Amount //
	Route::get('result_raise_amount/appraisal_year', 'ResultRaiseAmountController@appraisal_year');
	Route::get('result_raise_amount/salary_period', 'ResultRaiseAmountController@salary_period');
	Route::post('result_raise_amount/result_raise_amount', 'ResultRaiseAmountController@result_raise_amount');	
	
	// Mail //
	Route::get('mail/send','MailController@send');
	Route::get('mail/monthly','MailController@monthly');


	//plaining
	Route::get('planning/auto','PlanningController@auto');
	Route::get('planning/list_name','PlanningController@list_name');
	
	Route::get('planning/index','PlanningController@index');
	Route::post('planning', 'PlanningController@store');
	Route::get('planning/{id}', 'PlanningController@show');
	Route::patch('planning/{id}', 'PlanningController@update');
	Route::delete('planning/{id}', 'PlanningController@destroy');	

	//profile
	Route::get('profile/index','ProfileController@index');
	Route::get('profile/military_rank','ProfileController@military_rank');
	
	Route::post('profile', 'ProfileController@store');
	Route::get('profile/{id}', 'ProfileController@show');
	Route::patch('profile/{id}', 'ProfileController@updateNew');
	Route::delete('profile/{id}', 'ProfileController@destroy');	

	//stock
	Route::get('stock/index','StockController@index');
	Route::post('stock', 'StockController@store');
	Route::get('stock/{id}', 'StockController@show');
	Route::patch('stock/{id}', 'StockController@update');
	Route::delete('stock/{id}', 'StockController@destroy');	
    Route::post('stock/returned_tools_amount_in_stock', 'StockController@returned_tools_amount_in_stock');

	Route::post('stock/upload_file/{item_result_id}', 'StockController@upload_files');
	Route::post('stock/import', 'StockController@import');
	Route::get('stock/upload_file/{item_result_id}','StockController@upload_files_list');
	//Route::get('stock/delete_file/{result_doc_id}','StockController@delete_file');
	Route::post('stock/delete_file','StockController@delete_file');

	

	//stock_order
	Route::get('picking_order/index','PickingOrderController@index');
	Route::get('picking_order/auto_pn','PickingOrderController@auto_pn');
	Route::get('picking_order/{id}','PickingOrderController@show');
	Route::post('picking_order/get_inventory','PickingOrderController@get_inventory');
	Route::post('picking_order/check_unique_stock','PickingOrderController@check_unique_stock');
	Route::post('picking_order/delete_all_by_profile', 'PickingOrderController@delete_all_by_profile');	
	Route::post('picking_order/confirm_all_by_profile','PickingOrderController@confirm_all_by_profile');
	
	
	Route::post('picking_order', 'PickingOrderController@store');
	Route::get('picking_order/{id}', 'PickingOrderController@show');
	Route::patch('picking_order/{id}', 'PickingOrderController@update');
	Route::delete('picking_order/{id}', 'PickingOrderController@destroy');	

	//returned tools
	Route::post('returned_tools/auto_fullname','ReturnedController@auto_fullname');
	Route::post('returned_tools/list_user_returned','ReturnedController@list_user_returned');
	Route::post('returned_tools/list_tools_returned','ReturnedController@list_tools_returned');
	Route::post('returned_tools/tools_returned','ReturnedController@tools_returned');
	
	//borrow tools notice 
	Route::post('borrow_tools_notice/auto_fullname','BorrowToolsNoticeController@auto_fullname');
	Route::post('borrow_tools_notice/list_user_borrow_notice','BorrowToolsNoticeController@list_user_borrow_notice');
	Route::post('borrow_tools_notice/list_tools_borrow_notice','BorrowToolsNoticeController@list_tools_borrow_notice');
	Route::post('borrow_tools_notice/list_myloan_tools_borrow_notice','BorrowToolsNoticeController@list_myloan_tools_borrow_notice');
	
	//Route::post('borrow_tools_notice/tools_borrow_notice','BorrowToolsNoticeController@tools_borrow_notice');
	Route::post('borrow_tools_notice/tools_ready','BorrowToolsNoticeController@tools_ready');
	Route::post('borrow_tools_notice/tools_wait','BorrowToolsNoticeController@tools_wait');

	Route::post('borrow_tools_notice/tools_returned','BorrowToolsNoticeController@tools_returned');
	Route::post('borrow_tools_notice/tools_out_of_stock','BorrowToolsNoticeController@tools_out_of_stock');
	



	//report borrow/returned
	Route::post('report_borrow_returned/list_user_borrow_returned','ReportBorrowReturnedController@list_user_borrow_returned');
	Route::post('report_borrow_returned/list_tools_borrow_returned_by_profile','ReportBorrowReturnedController@list_tools_borrow_returned_by_profile');
	// Route::post('report_borrow_returned/auto_fullname','ReportBorrowReturnedController@auto_fullname');

	

	//schedule
	Route::get('stock_order/index','ScheduleController@index');


	
	// Report //
	Route::get('report/usage_log','ReportController@usage_log');
	Route::get('report/al_list','ReportController@al_list');

	// VehicleType //
	Route::get('vehicle_type/index','VehicleTypeController@index');
	Route::post('vehicle_type', 'VehicleTypeController@store');
	Route::get('vehicle_type/{id}', 'VehicleTypeController@show');
	Route::patch('vehicle_type/{id}', 'VehicleTypeController@update');
	Route::delete('vehicle_type/{id}', 'VehicleTypeController@destroy');
	
	// AroundVehicle //
	Route::get('around_vehicle/index','AroundVehicleController@index');
	Route::post('around_vehicle', 'AroundVehicleController@store');
	Route::get('around_vehicle/{id}', 'AroundVehicleController@show');
	Route::patch('around_vehicle/{id}', 'AroundVehicleController@update');
	Route::delete('around_vehicle/{id}', 'AroundVehicleController@destroy');


	// Vehicle //
	Route::post('vehicle/upload_file/{id}', 'VehicleController@upload_files');
	Route::get('vehicle/upload_file/{id}','VehicleController@upload_files_list');
	Route::post('vehicle/delete_file','VehicleController@delete_file');

	Route::post('vehicle/report_vehicle','VehicleController@report_vehicle');
	Route::get('vehicle/index','VehicleController@index');
	Route::post('vehicle', 'VehicleController@store');
	Route::get('vehicle/{id}', 'VehicleController@show');
	Route::patch('vehicle/{id}', 'VehicleController@update');
	Route::delete('vehicle/{id}', 'VehicleController@destroy');

	// chauffeur //
	Route::post('chauffeur/upload_file/{id}', 'ChauffeurController@upload_files');
	Route::get('chauffeur/upload_file/{id}','ChauffeurController@upload_files_list');
	Route::post('chauffeur/delete_file','ChauffeurController@delete_file');


	Route::post('chauffeur/report_chauffeur','ChauffeurController@report_chauffeur');
	Route::get('chauffeur/index','ChauffeurController@index');
	Route::post('chauffeur', 'ChauffeurController@store');
	Route::get('chauffeur/{id}', 'ChauffeurController@show');
	Route::patch('chauffeur/{id}', 'ChauffeurController@update');
	Route::delete('chauffeur/{id}', 'ChauffeurController@destroy');


	// booking //
	
	Route::get('booking/booking_sum_booking_assigned_approved_by_user','BookingController@booking_sum_booking_assigned_approved_by_user');
	Route::get('booking/booking_sum_booking_assigned_approved_by_all_user','BookingController@booking_sum_booking_assigned_approved_by_all_user');
	Route::get('booking/booking_approved_list','BookingController@booking_approved_list');
	Route::patch('booking/booking_approved_status/{booking_id}','BookingController@booking_approved_status');

	Route::get('booking/booking_list','BookingController@booking_list');
	Route::get('booking/booking_confrim_detail/{booking_id}','BookingController@booking_confrim_detail');
	Route::get('booking/booking_confrim_assign_status/{booking_id}','BookingController@booking_confrim_assign_status');
	


	Route::post('booking/upload_file/{id}', 'BookingController@upload_files');
	Route::get('booking/upload_file/{id}','BookingController@upload_files_list');
	Route::post('booking/delete_file','BookingController@delete_file');
	
	Route::post('booking/report_overview_booking_car','BookingController@report_overview_booking_car');
	Route::post('booking/report_overview_booking_vehicle','BookingController@report_overview_booking_vehicle');
	
	Route::post('booking/report_calendar_booking_car','BookingController@report_calendar_booking_car');
	Route::post('booking/report_calendar_booking_vehicle','BookingController@report_calendar_booking_vehicle');
	
	
	Route::get('booking/index','BookingController@index');
	Route::post('booking/booking_search','BookingController@booking_search');
	Route::post('booking/confirm_booking_search','BookingController@confirm_booking_search');
	
	Route::post('booking/booking_approved_search','BookingController@booking_approved_search');
	Route::post('booking/booking_search_by_user','BookingController@booking_search_by_user');
	Route::post('booking/list_vehicle_image','BookingController@list_vehicle_image');

	Route::post('booking/confirm_booking/{booking_id}','BookingController@confirm_booking');
	Route::get('booking/not_confirm_booking/{booking_id}','BookingController@not_confirm_booking');
	Route::post('booking/send_to_draft_booking/{booking_id}','BookingController@send_to_draft_booking');

	
	
	Route::get('booking/around_vehicle','BookingController@around_vehicle');
	Route::get('booking/vehicle','BookingController@vehicle');
	Route::get('booking/vehicle_seat_by_boat/{boat_id}','BookingController@vehicle_seat_by_boat');
	Route::post('booking/seat_go_back_reserve','BookingController@seat_go_back_reserve');
	
	Route::get('booking/get_seat_reserve_by_booking_date/{booking_date}/{booking_id}','BookingController@get_seat_reserve_by_booking_date');
	Route::get('booking/get_data_booking_by_booker','BookingController@get_data_booking_by_booker');
	// Route::get('booking/get_data_booking_back_by_booker','BookingController@get_data_booking_back_by_booker');
	Route::get('booking/booking_vehicle_report','BookingController@booking_vehicle_report');
	


	
	
	
	
	Route::post('booking', 'BookingController@store');
	Route::get('booking/{id}', 'BookingController@show');
	Route::patch('booking/{id}', 'BookingController@update');
	Route::delete('booking/{id}', 'BookingController@destroy');

	
	
	



	// booking confirm 
	


	// chauffeur_and_vehicle
	Route::get('chauffeur_and_vehicle/check_vehicle_chauffeur_alreay','ChauffeurAndVehicleController@check_vehicle_chauffeur_alreay');
	Route::get('chauffeur_and_vehicle/index/{id}','ChauffeurAndVehicleController@index');
	Route::post('chauffeur_and_vehicle', 'ChauffeurAndVehicleController@store');
	Route::get('chauffeur_and_vehicle/{id}', 'ChauffeurAndVehicleController@show');
	Route::patch('chauffeur_and_vehicle/{id}', 'ChauffeurAndVehicleController@update');
	Route::delete('chauffeur_and_vehicle/{id}', 'ChauffeurAndVehicleController@destroy');

	
	Route::get('404', ['as' => 'notfound', function () {
		return response()->json(['status' => '404']);
	}]);

	Route::get('405', ['as' => 'notallow', function () {
		return response()->json(['status' => '405']);
	}]);	
});



