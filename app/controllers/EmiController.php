<?php

use Http\ApiResponse;
use Models\Key;

class EmiController extends BaseController
{
    public function fetchAvailableEmiOptions()
    {
        $data = (new Emi\Service)->fetch();

        return ApiResponse::json($data);
    }

    public function addEmiOptions()
    {
        $input = Input::all();

        $data = (new Emi\Service)->addEmiOption($input);

        return ApiResponse::json($data);
    }
}