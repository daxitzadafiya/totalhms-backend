<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentAttachment;
use App\Models\DocumentNew;
use App\Models\Employee;
use App\Models\Industry;
use App\Models\JobTitle;
use App\Models\Repository;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use JWTAuth;
use app\Helpers\ValidateResponse;


/**
 * @OA\Tag(
 *     name="Companies",
 *     description="Users APIs",
 * )
 **/
class CompanyController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/companies",
     *     tags={"Companies"},
     *     summary="Get companies",
     *     description="Get companies list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getCompanies",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            if ($user->role->level > 0 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            $checkUser = $request->checkUser;
            $result = Company::with('planActive')->leftJoin('industries', 'companies.industry_id', '=', 'industries.id')
            ->leftJoin(DB::raw('(SELECT count(id) as count_employees, company_id FROM users WHERE role_id <> 1 AND company_id IS NOT NULL GROUP BY company_id) AS CE'), 'companies.id', '=', 'CE.company_id')
            //                    ->leftJoin(DB::raw('(SELECT company_id, name as root_department_name FROM departments WHERE parent_id IS NULL) AS CD'), 'companies.id', '=', 'CD.company_id')
            ->where('companies.status', 'active');
            if ($checkUser) { // get list companies not have any employees yet
                $result = $result->where('CE.count_employees', null);
            }
            if ($result) {
                $result = $result->select('companies.*', 'industries.name as industry_name', 'CE.count_employees')
                ->get();
                return $this->responseSuccess($result);
            } else {
                return $this->responseSuccess([]);
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/companies",
     *     tags={"Companies"},
     *     summary="Create new company",
     *     description="Create new company",
     *     security={{"bearerAuth":{}}},
     *     operationId="createCompany",
     *     @OA\RequestBody(
     *         description="Company schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Company")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            if ($user->role->level > 0 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            $input = $request->all();
            $input['active_since'] = Carbon::now()->format('Y-m-d');
            $input['status'] = 'active';
            $rules = Company::$rules;

            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors, 400);
            }
            $newCompany = Company::create($input);

            // create 2 default department: Managers, Employees
            // & 5 default job title: CEO, HSE Manager, Managers, Safety Manager, Employees
            $this->createDepartment($newCompany, $user['id'], 'Managers');
            $this->createDepartment($newCompany, $user['id'], 'Employees');

            // create default categories for each type
            $this->createCategoryByType($newCompany, $user['id'], 'goal');
            $this->createCategoryByType($newCompany, $user['id'], 'routine');
            $this->createCategoryByType($newCompany, $user['id'], 'instruction');
            $this->createCategoryByType($newCompany, $user['id'], 'document');
            $this->createCategoryByType($newCompany, $user['id'], 'contact');
            $this->createCategoryByType($newCompany, $user['id'], 'attachment');
            $this->createCategoryByType($newCompany, $user['id'], 'checklist');
            $this->createCategoryByType($newCompany, $user['id'], 'deviation');
            $this->createCategoryByType($newCompany, $user['id'], 'risk');
            $this->createCategoryByType($newCompany, $user['id'], 'task');

            return $this->responseSuccess($newCompany);
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    private function createDepartment($company, $userID, $depName)
    {
        // create department
        $departmentRules = Department::$rules;
        $inputDep['name'] = $depName;
        $inputDep['company_id'] = $company['id'];

        $departmentValidator = Validator::make($inputDep, $departmentRules);
        if ($departmentValidator->fails()) {
            $errors = ValidateResponse::make($departmentValidator);
            return $this->responseError($errors,400);
        }
        $newDep = Department::create($inputDep);

        // create job title
        if ($depName == 'Managers') {
            $this->createJobTitle($company, $userID, $newDep['id'], 'CEO', 3);
            $this->createJobTitle($company, $userID, $newDep['id'], 'HSE Manager', 3);
            $this->createJobTitle($company, $userID, $newDep['id'], 'Managers', 3);
        } else {
            $this->createJobTitle($company, $userID, $newDep['id'], 'Safety Manager', 4);
            $this->createJobTitle($company, $userID, $newDep['id'], 'Employees', 4);
        }
    }

    private function createJobTitle($company, $userID, $depID, $jobName, $roleID) 
    {
        $inputJobTitle['name'] = $jobName;
        $inputJobTitle['role_id'] = $roleID;
        $inputJobTitle['role_name'] = Role::find($roleID)['name'];
        $inputJobTitle['permission'] = Role::find($roleID)['permission'];
        $inputJobTitle['company_id'] = $company['id'];
        $inputJobTitle['industry_id'] = $company['industry_id'];
        $inputJobTitle['department'] = json_encode($depID);
        $inputJobTitle['added_by'] = $userID;

        $jobTitleRules = JobTitle::$rules;
        $jobTitleValidator = Validator::make($inputJobTitle, $jobTitleRules);
        if ($jobTitleValidator->fails()) {
            $errors = ValidateResponse::make($jobTitleValidator);
            return $this->responseError($errors,400);
        }
        JobTitle::create($inputJobTitle);
    }

    private function createCategoryByType($company, $userID, $type) 
    {
        // create 5 default categories for each type
        $this->createCategory($company, $userID, $type, 'Fire and Emergency');
        $this->createCategory($company, $userID, $type, 'HSE-activities');
        $this->createCategory($company, $userID, $type, 'Environment');
        $this->createCategory($company, $userID, $type, 'Psychosocial routines');
        $this->createCategory($company, $userID, $type, 'Physical and chemical conditions');
        $this->createCategory($company, $userID, $type, 'Others');
    }

    private function createCategory($company, $userID, $type, $name) 
    {
        $rules = Category::$rules;
        $input['name'] = $name;
        $input['company_id'] = $company['id'];
        $input['industry_id'] = $company['industry_id'];
        $input['type'] = $type;
        $input['added_by'] = $userID;

        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            $errors = ValidateResponse::make($validator);
            return $this->responseError($errors,400);
        }

        Category::create($input);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/companies/{id}",
     *     tags={"Companies"},
     *     summary="Get company by id",
     *     description="Get company by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getCompanyByIdAPI",
     *     @OA\Parameter(
     *         description="company id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     )
     * )
     */
    public function show(Request $request, $id)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                //                if ($user['role_id'] > 2) {
                //                    return $this->responseException('This action is unauthorized.', 404);
                //                }

                $companyData = Company::leftJoin('industries', 'companies.industry_id', '=', 'industries.id')
                    ->leftJoin(DB::raw('(SELECT count(id) as countEmployee, company_id FROM users WHERE role_id <> 1 AND company_id IS NOT NULL GROUP BY company_id) AS CE'), 'companies.id', '=', 'CE.company_id')
                    ->where("companies.id", $id)
                    ->select('companies.*', 'industries.name as industry_name', 'CE.countEmployee')
                    ->first();
                if (empty($companyData)) {
                    return $this->responseException('Not found company', 404);
                }
                if ($companyData['ceo']) {
                    $employee = User::find($companyData['ceo']);
                    if ($employee) {
                        $companyData['ceoName'] = $employee['first_name'] . ' ' . $employee['last_name'];
                    }
                }
                if ($companyData['hse_manager']) {
                    $employee = User::find($companyData['hse_manager']);
                    if ($employee) {
                        $companyData['hseManagerName'] = $employee['first_name'] . ' ' . $employee['last_name'];
                    }
                }
                if ($companyData['safety_manager']) {
                    $employee = User::find($companyData['safety_manager']);
                    if ($employee) {
                        $companyData['safetyManagerName'] = $employee['first_name'] . ' ' . $employee['last_name'];
                    }
                }
                //                $logoOriginalName = Document::where("company_id", $id)
                //                    ->where('category_id', '=', 8)
                //                    ->where('added_from', '=', 2)
                //                    ->first();
                $logoOriginalName = DocumentNew::where("company_id", $id)
                    ->where('category_id', '=', 8)
                    ->where('type', 'attachment')
                    ->where('object_type', 'company')
                    ->first();
                if ($logoOriginalName) {
                    $logoOriginalName = DocumentAttachment::where('document_id', $logoOriginalName['id'])->first();
                    $companyData['original_file_name'] = $logoOriginalName['original_file_name'];
                }

                //                $storageUpload = Document::where('company_id', $id)
                //                    ->where('delete_status', 0)
                //                    ->sum('file_size');
                $storageUpload = DocumentNew::leftJoin('documents_attachments', 'documents_new.id', 'documents_attachments.document_id')
                    ->where('company_id', $id)
                    ->where('delete_status', 0)
                    ->sum('file_size');

                $storageRepo = Repository::where('company_id', $id)
                    ->whereNotNull('attachment_uri')
                    ->whereNull('restore_date')
                    ->sum('attachment_size');

                $numberOfEmployee = Employee::leftJoin('users', 'employees.user_id','=', 'users.id')
                    ->where('users.company_id', $id)
                    ->where('employees.disable_status', 0)
                    ->get()
                    ->count();

                $companyData->employees = $numberOfEmployee;
                $companyData->storage_upload = round($storageUpload, 2) . ' (KB)';
                $companyData->storage_repo = round($storageRepo, 2) . ' (KB)';

                return $this->responseSuccess($companyData, 201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/companies/{id}",
     *     tags={"Companies"},
     *     summary="Update company API",
     *     description="Update company API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateCompanyAPI",
     *     @OA\Parameter(
     *         description="company id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Company schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Company")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $rules = Company::$updateRules;
            $input = $request->all();

            $companyData = Company::where("id", $id)->first();
            if (empty($companyData)) {
                return $this->responseException('Not found company', 404);
            }

            if (!$user = $this->getAuthorizedUser('company', 'basic', 'update', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if ($user->filterBy != 'company admin') {
                    return $this->responseException('This action is unauthorized.', 404);
                }

                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors, 400);
                }
                // update CEO
                if (!empty($input['ceo']) && $input['ceo'] != $companyData['ceo']) {
                    // change job title to CEO
                    $this->changeJobTitle($companyData['id'], $input['ceo'], 'Managers', 'CEO');
                    // old: back to job title Managers
                    $this->changeJobTitle($companyData['id'], $companyData['ceo'], 'Managers', 'Managers');
                }
                // update HSE Manager
                if (!empty($input['hse_manager']) && $input['hse_manager'] != $companyData['hse_manager']) {
                    // change job title to HSE Manager
                    $this->changeJobTitle($companyData['id'], $input['hse_manager'], 'Managers', 'HSE Manager');
                    // old: back to job title Managers
                    $this->changeJobTitle($companyData['id'], $companyData['hse_manager'], 'Managers', 'Managers');
                }
                // update Safety Manager
                if (!empty($input['safety_manager']) && $input['safety_manager'] != $companyData['safety_manager']) {
                    // change job title to Safety Manager
                    $this->changeJobTitle($companyData['id'], $input['safety_manager'], 'Employees', 'Safety Manager');
                    // old: back to job title Employees
                    $this->changeJobTitle($companyData['id'], $companyData['safety_manager'], 'Employees', 'Employees');
                }

                $companyData->update($input);
                return $this->responseSuccess($companyData);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    private function changeJobTitle($companyID, $employeeID, $depName, $jobTitleName) 
    {
        $employee = Employee::where('user_id', $employeeID)->first();
        $department = Department::where('company_id', $companyID)
            ->where('name', $depName)->first();
        $job_title = JobTitle::where('company_id', $companyID)
            ->where('name', $jobTitleName)->where('added_by', 1)->first();
        if ($employee && $department && $job_title) {
            if ($employee->user->role_id > 2) {
                $employee->update([
                    'department_id' => $department['id'],
                    'job_title_id' => $job_title['id']
                ]);
            }
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/companies/{id}",
     *     tags={"Companies"},
     *     summary="Delete company API",
     *     description="Delete company API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteCompanyAPI",
     *     @OA\Parameter(
     *         description="company id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     )
     * )
     */
    public function destroy($id)
    {
        try {
            $companyData = Company::find($id);
            if (empty($companyData)) {
                return $this->responseException('Not found company', 404);
            }
            if (!$user = $this->getAuthorizedUser('company', 'basic', 'destroy', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                if ($user->filterBy != 'super admin') {
                    return $this->responseException('This action is unauthorized.', 404);
                }
                if ($this->moveToRepository($user['id'], null, 0, 'Company', $companyData->id, $companyData->name)) {
                    $companyData->update(['status' => 'banned']);
                    return $this->responseSuccess("Disable company success");
                }
                return $this->responseException('Disable failed!', 404);
            }
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function changeStatus($id)
    {
        try {
            if (!$user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('This action is unauthorized.', 404);
            }
            if ($user->role->level > 0 && $user->role->level < 4) {
                return $this->responseException('This action is unauthorized.', 404);
            }

            $company = Company::find($id);
            $company->update(['is_freeze' => !$company->is_freeze]);

            return $this->responseSuccess('is_freeze updated successfully.');
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}