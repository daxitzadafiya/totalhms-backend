<?php

App::setLocale('ch'); //by facade.
$locale = App::getLocale();

Route::get('/clear', function () {
    $exitCode = Artisan::call('cache:clear');

    $exitCode = Artisan::call('view:clear');

    $exitCode = Artisan::call('config:clear');
    echo $exitCode;
});
Route::get('invoice/pdf', 'BillingController@pdfDownload');
// Login Routes...
Route::get('login', ['as' => 'login', 'uses' => 'Auth\LoginController@showLoginForm']);
Route::post('login', ['as' => 'login.post', 'uses' => 'Auth\LoginController@login']);
Route::post('logout', ['as' => 'logout', 'uses' => 'Auth\LoginController@logout']);
Route::get('logout', 'Auth\LoginController@logout');

// Registration Routes...
//Route::get('register', ['as' => 'register', function(){abort(499, 'Not available in demo mode.');}]);
Route::get('register', ['as' => 'register', 'uses' => 'Auth\RegisterController@showRegistrationForm']);
Route::post('register', ['as' => 'register.post', 'uses' => 'Auth\RegisterController@register']);

// Password Reset Routes...
Route::get('password/reset', ['as' => 'password.reset', 'uses' => 'Auth\ForgotPasswordController@showLinkRequestForm']);
//Route::post('password/email', ['as' => 'password.email', function(){abort(499, 'Not available in demo mode.');}]);
Route::post('password/email', ['as' => 'password.email', 'uses' => 'Auth\ForgotPasswordController@sendResetLinkEmail']);
Route::get('password/reset/{token}', ['as' => 'password.reset.token', 'uses' => 'Auth\ResetPasswordController@showResetForm']);
Route::post('password/reset', ['as' => 'password.reset.post', 'uses' => 'Auth\ResetPasswordController@reset']);

Route::get('invitation/member/{token}', 'InviteController@invitationShow')->name('invitation.show');
Route::post('invitation/member', 'InviteController@invitationAccepet')->name('invitation.accepet');

Route::get('invitation/customer_service/{code}', 'CustomerServiceController@invitationShow')->name('customerService.invitation.show');
Route::post('invitation/customer_service', 'CustomerServiceController@invitationAccepet')->name('customerService.invitation.accepet');

// Route::get('/', 'HomeController@index')->name('home');
Route::group(['middleware' => 'auth'], function () {
//Auth::routes();
    Route::get('user/profile', 'AdminUserController@profile')->name('Profile-view');

    // Route::get('/home', 'HomeController@index')->name('home');
    //Admin routes
    Route::group(['prefix' => "admin", "middleware" => "super-admin"], function () {
        //admin user
        Route::get('user/list', 'AdminUserController@userList')->name('List');
        Route::get('add/user', 'AdminUserController@userAdd')->name('Add USer');
        Route::get('edit/user/{id}', 'AdminUserController@userAdd')->name('Edit User');
        Route::post('save/user', 'AdminUserController@userSave')->name('Save USer');
        Route::post('del/user', 'AdminUserController@delete')->name('User-del');
        Route::get('userdata', 'AdminUserController@userData')->name('Data USer');
        //Company
        Route::get('company/list', 'CompanyController@companyList')->name('Compoany-List');
        Route::get('company/request/list', 'CompanyController@companyRequestList')->name('List');
        Route::get('/add/company', 'CompanyController@companyAdd')->name('Compoany-Add');
        Route::get('/edit/company/{id}', 'CompanyController@companyAdd')->name('Compoany-edit');
        Route::post('save/company', 'CompanyController@companyAdd')->name('Compoany-save');
        Route::post('del/company', 'CompanyController@delete')->name('Compoany-del');
        Route::get('comapnyData', 'CompanyController@comapnyData')->name('Compoany-data');
        Route::post('company/approve', 'CompanyController@comapnyApprove')->name('Compoany-Approve');
        Route::get('comapnyRequestData', 'CompanyController@comapnyRequestData')->name('Compoany-data');
    });
    //Route::group(["middleware" => "company-admin"], function () {

    /*********************************Company-Instruction*********************************************/
    Route::get('foretak/innstrukser', 'CompanyController@instruction')->name('Instruction');
    Route::get('foretak/innstrukser/ny/{id?}', 'CompanyController@addInstruction')->name('Instruction');
    Route::post('save/instruction', 'CompanyController@saveInstruction')->name('save-instruction');
    Route::get('edit/instructions/{id?}', 'CompanyController@addInstruction')->name('save-instruction');
    Route::get('instructiondata', 'CompanyController@instructiondata')->name('data-instruction');
    Route::post('get/template/list', 'CompanyController@gettemplatelist')->name('Template-List');
    Route::post('get/instruction/list', 'CompanyController@getinstructionlist')->name('Instruction-List');
    Route::post('delete/instructions', 'CompanyController@deleteInstruction')->name('save-instruction');

    //category
    Route::get('foretak/categories', 'CompanyController@categoryList')->name('CategoryList');
    Route::get('categorydata', 'CompanyController@categoryData')->name('CategoryList');
    Route::get('add/category', 'CompanyController@addcategory')->name('add-category');
    Route::post('add/category', 'CompanyController@addcategory')->name('add-category');
    Route::get('edit/category/{id?}', 'CompanyController@addcategory')->name('add-category');
    Route::post('delete/category', 'CompanyController@deletecategory')->name('Delete-category');
    Route::get('view/category/{id?}', 'CompanyController@viewcategory')->name('View-category');
    //activities
    Route::get('foretak/activities', 'CompanyController@activitiesList')->name('ActivitiesList');
    Route::get('activitiesdata', 'CompanyController@activitiesData')->name('ActivitiesData');
    Route::post('add/activities', 'CompanyController@addactivities')->name('Add-activities');
    Route::get('edit/activity/{id?}', 'CompanyController@addactivities')->name('Add-activities');
    Route::get('view/activity/{id?}', 'CompanyController@viewactivity')->name('View-activities');
    //templates

    Route::get('foretak/templates', 'CompanyController@templateList')->name('TemplateList');
    Route::get('templatedata', 'CompanyController@templateData')->name('TemplateData');
    Route::get('add/template', 'CompanyController@addtemplate')->name('Add-template');
    Route::get('edit/template/{id?}', 'CompanyController@addtemplate')->name('Edit-template');
    Route::get('view/template/{id?}', 'CompanyController@viewtemplate')->name('VIew-template');
    Route::get('delete/template/{id}', 'CompanyController@deletetemplate')->name('Delte-template');
    Route::get('template/instruk/{id}', 'CompanyController@usetemplate')->name('Use-template');
    Route::post('add/template', 'CompanyController@addtemplate')->name('Add-template');
    Route::post('get/template', 'CompanyController@gettemplate')->name('Get-template');
    Route::post('delete/template', 'CompanyController@deletetemplate')->name('Delete-template');


    //Risk Area
    Route::get('foretak/risikoområder', 'CompanyController@riskarea')->name('riskarea');
    Route::post('/save/riskarea', 'CompanyController@saveriskarea')->name('riskarea');

    /********************************************Company-Routine*************************************************/
    Route::get('foretak/rutiner', 'RoutineController@routineList')->name('Routine-list');
    Route::get('foretak/rutine/ny', 'RoutineController@addRoutine')->name('Routine-add');
    Route::post('save/rutine', 'RoutineController@saveRoutine')->name('save-rutine');
    Route::post('delete/rutine', 'RoutineController@deleteRoutine')->name('delete-rutine');
    Route::get('edit/rutine/{id?}', 'RoutineController@addRoutine')->name('edit-rutine');
    Route::get('routineData', 'RoutineController@routineData')->name('RoutineList');
    Route::get('template/rutine/{id}', 'RoutineController@usetemplate')->name('Use-template');

    /********************************************Company-Contacts *********************************************/
    Route::get('foretak/kontakter', 'RoutineController@contactList')->name('Contacts');
    Route::get('foretak/kontakt/ny', 'RoutineController@addcontact')->name('Add-Contacts');
    Route::post('add/contact', 'RoutineController@saveContact')->name('save-Contacts');
    Route::post('add/contactperson', 'RoutineController@contactperson')->name('save-contactperson');
    Route::get('edit/contact/{id?}', 'RoutineController@editContact')->name('save-Contacts');
    Route::get('contactdata', 'RoutineController@contactdata')->name('data-contact');
    Route::post('delete/contact', 'RoutineController@deletecontact')->name('Delete-contact');

    /**********************************************HSE-statement**********************************************/
    Route::get('foretak/erklering', 'CompanyController@hsecontent')->name('Compoany-HSE-statement');
    Route::post('add/hse', 'CompanyController@hsecontent')->name('add-HSE-statement');

    /*********************************************HSE-Goals**************************************************/
    Route::get('foretak/malsetting', 'RoutineController@hsegoals')->name('Compoany-HSE-goals');
    Route::get('foretak/goal/ny', 'RoutineController@addGoal')->name('Routine-add');
    Route::get('foretak/goal/{id?}', 'RoutineController@addGoal')->name('Routine-edit');
    Route::post('save/goal', 'RoutineController@saveGoal')->name('Save-Goal');
    Route::get('goalData', 'RoutineController@goalData')->name('GoalList');
    Route::post('delete/goal', 'RoutineController@deleteGoal')->name('Delete-Goal');
    Route::post('user/update', 'AdminUserController@updateUser')->name('Profile-Update');

    //});
    /*********************************************Company Information**************************************************/
    Route::get('foretak/mittforetak', 'CompanyController@companyinfo')->name('Compoany-Info-company-amdin');
    Route::post('/update/info', 'CompanyController@companyAdd')->name('Compoany-Info-company-amdin');
    Route::post('/update/company', 'CompanyController@companyUpdate')->name('Compoany-Info-company-amdin');
    Route::post('/company/details', 'CompanyController@companyUpdate')->name('Compoany-Info-company-amdin');
    Route::post('/company/logo', 'CompanyController@logoupdate')->name('Compoany-Info-company-amdin');
    Route::post('/add/department', 'CompanyController@addDepartment')->name('Compoany-department-add');
    Route::post('/delete/department', 'CompanyController@deleteDepartment')->name('Compoany-department-delete');
    Route::post('/add/job', 'CompanyController@addJobrole')->name('Compoany-jobtitle-add');

    /*********************************************Employee**************************************************/
    Route::group(['prefix' => "ansatte"], function () {
        Route::get('/employees', 'EmployeeController@employeeList')->name('employee-list');
        Route::get('/employee/{id?}', 'EmployeeController@employeeView')->name('employee-View');
        Route::post('/add/employee', 'EmployeeController@addemployee')->name('employee-add');
        /*****************************Employee Organisation chart*************************/
        Route::get('/organization', 'EmployeeController@organisationchart')->name('organisationchart');
        Route::post('/organization/updateUserOrder', 'EmployeeController@updateUserOrder')->name('updateUserOrder');
        /*****************************Employee Appraisal**********************************/
        Route::get('/appraisals', 'AppraisalController@index')->name('appraisallist');
        Route::get('/appraisal/ny/', 'AppraisalController@create')->name('appraisalcreate');
        Route::get('/appraisal/{id?}', 'AppraisalController@create')->name('appraisalcreate');
    });
    /****************************Apraisal ajax****************************************/
    Route::post('appraisal/add/topic', 'AppraisalController@addTopic')->name('topicadd');
    Route::post('appraisal/add/question', 'AppraisalController@addQuestion')->name('addQuestion');
    Route::post('/appraisal/add/topic/row', 'AppraisalController@addTopicRow')->name('topicadd');
    Route::post('appraisal/add/employee/topic', 'AppraisalController@addemployeeTopic')->name('employeetopicadd');
    Route::post('appraisal/add', 'AppraisalController@addAppraisal')->name('addAppraisal');
    Route::post('/appraisal/question/update', 'AppraisalController@updatequestion')->name('addAppraisal');
    Route::post('/delete/appraisal/category', 'AppraisalController@deleteTopic')->name('topicdelete');
    
    Route::post('/delete/appraisal/question', 'AppraisalController@deleteQuestion')->name('questiondelete');
    Route::post('/appraisal/use/topic', 'AppraisalController@useTopic')->name('useTopic');
    Route::post('/appraisal/use/question', 'AppraisalController@useQuestion')->name('useQuestion');
    Route::post('/appraisal/questionadd', 'AppraisalController@addAppraisalQuestion')->name('addAppraisalQuestion');
    /*****/
    Route::post('add/template/question', 'AppraisalController@updatequestion')->name('addAppraisal');


    Route::post('/get/roles', 'EmployeeController@getRoles')->name('employee-role');
    Route::post('/get/data', 'EmployeeController@getData')->name('employee-data');
    Route::post('/get/showRole', 'CompanyController@showRole')->name('show-roles');
    Route::post('/delete/roles', 'CompanyController@deleteRoles')->name('Compoany-Roles-delete');
    Route::post('/add/relation', 'CompanyController@addRelation')->name('Compoany-relation-add');
    Route::post('/delete/relation', 'CompanyController@deleteRelation')->name('Compoany-relation-delete');
    Route::post('/employee/doc', 'EmployeeController@documentUpload')->name('Employee-document-upload');
    Route::post('/delete/document', 'EmployeeController@deleteDocument')->name('Employee-document-delete');
    Route::post('/add/dependents', 'EmployeeController@addDependents')->name('Employee-dependent-add');
    Route::post('/ansatte/delete/depends', 'EmployeeController@deleteDepends')->name('Employee-dependent-delete');

    /*********************************************Absence**************************************************/
    Route::get('ansatte/absence', 'AbsenceController@absenceList')->name('Absence-list');
    Route::get('/ansatte/absenceadd', 'AbsenceController@absenceAdd')->name('Absence-Add');
    Route::post('/add/reason', 'AbsenceController@addReason')->name('Absence-Reason-add');
    Route::post('/ansatte/absencelist', 'AbsenceController@addAbsence')->name('Add-Absence');
    Route::get('/ansatte/absenceprocess/{id?}', 'AbsenceController@absenceProcess')->name('process-Absence');
    Route::post('/get/kids', 'AbsenceController@getKids')->name('absence-kids');
    Route::post('/add/kids', 'AbsenceController@addKids')->name('absence-addkis');
    Route::post('/get/hold', 'AbsenceController@gethold')->name('absence-holidays');
    Route::post('/delete/absence', 'AbsenceController@deleteAbsence')->name('absence-delete');
    Route::post('/get/interval', 'AbsenceController@getinterval')->name('interval-get');

    /*********************************************Document**************************************************/

    Route::get('/document/list', 'DocumentController@documentList')->name('Document-list');
    Route::get('/document/upload', 'DocumentController@documentUpload')->name('Document-upload');
    Route::get('/document/create', 'DocumentController@documentUpload')->name('Document-upload');
    Route::get('/edit/doc/{id?}', 'DocumentController@documentEdit')->name('Edit-Document-Upload');
    Route::get('/document/template/{id?}', 'DocumentController@documentUpload')->name('Document-create-with-template');
    Route::post('/document/save', 'DocumentController@docUpload')->name('Document-upload');
    Route::post('/document/edit/save', 'DocumentController@docUpload')->name('Document-upload');
    Route::post('/document/delete', 'DocumentController@deleteDoc')->name('Document-delete');
    Route::get('/download/document/{id}', 'DocumentController@downloadDoc')->name('Document-delete');

    /*********************************************Deviation(Avik)**************************************************/
    Route::get('/avvik', 'DeviationController@deviationList')->name('deviation-list');
    Route::post('/avvik/add', 'DeviationController@deviationAdd')->name('deviation-add');
    Route::get('/avvik/edit/{id?}', 'DeviationController@deviationProcess')->name('deviation-edit');
    Route::post('/add/place', 'DeviationController@addPlace')->name('add-place');
    Route::post('/delete/dev', 'DeviationController@deleteDeviation')->name('delete-deviation');
    Route::get('devData', 'DeviationController@devData')->name('DeviationList');
    Route::post('/delete/place', 'DeviationController@delPlace')->name('DeviationPlace-delete');

    /*********************************************Checklist(Risikoområder)**************************************************/
    Route::get('sjekklister/oversikt', 'ChecklistController@Checklisting')->name('Checklist-view');
    Route::post('/add/chktopic', 'ChecklistController@addchktopic')->name('checklist-topic-add');
    //Route::post('/add/chkquestion', 'ChecklistController@addchkquestion')->name('checklist-question-add');
    Route::post('/add/chkcat', 'ChecklistController@addCategory')->name('checklist-category-add');
    Route::get('/add/checklist', 'ChecklistController@addchecklist')->name('checklist-add');
    //Route::post('/add/resourcequestion', 'ChecklistController@addResourceQuestion')->name('checklistresource-question-add');
    Route::post('/change/type', 'ChecklistController@changechecklisttype')->name('change-topic-type');
    Route::get('/add/sjekkliste', 'ChecklistController@addchecklists')->name('add-checklist');
    Route::get('/add/sjekkliste/{id?}', 'ChecklistController@addchecklists')->name('edit-checklist-id');
    Route::post('/delete/category', 'ChecklistController@deleteCategory')->name('delete-category');
    Route::post('/add/activechecklist', 'ChecklistController@addactivechecklist')->name('active-checklist');
    Route::get('/add/inactchecklist', 'ChecklistController@addinactivechecklist')->name('inactive-checklist');
    Route::get('/edit/sjekkliste/{id}', 'ChecklistController@editchecklists')->name('edit-checklist');
    Route::get('/edit/inactsjekkliste/{id}', 'ChecklistController@editinactchecklists')->name('edit-inactivechecklist');
    Route::post('/delete/checklist/topic', 'ChecklistController@deleteCheckTopic')->name('topicdelete');
    Route::post('add/checklist/question', 'ChecklistController@addquestion')->name('addquestion');
    /*********************************************mapping(kartlegging)**************************************************/
    Route::get('kartlegging/oversikt', 'MappingController@maplist')->name('maplist-view');

    /*********************************************RiskAnalysis(risikoanalyse)**************************************************/
    Route::get('/risikoanalyse', 'RiskAnalysisController@riskanalysislist')->name('riskanalysis-view');

    /*********************************************SafetyInspection(vernerunder)**************************************************/
    Route::get('vernerunder/oversikt', 'SafetyInspectionController@safetyinspectionlist')->name('safetyinspection-view');

    /*********************************************Project**************************************************/
    Route::get('/project','ProjectController@projectview')->name('project-viewfile-route');
    Route::get('/add/project','ProjectController@addproject')->name('project-addviewfile-route');
    Route::post('/project/add','ProjectController@addprojectdb')->name('project-addin-database');
    Route::post('/add/suppliers','ProjectController@addsuppliers')->name('project-add-suppliers');
    Route::get('/edit/project/{id?}','ProjectController@editproject')->name('edit-project-viewfile');
    Route::get('/projectdata','ProjectController@projectData')->name('showdata-in-projectview-via-ajax-filters-too');
});