<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Places;
use JWTAuth;
use Auth;
class PlaceController extends Controller
{
    public function index(Request $request){
        if($request->isMethod('post')){
            try {
                $input = $request -> all();
                // if (!$user = $this->getAuthorizedUser('place', 'basic', 'store', 1)) {
                //     return $this->responseException('This action is unauthorized.', 404);
                // } else { 
                    $user = Auth::user();
                    $rules = Places::$rules;  
                    $newplace = Places::updateOrCreate([
                        'place_name'=>$input['place_name']
                    ],
                    [
                        'place_name'=>$input['place_name'],
                        'added_by'=>$input['added_by'],
                        'company_id'=>$user['company_id'],
                    ]);
                    if ($newplace && $user['role_id'] == 1) {
                        $this->pushNotificationToAllCompanies('Place', $newplace['id'], $newplace['place_name'],'create', '');
                    }
    
                    return $this->responseSuccess($newplace);
                // }
            }catch(Exception $e){
                return $this->responseException($e->getMessage(), 400);
            }
        }else{
            try{ 
                if(Auth::user()->role_id == 1){
                    $result = Places::get();   
                }else{
                    $result = Places::where('company_id',Auth::user()->company_id)->get();   
                }
                if($result){
                    return $this->responseSuccess($result);
                }else{
                    return $this->responseSuccess([]);
                } 
            }catch (Exception $e){
                return $this->responseException($e->getMessage(), 400);
            }
        }
    }
}