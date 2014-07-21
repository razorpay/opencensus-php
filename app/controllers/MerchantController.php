<?php

use Http\ApiResponse;
use Models\Merchant;

class MerchantController extends BaseController
{
    public function postIndex()
    {
        $input = Input::all();

        $data = (new Merchant\Service)->register($input);

        return ApiResponse::json($data);
    }

    public function getKeys()
    {
        $merchant_id = BasicAuth::getMerchantId();

        $data = (new Merchant\Service)->fetchKeys($merchant_id);

        return ApiResponse::json($data);
    }

    public function putKeys($id)
    {
        $input = Input::all();

        $input['merchant_id'] = BasicAuth::getMerchantId();

        $keys = (new Merchant\Service)->updateKey($id, $input);

        return ApiResponse::json($keys);
    }
}
