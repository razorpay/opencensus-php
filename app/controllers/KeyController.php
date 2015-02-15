<?php

use Http\ApiResponse;
use Models\Key;

class MerchantController extends BaseController
{
    public function getKey($id)
    {
        $data = (new Key\Service)->fetch($id);

        return ApiResponse::json($data);
    }

    /**
     * Retrieves payment details
     */
    public function getPayments()
    {
        $input = Input::all();

        $data = (new Key\Service)->fetchMultiple($input);

        return ApiResponse::json($data);
    }
}