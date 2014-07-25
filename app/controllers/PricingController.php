<?php

use Http\ApiResponse;
use Models\Merchant;

class PricingController extends BaseController
{
    public function postIndex()
    {
        $input = Input::all();

        $data = (new Pricing\Service)->create($input);

        return ApiResponse::json($data);
    }

    public function get()
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
