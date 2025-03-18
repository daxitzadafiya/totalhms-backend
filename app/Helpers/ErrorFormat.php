<?php namespace app\helpers;

class ErrorFormat{
    public $errorCode;
    public $errorMessage;

    public function __construct($errorMessage,$errorCode) {
        $this->errorMessage = $errorMessage;
        $this->errorCode = $errorCode;

    }
}
