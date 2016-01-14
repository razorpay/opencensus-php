<?php

use Http\ApiResponse;
use EE\Exception\RecoverableException;
use Models\Emi;

class EmiController extends BaseController
{
    public function fetchAvailableEmiOptions()
    {
        $data = (new Emi\Service)->all();

        return ApiResponse::json($data);
    }

    public function addEmiOption()
    {
        $input = Input::all();

        $data = (new Emi\Service)->addEmiOption($input);

        return ApiResponse::json($data);
    }

    public function fetchEmiPlanById($id)
    {
        $data = (new Emi\Service)->fetch($id);

        return ApiResponse::json($data);

    }
}