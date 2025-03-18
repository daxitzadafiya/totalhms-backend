<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// App v1 API
Route::get('cache-clear',function(){
        Artisan::call('optimize:clear');
        return 'optimize:clear successfully!';
});
Route::group(['prefix' => '/v1'], function () {
    Route::group(['prefix' => '/auth'], function() {
        Route::post('/login', 'AuthController@login');
        Route::post('/logout', 'AuthController@logout');
        Route::post('/forgot', 'AuthController@forgot');
        Route::post('/reset', 'AuthController@callResetPassword')->name('resetPassword');
    });
    //VerificationCode controller
    Route::get('verificationCode/{code}', 'VerificationCodeController@show');

    Route::get('/image/{filename}', 'AttachmentsController@showImage');
    Route::get('/image/{companyId}/{filename}', 'AttachmentsController@showAvatar');

    // TODO: Check company valid Subscription
    Route::group(['middleware' => ['jwt.verify']], function() {
        Route::get('users/me', 'UserController@me');
        Route::get('users/employees', 'UserController@employees');
        Route::get('users/managers', 'UserController@managers');

        //Role controller
        Route::group(['middleware' => ['cs-accessible']], function() {
        Route::get('roles', 'RoleController@index');
        Route::get('roles/{id}', 'RoleController@show');
        Route::post('roles', 'RoleController@store');
        Route::put('roles/{id}', 'RoleController@update');
        Route::delete('roles/{id}', 'RoleController@destroy');
        });
        Route::get('role/all', 'RoleController@all');

        //Permission controller
        Route::get('permissions', 'PermissionController@index');
        Route::get('permissions/{id}', 'PermissionController@show');
        Route::post('permissions', 'PermissionController@store');
        Route::put('permissions/{id}', 'PermissionController@update');
        Route::delete('permissions/{id}', 'PermissionController@destroy');

        //User controller
        Route::group(['middleware' => ['cs-accessible']], function() {
        Route::get('users', 'UserController@index');
        Route::get('users/{id}', 'UserController@show');
        Route::post('users', 'UserController@store');
        Route::put('users/{id}', 'UserController@update');
        Route::delete('users/{id}', 'UserController@destroy');
        });

        // invite controller
        Route::group(['middleware' => ['cs-accessible']], function() {
        Route::get('invites', 'InviteController@index');
        Route::get('invites/{id}', 'InviteController@show');
        Route::post('invites', 'InviteController@store');
        Route::put('invites/{id}', 'InviteController@update');
        Route::delete('invites/{id}', 'InviteController@destroy');
        Route::get('invites/resendInvitation/{id}', 'InviteController@resendInvitation');
        });
        Route::post('invite/import', 'InviteController@inviteImport');
        Route::get('uploads/invites/{filename}', 'InviteController@fileShow');

        //ContactPerson controller
        Route::get('contactPersons', 'ContactPersonController@index');
        Route::get('contactPersons/{id}', 'ContactPersonController@show');
        Route::post('contactPersons', 'ContactPersonController@store');
        Route::put('contactPersons/{id}', 'ContactPersonController@update');
        Route::delete('contactPersons/{id}', 'ContactPersonController@destroy');

        //InstructionActivity controller
//        Route::get('instructionActivities', 'InstructionActivityController@index');
//        Route::get('instructionActivities/{id}', 'InstructionActivityController@show');
//        Route::post('instructionActivities', 'InstructionActivityController@store');
//        Route::put('instructionActivities/{id}', 'InstructionActivityController@update');
//        Route::delete('instructionActivities/{id}', 'InstructionActivityController@destroy');

        //EmployeeRelation controller
        Route::get('employeeRelations', 'EmployeeRelationController@index');
        Route::get('employeeRelations/{id}', 'EmployeeRelationController@show');
        Route::post('employeeRelations', 'EmployeeRelationController@store');
        Route::put('employeeRelations/{id}', 'EmployeeRelationController@update');
        Route::delete('employeeRelations/{id}', 'EmployeeRelationController@destroy');

        //Topic controller
        Route::get('topics', 'TopicController@index');
        Route::get('topics/{id}', 'TopicController@show');
        Route::post('topics', 'TopicController@store');
        Route::put('topics/{id}', 'TopicController@update');
        Route::delete('topics/{id}', 'TopicController@destroy');

        //Industry controller
        Route::get('industries', 'IndustryController@index');
        Route::get('industries/{id}', 'IndustryController@show');
        Route::post('industries', 'IndustryController@store');
        Route::put('industries/{id}', 'IndustryController@update');
        Route::delete('industries/{id}', 'IndustryController@destroy');

        //Question controller
        Route::get('questions', 'QuestionController@index');
        Route::get('questions/{id}', 'QuestionController@show');
        Route::post('questions', 'QuestionController@store');
        Route::put('questions/{id}', 'QuestionController@update');
        Route::delete('questions/{id}', 'QuestionController@destroy');

        //Report controller
//        Route::post('reports/documents', 'DocumentNewController@store');
//        Route::post('reports/riskElementSource', 'RiskElementSourceController@store');
        Route::post('reports/tasks', 'TaskController@store');
//        Route::post('reports/documentsMultiple', 'DocumentController@uploadMultiple');
        Route::post('reports/documentsMultiple', 'DocumentNewController@uploadMultiple');

        Route::get('reports', 'ReportController@index');
        Route::get('reports/filter', 'ReportController@filterRecord');
        Route::get('reports/{id}', 'ReportController@show');
        Route::post('reports', 'ReportController@store');
        Route::put('reports/{id}', 'ReportController@update');
        Route::delete('reports/{id}', 'ReportController@destroy');

        //Absence controller
        Route::get('absences/documents', 'DocumentController@index');
        Route::get('absences/documents/{id}', 'DocumentController@show');
        Route::post('absences/documents', 'DocumentController@store');
        Route::put('absences/documents/{id}', 'DocumentController@update');
//        Route::delete('absences/documents/{id}', 'DocumentController@destroy');

        Route::get('absences', 'AbsenceController@index');
        Route::get('absences/{id}', 'AbsenceController@show');
        Route::post('absences', 'AbsenceController@store');
        Route::put('absences/{id}', 'AbsenceController@update');
        Route::delete('absences/{id}', 'AbsenceController@destroy');

        //AbsenceReason controller
        Route::get('absenceReasons', 'AbsenceReasonController@index');
        Route::get('absenceReasons/{id}', 'AbsenceReasonController@show');
        Route::post('absenceReasons', 'AbsenceReasonController@store');
        Route::put('absenceReasons/{id}', 'AbsenceReasonController@update');
        Route::delete('absenceReasons/{id}', 'AbsenceReasonController@destroy');

        //Appraisal controller
        Route::get('appraisals', 'AppraisalController@index');
        Route::get('appraisals/{id}', 'AppraisalController@show');
        Route::post('appraisals', 'AppraisalController@store');
        Route::put('appraisals/{id}', 'AppraisalController@update');
        Route::delete('appraisals/{id}', 'AppraisalController@destroy');

        //AppraisalTemplate controller
        Route::get('appraisalTemplates', 'AppraisalTemplateController@index');
        Route::get('appraisalTemplates/{id}', 'AppraisalTemplateController@show');
        Route::post('appraisalTemplates', 'AppraisalTemplateController@store');
        Route::put('appraisalTemplates/{id}', 'AppraisalTemplateController@update');
        Route::delete('appraisalTemplates/{id}', 'AppraisalTemplateController@destroy');

        Route::get('places', 'PlaceController@index');
        Route::post('add_place', 'PlaceController@index');

        Route::get('consequences', 'ConsequencesController@index');
        Route::post('add_consequence', 'ConsequencesController@index');
        //Company controller
//        Route::get('companies/documents', 'DocumentController@index');
//        Route::get('companies/documents/{id}', 'DocumentController@show');
//        Route::post('companies/documents', 'DocumentController@store');
//        Route::put('companies/documents/{id}', 'DocumentController@update');
//        Route::delete('companies/documents/{id}', 'DocumentController@destroy');

        Route::group(['middleware' => ['cs-accessible']], function() {
        Route::get('companies', 'CompanyController@index');
        Route::get('companies/{id}', 'CompanyController@show');
        Route::post('companies', 'CompanyController@store');
        Route::put('companies/{id}', 'CompanyController@update');
        Route::delete('companies/{id}', 'CompanyController@destroy');
        Route::post('companies/{id}/change_status', 'CompanyController@changeStatus');
        });
        
        //Department controller
        Route::get('departments', 'DepartmentController@index');
        Route::get('departments/{id}', 'DepartmentController@show');
        Route::post('departments', 'DepartmentController@store');
        Route::put('departments/{id}', 'DepartmentController@update');
        Route::delete('departments/{id}', 'DepartmentController@destroy');
        Route::post('departments/list', 'DepartmentController@list');

        // Category controller
        Route::get('categories', 'CategoryController@index');
        Route::get('categories/{id}', 'CategoryController@show');
        Route::post('categories', 'CategoryController@store');
        Route::put('categories/{id}', 'CategoryController@update');
        Route::delete('categories/{id}', 'CategoryController@destroy');

        // NEW V2 - Category controller
        Route::get('categoriesV2', 'CategoryControllerV2@index');
        Route::get('categoriesV2/{id}', 'CategoryControllerV2@show');
        Route::post('categoriesV2', 'CategoryControllerV2@store');
        Route::put('categoriesV2/{id}', 'CategoryControllerV2@update');
        Route::delete('categoriesV2/{id}', 'CategoryControllerV2@destroy');

        //Contact controller
        Route::get('contacts/persons', 'ContactPersonController@index');
        Route::get('contacts/persons/{id}', 'ContactPersonController@show');
        Route::post('contacts/persons', 'ContactPersonController@store');
        Route::put('contacts/persons/{id}', 'ContactPersonController@update');
        Route::delete('contacts/persons/{id}', 'ContactPersonController@destroy');

//        Route::get('contacts/documents', 'DocumentController@index');
//        Route::get('contacts/documents/{id}', 'DocumentController@show');
//        Route::post('contacts/documents', 'DocumentController@store');
//        Route::put('contacts/documents/{id}', 'DocumentController@update');
//        Route::delete('contacts/documents/{id}', 'DocumentController@destroy');

        Route::get('contacts', 'ContactController@index');
        Route::get('contacts/{id}', 'ContactController@show');
        Route::post('contacts', 'ContactController@store');
        Route::put('contacts/{id}', 'ContactController@update');
        Route::delete('contacts/{id}', 'ContactController@destroy');

        //Document controller
        Route::get('documents', 'DocumentController@index');
        Route::get('documents/{id}', 'DocumentController@show');
        Route::post('documents', 'DocumentController@store');
        Route::put('documents/{id}', 'DocumentController@update');
        Route::delete('documents/{id}', 'DocumentController@destroy');

        // OLD - Instruction controller
//        Route::get('instructions/activities', 'InstructionActivityController@index');
//        Route::delete('instructions/activities/{id}', 'InstructionActivityController@destroy');

        Route::get('instructionsOld', 'InstructionOldController@index');
        Route::get('instructionsOld/{id}', 'InstructionOldController@show');
        Route::post('instructionsOld', 'InstructionOldController@store');
        Route::put('instructionsOld/{id}', 'InstructionOldController@update');
        Route::delete('instructionsOld/{id}', 'InstructionOldController@destroy');

        // Instruction controller
        Route::get('instructions', 'InstructionController@index');
        Route::get('instructions/{id}', 'InstructionController@show');
        Route::post('instructions', 'InstructionController@store');
        Route::put('instructions/{id}', 'InstructionController@update');
        Route::delete('instructions/{id}', 'InstructionController@destroy');

        // OLD - Goal controller
        Route::get('goalsOld', 'GoalOldController@index');
        Route::get('goalsOld/{id}', 'GoalOldController@show');
        Route::post('goalsOld', 'GoalOldController@store');
        Route::put('goalsOld/{id}', 'GoalOldController@update');
        Route::delete('goalsOld/{id}', 'GoalOldController@destroy');

        // Goal controller
        Route::get('goals', 'GoalController@index');
        Route::get('goals/{id}', 'GoalController@show');
        Route::post('goals', 'GoalController@store');
        Route::put('goals/{id}', 'GoalController@update');
        Route::delete('goals/{id}', 'GoalController@destroy');

        //Routine controller
                Route::get('routines', 'RoutineController@index');
                Route::get('routines/{id}', 'RoutineController@show');
                Route::post('routines', 'RoutineController@store');
                Route::put('routines/{id}', 'RoutineController@update');
                Route::delete('routines/{id}', 'RoutineController@destroy');

        //Task controller
        Route::get('tasks/user/goals/{id}', 'GoalOldController@showLimit');
//        Route::get('tasks/goals/{id}', 'GoalOldController@show');
//        Route::get('tasks/goals/{id}', 'SubGoalController@show');
        Route::put('tasks/goals/{id}', 'GoalOldController@updateTask');
        Route::get('tasks/user/deviations/{id}', 'DeviationController@showLimit');
        Route::get('tasks/deviations/{id}', 'DeviationController@show');
        Route::put('tasks/deviations/{id}', 'DeviationController@updateTask');
        Route::get('tasks/user/riskAnalysis/{id}', 'RiskAnalysisController@showLimit');
        Route::get('tasks/riskAnalysis/{id}', 'RiskAnalysisController@show');
        Route::put('tasks/riskAnalysis/{id}', 'RiskAnalysisController@updateTask');
        Route::get('tasks/reports/{id}', 'ReportController@show');
        Route::put('tasks/progress/{id}', 'TaskController@updateProgressOfTask');
        Route::get('tasks/user/attachments/{id}', 'DocumentController@showLimit');
        Route::get('tasks/attachments/{id}', 'DocumentNewController@show');
        Route::put('tasks/attachments/{id}', 'DocumentNewController@updateTask');
        Route::get('tasks/user/userTasks/{id}', 'UserTaskController@showLimit');
        Route::get('tasks/userTasks/{id}', 'UserTaskController@show');
        Route::put('tasks/userTasks/{id}', 'UserTaskController@updateTask');

        Route::get('tasks/report/reportTasks/{id}', 'ReportTaskController@showLimit');
        Route::get('tasks/reportTasks/{id}', 'ReportTaskController@show');
        Route::put('tasks/reportTasks/{id}', 'ReportTaskController@updateTask');

        //task admin
        Route::get('tasks/admin', 'TaskController@indexAdmin');

        Route::get('tasks', 'TaskController@index');
        Route::get('tasks/{id}', 'TaskController@show');
        Route::post('tasks', 'TaskController@store');
        Route::put('tasks/{id}', 'TaskController@update');
        Route::delete('tasks/{id}', 'TaskController@destroy');

        //Employee controller
        Route::post('employees/import', 'EmployeeController@importCsvFile');

        Route::get('employees/absence/processor/{id}', 'EmployeeController@getAbsenceProcessor');

        Route::get('employees/relations', 'EmployeeRelationController@index');
        Route::get('employees/relations/{id}', 'EmployeeRelationController@show');
        Route::post('employees/relations', 'EmployeeRelationController@store');
        Route::put('employees/relations/{id}', 'EmployeeRelationController@update');
        Route::delete('employees/relations/{id}', 'EmployeeRelationController@destroy');

//        Route::get('employees/documents', 'DocumentController@index');
//        Route::get('employees/documents/{id}', 'DocumentController@show');
//        Route::post('employees/documents', 'DocumentController@store');
//        Route::put('employees/documents/{id}', 'DocumentController@update');
//        Route::delete('employees/documents/{id}', 'DocumentController@destroy');

        Route::get('employees/roles', 'RoleController@index');

        Route::get('employees', 'EmployeeController@index');
        Route::get('employees/{id}', 'EmployeeController@show');
        Route::post('employees', 'EmployeeController@store');
        Route::put('employees/{id}', 'EmployeeController@update');
        Route::delete('employees/{id}', 'EmployeeController@destroy');

        //Deviation controller
//        Route::post('deviations/documents', 'DocumentController@store');

        Route::get('deviations', 'DeviationController@index')->middleware('accessible:deviation');
        Route::get('deviations/filter', 'DeviationController@filterRecord');
        Route::get('deviations/{id}', 'DeviationController@show');
        Route::post('deviations', 'DeviationController@store');
        Route::put('deviations/{id}', 'DeviationController@update');
        Route::delete('deviations/{id}', 'DeviationController@destroy');

        //Checklist controller
        Route::get('checklists', 'ChecklistController@index');
        Route::get('checklists/{id}', 'ChecklistController@show');
        Route::post('checklists', 'ChecklistController@store');
        Route::put('checklists/{id}', 'ChecklistController@update');
        Route::delete('checklists/{id}', 'ChecklistController@destroy');

        //RiskAnalysis controller
        Route::get('riskAnalysis', 'RiskAnalysisController@index');
        Route::get('riskAnalysis/filter', 'ObjectController@riskAnalysisFilter');
        Route::get('riskAnalysis/{id}', 'RiskAnalysisController@show');
        Route::post('riskAnalysis', 'RiskAnalysisController@store');
        Route::put('riskAnalysis/{id}', 'RiskAnalysisController@update');
        Route::delete('riskAnalysis/{id}', 'RiskAnalysisController@destroy');

        //RiskElementSource controller
//        Route::get('riskElementSource/documents', 'DocumentController@index');
//        Route::get('riskElementSource/documents/{id}', 'DocumentController@show');
//        Route::put('riskElementSource/documents/{id}', 'DocumentController@update');
//        Route::delete('riskElementSource/documents/{id}', 'DocumentController@destroy');

        Route::get('riskElementSource', 'RiskElementSourceController@index');
        Route::get('riskElementSource/{id}', 'RiskElementSourceController@show');
        Route::post('riskElementSource', 'RiskElementSourceController@store');
        Route::put('riskElementSource/{id}', 'RiskElementSourceController@update');
        Route::delete('riskElementSource/{id}', 'RiskElementSourceController@destroy');

//        Route::post('riskElementSource/documents', 'DocumentNewController@store');

        //TitleCaption controller
        Route::get('titleCaption', 'TitleCaptionController@index');
        Route::get('titleCaption/key/{key}', 'TitleCaptionController@showByKey');
        Route::get('titleCaption/{id}', 'TitleCaptionController@show');
        Route::post('titleCaption', 'TitleCaptionController@store');
        Route::put('titleCaption/{id}', 'TitleCaptionController@update');
        Route::delete('titleCaption/{id}', 'TitleCaptionController@destroy');

        //Request push notification controller
        Route::get('requestPushNotification', 'RequestPushNotificationController@index');
        Route::get('requestPushNotification/{id}', 'RequestPushNotificationController@show');
        Route::post('requestPushNotification', 'RequestPushNotificationController@store');
        Route::put('requestPushNotification/{id}', 'RequestPushNotificationController@update');
        Route::delete('requestPushNotification/{id}', 'RequestPushNotificationController@destroy');

        //Notification controller
        Route::get('notifications/countUnRead', 'NotificationController@countUnRead');

        Route::get('notifications', 'NotificationController@index');
        Route::get('notifications/{id}', 'NotificationController@show');
        Route::post('notifications', 'NotificationController@store');
        Route::put('notifications/{id}', 'NotificationController@update');
        Route::delete('notifications/{id}', 'NotificationController@destroy');

        //Statement controller
        Route::get('statements', 'StatementController@index');
        Route::get('statements/{id}', 'StatementController@show');
        Route::post('statements', 'StatementController@store');
        Route::put('statements/{id}', 'StatementController@update');
        Route::delete('statements/{id}', 'StatementController@destroy');

        //API to load document
        Route::get('uploads/documents/{filename}', 'DocumentNewController@fileShow');

        // Checklist Option controller
        Route::get('options', 'ChecklistOptionController@index');
        Route::post('options', 'ChecklistOptionController@store');
        Route::get('options/{id}', 'ChecklistOptionController@show');
        Route::put('options/{id}', 'ChecklistOptionController@update');

        // Checklist Option Answer controller
        Route::get('optionAnswers', 'ChecklistOptionAnswerController@index');

        //APIs for manage user

        //Action new update version
        Route::put('new-update/apply/role/{request_push_notification_id}', 'RoleController@applyNewUpdate');
        Route::put('new-update/decline/{request_push_notification_id}', 'RequestPushNotificationController@declineNewUpdate');

        //UserTask controller
        Route::get('userTasks', 'UserTaskController@index');
        Route::get('userTasks/{id}', 'UserTaskController@show');
        Route::post('userTasks', 'UserTaskController@store');
        Route::put('userTasks/{id}', 'UserTaskController@update');
        Route::delete('userTasks/{id}', 'UserTaskController@destroy');

        //PermissionFormat controller
        Route::get('permissionsFormat', 'PermissionFormatController@index');

        //Job Title controller
        Route::group(['middleware' => ['cs-accessible']], function() {
        Route::get('jobTitles', 'JobTitleController@index');
        Route::get('jobTitles/{id}', 'JobTitleController@show');
        Route::post('jobTitles', 'JobTitleController@store');
        Route::put('jobTitles/{id}', 'JobTitleController@update');
        Route::delete('jobTitles/{id}', 'JobTitleController@destroy');
        Route::post('jobTitles/list', 'JobTitleController@list');
        });


        //User Permission controller
        Route::get('userPermissions/{user_id}', 'UserPermissionController@show');
        Route::put('userPermissions/{user_id}', 'UserPermissionController@update');

        //Unwanted Event controller
        Route::get('unwantedEvents', 'UnwantedEventController@index');
        Route::get('unwantedEvents/{id}', 'UnwantedEventController@show');
        Route::post('unwantedEvents', 'UnwantedEventController@store');
        Route::put('unwantedEvents/{id}', 'UnwantedEventController@update');
        Route::delete('unwantedEvents/{id}', 'UnwantedEventController@destroy');

        //Repository controller
        Route::get('repositories', 'RepositoryController@index');
        Route::get('repositories/{id}', 'RepositoryController@show');
        Route::post('repositories', 'RepositoryController@store');
        Route::put('repositories/{id}', 'RepositoryController@update');
        Route::delete('repositories/{id}', 'RepositoryController@destroy');

        //IntervalSetting controller
        Route::get('intervalSetting', 'IntervalSettingController@index');
        Route::get('intervalSetting/{id}', 'IntervalSettingController@show');
        Route::post('intervalSetting', 'IntervalSettingController@store');
        Route::put('intervalSetting/{id}', 'IntervalSettingController@update');
        Route::delete('intervalSetting/{id}', 'IntervalSettingController@destroy');

        //TaskAssignee controller
        Route::get('taskAssignees', 'TaskAssigneeController@index');
        Route::get('taskAssignees/{id}', 'TaskAssigneeController@show');
        Route::post('taskAssignees', 'TaskAssigneeController@store');
        Route::put('taskAssignees/{id}', 'TaskAssigneeController@update');
        Route::delete('taskAssignees/{id}', 'TaskAssigneeController@destroy');

        // Help Center controller
        Route::get('help', 'HelpCenterController@index');
        Route::get('help/{id}', 'HelpCenterController@show');
        Route::post('help', 'HelpCenterController@store');
        Route::put('help/{id}', 'HelpCenterController@update');
        Route::delete('help/{id}', 'HelpCenterController@destroy');

        // Help Question Center controller
        Route::get('helpQuestion', 'HelpCenterQuestionController@index');
        Route::get('helpQuestion/{id}', 'HelpCenterQuestionController@show');
        Route::post('helpQuestion', 'HelpCenterQuestionController@store');
        Route::put('helpQuestion/{id}', 'HelpCenterQuestionController@update');
        Route::delete('helpQuestion/{id}', 'HelpCenterQuestionController@destroy');

        // Billing controller
        Route::get('billings', 'BillingController@index');
        Route::get('billings/{id}', 'BillingController@show');
        Route::post('billings', 'BillingController@store');
        Route::put('billings/{id}', 'BillingController@update');
        Route::delete('billings/{id}', 'BillingController@destroy');
        Route::post('billings/status/{id}', 'BillingController@status');
        Route::get('billings/sendEmail/{id}', 'BillingController@sendEmail');
        Route::post('billings/pdf', 'BillingController@pdf');

        // NEW - Document controller
        Route::get('documentsNew', 'DocumentNewController@index')->middleware('accessible:document');
        Route::get('documentsNew/attachments', 'DocumentNewController@attachments');
        Route::get('documentsNew/{id}', 'DocumentNewController@show');
        Route::post('documentsNew', 'DocumentNewController@store');
        Route::put('documentsNew/{id}', 'DocumentNewController@update');
        Route::delete('documentsNew/{id}', 'DocumentNewController@destroy');

        //Connect To controller
        Route::get('connectTo/getObjects', 'ConnecToController@getObjects');

        Route::get('connectTo', 'ConnecToController@getByObject');
        Route::post('connectTo', 'ConnecToController@saveByObject');
        Route::put('connectTo/{id}', 'ConnecToController@updateByObject');
        Route::delete('connectTo/{id}', 'ConnecToController@deleteByID');

        // Object controller
        Route::put('objects/processing/{id}', 'ObjectController@processObject');

        Route::get('objects', 'ObjectController@index');
        Route::get('objects/getAttendee/{id?}', 'ObjectController@getAttendee');
        Route::get('objects/getResponsible/{id?}', 'ObjectController@getResponsible');
        Route::post('objects/objectsAttendee/processing', 'ObjectController@attendee_process');
        Route::post('objects/objectsResponsible/processing', 'ObjectController@responsible_process');
        Route::get('objects/getProcessingInfo/{id?}/{p_id?}', 'ObjectController@getProcessingInfo');
        Route::get('objects/getProcessingInfoResponsible/{id?}/{p_id?}', 'ObjectController@getProcessingInfoResponsible');
        Route::get('objects/{id}', 'ObjectController@show');
        Route::post('objects', 'ObjectController@store');
        Route::put('objects/{id}', 'ObjectController@update');
        Route::delete('objects/{id}', 'ObjectController@destroy');
        Route::post('objects/update_attendee/{id}', 'ObjectController@update_attendee');
        Route::post('objects/update_responsible/{id}', 'ObjectController@update_responsible');
        Route::post('objects/extend_timeline', 'ObjectController@extend_timeline');
        Route::post('objects/update_extended_timeline', 'ObjectController@update_extended_timeline');

        // Required Attachment controller
        Route::post('attachments', 'AttachmentRequiredController@store');

        //plan controller
        Route::group(['middleware' => ['cs-accessible']], function() {
        Route::get('plans', 'PlanController@index');
        Route::post('plans/store', 'PlanController@store');
        Route::get('plans/{id}', 'PlanController@show');
        Route::put('plans/{id}', 'PlanController@update');
        Route::delete('plans/{id}', 'PlanController@destroy');
        });

        //card
        Route::get('cards', 'CardController@index');
        Route::post('cards/store', 'CardController@store');
        Route::delete('cards/{id}', 'CardController@destroy');
        Route::post('cards/active', 'CardController@activeCard');

        //addon controller
        Route::group(['middleware' => ['cs-accessible']], function() {
        Route::get('addons', 'AddonController@index');
        Route::post('addons/store', 'AddonController@store');
        Route::get('addons/{id}', 'AddonController@show');
        Route::put('addons/{id}', 'AddonController@update');
        Route::delete('addons/{id}', 'AddonController@destroy');
        });

        //subscription controller
        Route::post('stripeCard', 'SubscriptionController@stripeCard');
        Route::get('creditCheck', 'SubscriptionController@creditCheck');
        Route::post('plan/purchase', 'SubscriptionController@planPurchase');
        Route::post('plan/purchase_completed', 'SubscriptionController@planPurchaseCompleted');
        Route::get('plan/active', 'SubscriptionController@activePlan');
        Route::post('plan/cancel', 'SubscriptionController@cancelPlan');
        Route::post('addon/purchase', 'SubscriptionController@addonPurchase');
        Route::post('addon/cancel', 'SubscriptionController@cancelAddon');
        Route::post('immediatelyDeactive', 'SubscriptionController@immediatelyDeactive');

        //coupon controller
        Route::group(['middleware' => ['cs-accessible']], function() {
        Route::get('coupons', 'CouponController@index');
        Route::post('coupons/store', 'CouponController@store');
        Route::get('coupons/{id}', 'CouponController@show');
        Route::put('coupons/{id}', 'CouponController@update');
        Route::delete('coupons/{id}', 'CouponController@destroy');
        });

        Route::post('coupon-checking', 'CouponController@couponCheck');

        //contain controller
        Route::group(['middleware' => ['cs-accessible']], function() {
        Route::get('settings', 'SettingController@index');
        Route::put('settings', 'SettingController@update');
        });
        Route::get('check-disable-setting', 'SettingController@checkDisabled');

        //email content
        Route::group(['middleware' => ['cs-accessible']], function() {
        Route::get('email_contents', 'EmailContentController@index');
        Route::put('email_contents/{id}', 'EmailContentController@update');
        });

        //invoice history
        Route::get('invoice_histories', 'InvoiceHistoryController@index');
        Route::get('invoice_histories/{id}', 'InvoiceHistoryController@show');

        // email logs
        Route::group(['middleware' => ['cs-accessible']], function() {
        Route::get('email_logs', 'EmailLogController@index');
        Route::get('email_logs{id}', 'EmailLogController@show');
        });

        //custome sercive
        Route::group(['middleware' => ['cs-accessible']], function() {
         Route::get('customerService', 'CustomerServiceController@index');
         Route::get('customerService/{id}', 'CustomerServiceController@show');
         Route::post('customerService', 'CustomerServiceController@store');
         Route::put('customerService/{id}', 'CustomerServiceController@update');
         Route::delete('customerService/{id}', 'CustomerServiceController@destroy');
        });
         Route::get('cs/permissions', 'CustomerServiceController@permissions');
         Route::put('cs/permissions', 'CustomerServiceController@updatepermission');
         Route::get('get_permissions', 'CustomerServiceController@getPermissions');

    });
});