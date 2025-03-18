<?php namespace app\helpers;

class ValidateResponse{
    public $validator;

    public static function make($validator) {
        $errorList = array();
        foreach ($validator->errors()->all() as $error) {
            $errorList[] = array($error, 400);
        }
        return $errorList;
    }
}
