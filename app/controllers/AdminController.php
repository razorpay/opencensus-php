<?php

use Http\ApiResponse;
use EE\Exception\RecoverableException;
use Models\Admin;

class AdminController extends BaseController
{
    public function getEntityMultiple($type)
    {
        $input = Input::all();

        $data = (new Admin\Service)->fetchMultipleEntities($type, $input);

        return ApiResponse::json($data);
    }

    public function getEntityById($type, $id)
    {
        $data = (new Admin\Service)->fetchEntityById($type, $id);

        return ApiResponse::json($data);
    }
}