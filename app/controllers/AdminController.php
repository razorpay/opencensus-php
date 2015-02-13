<?php

use Http\ApiResponse;
use EE\Exception\RecoverableException;
use Models\Payment;
use Models\Card;

class AdminController extends BaseController
{
    public function getEntityMultiple($type)
    {
        $data = (new Admin\Serivce)->fetchEntityById($type, $id);

        return ApiResponse::json($data);
    }

    public function fetchMultipleEntities($type, $id)
    {
        $data = (new Admin\Serivce)->fetchEntityById($type, $id);

        return ApiResponse::json($data);
    }
}