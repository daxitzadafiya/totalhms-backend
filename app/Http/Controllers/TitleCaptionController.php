<?php

namespace App\Http\Controllers;

use app\helpers\ValidateResponse;
use App\Models\TitleCaption;
use Validator;
use JWTAuth;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="TitleCaption",
 *     description="TitleCaption APIs",
 * )
 **/
class TitleCaptionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/titleCaption",
     *     tags={"TitleCaption"},
     *     summary="Get TitleCaption",
     *     description="Get TitleCaption list",
     *     security={{"bearerAuth":{}}},
     *     operationId="getTitleCaption",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try{
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }else{
                $menuKey = $request->menuKey;
                $subMenuKey = $request->subMenuKey;
                if ($menuKey && $subMenuKey) {
                    $result = TitleCaption::where([
                        ['menu', $menuKey], ['sub_menu', $subMenuKey]
                    ])->get();
                } else {
                    $result = TitleCaption::with(['role'])->get();
                }

                if($result) {
                    return $this->responseSuccess($result);
                } else {
                    return $this->responseSuccess([]);
                }
            }
        } catch (Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/titleCaption",
     *     tags={"TitleCaption"},
     *     summary="Create new TitleCaption",
     *     description="Create new TitleCaption",
     *     security={{"bearerAuth":{}}},
     *     operationId="createTitleCaption",
     *     @OA\RequestBody(
     *         description="TitleCaption schemas",
     *         @OA\JsonContent(ref="#/components/schemas/TitleCaption")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function store(Request $request, TitleCaption $titleCaption)
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return $this->responseException('Not found user', 404);
            }else{
                $rules = TitleCaption::$rules;
                $input = $request->all();
                $title_key = array($input['role_id'], $input['menu'], $input['sub_menu'], $input['tab']);
                if (!empty($input['sub_tab'])) {
                    array_push($title_key, $input['sub_tab']);
                }
                $input['title_key'] = str_replace(' ', '-', strtolower(implode('-', $title_key)));

                $validator = Validator::make($input, $rules);

                if ($validator->fails()) {
                    $errors = ValidateResponse::make($validator);
                    return $this->responseError($errors,400);
                }
                $newTitleCaption = TitleCaption::create($input);

                return $this->responseSuccess($newTitleCaption, 201);
            }
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/titleCaption/{id}",
     *     tags={"TitleCaption"},
     *     summary="Get TitleCaption by id",
     *     description="Get TitleCaption by id API",
     *     security={{"bearerAuth":{}}},
     *     operationId="getTitleCaptionByIdAPI",
     *     @OA\Parameter(
     *         description="TitleCaption id",
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
            $titleCaptionData = TitleCaption::find($id);
            if (empty($titleCaptionData)) {
                return $this->responseException('Not found TitleCaption', 404);
            }

            return $this->responseSuccess($titleCaptionData, 201);

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function showByKey(Request $request, $key)
    {
        try {
            $titleCaptionData = TitleCaption::where('title_key', $key)->where('activate', 1)->first();
            if (empty($titleCaptionData)) {
                return '';
            }

            return $this->responseSuccess($titleCaptionData, 201);

        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/titleCaption/{id}",
     *     tags={"TitleCaption"},
     *     summary="Update TitleCaption API",
     *     description="Update TitleCaption API",
     *     security={{"bearerAuth":{}}},
     *     operationId="updateTitleCaptionAPI",
     *     @OA\Parameter(
     *         description="TitleCaption id",
     *         in="path",
     *         name="id",
     *         required=true,
     *         @OA\Schema(
     *           type="string",
     *           format="int64"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         description="TitleCaption schemas",
     *         @OA\JsonContent(ref="#/components/schemas/TitleCaption")
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
            $rules = TitleCaption::$updateRules;
            $input = $request->all();

            $titleCaptionData = TitleCaption::find($id);
            if (empty($titleCaptionData)) {
                return $this->responseException('Not found TitleCaption', 404);
            }

            $menu = $titleCaptionData->role_id;
            if (!empty($input['role_id'])) {
                $role_id = $input['role_id'];
            }

            $menu = $titleCaptionData->menu;
            if (!empty($input['menu'])) {
                $menu = $input['menu'];
            }

            $sub_menu = $titleCaptionData->sub_menu;
            if (!empty($input['sub_menu'])) {
                $sub_menu = $input['sub_menu'];
            }

            $tab = $titleCaptionData->tab;
            if (!empty($input['tab'])) {
                $tab = $input['tab'];
            }

            $title_key = array($role_id, $menu, $sub_menu, $tab);

            $sub_tab = $titleCaptionData->sub_tab;
            if (!empty($input['sub_tab'])) {
                $sub_tab = $input['sub_tab'];
            }

            if (!empty($sub_tab)) {
                array_push($title_key, $sub_tab);
            }
            $input['title_key'] = str_replace(' ', '-', strtolower(implode('-', $title_key)));

            $validator = Validator::make($input, $rules);

            if ($validator->fails()) {
                $errors = ValidateResponse::make($validator);
                return $this->responseError($errors,400);
            }

            $titleCaptionData->update($input);

            return $this->responseSuccess($titleCaptionData, 201);
        }catch(Exception $e){
            return $this->responseException($e->getMessage(), 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/titleCaption/{id}",
     *     tags={"TitleCaption"},
     *     summary="Delete TitleCaption API",
     *     description="Delete TitleCaption API",
     *     security={{"bearerAuth":{}}},
     *     operationId="deleteTitleCaptionAPI",
     *     @OA\Parameter(
     *         description="TitleCaption id",
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
            $titleCaptionData = TitleCaption::find($id);
            if (empty($titleCaptionData)) {
                return $this->responseException('Not found TitleCaption', 404);
            }

            TitleCaption::destroy($id);

            return $this->responseSuccess("Delete TitleCaption success", 200);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
