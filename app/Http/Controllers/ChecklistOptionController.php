<?php


namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use Illuminate\Support\Facades\DB;
use Validator;
use JWTAuth;
use App\Models\ChecklistOption;
use App\Models\ChecklistOptionAnswer;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="OptionAnswers",
 *     description="ChecklistOption APIs",
 * )
 **/


class ChecklistOptionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/options",
     *     tags={"Options"},
     *     summary="Get options",
     *     description="Get options list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getOptions",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    { 
        try{
            if (!$user = $this->getAuthorizedUser('checklist', 'view', 'index', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else{
                $isNewChecklist = $request->isNewChecklist;
                $checkOptionsList = $request->checkOptionsList;
                $checklistID = $request->checklistID;
               
                $result = ChecklistOption::leftJoin('checklists', 'checklist_options.checklist_id', '=', 'checklists.id')
                    ->leftJoin('users','checklist_options.added_by','=','users.id')
                    ->where (function ($query) use ($user) {
                            if ($user['role_id'] > 1) {
                                $query->Where('checklist_options.company_id', $user['company_id'])
                                    ->orWhere('checklist_options.added_by', 1);
                            } else if ($user['role_id'] == 1) {
                                $query->where('checklist_options.added_by', 1);
                            }
                        })
                        // ->orWhere('checklist_options.checklist_id', null)
                        ->select('checklist_options.*', 'checklists.name as checklist_id_name',
                        'users.first_name as added_by_first_name', 'users.last_name as added_by_last_name',
                        DB::raw('CONCAT(users.first_name, " ", users.last_name) as added_by_name'));
                        


                if ($isNewChecklist) {
                    $result = ChecklistOption::where('checklist_id', null);
                }
                if ($checklistID) {
                    $result = $result->where('checklist_options.checklist_id', $checklistID);
                }
                $result = $result->with(['optionAnswers'])->orderBy('checklist_options.id','desc')
                    ->get();
                if($result){
                    // if ($checkOptionsList) {
                    //     $result = $this->checkTemplate($result);
                    // }
                    return $this->responseSuccess($result);
                }else{
                    return $this->responseSuccess([]);
                }
            }
        }catch (Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    private function checkTemplate ($options) {
        if (!empty($options)) {
            $checkOptions = $options->where('is_template', 0)->where('checklist_id', null);
            if (!empty($checkOptions)) {
                foreach ($checkOptions as $option) {
                    $optionData = ChecklistOption::find($option['id']);
                    $optionData->update(['is_template' => 1, 'count_used_time' => 0]);
                }
            }
        }
        return $options;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/options",
     *     tags={"Options"},
     *     summary="Create new option",
     *     description="Create new option",
     *     security={{"bearerAuth":{}}},
     *     operationId="createOption",
     *     @OA\RequestBody(
     *         description="Option schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Option")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, ChecklistOption $checklistOption)
    {
         
        try {
            if (!$user = $this->getAuthorizedUser('checklist', 'basic', 'store', 1)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else{
                $rules = ChecklistOption::$rules;
                $input = $request -> all();
                $input['added_by'] = $user['id'];
                if ($user->role_id > 1) {
                    $input['company_id'] = $user['company_id'];
                }
                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }
                $newChecklistOption = ChecklistOption::create($input);

                //Handle to create list option answer
                $answers = $input['answer'];
                foreach ($answers as $answer) {
                    $answerRules = ChecklistOptionAnswer::$rules;
                    $answer['default_option_id'] = $newChecklistOption->id;
                    $answerValidator = Validator::make($answer, $answerRules);

                    if ($answerValidator->fails()) {
                        $errors = ValidateResponse::make($answerValidator);
                        return $this->responseError($errors,400);
                    }
                    ChecklistOptionAnswer::create($answer);
                }

                return $this->responseSuccess($newChecklistOption);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/reports/{id}",
     *     tags={"Reports"},
     *     summary="Update report API",
     *     description="Update report API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateReportAPI",
     *     @OA\Parameter(
     *         description="report id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="Reports schemas",
     *         @OA\JsonContent(ref="#/components/schemas/Report")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     )
     * )
     */

        public function show($id){
            try {
                $checklistData = ChecklistOption::where('id',$id)->with('optionAnswers')->first();
                return $this->responseSuccess($checklistData); 
            }catch(Exception $e){
                return $this->responseException($e->getMessage(), 400);
            }
        }


    public function update(Request $request, $id)
    {
        try {
            $rules = ChecklistOption::$updateRules;
            $input = $request -> all();

            $optionData = ChecklistOption::find($id);
            if (empty($optionData)) {
                return $this->responseException('Not found checklist option', 404);
            }

            $objectInfo = [
                'name' => 'objectInfo',
                'objectType' => 'checklist',
                'objectItem' => $optionData,
            ];
            if (!$user = $this->getAuthorizedUser('checklist', 'basic', 'update', $objectInfo)) {
                return $this->responseException('This action is unauthorized.', 404);
            } else {
                $validator = Validator::make($input, $rules);
                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }
                $optionData->update($input);

                //Handle to create list option answer
                $answers = $input['answer'];
                $oldAnswers = ChecklistOptionAnswer::where('default_option_id', $id)->pluck('id')->toArray();
                $diff = array_diff($oldAnswers, array_column($answers, 'id'));
                // delete answer
                ChecklistOptionAnswer::whereIn('id', $diff)->delete();

                $answerRules = ChecklistOptionAnswer::$updateRules;
                foreach ($answers as $answer) {
                    if (isset($answer['id'])) {
                        $answerData = ChecklistOptionAnswer::find($answer['id']);
                        if (empty($answerData)) {
                            return $this->responseException('Not found answer', 404);
                        }
                        $answerValidator = Validator::make($answer, $answerRules);
                        if ($answerValidator->fails()) {
                            $errors = ValidateResponse::make($answerValidator);
                            return $this->responseError($errors,400);
                        }
                        $answerData->update($answer);
                    } else {
                        $answer['default_option_id'] = $optionData->id;$answerValidator = Validator::make($answer, $answerRules);

                        $answerValidator = Validator::make($answer, $answerRules);
                        if ($answerValidator->fails()) {
                            $errors = ValidateResponse::make($answerValidator);
                            return $this->responseError($errors,400);
                        }
                        ChecklistOptionAnswer::create($answer);
                    }
                }
                return $this->responseSuccess($optionData,201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
