<?php


namespace App\Http\Controllers;

use File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;
use DB;


class AttachmentsController extends Controller
{
    public function showImage($fileName) {
        try {
            $path = public_path() . '/uploads/attachments/' . $fileName;
            return Response::download($path, $fileName);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }

    public function showAvatar($companyId, $fileName) {
        try {
            $path = public_path() . '/uploads/attachments/' . $companyId . '/' . $fileName;
            return Response::download($path, $fileName);
        } catch (\Exception $e) {
            return $this->responseException($e->getMessage(), 400);
        }
    }
}
